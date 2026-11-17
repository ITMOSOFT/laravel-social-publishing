<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $table = config('itmosoft-social-publishing.table_prefix', 'itmosoft_').'social_posts';

        Schema::create($table, function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->morphs('owner');
            $blueprint->longText('content');
            $blueprint->json('media')->nullable();
            $blueprint->json('metadata')->nullable();
            $blueprint->string('status', 24)->default('draft')->index();
            $blueprint->timestamp('scheduled_at')->nullable()->index();
            $blueprint->timestamp('published_at')->nullable();
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            config('itmosoft-social-publishing.table_prefix', 'itmosoft_').'social_posts'
        );
    }
};
