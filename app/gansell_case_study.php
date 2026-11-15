<?php
declare(strict_types=1);

/**
 * Owner-approved Gansell portfolio entry.
 * Source-managed public case until an editorial CMS record is published.
 * The scope below describes the verified live product, not unverified
 * iWORKS deliverables, invoices, hosting or performance metrics.
 */
function iworks_gansell_case_study(): array
{
    return [
        'slug' => 'gansell-website-project',
        'client_name' => 'Gansell',
        'title' => 'Gansell: A Nigerian Vendor Marketplace',
        'category' => 'Marketplace Website Project',
        'summary' => 'Gansell is a Nigerian marketplace for discovering vendors, products and services, with searchable business listings and a way for vendors to list their businesses.',
        'outcome' => 'A public online marketplace with vendor discovery, product and service categories, and business-listing entry points.',
        'website' => 'https://gansell.com/',
        'status' => 'published',
        'image_id' => 0,
        'logo_id' => 0,
        'story_html' => <<<'HTML'
<h2>Marketplace Overview</h2>
<p>Gansell brings Nigerian vendors and prospective customers together on one digital platform. Visitors can discover businesses, browse products and find services from local providers.</p>
<h2>The Website Experience</h2>
<p>The public website provides vendor and product search, categories such as fashion, food, health, beauty, electronics and home products, and an option for businesses to list themselves on Gansell.</p>
<h2>Project Snapshot</h2>
<p>Gansell is featured among iWORKS GLOBAL's selected digital case studies. The website screenshot shows the live marketplace, while the project description focuses on the publicly verifiable product experience.</p>
HTML
    ];
}
