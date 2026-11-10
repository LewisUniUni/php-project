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

    public function test_frankenphp_binary_uses_the_approved_source_pins(): void
    {
        $root = dirname(__DIR__, 2);
        $pins = file_get_contents($root.'/docker/frankenphp/PINS.txt');
        $dockerfile = file_get_contents($root.'/Dockerfile');
        $goMod = file_get_contents($root.'/docker/frankenphp/go.mod');
        $goSum = file_get_contents($root.'/docker/frankenphp/go.sum');

        $this->assertIsString($pins);
        $this->assertIsString($dockerfile);
        $this->assertIsString($goMod);
        $this->assertIsString($goSum);

        $this->assertStringContainsString('frankenphp_commit=a3663e9e2cc4c258018a28ee068e50cc12f3ed2d', $pins);
        $this->assertStringContainsString('certificates_commit=cddde30af36e238f156bb557bfd1f11a05ffe6f9', $pins);
        $this->assertStringContainsString('go_jose_v4=v4.1.9', $pins);
        $this->assertStringContainsString('sha256:7e73a27eb7712a5b4073b92f8f0dec91e9400138604b76cc791589faaa6a639c', $dockerfile);
        $this->assertStringContainsString('sha256:5b553d5b993d3f0cbe187ee0a6a69b41f30c9ce355442a1b3e5aea24682eaa04', $dockerfile);
        $this->assertStringContainsString('a3663e9e2cc4c258018a28ee068e50cc12f3ed2d', $dockerfile);
        $this->assertStringContainsString('cddde30af36e238f156bb557bfd1f11a05ffe6f9', $dockerfile);
        $this->assertStringContainsString('github.com/smallstep/certificates v0.30.3-0.20261105204752-cddde30af36e', $goMod);
        $this->assertStringContainsString('github.com/go-jose/go-jose/v4 v4.1.9', $goMod);
        $this->assertStringNotContainsString('github.com/go-jose/go-jose/v3', $goMod);
        $this->assertStringNotContainsString('github.com/go-jose/go-jose/v3', $goSum);
        $this->assertDoesNotMatchRegularExpression('/replace\s+github\.com\/go-jose\/go-jose\/v3/', $goMod);
    }
}
