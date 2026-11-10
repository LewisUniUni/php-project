<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Quay\Tools\Guardrails\Cli;
use Quay\Tools\Guardrails\Git;
use Quay\Tools\Guardrails\TestChanges;

$base = Cli::base(Cli::arguments(), 1);
$merge = Git::mergeBase($base);
$approved = TestChanges::pathsFromFile((string) file_get_contents(__DIR__.'/approved-tests.txt'));
$before = [];
$after = [];

foreach (Git::changedNames($merge) as $name) {
    if (! str_contains(str_replace('\\', '/', $name), 'tests/') || ! str_ends_with($name, 'Test.php')) {
        continue;
    }

    $before[$name] = Git::file($merge, $name);
    $after[$name] = Git::file('HEAD', $name);
}

$result = TestChanges::review($before, $after, $approved);
fwrite(STDOUT, "Deleted tests:\n");
foreach ($result['deleted'] as $path) {
    fwrite(STDOUT, '- '.$path."\n");
}
fwrite(STDOUT, "Suspected assertion weakening (textual, not proof):\n");
foreach ($result['suspected'] as $line) {
    fwrite(STDOUT, '- '.$line."\n");
}
if ($result['approved'] !== []) {
    fwrite(STDOUT, "Approved test paths changed:\n");
    foreach ($result['approved'] as $path) {
        fwrite(STDOUT, '- '.$path."\n");
    }
}
if ($approved === []) {
    fwrite(STDOUT, "No production test path is approved. Deletions and suspected weakening are reports only.\n");
}

exit($result['fail'] ? 1 : 0);
