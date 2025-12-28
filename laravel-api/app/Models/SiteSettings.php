<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SiteSettings extends Model
{
    use HasUuids;

    protected $table = 'site_settings';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'site_name',
        'meta_title',
        'meta_description',
        'contact_email',
        'contact_phone',
        'logo',
        'favicon',
        'hero_image',
        'category_icon',
        'daily_banner_enabled',
        'daily_banner_title',
        'daily_banner_subtitle',
        'daily_banner_icon',
        'daily_countdown_enabled',
        'daily_countdown_label',
        'active',
    ];

    protected $casts = [
        'daily_banner_enabled' => 'boolean',
        'daily_countdown_enabled' => 'boolean',
        'active' => 'boolean',
    ];

    public function toApiArray(): array
    {
        return [
            'siteName' => $this->site_name,
            'metaTitle' => $this->meta_title,
            'metaDescription' => $this->meta_description,
            'contactEmail' => $this->contact_email,
            'contactPhone' => $this->contact_phone,
            'logo' => $this->logo,
            'favicon' => $this->favicon,
            'heroImage' => $this->hero_image,
            'categoryIcon' => $this->category_icon,
            'dailyBannerEnabled' => $this->daily_banner_enabled,
            'dailyBannerTitle' => $this->daily_banner_title,
            'dailyBannerSubtitle' => $this->daily_banner_subtitle,
            'dailyBannerIcon' => $this->daily_banner_icon,
            'dailyCountdownEnabled' => $this->daily_countdown_enabled,
            'dailyCountdownLabel' => $this->daily_countdown_label,
        ];
    }

    public static function getActive(): ?self
    {
        return self::where('active', true)->first();
    }
}