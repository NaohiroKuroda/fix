<?php

namespace App\Utils;

/**
 * 共通関数クラスの雛形（テンプレート）。
 *
 * 使う際は以下に従うこと（docs/architecture/backend.md §5）:
 * - クラス名・ファイル名を「責務名」にリネームする（例: DateHelper / NumberHelper）。
 * - メソッドは static・ステートレス（状態を持たず、同じ入力に同じ出力）。
 * - 1 クラス 1 関心。汎用な「何でも入れ」クラスにしない。
 * - 業務ルール（判断・業務計算）は置かない（→ Service / Action）。
 * - 全メソッドに引数型・戻り値型を付与する。
 */
class Sample
{
    /** 雛形メソッド。実際の共通処理に置き換えること。 */
    public static function example(string $value): string
    {
        return $value;
    }
}
