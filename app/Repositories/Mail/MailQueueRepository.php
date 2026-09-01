<?php

namespace App\Repositories\Mail;

use App\Repositories\Contracts\Mail\MailQueueRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * メールキュー（`mail_queues`）への書き込み。
 *
 * 現行 felix_total と同じ別DB（`mysql_2` = itplus4_list）を指す。移行期間中は
 * 新旧どちらから積んでも同じ送信バッチが拾うため、テーブル・列は現行に合わせる。
 * 接続名／テーブル名は config/mail_queue.php を唯一の正とする。
 */
class MailQueueRepository implements MailQueueRepositoryInterface
{
    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function insert(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        DB::connection((string) config('mail_queue.connection'))
            ->table((string) config('mail_queue.table'))
            ->insert($rows);
    }
}
