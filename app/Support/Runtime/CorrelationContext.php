<?php

declare(strict_types=1);

namespace App\Support\Runtime;

final class CorrelationContext
{
    private ?string $requestId = null;

    private ?string $traceId = null;

    private ?string $spanId = null;

    private ?string $jobId = null;

    private ?string $clientId = null;

    private ?string $warehouseId = null;

    public function replace(
        ?string $requestId,
        ?string $traceId,
        ?string $spanId = null,
        ?string $jobId = null,
        ?string $clientId = null,
        ?string $warehouseId = null,
    ): void {
        $this->requestId = $requestId;
        $this->traceId = $traceId;
        $this->spanId = $spanId;
        $this->jobId = $jobId;
        $this->clientId = $clientId;
        $this->warehouseId = $warehouseId;
    }

    public function clear(): void
    {
        $this->requestId = null;
        $this->traceId = null;
        $this->spanId = null;
        $this->jobId = null;
        $this->clientId = null;
        $this->warehouseId = null;
    }

    /**
     * @return array<string, string>
     */
    public function logContext(): array
    {
        return array_filter([
            'request_id' => $this->requestId,
            'trace_id' => $this->traceId,
            'span_id' => $this->spanId,
            'job_id' => $this->jobId,
            'client_id' => $this->clientId,
            'warehouse_id' => $this->warehouseId,
        ], static fn (?string $value): bool => $value !== null && $value !== '');
    }

    public function requestId(): string
    {
        return $this->requestId ?? 'unset';
    }
}
