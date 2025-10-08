<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class MembersSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('members')) {
            return; // テーブルが無い環境ではスキップ
        }

        $today = Carbon::now();

        $members = [
            [
                'company_name' => 'ABC工業株式会社',
                'representative_name' => '佐藤次郎',
                'email' => 'test@gmail.com',
                'password' => Hash::make('password123'),
                'membership_type' => 'standard',
                'status' => 'active',
                'is_active' => true,
                'joined_date' => $today->copy()->subMonths(6)->toDateString(),
                'started_at' => $today->copy()->subMonths(6),
                'membership_expires_at' => null, // 無期限
                'expiry_date' => null,
                'email_verified_at' => $today->copy()->subMonths(6),
                'created_at' => $today->copy()->subMonths(6),
                'updated_at' => $today,
            ],
            [
                'company_name' => '有限会社サンプル',
                'representative_name' => '鈴木花子',
                'email' => 'test4@example.com',
                'password' => Hash::make('password123'),
                'membership_type' => 'standard',
                'status' => 'active',
                'is_active' => true,
                'joined_date' => $today->copy()->subMonths(3)->toDateString(),
                'started_at' => $today->copy()->subMonths(3),
                'membership_expires_at' => Carbon::parse('2025-09-27'),
                'expiry_date' => Carbon::parse('2025-09-27'),
                'email_verified_at' => $today->copy()->subMonths(3),
                'created_at' => $today->copy()->subMonths(3),
                'updated_at' => $today,
            ],
            [
                'company_name' => '株式会社テスト商事',
                'representative_name' => '山田',
                'email' => 'test1@example.com',
                'password' => Hash::make('password123'),
                'membership_type' => 'premium',
                'status' => 'active',
                'is_active' => true,
                'joined_date' => $today->copy()->subMonths(4)->toDateString(),
                'started_at' => $today->copy()->subMonths(4),
                'membership_expires_at' => Carbon::parse('2025-10-30'),
                'expiry_date' => Carbon::parse('2025-10-30'),
                'email_verified_at' => $today->copy()->subMonths(4),
                'created_at' => $today->copy()->subMonths(4),
                'updated_at' => $today,
            ],
        ];

        foreach ($members as $memberData) {
            // email_indexを使って既存チェック（emailは暗号化されるため）
            $emailIndex = mb_strtolower(trim($memberData['email']));
            
            $exists = Member::where('email_index', $emailIndex)->exists();
            
            if (!$exists) {
                Member::create($memberData);
            }
        }
    }
}

