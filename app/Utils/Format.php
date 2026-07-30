<?php

namespace App\Utils;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * 表示用の整形ヘルパ（ステートレス）。
 *
 * ビジネスロジックは持たず、値の見た目だけを整える。
 */
class Format
{
    /** 金額を整数の円表現（カンマ区切り）に整形する。null は null のまま返す。 */
    public static function yen(int|float|string|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) round((float) $value);
    }

    /**
     * 集計値の summary に混入したレガシ HTML を除去して素のテキストにする。
     * 空（タグのみ等）なら null を返す。
     */
    public static function plain(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5));

        return $text === '' ? null : $text;
    }

    /**
     * 日時値を `Y/m/d H:i` 文字列へ整形する。空・ゼロ日付・解釈不能は null を返す。
     * 「値なし」をフロントで「—」表示に落とすため、date() と違い空文字ではなく null を返す。
     */
    public static function dateTime(string|CarbonInterface|null $value): ?string
    {
        if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y/m/d H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    /** 日付値を `Y/m/d` 文字列へ整形する。空・ゼロ日付は空文字を返す。 */
    public static function date(string|CarbonInterface|null $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('Y/m/d');
        } catch (\Throwable) {
            return '';
        }
    }
}
