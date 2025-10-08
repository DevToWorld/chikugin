# CMS System Architecture

This project uses **TWO different CMS systems** running in parallel:

## 1. CMS V2 (Block-based CMS) - NEW System

**Database Table:** `cms_v2_pages`, `cms_v2_sections`
**Admin Interface:** `BlockCmsEditor.vue`
**API Endpoints:** `/api/admin/cms-v2/*`, `/api/public/pages-v2/{slug}`

### Pages Using CMS V2:
- `home`
- `company`
- `company-profile`
- `services`
- `contact`
- `membership`
- `standard-membership`
- `premium-membership`
- `privacy-policy`
- `terms`
- `faq`
- `cri-consulting`
- Flow pages (contact-confirm, contact-complete, etc.)

### How It Works:
1. Pages have sections with component types (Hero, RichText, CardGrid, etc.)
2. Sections have `props_json` for configuration
3. Must be **published** to be visible publicly
4. Supports versioning and rollback

## 2. PageContent (JSON-based CMS) - OLD System

**Database Table:** `page_contents`
**Admin Interface:** Various admin forms
**API Endpoints:** `/api/public/pages/{pageKey}`

### Pages Using OLD PageContent System:
- **`about-institute`** (accessed via `/aboutus` route)
- All pages with `<CmsText>`, `<CmsManagedImage>`, `<HeroSection>` components
- Pages seeded by `*PageJsonSeeder.php` files

### How It Works:
1. Pages store data as JSON with text/html fields
2. Components use `pageKey` and `fieldKey` to fetch content
3. Uses `CmsText` and `CmsManagedImage` Vue components

## Important Notes

### About Pages Confusion
- **Router Path:** `/aboutus` → loads `AboutInstitutePage.vue`
- **Component Uses:** OLD PageContent system with key `about-institute`
- **Router Path:** `/about` → redirects to `/company`
- **Router Path:** `/about-institute` → redirects to `/aboutus`

**Do NOT create CMS V2 pages for:** `about`, `aboutus`, or `about-institute`
These use the OLD PageContent system!

## Migration Strategy

To migrate a page from OLD to CMS V2:
1. Update the Vue component to fetch from `/api/public/pages-v2/{slug}`
2. Create CMS V2 page and sections
3. Publish the page
4. Remove OLD PageContent seeder

## Seeder Order

```php
CmsV2DefaultPagesSeeder::class,          // Create CMS V2 pages
CmsV2AdditionalPagesSeeder::class,       // More pages
CmsV2FlowPagesSeeder::class,             // Flow pages
CmsV2CleanupAboutPageSeeder::class,      // Remove about* pages from CMS V2
CmsV2SectionsSeeder::class,              // Add sections to pages
CmsV2PublishPagesSeeder::class,          // Publish all pages
// ...
AboutInstitutePageJsonSeeder::class,     // OLD system for about-institute
```

## Quick Reference

| Need to... | Use System |
|------------|------------|
| Edit home page blocks | CMS V2 (BlockCmsEditor) |
| Edit aboutus content | OLD PageContent (AboutInstitutePageJsonSeeder) |
| Add new page with sections | CMS V2 |
| Edit existing text/images | Check which system the page uses |

