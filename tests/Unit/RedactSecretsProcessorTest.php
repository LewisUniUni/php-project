<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Logging\RedactSecretsProcessor;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

final class RedactSecretsProcessorTest extends TestCase
{
    public function test_it_redacts_tokens_passwords_and_bearer_strings(): void
    {
        $processor = new RedactSecretsProcessor;
        $record = new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'test',
            level: Level::Info,
            message: 'login',
            context: [
                'password' => 'local-operator-password',
                'client_secret' => 'local-warehouse-client-secret',
                'note' => 'Authorization: Bearer abc.def.ghi',
            ],
        );

        $processed = $processor($record);

        $this->assertSame('[redacted]', $processed->context['password']);
        $this->assertSame('[redacted]', $processed->context['client_secret']);
        $this->assertStringContainsString('Bearer [redacted]', (string) $processed->context['note']);
        $this->assertStringNotContainsString('abc.def.ghi', (string) $processed->context['note']);
    }
}
