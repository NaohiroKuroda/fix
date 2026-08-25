<?php

namespace App\Http\Controllers\Quotation;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuotationMessageRequest;
use App\Http\Resources\CommentResource;
use App\Models\TPayablePartner;
use App\Services\Quotation\QuotationCommentService;
use Illuminate\Http\JsonResponse;

/**
 * 費用項目（t_building_cost_items）単位のやり取り（コメント）。
 * 業者選定（部下）と部長承認（部長）が同じ項目についてコメントを交わす。
 * チャットUI（モーダル）から fetch で利用するため JSON を返す。
 *
 * ルートは見積先（t_cost_quotations）でバインドされるが、コメントはポリモーフィックで
 * 費用項目（commentable_type = App\Models\TBuildingCostItem（モーフ別名。実体は TBuildingBudgetItem））に集約して保存・取得する。
 * これにより、項目に見積先が複数あっても1スレッドにまとまる。
 */
class QuotationMessageController extends Controller
{
    public function __construct(
        private readonly QuotationCommentService $service,
    ) {}

    /**
     * 項目の全コメントを古い順で返す。開いた時点でログインユーザーの既読を更新する。
     */
    public function index(TPayablePartner $quotation): JsonResponse
    {
        $comments = $this->service->thread((int) $quotation->building_cost_item_id);

        return response()->json([
            'messages' => CommentResource::collection($comments)->resolve(),
        ]);
    }

    /** コメントを1件投稿する（投稿者＝ログイン中の admin）。本文・添付いずれか必須。 */
    public function store(StoreQuotationMessageRequest $request, TPayablePartner $quotation): JsonResponse
    {
        $comment = $this->service->post(
            (int) $quotation->building_cost_item_id,
            $request->body(),
            $request->attachments(),
        );

        return response()->json([
            'message' => (new CommentResource($comment))->resolve(),
        ], 201);
    }
}
