<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CmsV2Page;
use App\Models\CmsV2Section;
use App\Models\CmsV2Override;
use Illuminate\Support\Facades\DB;

class CleanupCmsV2AboutPages extends Command
{
    protected $signature = 'cms:cleanup-about-pages';
    protected $description = 'Remove about/aboutus/about-institute pages from CMS V2 (they use OLD PageContent system)';

    public function handle()
    {
        $slugsToRemove = ['about', 'aboutus', 'about-institute'];
        
        $this->info('Searching for pages to remove...');
        
        $pages = CmsV2Page::whereIn('slug', $slugsToRemove)->get();
        
        if ($pages->isEmpty()) {
            $this->info('✓ No about-related CMS V2 pages found. Already clean!');
            return 0;
        }

        $this->warn("Found {$pages->count()} page(s) to remove:");
        foreach ($pages as $page) {
            $this->line("  - {$page->slug} (ID: {$page->id})");
        }

        if (!$this->confirm('Do you want to delete these pages?', true)) {
            $this->info('Cancelled.');
            return 0;
        }

        DB::transaction(function () use ($pages) {
            foreach ($pages as $page) {
                $this->info("Removing: {$page->slug}");
                
                // Delete overrides
                $overrides = CmsV2Override::where('page_id', $page->id)->count();
                CmsV2Override::where('page_id', $page->id)->delete();
                if ($overrides > 0) {
                    $this->line("  - Deleted {$overrides} override(s)");
                }
                
                // Delete sections
                $sections = CmsV2Section::where('page_id', $page->id)->count();
                CmsV2Section::where('page_id', $page->id)->delete();
                if ($sections > 0) {
                    $this->line("  - Deleted {$sections} section(s)");
                }
                
                // Delete page
                $page->delete();
                $this->line("  ✓ Deleted page");
            }
        });

        $this->info('');
        $this->info('✓ Cleanup complete!');
        $this->comment('Note: These pages should use the OLD PageContent system (page_contents table)');
        $this->comment('Route /aboutus uses AboutInstitutePage.vue which fetches from PageContent with key "about-institute"');
        
        return 0;
    }
}

