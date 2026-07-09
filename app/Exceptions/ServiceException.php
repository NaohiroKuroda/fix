<?php

namespace App\Exceptions;

use Exception;
use RuntimeException;

/**
 * サービス層で発生した処理失敗を表すドメイン例外。
 *
 * - message は「そのまま画面右上のトースト（flash.error）へ表示してよいユーザー向け文言」とする
 *   （SQL やスタックトレースなどの内部情報は載せない）。技術的詳細は Service 側で Log::error に記録し、
 *   元例外は $previous に保持する。
 * - 記録は Service 層が担うため、この例外は bootstrap/app.php の withExceptions() で
 *   dontReport 指定し、フレームワーク側の二重ログを止める。
 */
class ServiceException extends RuntimeException
{
    public function __construct(
        string $message = 'サーバー処理でエラーが発生しました。時間をおいて再度お試しください。',
        ?Exception $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
