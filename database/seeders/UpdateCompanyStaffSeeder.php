<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PageContent;

class UpdateCompanyStaffSeeder extends Seeder
{
    public function run(): void
    {
        $key = 'company';
        
        // Updated staff data with proper structure
        $staffData = [
            [
                'id' => 'morita',
                'order' => 0,
                'name' => '森田 祥子',
                'reading' => 'もりた さちこ',
                'position' => '企画部　部長代理',
                'note' => '（アジア福岡パートナーズへ出向）',
                'image_key' => 'company_profile_staff_morita',
            ],
            [
                'id' => 'mizokami',
                'order' => 1,
                'name' => '溝上 浩文',
                'reading' => 'みぞかみ ひろふみ',
                'position' => '取締役企画部長　兼調査部長',
                'note' => '',
                'image_key' => 'company_profile_staff_mizokami',
            ],
            [
                'id' => 'kuga',
                'order' => 2,
                'name' => '空閑 重信',
                'reading' => 'くが しげのぶ',
                'position' => '代表取締役社長',
                'note' => '',
                'image_key' => 'company_profile_staff_kuga',
            ],
            [
                'id' => 'takada',
                'order' => 3,
                'name' => '髙田 友里恵',
                'reading' => 'たかだ ゆりえ',
                'position' => '調査部　主任',
                'note' => '',
                'image_key' => 'company_profile_staff_takada',
            ],
            [
                'id' => 'nakamura',
                'order' => 4,
                'name' => '中村 公栄',
                'reading' => 'なかむら きえみ',
                'position' => '',
                'note' => '',
                'image_key' => 'company_profile_staff_nakamura',
            ],
        ];

        $page = PageContent::where('page_key', $key)->first();
        
        if (!$page) {
            echo "Page with key '{$key}' not found. Creating...\n";
            PageContent::create([
                'page_key' => $key,
                'title' => '会社概要',
                'content' => [
                    'texts' => [
                        'page_title' => '会社概要',
                        'staff_title' => '所員紹介',
                        'staff_subtitle' => 'MEMBER',
                    ],
                    'staff' => $staffData,
                    'images' => [],
                ],
                'is_published' => true,
                'published_at' => now(),
            ]);
            echo "Created company page with staff data\n";
            return;
        }

        $content = $page->content ?? [];
        if (!is_array($content)) {
            $content = [];
        }

        // Update staff data
        $content['staff'] = $staffData;
        
        // Ensure texts array exists with staff section titles
        if (!isset($content['texts']) || !is_array($content['texts'])) {
            $content['texts'] = [];
        }
        
        $content['texts']['staff_title'] = $content['texts']['staff_title'] ?? '所員紹介';
        $content['texts']['staff_subtitle'] = $content['texts']['staff_subtitle'] ?? 'MEMBER';

        $page->update([
            'content' => $content,
        ]);

        echo "Updated company page with {" . count($staffData) . "} staff members\n";
    }
}

