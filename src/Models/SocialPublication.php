<?php

namespace ItmoSoft\SocialPublishing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use ItmoSoft\SocialPublishing\Enums\SocialPublicationStatus;

class SocialPublication extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('itmosoft-social-publishing.table_prefix', 'itmosoft_').'social_publications';
    }

    protected function casts(): array
    {
        return [
            'status' => SocialPublicationStatus::class,
            'published_at' => 'datetime',
            'response' => 'array',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(
            config('itmosoft-social-publishing.models.post'),
            'social_post_id'
        );
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(
            config('itmosoft-social-publishing.models.account'),
            'social_account_id'
        );
    }
}
