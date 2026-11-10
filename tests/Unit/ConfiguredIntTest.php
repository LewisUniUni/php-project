<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Values\ConfiguredInt;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class ConfiguredIntTest extends TestCase
{
    public function test_it_accepts_integers_and_integer_strings(): void
    {
        $this->assertSame(15, ConfiguredInt::parse(15, 'identity.client_token_minutes'));
        $this->assertSame(15, ConfiguredInt::parse('15', 'identity.client_token_minutes'));
        $this->assertSame(-2, ConfiguredInt::parse('-2', 'example'));
    }

    public function test_it_rejects_values_that_are_not_integers(): void
    {
        $this->expectException(UnexpectedValueException::class);

        ConfiguredInt::parse('15.5', 'example');
    }
}
