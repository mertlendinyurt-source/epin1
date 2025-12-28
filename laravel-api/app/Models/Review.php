<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Review extends Model
{
    use HasUuids;

    protected $table = 'reviews';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'game',
        'user_name',
        'rating',
        'comment',
        'approved',
    ];

    protected $casts = [
        'rating' => 'integer',
        'approved' => 'boolean',
    ];

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'game' => $this->game,
            'userName' => $this->user_name,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'approved' => $this->approved,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}