<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Quay\Tools\Coverage\CloverSummary;

final class CloverSummaryTest extends TestCase
{
    public function test_the_denominator_includes_files_with_zero_covered_statements(): void
    {
        $clover = tempnam(sys_get_temp_dir(), 'clover');
        $this->assertIsString($clover);
        file_put_contents($clover, <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <coverage generated="0">
              <project timestamp="0">
                <file name="/app/Covered.php">
                  <line num="2" type="stmt" count="1"/>
                  <line num="3" type="stmt" count="0"/>
                </file>
                <file name="/app/Uncovered.php">
                  <line num="2" type="stmt" count="0"/>
                </file>
              </project>
            </coverage>
            XML);

        $summary = (new CloverSummary)->summarize($clover, [
            '/app/Covered.php',
            '/app/Uncovered.php',
            '/app/Missing.php',
        ]);

        $this->assertSame(3, $summary['statement_count']);
        $this->assertSame(1, $summary['covered_statement_count']);
        $this->assertEqualsWithDelta(1 / 3, $summary['statement_rate'], 0.0001);
        $this->assertSame(['/app/Uncovered.php'], $summary['zero_coverage_files']);
        $this->assertSame(['/app/Missing.php'], $summary['missing_production_files']);
        unlink($clover);
    }
}
