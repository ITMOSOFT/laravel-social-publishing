<?php

namespace ItmoSoft\SocialPublishing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialAccount extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('itmosoft-social-publishing.table_prefix', 'itmosoft_').'social_accounts';
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credentials' => 'encrypted:array',
            'metadata' => 'array',
        ];
    }

    public function publications(): HasMany
    {
        return $this->hasMany(
            config('itmosoft-social-publishing.models.publication'),
            'social_account_id'
        );
    }
}
