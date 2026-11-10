<?php

declare(strict_types=1);

namespace Quay\Tools\Guardrails;

use RuntimeException;

final class Git
{
    public static function mergeBase(string $baseRef): string
    {
        $base = trim(self::run(['git', 'rev-parse', '--verify', $baseRef]));
        $head = trim(self::run(['git', 'rev-parse', 'HEAD']));

        return trim(self::run(['git', 'merge-base', $base, $head]));
    }

    public static function file(string $revision, string $path): ?string
    {
        $process = self::process(['git', 'show', $revision.':'.$path]);
        if ($process['exit'] !== 0) {
            return null;
        }

        return $process['output'];
    }

    /**
     * @return list<string>
     */
    public static function changedNames(string $mergeBase): array
    {
        $output = self::run(['git', 'diff', '--name-only', '--diff-filter=ACMRT', $mergeBase, 'HEAD']);
        $names = [];

        foreach (Text::lines(trim($output)) as $line) {
            if ($line !== '') {
                $names[] = $line;
            }
        }

        return $names;
    }

    /**
     * @return list<array{0: int, 1: int}>
     */
    public static function numstat(string $mergeBase): array
    {
        $output = self::run(['git', 'diff', '--numstat', $mergeBase, 'HEAD']);
        $rows = [];

        foreach (Text::lines(trim($output)) as $line) {
            if ($line === '' || str_starts_with($line, '-')) {
                continue;
            }

            $parts = Text::fields($line);
            if (count($parts) < 2 || ! ctype_digit($parts[0]) || ! ctype_digit($parts[1])) {
                continue;
            }

            $rows[] = [(int) $parts[0], (int) $parts[1]];
        }

        return $rows;
    }

    /**
     * @param  list<string>  $command
     */
    private static function run(array $command): string
    {
        $result = self::process($command);
        if ($result['exit'] !== 0) {
            throw new RuntimeException('Command failed: '.implode(' ', $command)."\n".$result['output']);
        }

        return $result['output'];
    }

    /**
     * @param  list<string>  $command
     * @return array{exit: int, output: string}
     */
    private static function process(array $command): array
    {
        $spec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $cwd = getcwd();
        $process = proc_open($command, $spec, $pipes, $cwd === false ? null : $cwd);
        if (! is_resource($process)) {
            throw new RuntimeException('Could not start '.implode(' ', $command));
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [
            'exit' => proc_close($process),
            'output' => (is_string($stdout) ? $stdout : '').(is_string($stderr) ? $stderr : ''),
        ];
    }
}
