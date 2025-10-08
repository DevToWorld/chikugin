<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CmsV2Page;
use App\Models\CmsV2Section;
use Illuminate\Support\Str;

class CmsV2AddAboutusPageSeeder extends Seeder
{
    /**
     * Add aboutus page to CMS V2 database so BlockCmsEditor can manage it.
     */
    public function run(): void
    {
        // Check if aboutus page already exists
        $existingPage = CmsV2Page::where('slug', 'aboutus')->first();
        
        if ($existingPage) {
            $this->command->info("'aboutus' page already exists (ID: {$existingPage->id})");
            return;
        }

        $this->command->info("Creating 'aboutus' page in CMS V2...");

        // Create the page
        $pageId = (string) Str::ulid();
        $page = CmsV2Page::create([
            'id' => $pageId,
            'slug' => 'aboutus',
            'title' => 'ちくぎん地域経済研究所について',
            'meta_json' => [],
            'published_snapshot_json' => null,
        ]);

        $this->command->info("✓ Created page: aboutus (ID: {$pageId})");

        // Add sections
        $sections = [
            [
                'sort' => 10,
                'component_type' => 'Hero',
                'props_json' => [
                    'title' => 'ちくぎん地域経済研究所について',
                    'subtitle' => 'FOR YOU'
                ],
                'status' => 'draft',
            ],
            [
                'sort' => 20,
                'component_type' => 'RichText',
                'props_json' => [
                    'html' => '<h2>私たちについて</h2><p>当研究所は、産・官・学・金(金融)のネットワークによる様々な分野の調査研究を通じ、企業活動などをサポートします。</p><p>経済・社会・産業動向などに関する調査研究及び企業経営や県民の生活のお役に立つ情報をご提供するとともに、各種経済・文化団体の事務局活動等を通じて、地域社会に貢献することを目指しております。</p>'
                ],
                'status' => 'draft',
            ],
        ];

        foreach ($sections as $sectionData) {
            $sectionId = (string) Str::ulid();
            CmsV2Section::create([
                'id' => $sectionId,
                'page_id' => $page->id,
                'sort' => $sectionData['sort'],
                'component_type' => $sectionData['component_type'],
                'props_json' => $sectionData['props_json'],
                'status' => $sectionData['status'],
            ]);
            $this->command->info("  - Added {$sectionData['component_type']} section (sort: {$sectionData['sort']})");
        }

        // Publish the page
        $snapshot = [
            'slug' => $page->slug,
            'title' => $page->title,
            'meta' => $page->meta_json ?? [],
            'sections' => CmsV2Section::where('page_id', $page->id)
                ->orderBy('sort')
                ->get()
                ->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'sort' => $s->sort,
                        'component_type' => $s->component_type,
                        'props' => $s->props_json ?? [],
                    ];
                })
                ->values()
                ->all(),
        ];

        $page->update(['published_snapshot_json' => $snapshot]);
        $this->command->info("  - Published page");

        $this->command->info('');
        $this->command->info('✓ Successfully added aboutus page to CMS V2!');
    }
}

