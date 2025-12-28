<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Region extends Model
{
    use HasUuids;

    protected $table = 'regions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'code',
        'name',
        'enabled',
        'flag_image_url',
        'sort_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'enabled' => $this->enabled,
            'flagImageUrl' => $this->flag_image_url,
            'sortOrder' => $this->sort_order,
        ];
    }
}