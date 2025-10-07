<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NoticeCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('notice_categories')) {
            return; // table not present; migration should create it
        }

        // Friendly label mapping for common slugs
        $slugToLabel = [
            'news' => 'ニュース',
            'important' => '重要',
            'event' => 'イベント',
            'maintenance' => 'メンテナンス',
            'incident' => '障害',
            'media' => 'メディア',
        ];

        // Defaults (stable sort order in steps of 10)
        $defaults = [
            [ 'name' => 'ニュース', 'slug' => 'news', 'sort_order' => 10, 'is_active' => true ],
            [ 'name' => '重要', 'slug' => 'important', 'sort_order' => 20, 'is_active' => true ],
            [ 'name' => 'イベント', 'slug' => 'event', 'sort_order' => 30, 'is_active' => true ],
            [ 'name' => 'メンテナンス', 'slug' => 'maintenance', 'sort_order' => 40, 'is_active' => true ],
            [ 'name' => '障害', 'slug' => 'incident', 'sort_order' => 50, 'is_active' => true ],
            [ 'name' => 'メディア', 'slug' => 'media', 'sort_order' => 60, 'is_active' => true ],
        ];

        // Upsert defaults
        foreach ($defaults as $row) {
            DB::table('notice_categories')->updateOrInsert(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => $row['is_active'],
                    'updated_at' => now(),
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
        }

        // Backfill from used categories in news_articles (type='notice')
        $used = [];
        if (Schema::hasTable('news_articles')) {
            $used = DB::table('news_articles')
                ->where('type', 'notice')
                ->whereNotNull('category')
                ->distinct()
                ->pluck('category')
                ->filter(fn($v) => (string)$v !== '')
                ->values()
                ->all();
        }

        if (!empty($used)) {
            // Existing slug set
            $existingSlugs = DB::table('notice_categories')->pluck('slug')->all();
            $existing = array_fill_keys($existingSlugs, true);

            // Generate a unique slug using dictionary + slugging + numeric suffix
            $makeUnique = function (string $base) use (&$existing): string {
                $base = trim($base);
                if ($base === '') { $base = 'cat'; }
                $slug = $base; $i = 2;
                while (isset($existing[$slug])) { $slug = $base.'-'.$i; $i++; }
                $existing[$slug] = true; // reserve
                return $slug;
            };

            // Dictionary for Japanese → intended English before slugging
            $dict = [
                'ニュース' => 'news',
                'お知らせ' => 'news',
                '新着' => 'news',
                'イベント' => 'event',
                '重要' => 'important',
                '重要なお知らせ' => 'important',
                'メンテナンス' => 'maintenance',
                '障害' => 'incident',
                '告知' => 'notice',
                'セミナー' => 'seminar',
                'レポート' => 'report',
                'メディア' => 'media',
            ];

            // Find current max sort_order to append new ones at the end
            $maxSort = (int) (DB::table('notice_categories')->max('sort_order') ?? 0);
            $step = 10; $cursor = $maxSort + $step;

            foreach ($used as $val) {
                $raw = (string) $val;
                // Build base slug: apply dictionary then slugify
                $replaced = strtr($raw, $dict);
                $base = Str::slug($replaced);
                if ($base === '') { $base = 'cat'; }
                $slug = isset($existing[$base]) ? $makeUnique($base) : $base;

                if (!isset($existing[$slug])) {
                    // Compute label: prefer friendly label for known slugs; otherwise keep original text
                    $label = $slugToLabel[$slug] ?? $raw;
                    DB::table('notice_categories')->insert([
                        'name' => $label,
                        'slug' => $slug,
                        'sort_order' => $cursor,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $existing[$slug] = true;
                    $cursor += $step;
                }
            }
        }
    }
}

