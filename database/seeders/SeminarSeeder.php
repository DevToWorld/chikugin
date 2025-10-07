<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Seminar;
use App\Models\Admin;
use Carbon\Carbon;

class SeminarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Admin::first();

        $seminars = [
            [
                'title' => '採用力強化！経営・人事向け　面接官トレーニングセミナー',
                'description' => '優秀な人材を見極め、獲得するための面接技術を習得できるセミナーを開催します。',
                'detailed_description' => '人材採用は企業の成長を左右する重要な要素です。本セミナーでは、効果的な面接手法、応募者の見極めポイント、法的な注意事項など、人事担当者が知っておくべき面接のノウハウを体系的に学んでいただけます。',
                'date' => Carbon::now()->addDays(30)->toDateString(),
                'start_time' => '14:00:00',
                'end_time' => '17:00:00',
                'location' => '久留米リサーチ・パーク（国立研究機関都市開発）１ー１　２F　特別会議室',
                'capacity' => 30,
                'current_participants' => 0,
                'fee' => 5000.00,
                'status' => 'scheduled',
                'membership_requirement' => 'free',
                'application_deadline' => Carbon::now()->addDays(25)->toDateString(),
                'contact_email' => 'seminar@chikugin-cri.co.jp',
                'contact_phone' => '0942-46-5091',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'title' => 'DX推進セミナー　〜デジタル変革の実践手法〜',
                'description' => 'デジタルトランスフォーメーションの基礎から実践まで学べるセミナーです。',
                'detailed_description' => '急速に進むデジタル化の波に対応するため、企業のDX推進は急務となっています。本セミナーでは、DXの基本概念から具体的な推進手法、成功事例の紹介まで、実践的な内容をお届けします。',
                'date' => Carbon::now()->addDays(45)->toDateString(),
                'start_time' => '13:00:00',
                'end_time' => '16:00:00',
                'location' => 'オンライン開催（Zoom）',
                'capacity' => 100,
                'current_participants' => 0,
                'fee' => 3000.00,
                'status' => 'scheduled',
                'membership_requirement' => 'standard',
                'application_deadline' => Carbon::now()->addDays(40)->toDateString(),
                'contact_email' => 'seminar@chikugin-cri.co.jp',
                'contact_phone' => '0942-46-5091',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'title' => '経営戦略立案ワークショップ',
                'description' => '中小企業の経営戦略立案に必要な考え方とツールを学ぶワークショップです。',
                'detailed_description' => '変化の激しい経営環境において、明確な戦略を持つことは企業の生存に不可欠です。本ワークショップでは、SWOT分析、競合分析、市場分析などの戦略立案手法を実践的に学んでいただきます。',
                'date' => Carbon::now()->addDays(60)->toDateString(),
                'start_time' => '10:00:00',
                'end_time' => '17:00:00',
                'location' => 'ちくぎん地域経済研究所　研修室',
                'capacity' => 20,
                'current_participants' => 0,
                'fee' => 8000.00,
                'status' => 'scheduled',
                'membership_requirement' => 'premium',
                'application_deadline' => Carbon::now()->addDays(55)->toDateString(),
                'contact_email' => 'seminar@chikugin-cri.co.jp',
                'contact_phone' => '0942-46-5091',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'title' => '財務諸表の読み方　基礎講座',
                'description' => '財務諸表の基本的な読み方から分析手法まで分かりやすく解説します。',
                'detailed_description' => '企業の財務状況を正しく把握することは、経営判断の基礎となります。本講座では、貸借対照表、損益計算書、キャッシュフロー計算書の見方から、財務比率分析の手法まで、実例を交えながら解説します。',
                'date' => Carbon::now()->addDays(15)->toDateString(),
                'start_time' => '14:00:00',
                'end_time' => '16:30:00',
                'location' => 'ちくぎん地域経済研究所　会議室A',
                'capacity' => 40,
                'current_participants' => 0,
                'fee' => 0.00,
                'status' => 'scheduled',
                'membership_requirement' => 'free',
                'application_deadline' => Carbon::now()->addDays(10)->toDateString(),
                'contact_email' => 'seminar@chikugin-cri.co.jp',
                'contact_phone' => '0942-46-5091',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
            [
                'title' => '地域経済動向セミナー　2025年度版',
                'description' => '九州・福岡地域の最新経済動向と今後の展望について解説します。',
                'detailed_description' => '地域経済の動向を把握することは、地域企業の経営戦略立案に欠かせません。本セミナーでは、当研究所の最新調査データを基に、九州・福岡地域の経済動向と今後の展望について詳しく解説します。',
                'date' => Carbon::now()->subDays(30)->toDateString(),
                'start_time' => '15:00:00',
                'end_time' => '17:00:00',
                'location' => 'ちくぎん地域経済研究所　講堂',
                'capacity' => 80,
                'current_participants' => 65,
                'fee' => 2000.00,
                'status' => 'completed',
                'membership_requirement' => 'free',
                'application_deadline' => Carbon::now()->subDays(35)->toDateString(),
                'contact_email' => 'seminar@chikugin-cri.co.jp',
                'contact_phone' => '0942-46-5091',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ],
        ];

        foreach ($seminars as $seminarData) {
            Seminar::create($seminarData);
        }
    }
}
