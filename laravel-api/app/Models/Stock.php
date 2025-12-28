<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Stock extends Model
{
    use HasUuids;

    protected $table = 'stocks';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'product_id',
        'value',
        'status',
        'order_id',
        'created_by',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'productId' => $this->product_id,
            'value' => $this->value,
            'status' => $this->status,
            'orderId' => $this->order_id,
            'createdBy' => $this->created_by,
            'assignedAt' => $this->assigned_at?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}