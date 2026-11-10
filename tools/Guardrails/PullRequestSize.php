<?php

declare(strict_types=1);

namespace Quay\Tools\Guardrails;

final class PullRequestSize
{
    /**
     * @param  list<array{0: int, 1: int}>  $numstat
     * @return array{files: int, insertions: int, deletions: int, changed_lines: int}
     */
    public static function summarize(array $numstat): array
    {
        $insertions = 0;
        $deletions = 0;

        foreach ($numstat as [$added, $removed]) {
            $insertions += $added;
            $deletions += $removed;
        }

        return [
            'files' => count($numstat),
            'insertions' => $insertions,
            'deletions' => $deletions,
            'changed_lines' => $insertions + $deletions,
        ];
    }
}
