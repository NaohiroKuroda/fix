<?php

namespace App\Repositories\Contracts\Mail;

/**
 * メールキュー（別DBの `mail_queues`）への書き込み窓口。
 */
interface MailQueueRepositoryInterface
{
    /**
     * 予約レコードを一括登録する。
     *
     * @param  list<array<string, mixed>>  $rows  `mail_queues` の1行ぶんずつ
     */
    public function insert(array $rows): void;
}
