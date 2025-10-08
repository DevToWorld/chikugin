<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CmsV2Page;
use App\Models\CmsV2Section;
use App\Models\CmsV2Override;

class CmsV2CleanupAboutPageSeeder extends Seeder
{
    /**
     * Remove CMS V2 pages for 'about', 'aboutus', and 'about-institute'.
     * These pages use the OLD PageContent system, not CMS V2.
     */
    public function run(): void
    {
        $slugsToRemove = ['about', 'aboutus', 'about-institute'];
        $removed = 0;

        foreach ($slugsToRemove as $slug) {
            $page = CmsV2Page::where('slug', $slug)->first();
            
            if (!$page) {
                continue;
            }

            $this->command->warn("Removing CMS V2 page: '{$slug}' (ID: {$page->id})");

            // 1. Delete any overrides pointing to this page
            $overridesDeleted = CmsV2Override::where('page_id', $page->id)->delete();
            if ($overridesDeleted > 0) {
                $this->command->info("  - Deleted {$overridesDeleted} override(s)");
            }

            // 2. Delete sections belonging to this page
            $sectionsDeleted = CmsV2Section::where('page_id', $page->id)->delete();
            if ($sectionsDeleted > 0) {
                $this->command->info("  - Deleted {$sectionsDeleted} section(s)");
            }

            // 3. Delete the page itself
            $page->delete();
            $this->command->info("  - Deleted '{$slug}' page");
            $removed++;
        }

        if ($removed > 0) {
            $this->command->info("✓ Cleanup complete! Removed {$removed} CMS V2 page(s).");
            $this->command->comment("Note: These pages use the OLD PageContent system (page_contents table)");
        } else {
            $this->command->info("All about-related pages already cleaned up.");
        }
    }
}

