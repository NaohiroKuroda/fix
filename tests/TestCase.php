<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * テストが共有 DB（felix_total と同じ mysql の `fix`）に接続していたら即座に落とす。
     *
     * phpunit.xml の `force="true"` は Docker コンテナの実環境変数（DB_CONNECTION=mysql /
     * DB_DATABASE=fix）に勝てず、`RefreshDatabase` の migrate:fresh が共有 DB を
     * 全テーブル DROP した事故が実際に起きている（2026-09-04）。
     * 設定に頼らず、接続先そのものを実行時に検査する。
     */
    protected function setUp(): void
    {
        parent::setUp();

        $driver = DB::connection()->getDriverName();
        if ($driver !== 'sqlite') {
            throw new RuntimeException(
                "テストが sqlite 以外（{$driver}）に接続しています。共有 DB を破壊するため中断しました。"
                .' phpunit.xml の DB_CONNECTION / DB_DATABASE を確認してください。'
            );
        }
    }
}
