<?php

namespace App\Providers;

use App\Repositories\Comment\CommentRepository;
use App\Repositories\Contracts\Comment\CommentRepositoryInterface;
use App\Repositories\Contracts\Mail\BillingMailRepositoryInterface;
use App\Repositories\Contracts\Mail\MailQueueRepositoryInterface;
use App\Repositories\Contracts\Order\Payable\OrderDeliveryRepositoryInterface;
use App\Repositories\Contracts\Quotation\Billing\BillingRepositoryInterface;
use App\Repositories\Contracts\Quotation\Payable\PayableRepositoryInterface;
use App\Repositories\Mail\BillingMailRepository;
use App\Repositories\Mail\MailQueueRepository;
use App\Repositories\Order\Payable\OrderDeliveryRepository;
use App\Repositories\Quotation\Billing\BillingRepository;
use App\Repositories\Quotation\Payable\PayableRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /** interface ⇔ 実装のバインド。見積管理は新スキーマ（t_payable_partners 等）から取得する。 */
    public function register(): void
    {
        $this->app->bind(PayableRepositoryInterface::class, PayableRepository::class);
        $this->app->bind(BillingRepositoryInterface::class, BillingRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, CommentRepository::class);
        $this->app->bind(OrderDeliveryRepositoryInterface::class, OrderDeliveryRepository::class);
        // 通知メール（mail_queues）
        $this->app->bind(MailQueueRepositoryInterface::class, MailQueueRepository::class);
        $this->app->bind(BillingMailRepositoryInterface::class, BillingMailRepository::class);
    }
}
