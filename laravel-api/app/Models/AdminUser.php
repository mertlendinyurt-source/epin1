<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AdminUser extends Model
{
    use HasUuids;

    protected $table = 'admin_users';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'username',
        'password_hash',
    ];

    protected $hidden = [
        'password_hash',
    ];
}