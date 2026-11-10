<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Quay\Tools\Guardrails\Cli;
use Quay\Tools\Guardrails\Git;
use Quay\Tools\Guardrails\Packages;
use Quay\Tools\Guardrails\Tokens;

$base = Cli::base(Cli::arguments(), 1);
$merge = Git::mergeBase($base);
$before = Git::file($merge, 'composer.json') ?? '{}';
$after = Git::file('HEAD', 'composer.json') ?? '{}';
$added = Packages::newDirectPackages($before, $after);
$result = Packages::verify($added, static function (string $package): int {
    $name = Tokens::keep($package);
    if ($name === null) {
        return 404;
    }

    $url = 'https://repo.packagist.org/p2/'.$name.'.json';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 15,
            'ignore_errors' => true,
            'header' => "User-Agent: quay-package-existence\r\n",
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    $status = 0;
    foreach ($http_response_header as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches) === 1) {
            $status = (int) $matches[1];
        }
    }

    if ($body === false && $status === 0) {
        throw new RuntimeException('Packagist did not respond for '.$package);
    }

    return $status;
});

fwrite(STDOUT, "New direct packages are checked for registry existence only. A found package is not a trust decision.\n");
foreach ($result['results'] as $item) {
    fwrite(STDOUT, $item['status'].': '.$item['package']."\n");
}
if ($result['exit'] === 2) {
    fwrite(STDOUT, "The registry was unavailable. That is not evidence that a package does not exist.\n");
}

exit($result['exit']);
