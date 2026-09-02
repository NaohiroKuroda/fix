<?php

namespace App\Services\Mail;

use App\Exceptions\ServiceException;
use App\Models\Company;
use App\Models\EstimateUnitCompany;
use App\Models\TBillingPartner;
use App\Models\TPayablePartner;
use App\Repositories\Contracts\Mail\VendorMailRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * 業者へ送る通知メール（支払・請求で共用）
 *
 * 文面・宛先・まとめ方はすべて現行 felix_total の既存メール（見積依頼／発注書請負承認依頼）を
 * 踏襲する。会社（`companies.id`）単位で1通にまとめ、本文へ項目ぶんのリンクを並べる。
 *
 * 送信（キューへの登録）に失敗しても承認そのものは巻き戻さない。呼び出し側の
 * `BillingQuotationService::notifyQuoteConfirmed()` が例外を捕まえてログに残し、
 * 画面には「※業者への確認依頼メールの送信に失敗しました。」と出す。
 */
class VendorNotificationMailService
{
    /** ④ 見積承認メール（見積確認・発注承諾のご依頼）のタイトル・依頼文・注意書き。 */
    private const QUOTE_CONFIRM_TITLE = '見積確認・発注承諾のご依頼';

    private const QUOTE_CONFIRM_EXP = '見積内容のご確認と発注承諾をお願い致します。';

    private const QUOTE_CONFIRM_NOTICE = '※内容にご承諾いただける場合は、ページ内の「発注承諾」ボタンを押してください。';

    /** 【支払】部長取消申請メール（発注取消のご連絡）の依頼文。宛先が業者なので文面は請求とほぼ同じ。 */
    private const PAYABLE_CANCEL_EXP = 'ご承諾いただいた発注について、取消のお手続きを進めております。';

    private const PAYABLE_CANCEL_NOTICE = '※本件はキャンセル扱いとなり、請負承認はいただけません。';

    /** 見積取消申請メール（発注取消のご連絡）のタイトル・依頼文・注意書き。 */
    private const CANCEL_REQUEST_TITLE = '発注取消のご連絡';

    private const CANCEL_REQUEST_EXP = '先にお送りした見積について、取消のお手続きを進めております。';

    private const CANCEL_REQUEST_NOTICE = '※本件はキャンセル扱いとなり、発注承諾はいただけません。';

    public function __construct(
        private readonly VendorMailRepositoryInterface $repository,
        private readonly SendMailService $sendMail,
    ) {}

    /**
     * ④ 見積承認：業者へ「見積確認・発注承諾のご依頼」メールを送る。
     * リンク先は業者マイページの見積タブ。
     *
     * @param  list<int>  $partnerIds  請求取引先（`t_billing_partners.id`）
     * @return int 登録できた通数（会社単位。宛先が複数ある会社も1通と数える）
     *
     * @throws ServiceException 1社でもキューへの登録に失敗したとき
     */
    public function sendQuoteConfirmMail(array $partnerIds): int
    {
        return $this->send(
            $partnerIds,
            self::QUOTE_CONFIRM_TITLE,
            self::QUOTE_CONFIRM_EXP,
            self::QUOTE_CONFIRM_NOTICE,
            fn (int $legacyCompanyId, string $token): string => $this->vendorEstimateUrl($legacyCompanyId, $token),
        );
    }

    /**
     * 見積取消申請：業者へ「発注取消のご連絡」メールを送る。
     * リンク先は業者マイページの見積タブ（取消申請中はキャンセル扱いで表示される）。
     *
     * @param  list<int>  $partnerIds  請求取引先（`t_billing_partners.id`）
     * @return int 登録できた通数（会社単位。宛先が複数ある会社も1通と数える）
     *
     * @throws ServiceException 1社でもキューへの登録に失敗したとき
     */
    public function sendCancelRequestMail(array $partnerIds): int
    {
        return $this->send(
            $partnerIds,
            self::CANCEL_REQUEST_TITLE,
            self::CANCEL_REQUEST_EXP,
            self::CANCEL_REQUEST_NOTICE,
            fn (int $legacyCompanyId, string $token): string => $this->vendorEstimateUrl($legacyCompanyId, $token),
        );
    }

    /**
     * 【支払】部長取消申請：業者へ「発注取消のご連絡」メールを送る。
     * リンク先は業者マイページ（取消申請中は発注書がキャンセル扱いで表示される）。
     *
     * @param  list<int>  $partnerIds  支払取引先（`t_payable_partners.id`）
     * @return int 登録できた通数（会社単位。宛先が複数ある会社も1通と数える）
     *
     * @throws ServiceException 1社でもキューへの登録に失敗したとき
     */
    public function sendPayableCancelRequestMail(array $partnerIds): int
    {
        $ids = $this->normalizeIds($partnerIds);
        if ($ids === []) {
            return 0;
        }

        return $this->sendToPartners(
            $this->repository->findPayablePartnersForMail($ids),
            $ids,
            self::CANCEL_REQUEST_TITLE,
            self::PAYABLE_CANCEL_EXP,
            self::PAYABLE_CANCEL_NOTICE,
            fn (int $legacyCompanyId, string $token): string => $this->vendorEstimateUrl($legacyCompanyId, $token),
        );
    }

    /**
     * 通知メールの共通処理。対象を会社単位にまとめ、1社ずつキューへ積む。
     *
     * @param  list<int>  $partnerIds
     * @param  callable(int, string): string  $linkBuilder  (見積業者ID, トークン) => リンクURL
     * @return int 登録できた通数（会社単位）
     *
     * @throws ServiceException
     */
    private function send(array $partnerIds, string $title, string $exp, string $notice, callable $linkBuilder): int
    {
        $ids = $this->normalizeIds($partnerIds);
        if ($ids === []) {
            return 0;
        }

        return $this->sendToPartners($this->repository->findPartnersForMail($ids), $ids, $title, $exp, $notice, $linkBuilder);
    }

    /**
     * 取得済みの取引先へ通知メールを送る。支払（{@see TPayablePartner}）・
     * 請求（{@see TBillingPartner}）のどちらも受け付ける（`source_id` と `budgetItem` だけを使う）。
     *
     * @param  Collection<int, TBillingPartner|TPayablePartner>  $partners
     * @param  list<int>  $ids  ログ用の取引先ID
     * @param  callable(int, string): string  $linkBuilder  (見積業者ID, トークン) => リンクURL
     * @return int 登録できた通数（会社単位）
     *
     * @throws ServiceException
     */
    private function sendToPartners(
        Collection $partners,
        array $ids,
        string $title,
        string $exp,
        string $notice,
        callable $linkBuilder,
    ): int {
        if ($partners->isEmpty()) {
            Log::warning('業者通知メール：送信対象の取引先がありません', [
                'title' => $title,
                'partnerIds' => $ids,
            ]);

            return 0;
        }

        $legacyCompanies = $this->repository->findLegacyCompaniesByIds(
            $partners->pluck('source_id')->map(fn ($id) => (int) $id)->all()
        );

        // 会社（companies.id）単位でまとめる。1社に複数項目あっても1通にする。
        /** @var array<int, list<array{legacy: EstimateUnitCompany, partner: TBillingPartner|TPayablePartner}>> $grouped */
        $grouped = [];
        foreach ($partners as $partner) {
            $legacy = $legacyCompanies->get((int) $partner->source_id);
            if ($legacy === null) {
                Log::warning('業者通知メール：移行元の見積業者が見つからないため対象外にします', [
                    'title' => $title,
                    'partnerId' => $partner->id,
                    'sourceId' => $partner->source_id,
                ]);

                continue;
            }

            $grouped[(int) $legacy->company_id][] = ['legacy' => $legacy, 'partner' => $partner];
        }

        $companies = $this->repository->findCompaniesByIds(array_keys($grouped));

        $sent = 0;
        $failed = 0;

        foreach ($grouped as $companyId => $rows) {
            // トークンは会社単位で使い回す（無ければ発行）。
            $token = $this->repository->firstOrCreateAccessToken($companyId);

            $buildingName = '';
            $labels = '';
            $urlBlock = '';
            $toAddresses = [];

            foreach ($rows as $row) {
                $legacy = $row['legacy'];
                $partner = $row['partner'];

                $itemName = (string) ($partner->budgetItem?->name ?? '');

                if ($buildingName === '') {
                    $buildingName = (string) ($partner->budgetItem?->building?->name ?? '');
                }

                $labels .= '| '.$itemName;
                $urlBlock .= '【'.$itemName.'】'.$title."\n";
                $urlBlock .= $linkBuilder((int) $legacy->id, $token)."\n\n";

                $toAddresses = array_merge($toAddresses, $this->resolveRecipients($legacy, $companies->get($companyId)));
            }

            $urlBlock .= $notice."\n";

            // 宛先が1件も無い業者は送らない。空のまま積むと送信バッチ側で捌けないため。
            $toAddresses = array_values(array_unique(array_filter(
                array_map(static fn ($mail) => trim((string) $mail), $toAddresses),
                static fn (string $mail) => $mail !== ''
            )));

            // テスト送信（MAIL_QUEUE_OVERRIDE_TO）が有効なときは、実宛先が無くても
            // 差し替え先へ積めるので送る（動作確認できるようにするため。差し替えは SendMailService）。
            if ($toAddresses === [] && (array) config('mail_queue.override_to', []) === []) {
                Log::warning('業者通知メール：宛先が無いため送信しません', [
                    'title' => $title,
                    'companyId' => $companyId,
                ]);

                continue;
            }

            $company = $companies->get($companyId);

            $subject = '【'.config('mail_queue.from_name').'】'.$buildingName.' '.$title.'メール '.$labels;

            $body = view('mail.notification.body', [
                'companyName' => $company?->all_company_name ?? '',
                'keisho' => (string) ($company?->keisho ?? ''),
                'name' => $buildingName,
                'exp' => $exp,
                'url' => $urlBlock,
            ])->render();

            // キュー用DB（mysql_2）が落ちていても承認処理まで巻き込まないよう、1社ずつ握る。
            try {
                $this->sendMail->enqueue($toAddresses, $subject, $body);
                $sent++;

                Log::info('業者通知メールをメールキューへ登録しました', [
                    'title' => $title,
                    'companyId' => $companyId,
                    'partnerIds' => $ids,
                    'to' => $toAddresses,
                ]);
            } catch (\Exception $e) {
                $failed++;

                Log::error('業者通知メールの登録に失敗しました', [
                    'message' => $e->getMessage(),
                    'title' => $title,
                    'companyId' => $companyId,
                    'partnerIds' => $ids,
                    'to' => $toAddresses,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        // 成功ぶんは残したうえで、呼び出し側（画面のトースト）へ知らせる。
        if ($failed > 0) {
            throw new ServiceException("業者への通知メールを登録できませんでした（成功 {$sent} 通 / 失敗 {$failed} 通）。");
        }

        return $sent;
    }

    /**
     * 宛先の解決。現行の `EstimateUnitCompany::$pic_mail` と同じ優先順位で、
     * 担当者（`staff_id`）が設定されていれば有効期間内の担当者メール、
     * 無ければ業者の見積管轄メール（`estimate_email` / `estimate_tantou_email`）を使う。
     *
     * @return list<string>
     */
    private function resolveRecipients(EstimateUnitCompany $legacy, ?Company $company): array
    {
        $staffIds = $legacy->staff_id;

        if (is_array($staffIds) && $staffIds !== []) {
            return $this->repository->findActiveStaffEmails(array_values($staffIds));
        }

        return [
            (string) ($company?->estimate_email ?? ''),
            (string) ($company?->estimate_tantou_email ?? ''),
        ];
    }

    /**
     * @param  list<int>  $partnerIds
     * @return list<int>
     */
    private function normalizeIds(array $partnerIds): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $partnerIds))));
    }

    /**
     * 業者マイページ（見積タブ）のログイン URL。
     * 現行と同じ `{APP_URL}/estimate/login/{estimate_unit_companies.id}/{access_token}`。
     */
    private function vendorEstimateUrl(int $legacyCompanyId, string $token): string
    {
        $base = rtrim((string) config('mail_queue.vendor_base_url'), '/');

        return $base.'/estimate/login/'.$legacyCompanyId.'/'.$token;
    }
}
