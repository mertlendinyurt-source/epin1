<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Model
{
    use HasUuids;

    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password_hash',
        'auth_provider',
        'google_id',
        'avatar_url',
        'phone_verified',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'phone_verified' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'authProvider' => $this->auth_provider,
            'avatarUrl' => $this->avatar_url,
            'phoneVerified' => $this->phone_verified,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}