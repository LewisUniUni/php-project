<?php

declare(strict_types=1);

use App\Console\HeartbeatCommand;
use App\Console\HeartbeatStatusCommand;
use App\Http\Controllers\MetricsController;
use App\Http\Controllers\ReadyController;
use App\Http\Middleware\AssignCorrelationId;
use App\Http\Middleware\CountHttpResponse;
use App\Http\Problem\ProblemResponse;
use App\Modules\Catalog\Exceptions\UnknownCatalogItem;
use App\Modules\Delivery\Exceptions\InvalidTransition;
use App\Modules\Inventory\Exceptions\InsufficientStock;
use App\Modules\Inventory\Exceptions\ReservationNotReleasable;
use App\Modules\Outbox\Commands\RelayOutbox;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
        then: function (): void {
            Route::middleware([AssignCorrelationId::class, CountHttpResponse::class])->group(function (): void {
                Route::get('/ready', ReadyController::class);
                Route::get('/metrics', MetricsController::class);
            });
        },
    )
    ->withCommands([
        RelayOutbox::class,
        HeartbeatCommand::class,
        HeartbeatStatusCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request): ?string {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            return route('login');
        });

        $middleware->web(append: [
            AssignCorrelationId::class,
            CountHttpResponse::class,
        ]);

        $middleware->api(prepend: [
            AssignCorrelationId::class,
            CountHttpResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport([
            InsufficientStock::class,
            InvalidTransition::class,
            ReservationNotReleasable::class,
            UnknownCatalogItem::class,
        ]);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if ($request->is('warehouse/*') && $request->isMethod('POST')) {
                $message = match (true) {
                    $exception instanceof InsufficientStock => sprintf(
                        'Location %s has %d available for %s; requested %d.',
                        $exception->locationCode,
                        $exception->available,
                        $exception->skuCode,
                        $exception->requested,
                    ),
                    $exception instanceof InvalidTransition,
                    $exception instanceof ReservationNotReleasable,
                    $exception instanceof UnknownCatalogItem => $exception->getMessage(),
                    default => null,
                };

                if (is_string($message)) {
                    return back()->withErrors(['form' => $message])->withInput();
                }
            }

            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return ProblemResponse::for($exception, $request);
        });
    })->create();
