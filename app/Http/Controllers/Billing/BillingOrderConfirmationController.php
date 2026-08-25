<?php

namespace App\Http\Controllers\Billing;

use App\Http\Requests\QuotationManagementRequest;
use Inertia\Response;

/**
 * 【請求】発注書確認画面（もらい運用フロー ⑦〜⑨）。
 *
 * @see docs/detailed-design/orders/02_請求_発注書確認_詳細設計.md
 */
class BillingOrderConfirmationController extends AbstractBillingScreenController
{
    public function index(QuotationManagementRequest $request): Response
    {
        return $this->renderScreen($request, 'billing-order-confirmation', 'order-delivery/billing-order-confirmation');
    }
}
