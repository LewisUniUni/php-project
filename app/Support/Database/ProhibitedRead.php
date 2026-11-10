<?php

declare(strict_types=1);

namespace App\Support\Database;

use RuntimeException;

final class ProhibitedRead extends RuntimeException {}
