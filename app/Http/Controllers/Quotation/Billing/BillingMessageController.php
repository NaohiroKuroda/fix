<?php

namespace App\Http\Controllers\Quotation\Billing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Quotation\Payable\PayableMessageController;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\TBillingPartner;
use App\Services\Comment\CommentService;
use Illuminate\Http\JsonResponse;

/**
 * 【請求】各画面の「やり取り（コメント）」。支払側（{@see PayableMessageController}）
 * と同じ仕組みで、建物予算項目（t_building_budget_items）単位の1スレッドに集約する。
 *
 * ルートは請求取引先（t_billing_partners）でバインドするが、コメントはポリモーフィックで項目に
 * ぶら下がるため、**同一項目なら支払・請求のどちらの画面から開いても同じスレッド**が見える。
 * チャット UI（モーダル）から fetch で利用するため JSON を返す。
 */
class BillingMessageController extends Controller
{
    public function __construct(
        private readonly CommentService $service,
    ) {}

    /**
     * 項目の全コメントを古い順で返す。開いた時点でログインユーザーの既読を更新する。
     */
    public function index(TBillingPartner $partner): JsonResponse
    {
        $comments = $this->service->thread((int) $partner->building_budget_item_id);

        return response()->json([
            'messages' => CommentResource::collection($comments)->resolve(),
        ]);
    }

    /** コメントを1件投稿する（投稿者＝ログイン中の admin）。本文・添付いずれか必須。 */
    public function store(StoreCommentRequest $request, TBillingPartner $partner): JsonResponse
    {
        $comment = $this->service->post(
            (int) $partner->building_budget_item_id,
            $request->body(),
            $request->attachments(),
        );

        return response()->json([
            'message' => (new CommentResource($comment))->resolve(),
        ], 201);
    }
}
