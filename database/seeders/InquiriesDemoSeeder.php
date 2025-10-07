<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InquiriesDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('inquiries')) {
            return;
        }
        $count = (int) DB::table('inquiries')->count();
        if ($count > 0) {
            return; // don't spam existing environments
        }

        DB::table('inquiries')->insert([
            [
                'name' => '山田 太郎',
                'email' => 'taro@example.com',
                'phone' => '090-0000-0000',
                'company' => 'テスト株式会社',
                'subject' => '会員について',
                'message' => '入会に関する問い合わせです。',
                'inquiry_type' => 'membership',
                'status' => 'new',
                'admin_notes' => null,
                'responded_at' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'seeder',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '佐藤 花子',
                'email' => 'hanako@example.com',
                'phone' => null,
                'company' => null,
                'subject' => 'サービスについて',
                'message' => 'サービス詳細の問い合わせです。',
                'inquiry_type' => 'general',
                'status' => 'new',
                'admin_notes' => null,
                'responded_at' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'seeder',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}

