<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CmsV2Page;
use Illuminate\Support\Facades\Log;

class CmsV2PublishPagesSeeder extends Seeder
{
    /**
     * Publish all CMS V2 pages that have sections.
     * This creates the published_snapshot_json needed for public viewing.
     */
    public function run(): void
    {
        $pages = CmsV2Page::with('sections')->get();

        foreach ($pages as $page) {
            // Only publish if page has sections
            if ($page->sections->isEmpty()) {
                $this->command->comment("Skipping {$page->slug} - no sections");
                continue;
            }

            // Create snapshot
            $snapshot = [
                'slug' => $page->slug,
                'title' => $page->title,
                'meta' => $page->meta_json ?? [],
                'sections' => $page->sections->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'sort' => $s->sort,
                        'component_type' => $s->component_type,
                        'props' => $s->props_json ?? [],
                    ];
                })->values()->all(),
            ];

            // Update published snapshot
            $page->update(['published_snapshot_json' => $snapshot]);

            $this->command->info("Published: {$page->slug} ({$page->sections->count()} sections)");
            
            Log::info("CMS V2 page published", [
                'slug' => $page->slug,
                'sections_count' => $page->sections->count()
            ]);
        }

        $this->command->info('All CMS V2 pages published!');
    }
}

