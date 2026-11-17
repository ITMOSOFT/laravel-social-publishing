<?php

namespace ItmoSoft\SocialPublishing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ItmoSoft\SocialPublishing\Enums\SocialPostStatus;

class SocialPost extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('itmosoft-social-publishing.table_prefix', 'itmosoft_').'social_posts';
    }

    protected function casts(): array
    {
        return [
            'status' => SocialPostStatus::class,
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'media' => 'array',
            'metadata' => 'array',
        ];
    }

    public function publications(): HasMany
    {
        return $this->hasMany(
            config('itmosoft-social-publishing.models.publication'),
            'social_post_id'
        );
    }
}
