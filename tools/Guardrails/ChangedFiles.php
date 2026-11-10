<?php

declare(strict_types=1);

namespace Quay\Tools\Guardrails;

final class ChangedFiles
{
    /**
     * @param  list<string>  $files
     * @return list<string>
     */
    public static function pintCommand(array $files): array
    {
        return ['vendor/bin/pint', '--test', ...$files];
    }

    /**
     * @param  list<string>  $files
     * @return list<string>
     */
    public static function phpstanCommand(array $files): array
    {
        return ['vendor/bin/phpstan', 'analyse', '--memory-limit=1G', '--no-progress', ...$files];
    }

    /**
     * @param  list<string>  $names
     * @return list<string>
     */
    public static function phpFiles(array $names): array
    {
        $files = [];

        foreach ($names as $name) {
            $path = str_replace('\\', '/', $name);
            if (! str_ends_with($path, '.php') || str_starts_with($path, 'vendor/')) {
                continue;
            }

            // Smell fixtures are negative cases. Pint and PHPStan must not treat them as application code.
            if (str_starts_with($path, 'tools/Guardrails/fixtures/')) {
                continue;
            }

            $files[] = $path;
        }

        return $files;
    }
}
