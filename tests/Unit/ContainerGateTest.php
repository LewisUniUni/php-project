<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ContainerGateTest extends TestCase
{
    public function test_snyk_gates_stay_high_and_do_not_skip_or_hide_findings(): void
    {
        $workflow = file_get_contents(dirname(__DIR__, 2).'/.github/workflows/ci.yml');
        $this->assertIsString($workflow);

        $snyk = strstr($workflow, "\n  snyk:\n");
        $this->assertIsString($snyk);
        $snyk = strstr($snyk, "\n  dependency-review:\n", true);
        $this->assertIsString($snyk);

        $this->assertStringContainsString(
            'snyk container test quay-platform:ci --file=Dockerfile --severity-threshold=high',
            $snyk,
        );
        $this->assertStringContainsString('snyk test --file=composer.lock --severity-threshold=high', $snyk);
        $this->assertStringContainsString('snyk code test --severity-threshold=high', $snyk);
        $this->assertStringContainsString('snyk iac test Dockerfile compose.yaml --severity-threshold=high', $snyk);
        $this->assertStringNotContainsString('exclude-base-image-vulns', $snyk);
        $this->assertStringNotContainsString('continue-on-error', $snyk);
        $this->assertDoesNotMatchRegularExpression('/severity-threshold=(low|medium)/', $snyk);
    }

    public function test_runtime_uses_the_official_frankenphp_image_and_binary(): void
    {
        $dockerfile = file_get_contents(dirname(__DIR__, 2).'/Dockerfile');
        $this->assertIsString($dockerfile);
        $this->assertStringContainsString(
            'FROM dunglas/frankenphp:1.13.5-php8.5.12-trixie@sha256:7e73a27eb7712a5b4073b92f8f0dec91e9400138604b76cc791589faaa6a639c AS php-base',
            $dockerfile,
        );
        $this->assertStringNotContainsString('frankenphp-bin', $dockerfile);
        $this->assertStringNotContainsString('cddde30af36e238f156bb557bfd1f11a05ffe6f9', $dockerfile);
        $this->assertFileDoesNotExist(dirname(__DIR__, 2).'/docker/frankenphp/go.mod');
    }
}
