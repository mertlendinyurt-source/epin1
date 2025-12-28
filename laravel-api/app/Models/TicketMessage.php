<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TicketMessage extends Model
{
    use HasUuids;

    protected $table = 'ticket_messages';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'ticket_id',
        'sender',
        'message',
        'admin_username',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'ticketId' => $this->ticket_id,
            'sender' => $this->sender,
            'message' => $this->message,
            'adminUsername' => $this->admin_username,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}