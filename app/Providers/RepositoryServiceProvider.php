<?php

namespace App\Providers;

use App\Repositories\BuildingQuotationRepository;
use App\Repositories\CommentRepository;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\OrderDeliveryRepositoryInterface;
use App\Repositories\Contracts\QuotationRepositoryInterface;
use App\Repositories\OrderDeliveryRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /** interface ⇔ 実装のバインド。見積管理は新スキーマ（t_payable_partners 等）から取得する。 */
    public function register(): void
    {
        $this->app->bind(QuotationRepositoryInterface::class, BuildingQuotationRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, CommentRepository::class);
        $this->app->bind(OrderDeliveryRepositoryInterface::class, OrderDeliveryRepository::class);
    }
}
