<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AuditLog extends Model
{
    use HasUuids;

    protected $table = 'audit_logs';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'action',
        'actor_id',
        'entity_type',
        'entity_id',
        'ip',
        'user_agent',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'actorId' => $this->actor_id,
            'entityType' => $this->entity_type,
            'entityId' => $this->entity_id,
            'ip' => $this->ip,
            'userAgent' => $this->user_agent,
            'meta' => $this->meta,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}