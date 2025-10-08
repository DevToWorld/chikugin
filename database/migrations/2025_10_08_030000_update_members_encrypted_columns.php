<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // 暗号化されたデータを保存するために、列のサイズをTEXTに変更
            $table->text('email')->change();
            $table->text('representative_name')->change();
            $table->text('phone')->nullable()->change();
            $table->text('postal_code')->nullable()->change();
            $table->text('position')->nullable()->change();
            $table->text('department')->nullable()->change();
            $table->text('concerns')->nullable()->change();
            $table->text('notes')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // 元のサイズに戻す
            $table->string('email')->change();
            $table->string('representative_name', 100)->change();
            $table->string('phone', 20)->nullable()->change();
            $table->string('postal_code', 10)->nullable()->change();
            $table->string('position', 100)->nullable()->change();
            $table->string('department', 100)->nullable()->change();
            $table->text('concerns')->nullable()->change();
            $table->text('notes')->nullable()->change();
        });
    }
};

