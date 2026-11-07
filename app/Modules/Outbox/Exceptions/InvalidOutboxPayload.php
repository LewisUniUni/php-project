<?php

declare(strict_types=1);

namespace App\Modules\Outbox\Exceptions;

use RuntimeException;

final class InvalidOutboxPayload extends RuntimeException {}
