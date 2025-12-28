<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Order extends Model
{
    use HasUuids;

    protected $table = 'orders';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'product_id',
        'product_title',
        'uc_amount',
        'amount',
        'player_id',
        'player_name',
        'status',
        'customer',
        'delivery',
        'risk',
        'meta',
        'payment_url',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'uc_amount' => 'integer',
        'customer' => 'array',
        'delivery' => 'array',
        'risk' => 'array',
        'meta' => 'array',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'productId' => $this->product_id,
            'productTitle' => $this->product_title,
            'ucAmount' => $this->uc_amount,
            'amount' => (float) $this->amount,
            'playerId' => $this->player_id,
            'playerName' => $this->player_name,
            'status' => $this->status,
            'customer' => $this->customer,
            'delivery' => $this->delivery,
            'risk' => $this->risk,
            'meta' => $this->meta,
            'paymentUrl' => $this->payment_url,
            'paidAt' => $this->paid_at?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }

    public function toUserApiArray(): array
    {
        $data = $this->toApiArray();
        unset($data['risk'], $data['meta']);
        
        // User-friendly delivery status
        if (isset($this->delivery['status'])) {
            switch ($this->delivery['status']) {
                case 'hold':
                    $data['userDeliveryStatus'] = 'review';
                    $data['userDeliveryMessage'] = 'Siparişiniz kontrol aşamasındadır. En kısa sürede sonuçlandırılacaktır.';
                    break;
                case 'pending':
                    $data['userDeliveryStatus'] = 'pending';
                    $data['userDeliveryMessage'] = 'Siparişiniz hazırlanıyor.';
                    break;
                case 'delivered':
                    $data['userDeliveryStatus'] = 'delivered';
                    $data['userDeliveryMessage'] = 'Siparişiniz teslim edildi.';
                    break;
                case 'cancelled':
                    $data['userDeliveryStatus'] = 'cancelled';
                    $data['userDeliveryMessage'] = 'Sipariş iptal edildi / iade yapıldı.';
                    break;
            }
        }
        
        return $data;
    }
}