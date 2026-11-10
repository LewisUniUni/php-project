<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Telemetry\OtlpExport;
use PHPUnit\Framework\TestCase;

final class OtlpExportTest extends TestCase
{
    public function test_export_stays_off_during_tests_and_when_disabled(): void
    {
        $this->assertSame('disabled', OtlpExport::decision(false, null, false));
        $this->assertSame('disabled', OtlpExport::decision(true, 'http://collector:4318', true));
    }

    public function test_an_enabled_export_without_an_endpoint_is_incomplete(): void
    {
        $this->assertSame('missing-endpoint', OtlpExport::decision(true, '', false));
        $this->assertSame('missing-endpoint', OtlpExport::decision(true, null, false));
    }

    public function test_an_enabled_export_names_a_collector_endpoint(): void
    {
        $this->assertSame('configured', OtlpExport::decision(true, 'http://collector:4318', false));
    }
}
