<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Modules\Identity\Role;
use App\Support\Database\ReadCap;
use App\Support\Runtime\CorrelationContext;
use App\Support\Telemetry\OtlpExport;
use App\Support\Values\ConfiguredInt;
use App\Support\Values\Identifier;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CorrelationContext::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        OtlpExport::boot();
        $this->guardApplicationReads();

        $scopes = config('identity.scopes');
        if (is_array($scopes)) {
            /** @var array<string, string> $scopes */
            Passport::tokensCan($scopes);
        }
        $tokenMinutes = ConfiguredInt::get('identity.client_token_minutes', 15);
        Passport::clientCredentialsTokensExpireIn(now()->addMinutes($tokenMinutes));
        Passport::tokensExpireIn(now()->addMinutes($tokenMinutes));
        Passport::refreshTokensExpireIn(now()->addDays(7));

        Gate::define('warehouse.view', function (User $user): bool {
            return in_array($user->role, [Role::Operator, Role::Planner], true);
        });

        Gate::define('warehouse.mutate', function (User $user): bool {
            return $user->role === Role::Operator;
        });

        Gate::define('viewHorizon', function (?User $user): bool {
            return $user instanceof User && $user->role === Role::Operator;
        });

        Horizon::auth(function (Request $request): bool {
            $user = $request->user();

            return $user instanceof User && Gate::forUser($user)->allows('viewHorizon');
        });

        RateLimiter::for('api', function (Request $request) {
            $guard = auth('api');
            $client = method_exists($guard, 'client') ? $guard->client() : null;
            $name = $client instanceof Client ? $client->name : '';
            $limit = str_starts_with($name, 'partner:')
                ? ConfiguredInt::get('operations.partner_rate_per_minute', 30)
                : ConfiguredInt::get('operations.api_rate_per_minute', 60);
            $key = $client instanceof Client ? Identifier::string($client->getKey()) : ($request->ip() ?? '');

            return Limit::perMinute($limit)->by($key);
        });

        RateLimiter::for('login', function (Request $request) {
            $email = $request->string('email')->toString();

            return Limit::perMinute(5)->by(strtolower($email).'|'.$request->ip());
        });

        DB::listen(function ($event): void {
            $threshold = ConfiguredInt::get('operations.slow_query_ms', 200);

            if ($event->time < $threshold) {
                return;
            }

            Log::warning('slow_query', [
                'connection' => $event->connectionName,
                'time_ms' => $event->time,
                'sql' => $event->sql,
            ]);
        });
    }

    private function guardApplicationReads(): void
    {
        $armed = [];

        Event::listen(function (ConnectionEstablished $event) use (&$armed): void {
            $connection = $event->connection;
            $id = spl_object_id($connection);

            if (isset($armed[$id])) {
                return;
            }

            $armed[$id] = true;

            if ($connection->getDriverName() === 'mongodb') {
                return;
            }

            $connection->beforeExecuting(function (string $query, array $bindings): void {
                if (! ReadCap::originatesInApplication()) {
                    return;
                }

                ReadCap::assertSql($query, $bindings);
            });
        });
    }
}
