<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('publications')) {
            Schema::table('publications', function (Blueprint $table) {
                if (!Schema::hasColumn('publications', 'file_name')) {
                    $table->string('file_name')->nullable()->after('file_url');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('publications')) {
            Schema::table('publications', function (Blueprint $table) {
                if (Schema::hasColumn('publications', 'file_name')) {
                    $table->dropColumn('file_name');
                }
            });
        }
    }
};
