<?php

use App\Http\Middleware\CrossAuthCookie;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // cross_auth は平文＋HMAC で felix_total と共有するため暗号化しない。
        $middleware->encryptCookies(except: ['cross_auth']);

        $middleware->web(append: [
            // CrossAuthCookie は HandleInertiaRequests より前（auto-login を先に成立させる）。
            CrossAuthCookie::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // CrossAuthCookie を認証（auth:admin）より前に動かす。
        // StartSession の後・Authenticate の前で復元する必要があるため、優先度を明示する。
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: CrossAuthCookie::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
