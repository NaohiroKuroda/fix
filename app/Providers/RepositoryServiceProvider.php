<?php

namespace App\Providers;

use App\Repositories\Contracts\EstimateRepositoryInterface;
use App\Repositories\EstimateRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /** interface ⇔ 実装のバインド。新しい Repository を追加したらここに追記する。 */
    public function register(): void
    {
        $this->app->bind(EstimateRepositoryInterface::class, EstimateRepository::class);
    }
}
