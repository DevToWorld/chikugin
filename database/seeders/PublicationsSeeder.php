<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class PublicationsSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('publications')) {
            return; // テーブルが無い環境ではスキップ
        }

        $today = Carbon::now();

        $items = [
            [
                'title' => 'ちくぎん地域経済レポート 2024年版',
                'description' => '地域経済の最新動向をまとめたレポート。',
                'category' => 'research',
                'type' => 'pdf',
                'publication_date' => $today->copy()->subDays(30)->toDateString(),
                'issue_number' => '2024-1',
                'author' => 'ちくぎん地域経済研究所',
                'pages' => 48,
                'file_url' => '/files/sample-report.pdf',
                'cover_image' => '/img/image-1.png',
                'price' => 0,
                'tags' => '地域,経済,動向',
                'is_published' => true,
                'is_downloadable' => true,
                'members_only' => false,
                'download_count' => 0,
                'view_count' => 0,
                'created_at' => $today,
                'updated_at' => $today,
            ],
            [
                'title' => 'Hot Information 四半期レポート Q1 2024',
                'description' => '四半期の主要指標サマリー。',
                'category' => 'quarterly',
                'type' => 'pdf',
                'publication_date' => $today->copy()->subDays(60)->toDateString(),
                'issue_number' => '2024-Q1',
                'author' => 'ちくぎん地域経済研究所',
                'pages' => 16,
                'file_url' => '/files/sample-quarterly.pdf',
                'cover_image' => '/img/image-1.png',
                'price' => 0,
                'tags' => '四半期,指標',
                'is_published' => true,
                'is_downloadable' => true,
                'members_only' => true,
                'download_count' => 0,
                'view_count' => 0,
                'created_at' => $today,
                'updated_at' => $today,
            ],
        ];

        // 既存がある場合は重複を避ける
        foreach ($items as $row) {
            $exists = DB::table('publications')
                ->where('title', $row['title'])
                ->exists();
            if (!$exists) {
                DB::table('publications')->insert($row);
            }
        }
    }
}

