<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_tags', function (Blueprint $table) {
            $table->string('og_title')->nullable()->after('canonical_url');
            $table->text('og_description')->nullable()->after('og_title');
            $table->string('og_image')->nullable()->after('og_description');
            $table->enum('robots_meta', ['index,follow', 'noindex,follow', 'index,nofollow', 'noindex,nofollow'])
                ->default('index,follow')
                ->after('is_indexable');
        });
    }

    public function down(): void
    {
        Schema::table('blog_tags', function (Blueprint $table) {
            $table->dropColumn(['og_title', 'og_description', 'og_image', 'robots_meta']);
        });
    }
};
