<?php

declare(strict_types=1);

namespace App\Modules\Audit\Actions;

use App\Modules\Audit\Models\AuditEvent;
use App\Support\Actor;

final class RecordAudit
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function execute(
        Actor $actor,
        string $action,
        string $subjectType,
        string $subjectId,
        ?string $warehouseCode,
        array $metadata,
    ): void {
        AuditEvent::query()->create([
            'actor_type' => $actor->type,
            'actor_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'warehouse_code' => $warehouseCode,
            'metadata' => $metadata,
        ]);
    }
}
