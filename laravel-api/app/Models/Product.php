<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Product extends Model
{
    use HasUuids;

    protected $table = 'products';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'uc_amount',
        'price',
        'discount_price',
        'discount_percent',
        'active',
        'sort_order',
        'image_url',
        'region_code',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'active' => 'boolean',
        'sort_order' => 'integer',
        'uc_amount' => 'integer',
    ];

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function availableStocks()
    {
        return $this->hasMany(Stock::class)->where('status', 'available');
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'ucAmount' => $this->uc_amount,
            'price' => (float) $this->price,
            'discountPrice' => (float) $this->discount_price,
            'discountPercent' => (float) $this->discount_percent,
            'active' => $this->active,
            'sortOrder' => $this->sort_order,
            'imageUrl' => $this->image_url,
            'regionCode' => $this->region_code,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}