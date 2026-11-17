<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $prefix = config('itmosoft-social-publishing.table_prefix', 'itmosoft_');
        $posts = $prefix.'social_posts';
        $accounts = $prefix.'social_accounts';
        $table = $prefix.'social_publications';

        Schema::create($table, function (Blueprint $blueprint) use ($posts, $accounts): void {
            $blueprint->id();
            $blueprint->foreignId('social_post_id')->constrained($posts)->cascadeOnDelete();
            $blueprint->foreignId('social_account_id')->constrained($accounts)->cascadeOnDelete();
            $blueprint->string('status', 24)->default('pending')->index();
            $blueprint->string('external_post_id')->nullable();
            $blueprint->json('response')->nullable();
            $blueprint->text('error_message')->nullable();
            $blueprint->timestamp('published_at')->nullable();
            $blueprint->timestamps();
            $blueprint->unique(
                ['social_post_id', 'social_account_id'],
                'itmosoft_social_publications_post_account_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            config('itmosoft-social-publishing.table_prefix', 'itmosoft_').'social_publications'
        );
    }
};
