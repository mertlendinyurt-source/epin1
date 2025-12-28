<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameContent extends Model
{
    protected $table = 'game_content';
    protected $primaryKey = 'game';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'game',
        'title',
        'description',
        'default_rating',
        'default_review_count',
    ];

    protected $casts = [
        'default_rating' => 'decimal:2',
        'default_review_count' => 'integer',
    ];

    public function toApiArray(): array
    {
        return [
            'game' => $this->game,
            'title' => $this->title,
            'description' => $this->description,
            'defaultRating' => (float) $this->default_rating,
            'defaultReviewCount' => $this->default_review_count,
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}