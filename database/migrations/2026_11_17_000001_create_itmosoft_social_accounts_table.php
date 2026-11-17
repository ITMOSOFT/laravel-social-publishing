<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $table = config('itmosoft-social-publishing.table_prefix', 'itmosoft_').'social_accounts';

        Schema::create($table, function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->morphs('owner');
            $blueprint->string('platform', 40);
            $blueprint->string('account_identifier', 191);
            $blueprint->text('credentials')->nullable();
            $blueprint->json('metadata')->nullable();
            $blueprint->boolean('is_active')->default(true)->index();
            $blueprint->timestamps();
            $blueprint->unique(
                ['owner_type', 'owner_id', 'platform', 'account_identifier'],
                'itmosoft_social_accounts_owner_platform_identifier_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            config('itmosoft-social-publishing.table_prefix', 'itmosoft_').'social_accounts'
        );
    }
};
