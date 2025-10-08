<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class PublicationCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('publication_categories')) {
            return; // テーブルが無い環境ではスキップ
        }

        $today = Carbon::now();

        $categories = [
            [
                'name' => '調査レポート',
                'slug' => 'research-report',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $today,
                'updated_at' => $today,
            ],
            [
                'name' => '統計資料',
                'slug' => 'statistical-materials',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $today,
                'updated_at' => $today,
            ],
            [
                'name' => '特集',
                'slug' => 'special-feature',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => $today,
                'updated_at' => $today,
            ],
        ];

        // 既存がある場合は重複を避ける
        foreach ($categories as $category) {
            $exists = DB::table('publication_categories')
                ->where('slug', $category['slug'])
                ->exists();
            if (!$exists) {
                DB::table('publication_categories')->insert($category);
            }
        }
    }
}

