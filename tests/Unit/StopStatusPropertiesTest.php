<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Delivery\Enums\StopStatus;
use Eris\Generators;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;

final class StopStatusPropertiesTest extends TestCase
{
    use TestTrait;

    public function test_can_transition_matches_the_allowed_next_list(): void
    {
        $this->forAll(
            Generators::elements(StopStatus::cases()),
            Generators::elements(StopStatus::cases()),
        )->then(function (StopStatus $from, StopStatus $to): void {
            $allowed = in_array($to, $from->allowedNext(), true);

            $this->assertSame($allowed, $from->canTransitionTo($to));
        });
    }

    public function test_terminal_states_accept_no_next_status(): void
    {
        $this->forAll(
            Generators::elements([StopStatus::Delivered, StopStatus::Failed, StopStatus::Cancelled]),
            Generators::elements(StopStatus::cases()),
        )->then(function (StopStatus $from, StopStatus $to): void {
            $this->assertSame([], $from->allowedNext());
            $this->assertFalse($from->canTransitionTo($to));
        });
    }

    public function test_allowed_next_never_includes_the_current_status(): void
    {
        $this->forAll(
            Generators::elements(StopStatus::cases()),
        )->then(function (StopStatus $from): void {
            $this->assertNotContains($from, $from->allowedNext());
        });
    }
}
