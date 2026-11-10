<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Quay\Tools\Coverage\DiffCoverage;

final class DiffCoverageTest extends TestCase
{
    public function test_changed_statement_rate_has_no_threshold(): void
    {
        $clover = tempnam(sys_get_temp_dir(), 'clover');
        $this->assertIsString($clover);
        file_put_contents($clover, <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <coverage generated="0">
              <project timestamp="0">
                <file name="/workspace/app/Example.php">
                  <line num="4" type="stmt" count="1"/>
                  <line num="5" type="stmt" count="0"/>
                </file>
              </project>
            </coverage>
            XML);

        $diff = implode("\n", [
            'diff --git a/app/Example.php b/app/Example.php',
            '--- a/app/Example.php',
            '+++ b/app/Example.php',
            '@@ -3,0 +4,2 @@',
            '+        return 1;',
            '+        return 2;',
        ]);

        $result = (new DiffCoverage)->measure($clover, $diff);

        $this->assertSame(2, $result['changed_executable_statements']);
        $this->assertSame(1, $result['covered_changed_executable_statements']);
        $this->assertArrayNotHasKey('threshold', $result);
        unlink($clover);
    }
}
