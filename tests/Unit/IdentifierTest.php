<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Values\Identifier;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class IdentifierTest extends TestCase
{
    public function test_it_stringifies_int_and_string_identifiers(): void
    {
        $this->assertSame('42', Identifier::string(42));
        $this->assertSame('user-1', Identifier::string('user-1'));
    }

    public function test_it_rejects_empty_and_non_scalar_identifiers(): void
    {
        $this->expectException(UnexpectedValueException::class);

        Identifier::string('');
    }
}
