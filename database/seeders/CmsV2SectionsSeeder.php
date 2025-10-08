<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CmsV2Page;
use App\Models\CmsV2Section;
use Illuminate\Support\Str;

class CmsV2SectionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define sections for each page slug
        $pageSections = [
            'home' => [
                [
                    'sort' => 10,
                    'component_type' => 'Hero',
                    'props_json' => [
                        'title' => 'Welcome to Chikugin Research Institute',
                        'subtitle' => 'Your trusted partner in economic research',
                        'backgroundImage' => '',
                        'ctaText' => 'Learn More',
                        'ctaLink' => '/about'
                    ],
                    'status' => 'draft',
                ],
                [
                    'sort' => 20,
                    'component_type' => 'RichText',
                    'props_json' => [
                        'html' => '<h2>About Our Services</h2><p>We provide comprehensive economic research and analysis.</p>'
                    ],
                    'status' => 'draft',
                ],
                [
                    'sort' => 30,
                    'component_type' => 'CardGrid',
                    'props_json' => [
                        'cards' => [
                            ['title' => 'Research', 'description' => 'In-depth economic analysis', 'icon' => 'chart'],
                            ['title' => 'Consulting', 'description' => 'Expert advisory services', 'icon' => 'users'],
                            ['title' => 'Publications', 'description' => 'Latest reports and insights', 'icon' => 'book'],
                        ]
                    ],
                    'status' => 'draft',
                ],
            ],
            // Note: 'about', 'aboutus', 'about-institute' use OLD PageContent system (not CMS V2)
            'services' => [
                [
                    'sort' => 10,
                    'component_type' => 'Hero',
                    'props_json' => [
                        'title' => 'Our Services',
                        'subtitle' => 'Comprehensive research and consulting'
                    ],
                    'status' => 'draft',
                ],
                [
                    'sort' => 20,
                    'component_type' => 'CardGrid',
                    'props_json' => [
                        'cards' => [
                            ['title' => 'Economic Analysis', 'description' => 'Regional economic indicators and trends'],
                            ['title' => 'Industry Research', 'description' => 'Sector-specific analysis and forecasts'],
                            ['title' => 'Custom Reports', 'description' => 'Tailored research for your needs'],
                        ]
                    ],
                    'status' => 'draft',
                ],
            ],
            'contact' => [
                [
                    'sort' => 10,
                    'component_type' => 'Hero',
                    'props_json' => [
                        'title' => 'Contact Us',
                        'subtitle' => 'Get in touch with our team'
                    ],
                    'status' => 'draft',
                ],
                [
                    'sort' => 20,
                    'component_type' => 'RichText',
                    'props_json' => [
                        'html' => '<p>We would love to hear from you. Please fill out the form below.</p>'
                    ],
                    'status' => 'draft',
                ],
            ],
            'membership' => [
                [
                    'sort' => 10,
                    'component_type' => 'Hero',
                    'props_json' => [
                        'title' => 'Membership',
                        'subtitle' => 'Join our community'
                    ],
                    'status' => 'draft',
                ],
                [
                    'sort' => 20,
                    'component_type' => 'RichText',
                    'props_json' => [
                        'html' => '<h2>Member Benefits</h2><p>Access to exclusive research, reports, and seminars.</p>'
                    ],
                    'status' => 'draft',
                ],
                [
                    'sort' => 30,
                    'component_type' => 'CardGrid',
                    'props_json' => [
                        'cards' => [
                            ['title' => 'Standard', 'description' => 'Basic access to reports'],
                            ['title' => 'Premium', 'description' => 'Full access + consulting'],
                        ]
                    ],
                    'status' => 'draft',
                ],
            ],
            'standard-membership' => [
                [
                    'sort' => 10,
                    'component_type' => 'Hero',
                    'props_json' => [
                        'title' => 'Standard Membership',
                        'subtitle' => 'Access to essential research'
                    ],
                    'status' => 'draft',
                ],
                [
                    'sort' => 20,
                    'component_type' => 'RichText',
                    'props_json' => [
                        'html' => '<h2>Standard Plan</h2><p>Monthly reports, newsletter, and member events.</p>'
                    ],
                    'status' => 'draft',
                ],
            ],
            'premium-membership' => [
                [
                    'sort' => 10,
                    'component_type' => 'Hero',
                    'props_json' => [
                        'title' => 'Premium Membership',
                        'subtitle' => 'Full access and consulting'
                    ],
                    'status' => 'draft',
                ],
                [
                    'sort' => 20,
                    'component_type' => 'RichText',
                    'props_json' => [
                        'html' => '<h2>Premium Plan</h2><p>All reports, priority support, consulting hours.</p>'
                    ],
                    'status' => 'draft',
                ],
            ],
            'privacy-policy' => [
                [
                    'sort' => 10,
                    'component_type' => 'Hero',
                    'props_json' => [
                        'title' => 'Privacy Policy'
                    ],
                    'status' => 'draft',
                ],
                [
                    'sort' => 20,
                    'component_type' => 'RichText',
                    'props_json' => [
                        'html' => '<h2>Privacy Policy</h2><p>We respect your privacy and protect your personal information.</p>'
                    ],
                    'status' => 'draft',
                ],
            ],
            'terms' => [
                [
                    'sort' => 10,
                    'component_type' => 'Hero',
                    'props_json' => [
                        'title' => 'Terms of Service'
                    ],
                    'status' => 'draft',
                ],
                [
                    'sort' => 20,
                    'component_type' => 'RichText',
                    'props_json' => [
                        'html' => '<h2>Terms of Service</h2><p>Please read these terms carefully.</p>'
                    ],
                    'status' => 'draft',
                ],
            ],
            'faq' => [
                [
                    'sort' => 10,
                    'component_type' => 'Hero',
                    'props_json' => [
                        'title' => 'Frequently Asked Questions'
                    ],
                    'status' => 'draft',
                ],
                [
                    'sort' => 20,
                    'component_type' => 'RichText',
                    'props_json' => [
                        'html' => '<h2>FAQ</h2><p>Find answers to common questions.</p>'
                    ],
                    'status' => 'draft',
                ],
            ],
            'cri-consulting' => [
                [
                    'sort' => 10,
                    'component_type' => 'Hero',
                    'props_json' => [
                        'title' => 'CRI Consulting',
                        'subtitle' => 'Expert economic consulting services'
                    ],
                    'status' => 'draft',
                ],
                [
                    'sort' => 20,
                    'component_type' => 'RichText',
                    'props_json' => [
                        'html' => '<h2>Consulting Services</h2><p>Custom research and analysis for your business needs.</p>'
                    ],
                    'status' => 'draft',
                ],
            ],
        ];

        // Process each page
        foreach ($pageSections as $slug => $sections) {
            $page = CmsV2Page::where('slug', $slug)->first();
            
            if (!$page) {
                $this->command->warn("Page not found: {$slug}");
                continue;
            }

            $this->command->info("Adding sections to: {$slug}");

            foreach ($sections as $sectionData) {
                // Check if section already exists for this page with same sort order
                $exists = CmsV2Section::where('page_id', $page->id)
                    ->where('sort', $sectionData['sort'])
                    ->exists();

                if (!$exists) {
                    CmsV2Section::create([
                        'id' => (string) Str::ulid(),
                        'page_id' => $page->id,
                        'sort' => $sectionData['sort'],
                        'component_type' => $sectionData['component_type'],
                        'props_json' => $sectionData['props_json'],
                        'status' => $sectionData['status'],
                    ]);
                    $this->command->info("  - Created {$sectionData['component_type']} (sort: {$sectionData['sort']})");
                } else {
                    $this->command->comment("  - Section already exists (sort: {$sectionData['sort']})");
                }
            }
        }

        $this->command->info('CMS V2 Sections seeder completed!');
    }
}

