<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            // 優先度
            $table->string('priority', 20)->nullable()->after('category');
            
            // 公開終了日
            $table->timestamp('expire_date')->nullable()->after('published_at');
            
            // リンク情報
            $table->string('link_url', 500)->nullable()->after('featured_image');
            $table->string('link_text', 255)->nullable()->after('link_url');
            
            // インデックス
            $table->index('expire_date');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            $table->dropIndex(['expire_date']);
            $table->dropIndex(['priority']);
            $table->dropColumn(['priority', 'expire_date', 'link_url', 'link_text']);
        });
    }
};

