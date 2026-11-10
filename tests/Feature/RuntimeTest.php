<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\Runtime\CorrelationContext;
use App\Support\Runtime\ResetWorkerRequestState;
use App\Support\Values\ConfiguredInt;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Octane\Events\RequestReceived;
use Studio\Gesso\Laravel\ValidatesOpenApiSchema;
use Tests\TestCase;

final class RuntimeTest extends TestCase
{
    use ValidatesOpenApiSchema;

    public function test_readiness_is_sanitized_and_liveness_does_not_check_dependencies(): void
    {
        config(['database.connections.mysql.password' => 'local-migrator-password']);

        $ready = $this->getJson('/ready');
        $ready->assertOk();
        $this->assertResponseMatchesOpenApiSchema($ready);
        $ready->assertJsonPath('checks.mysql', true);
        $ready->assertJsonPath('checks.redis_cache', true);
        $ready->assertJsonPath('checks.redis_queue', true);
        $ready->assertJsonPath('checks.mongodb', true);
        $readyBody = $ready->getContent();
        $appKey = config('app.key');
        $this->assertIsString($readyBody);
        $this->assertIsString($appKey);
        $this->assertStringNotContainsString('local-migrator-password', $readyBody);
        $this->assertStringNotContainsString($appKey, $readyBody);

        $metrics = $this->get('/metrics');
        $metrics->assertOk();
        $metrics->assertSee('quay_outbox_pending', false);
        $metrics->assertSee('quay_scheduler_heartbeat_age_seconds', false);
        $this->assertStringNotContainsString('local-migrator-password', (string) $metrics->getContent());

        $live = $this->get('/up');
        $live->assertOk();
        $this->assertStringNotContainsString('local-migrator-password', (string) $live->getContent());
    }

    public function test_correlation_ids_do_not_stick_between_requests(): void
    {
        $first = (string) Str::uuid();
        $second = (string) Str::uuid();

        $this->get('/ready', ['X-Request-Id' => $first])->assertHeader('X-Request-Id', $first);
        $this->get('/ready', ['X-Request-Id' => $second])->assertHeader('X-Request-Id', $second);
    }

    public function test_octane_request_listener_clears_correlation_state(): void
    {
        $context = app(CorrelationContext::class);
        $context->replace(requestId: 'req-old', traceId: 'abc', clientId: 'client-old');

        (new ResetWorkerRequestState)->handle(new RequestReceived(app(), app(), request()));

        $this->assertSame([], $context->logContext());
    }

    public function test_scheduler_tasks_use_shared_locks_with_an_expiry(): void
    {
        Artisan::call('schedule:list');
        $events = collect(app(Schedule::class)->events());
        $relay = $events->first(fn ($event): bool => str_contains((string) $event->command, 'outbox:relay'));
        $heartbeat = $events->first(fn ($event): bool => str_contains((string) $event->command, 'ops:heartbeat'));

        $this->assertNotNull($relay);
        $this->assertNotNull($heartbeat);
        $this->assertTrue($relay->onOneServer);
        $this->assertTrue($relay->withoutOverlapping);
        $this->assertSame(5, $relay->expiresAt);
        $this->assertSame(2, $heartbeat->expiresAt);
    }

    public function test_stock_list_stays_inside_the_query_budget(): void
    {
        $this->actAsClient(['inventory.read']);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->getJson('/api/v1/stock-balances')->assertOk();

        $this->assertLessThanOrEqual(ConfiguredInt::get('operations.query_budget', 15), count(DB::getQueryLog()));
        $this->assertTrue(Model::preventsLazyLoading());
    }

    public function test_horizon_dashboard_is_not_public(): void
    {
        $this->get('/horizon')->assertForbidden();

        $operator = User::factory()->create();
        $this->actingAs($operator)->get('/horizon')->assertOk();
    }
}
