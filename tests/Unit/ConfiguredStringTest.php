<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Values\ConfiguredString;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class ConfiguredStringTest extends TestCase
{
    public function test_it_accepts_a_non_empty_string(): void
    {
        $this->assertSame('local-operator-password', ConfiguredString::parse('local-operator-password', 'seed.operator_password'));
    }

    public function test_it_rejects_empty_and_non_string_values(): void
    {
        $this->expectException(UnexpectedValueException::class);

        ConfiguredString::parse('', 'seed.operator_password');
    }
}
