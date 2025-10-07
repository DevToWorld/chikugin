<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure table exists with expected columns (slug-based)
        if (!Schema::hasTable('cms_v2_pages')) {
            Schema::create('cms_v2_pages', function (Blueprint $table) {
                if (method_exists($table, 'ulid')) { $table->ulid('id')->primary(); } else { $table->uuid('id')->primary(); }
                $table->string('slug')->unique();
                $table->string('title')->default('');
                $table->json('meta_json')->nullable();
                $table->json('published_snapshot_json')->nullable();
                $table->timestampsTz();
            });
            return;
        }

        // Table exists but older shape (e.g. only page_key) → add slug and fill
        if (!Schema::hasColumn('cms_v2_pages', 'slug')) {
            Schema::table('cms_v2_pages', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('id');
            });

            // If legacy page_key exists, copy values into slug
            try {
                if (Schema::hasColumn('cms_v2_pages', 'page_key')) {
                    DB::statement('UPDATE cms_v2_pages SET slug = page_key WHERE slug IS NULL');
                }
            } catch (\Throwable $e) { /* ignore */ }

            // Add unique index if not present (slug may remain nullable; OK for our use case)
            Schema::table('cms_v2_pages', function (Blueprint $table) {
                try { $table->unique('slug', 'cms_v2_pages_slug_unique'); } catch (\Throwable $e) { /* may already exist */ }
            });
        }

        // Ensure optional JSON columns exist
        Schema::table('cms_v2_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('cms_v2_pages', 'meta_json')) {
                $table->json('meta_json')->nullable()->after('title');
            }
            if (!Schema::hasColumn('cms_v2_pages', 'published_snapshot_json')) {
                $table->json('published_snapshot_json')->nullable()->after('meta_json');
            }
        });
    }

    public function down(): void
    {
        // No destructive rollback for safety on shared hosts.
        // Optionally drop the added unique index/column, but keep schema stable.
    }
};
