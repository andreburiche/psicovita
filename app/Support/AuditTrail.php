<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Registro de auditoria para ações em entidades (LGPD / rastreabilidade).
 * Persiste em log estruturado e tabela append-only {@see AuditLog}.
 */
final class AuditTrail
{
    /**
     * @param  array<string, mixed>|null  $changes
     */
    public static function entity(
        string $action,
        string $entity,
        Model $subject,
        ?array $changes = null,
        ?User $actor = null,
    ): void {
        $actor ??= auth()->user();

        $payload = [
            'action' => $action,
            'entity' => $entity,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'user_id' => $actor instanceof User ? $actor->getKey() : null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'changes' => $changes,
        ];

        Log::info('audit.entity', $payload);

        try {
            AuditLog::query()->create([
                'action' => $action,
                'entity' => $entity,
                'subject_type' => $payload['subject_type'],
                'subject_id' => (int) $payload['subject_id'],
                'user_id' => $payload['user_id'],
                'ip_address' => $payload['ip_address'],
                'user_agent' => $payload['user_agent'],
                'changes' => $changes,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Falha de persistência não interrompe a operação principal.
        }
    }
}
