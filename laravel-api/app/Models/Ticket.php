<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Ticket extends Model
{
    use HasUuids;

    protected $table = 'tickets';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'subject',
        'category',
        'status',
        'user_can_reply',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'user_can_reply' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at', 'asc');
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'subject' => $this->subject,
            'category' => $this->category,
            'status' => $this->status,
            'userCanReply' => $this->user_can_reply,
            'closedBy' => $this->closed_by,
            'closedAt' => $this->closed_at?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }

    public function toApiArrayWithUser(): array
    {
        $data = $this->toApiArray();
        if ($this->user) {
            $data['userEmail'] = $this->user->email;
            $data['userName'] = $this->user->full_name;
        }
        return $data;
    }
}