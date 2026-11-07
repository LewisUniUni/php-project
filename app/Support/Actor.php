<?php

declare(strict_types=1);

namespace App\Support;

final readonly class Actor
{
    public function __construct(
        public string $type,
        public string $id,
    ) {}

    public function key(): string
    {
        return $this->type.':'.$this->id;
    }
}
