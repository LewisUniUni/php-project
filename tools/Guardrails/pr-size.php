<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';

use Quay\Tools\Guardrails\Cli;
use Quay\Tools\Guardrails\Git;
use Quay\Tools\Guardrails\PullRequestSize;

$base = Cli::base(Cli::arguments(), 1);
$summary = PullRequestSize::summarize(Git::numstat(Git::mergeBase($base)));
fwrite(STDOUT, sprintf(
    "Changed lines: %d (%d insertions, %d deletions, %d files).\nNo failure threshold is configured.\n",
    $summary['changed_lines'],
    $summary['insertions'],
    $summary['deletions'],
    $summary['files'],
));

exit(0);
