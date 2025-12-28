<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Payment extends Model
{
    use HasUuids;

    protected $table = 'payments';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'order_id',
        'transaction_id',
        'status',
        'amount',
        'payment_method',
        'hash_validated',
        'raw_payload',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'hash_validated' => 'boolean',
        'raw_payload' => 'array',
        'verified_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'orderId' => $this->order_id,
            'transactionId' => $this->transaction_id,
            'status' => $this->status,
            'amount' => (float) $this->amount,
            'paymentMethod' => $this->payment_method,
            'hashValidated' => $this->hash_validated,
            'verifiedAt' => $this->verified_at?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}