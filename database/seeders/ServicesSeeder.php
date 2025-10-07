<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;

class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => '経済分析レポート',
                'slug' => 'economic-analysis-report',
                'short_description' => '専門アナリストによる詳細な経済分析レポートを提供',
                'description' => '当社の経験豊富なエコノミストチームが、最新の経済データを基に詳細な分析レポートを作成します。マクロ経済動向、産業別分析、地域経済レポートなど、お客様のニーズに応じたカスタマイズも可能です。',
                'icon' => 'chart-line',
                'features' => [
                    'マクロ経済分析',
                    '産業別動向レポート',
                    '四半期ごとの定期レポート',
                    'カスタマイズ分析対応'
                ],
                'pricing_type' => 'subscription',
                'display_order' => 1,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => '市場予測サービス',
                'slug' => 'market-forecast-service',
                'short_description' => 'AIを活用した高精度な市場予測サービス',
                'description' => '最先端のAI技術と豊富な市場データを組み合わせて、株式市場、為替市場、商品市場の動向を予測します。リアルタイムデータ分析により、投資判断に役立つ情報を提供します。',
                'icon' => 'trending-up',
                'features' => [
                    'AIによる予測モデル',
                    'リアルタイムアラート',
                    '複数市場対応',
                    'バックテスト機能'
                ],
                'pricing_type' => 'tiered',
                'display_order' => 2,
                'is_featured' => true,
                'is_active' => true,
            ],
            [
                'name' => 'コンサルティング',
                'slug' => 'consulting-service',
                'short_description' => '経営戦略から財務アドバイスまで幅広く支援',
                'description' => '企業の経営課題解決から成長戦略の立案まで、経験豊富なコンサルタントが総合的にサポートします。M&A支援、事業再編、新規事業開発など、様々な経営課題に対応します。',
                'icon' => 'users',
                'features' => [
                    '経営戦略立案',
                    'M&Aアドバイザリー',
                    '事業計画策定支援',
                    '組織改革支援'
                ],
                'pricing_type' => 'custom',
                'display_order' => 3,
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'name' => 'データ分析プラットフォーム',
                'slug' => 'data-analytics-platform',
                'short_description' => 'ビッグデータ分析のためのクラウドプラットフォーム',
                'description' => '大量の経済・金融データを効率的に分析できるクラウドベースのプラットフォームです。直感的なインターフェースと強力な分析ツールで、データドリブンな意思決定を支援します。',
                'icon' => 'database',
                'features' => [
                    'クラウドベース',
                    'ビジュアライゼーション',
                    'API連携',
                    'カスタムダッシュボード'
                ],
                'pricing_type' => 'usage',
                'display_order' => 4,
                'is_featured' => false,
                'is_active' => true,
            ],
            [
                'name' => '研修・セミナー',
                'slug' => 'training-seminar',
                'short_description' => '金融・経済分野の専門研修プログラム',
                'description' => '金融機関や事業会社向けに、経済分析、リスク管理、金融商品などに関する専門的な研修プログラムを提供します。オンライン・オフライン両方に対応し、カスタマイズも可能です。',
                'icon' => 'graduation-cap',
                'features' => [
                    'オーダーメイド研修',
                    'オンライン対応',
                    '資格取得支援',
                    '定期セミナー開催'
                ],
                'pricing_type' => 'per_event',
                'display_order' => 5,
                'is_featured' => false,
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}