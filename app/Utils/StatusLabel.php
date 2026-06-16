<?php

namespace App\Utils;

/**
 * ステータス値 → 表示ラベルの解決（ステートレス）。
 *
 * ラベル定義は config/status_management.php を唯一の正とする。
 */
class StatusLabel
{
    public static function subCate(int|string|null $value): string
    {
        return self::resolve('status_management.sub_cate', $value);
    }

    public static function companySelect(int|string|null $value): string
    {
        return self::resolve('status_management.company_select_status', $value);
    }

    public static function complete(int|string|null $value): string
    {
        return self::resolve('status_management.complete_status', $value);
    }

    private static function resolve(string $configKey, int|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        /** @var array<int, string> $map */
        $map = config($configKey, []);

        return $map[(int) $value] ?? '';
    }
}
