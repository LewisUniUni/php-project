<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Quay\Tools\Guardrails\Cli;
use Quay\Tools\Guardrails\Git;
use Quay\Tools\Guardrails\Suppressions;

$base = Cli::base(Cli::arguments(), 1);
$merge = Git::mergeBase($base);
$names = array_unique([
    ...Git::changedNames($merge),
    'phpstan.neon',
]);
$before = [];
$after = [];

foreach ($names as $name) {
    if (! str_ends_with($name, '.php') && ! str_ends_with($name, '.neon') && ! str_ends_with($name, '.xml') && ! str_contains($name, 'baseline')) {
        continue;
    }

    $before[$name] = Git::file($merge, $name);
    $after[$name] = Git::file('HEAD', $name);
}

$result = Suppressions::compare($before, $after);
fwrite(STDOUT, $result['fail'] ? "Suppression check failed.\n" : "Suppression check passed.\n");
foreach ($result['added'] as $item) {
    fwrite(STDOUT, 'added: '.$item."\n");
}
foreach ($result['removed'] as $item) {
    fwrite(STDOUT, 'removed: '.$item."\n");
}

exit($result['fail'] ? 1 : 0);
