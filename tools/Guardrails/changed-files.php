<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Quay\Tools\Guardrails\ChangedFiles;
use Quay\Tools\Guardrails\Cli;
use Quay\Tools\Guardrails\Git;

$arguments = Cli::arguments();
$mode = $arguments[1] ?? '';
if (! in_array($mode, ['pint', 'phpstan'], true)) {
    fwrite(STDERR, "Usage: changed-files.php pint|phpstan [base-ref]\n");
    exit(2);
}

$base = Cli::base($arguments, 2);
$files = ChangedFiles::phpFiles(Git::changedNames(Git::mergeBase($base)));
if ($files === []) {
    fwrite(STDOUT, "No changed PHP files.\n");
    exit(0);
}

$command = $mode === 'pint' ? ChangedFiles::pintCommand($files) : ChangedFiles::phpstanCommand($files);
if ($mode === 'phpstan') {
    fwrite(STDOUT, "Changed-file PHPStan does not see cross-file errors. composer analyse remains the full gate.\n");
}
fwrite(STDOUT, 'Running '.implode(' ', $command)."\n");

$pipes = [];
$process = proc_open($command, [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes);
if (! is_resource($process)) {
    fwrite(STDERR, "Could not start the changed-file command.\n");
    exit(2);
}

exit(proc_close($process));
