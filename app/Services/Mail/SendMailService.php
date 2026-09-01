<?php

namespace App\Services\Mail;

use App\Exceptions\ServiceException;
use App\Repositories\Contracts\Mail\MailQueueRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * メール送信（予約登録）の汎用サービス。
 *
 * 現行 felix_total の `SendMailService` / `EstimateCustomDetailController::send_mail()` を踏襲し、
 * **その場では送信せず** 別DBの `mail_queues` へ `status = 0` で積む。送信予約時刻は
 * 「現在 + `mail_queue.send_delay_minutes`（既定10分）」を `mail_queue.timezone`（既定 Asia/Tokyo）で作る。
 * 実際の配信は同テーブルを見る既存のメール送信バッチが行うため、新旧どちらから積んでも
 * 配信経路・送信履歴は1本に保たれる。
 *
 * 宛先ごとに1レコード（キューに BCC 列が無いため）。件名・本文は呼び出し側で組み立てる。
 */
class SendMailService
{
    public function __construct(
        private readonly MailQueueRepositoryInterface $mailQueue,
    ) {}

    /**
     * 宛先ぶんの予約レコードをメールキューへ登録する。
     *
     * @param  list<string>  $toAddresses  宛先。空文字・重複は除去する
     * @param  string  $subject  件名
     * @param  string  $body  本文（HTML。blade を描画したものをそのまま積む）
     * @param  string  $attachment  添付ファイル（現行と同じく文字列。無ければ空）
     * @return int 積んだ通数（宛先が無ければ 0）
     *
     * @throws ServiceException キューへの登録に失敗したとき
     */
    public function enqueue(array $toAddresses, string $subject, string $body, string $attachment = ''): int
    {
        $recipients = $this->resolveRecipients($toAddresses);
        if ($recipients === []) {
            return 0;
        }

        try {
            $base = [
                'subject' => $subject,
                'from_mail' => (string) config('mail_queue.from_mail'),
                'from_name' => (string) config('mail_queue.from_name'),
                'body' => $body,
                'send_time' => $this->sendTime(),
                'status' => 0,
                'attachment' => $attachment,
            ];

            $rows = [];
            foreach ($recipients as $toMail) {
                $rows[] = $base + ['to_mail' => $toMail];
            }

            $this->mailQueue->insert($rows);

            Log::info('メールキューへ登録しました', [
                'subject' => $subject,
                'to' => $recipients,
                'sendTime' => $base['send_time'],
            ]);

            return count($rows);
        } catch (\Exception $e) {
            Log::error('メールキューへの登録に失敗しました', [
                'message' => $e->getMessage(),
                'subject' => $subject,
                'to' => $recipients,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ServiceException('通知メールの送信予約に失敗しました。', $e);
        }
    }

    /**
     * 宛先を正規化する。テスト送信（`MAIL_QUEUE_OVERRIDE_TO`）が設定されていれば
     * 実宛先を捨ててそのアドレスへ差し替える。
     *
     * @param  list<string>  $toAddresses
     * @return list<string>
     */
    private function resolveRecipients(array $toAddresses): array
    {
        /** @var list<string> $override */
        $override = (array) config('mail_queue.override_to', []);

        if ($override !== []) {
            // 本来の宛先へ届かないため、取り違えに気づけるようログへ残す。
            Log::warning('メールキュー：テスト送信の宛先差し替えが有効です', [
                'original' => $this->normalize($toAddresses),
                'override' => $override,
            ]);

            return $this->normalize($override);
        }

        return $this->normalize($toAddresses);
    }

    /**
     * @param  array<int|string, mixed>  $addresses
     * @return list<string>
     */
    private function normalize(array $addresses): array
    {
        $normalized = array_map(static fn ($mail) => trim((string) $mail), $addresses);

        return array_values(array_unique(array_filter($normalized, static fn (string $mail) => $mail !== '')));
    }

    /**
     * 送信予約時刻（現在 + `send_delay_minutes`）。現行と同じく `Y-m-d H:i:s` 文字列で積む。
     *
     * 時刻は**キューDB／送信バッチのタイムゾーン**（`mail_queue.timezone` = 既定 Asia/Tokyo）で作る。
     * 本アプリは UTC で動いているため、そのまま積むと9時間前の日時になり、猶予を待たずに
     * 送信バッチへ拾われてしまう。
     */
    private function sendTime(): string
    {
        $delay = (int) config('mail_queue.send_delay_minutes', 10);
        $timezone = (string) config('mail_queue.timezone', 'Asia/Tokyo');

        return Carbon::now($timezone)->addMinutes($delay)->format('Y-m-d H:i:s');
    }
}
