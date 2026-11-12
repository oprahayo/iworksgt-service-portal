<?php
declare(strict_types=1);
/**
 * Search-intent copy for published iWorks services. Complements CMS descriptions;
 * intentionally makes no promises about CAC approvals, filing speed or rankings.
 */
function iworks_search_page_meta(string $page): array
{
    $map = [
        'home' => ['CAC Business Registration & Website Design in Nigeria | iWorks Global', 'Register a business in Nigeria, protect your trademark or build a website remotely. iWorks Global provides CAC filing support and digital services online.'],
        'services' => ['Business Registration, CAC Filing & Web Design Services | iWorks', 'Explore Nigerian business name registration, company incorporation, trademarks, website development, hosting and digital marketing services.'],
        'about' => ['About iWORKS GLOBAL LTD | Business & Digital Support in Nigeria', 'Learn about iWORKS GLOBAL LTD and its support for Nigerian founders: CAC registration, compliance, branding and remote website development.'],
        'contact' => ['Contact iWorks Global | CAC & Website Design Enquiries', 'Contact iWorks Global about business registration in Nigeria, trademarks, company compliance, web development and remote digital services.'],
        'faq' => ['Business Registration and Service FAQs | iWorks Global', 'Answers about CAC-related service requests, document submission, remote business support and tracking your iWorks Global service order.'],
        'blog' => ['CAC Registration & Digital Business Guides | iWorks Global', 'Practical guides for founders on Nigerian business registration, trademarks, website planning and remote business operations.'],
    ];
    return isset($map[$page]) ? ['title'=>$map[$page][0], 'description'=>$map[$page][1]] : [];
}
function iworks_search_service_meta(array $service): array
{
    $slug = strtolower(trim((string) ($service['slug'] ?? '')));
    $map = [
        'business-name-registration-cac-nigeria' => ['Register a Business Name in Nigeria | CAC Support | iWorks', 'Want to register a business name in Nigeria? iWorks helps with CAC name search, online filing preparation, documents and remote tracking.'],
        'company-registration' => ['Register a Company in Nigeria | CAC Incorporation | iWorks', 'Start company registration in Nigeria with structured CAC incorporation support. Prepare company and director details remotely with iWorks Global.'],
        'website-design-development' => ['Website Design in Nigeria | Remote Web Development | iWorks', 'Need a professional website designed in Nigeria? iWorks builds and supports business websites remotely, with responsive design and technical support.'],
        'domain-web-hosting' => ['Domain Registration & Web Hosting in Nigeria | iWorks', 'Register domains and arrange managed website hosting with iWorks Global. Get help with DNS, SSL, business email and hosting support.'],
        'trademark' => ['Trademark Registration Support in Nigeria | iWorks Global', 'Protect your business name or brand with Nigerian trademark filing support, application guidance and remote document coordination.'],
        'incorporated-trustees' => ['NGO & Incorporated Trustees Registration Nigeria | iWorks', 'Get help preparing incorporated trustees and NGO registration requests in Nigeria, including regulatory documentation and filing support.'],
        'launch-a-company-package' => ['Launch a Company in Nigeria | Registration & Branding | iWorks', 'Explore coordinated Nigerian company registration, compliance documentation, branding and online launch support through iWorks.'],
        'graphics-design' => ['Graphic Design & Branding Services in Nigeria | iWorks', 'Business logo, identity and marketing graphics design for Nigerian and international clients, coordinated remotely with iWorks Global.'],
        'seo-smm-digital-marketing' => ['SEO & Digital Marketing Services in Nigeria | iWorks', 'Plan SEO, search visibility and social media marketing support for your business with iWorks Global. Remote delivery available.'],
        'email-list-smtp' => ['Business Email & SMTP Setup in Nigeria | iWorks Global', 'Set up business email delivery, SMTP and email domain configuration with help from iWorks Global.'],
    ];
    return isset($map[$slug]) ? ['title'=>$map[$slug][0], 'description'=>$map[$slug][1]] : [];
}
function iworks_search_service_guide(array $service): array
{
    $slug = strtolower(trim((string) ($service['slug'] ?? '')));
    if ($slug === 'business-name-registration-cac-nigeria') {
        return [
            'heading' => 'How to register a business name in Nigeria',
            'intro' => 'Registration with the Corporate Affairs Commission starts with choosing a suitable name and preparing accurate proprietor and business information. iWorks helps coordinate remote requirements and track your service request; CAC remains responsible for registration decisions.',
            'steps' => [
                ['Check name availability', 'Prepare proposed names and check their availability using the official CAC process.'],
                ['Prepare documents', 'Gather your business activity, contact details, address and identification documents requested for the filing.'],
                ['Submit and follow up', 'Complete the applicable online registration steps, pay required charges and respond to queries if CAC requests corrections.'],
            ],
            'questions' => [
                ['Can I register a business name in Nigeria remotely?', 'The CAC provides online registration processes. iWorks can support remote coordination; specific identity or document checks still apply.'],
                ['Is a business name the same as a limited company?', 'No. Business name registration and company incorporation are different legal registration types with different filing requirements.'],
            ],
            'official' => 'https://cac.gov.ng/services/business-name',
        ];
    }
    if ($slug === 'company-registration') {
        return [
            'heading' => 'How to register a company in Nigeria',
            'intro' => 'Company incorporation involves a name reservation, company details, directors or shareholders and the documents required by the Corporate Affairs Commission. iWorks helps you organise these requirements and follow your application remotely.',
            'steps' => [
                ['Choose the legal structure', 'Identify the company type and proposed name for CAC availability checks.'],
                ['Prepare incorporation details', 'Collect required director, shareholder and registered-address information.'],
                ['File and track', 'Submit applicable forms and fees, then respond to any official queries or requests for correction.'],
            ],
            'questions' => [
                ['Can I register my Nigerian company from abroad?', 'iWorks can help coordinate the filing remotely, while official documentation and identity requirements remain applicable.'],
            ],
            'official' => 'https://cac.gov.ng/services/company-registration',
        ];
    }
    if ($slug === 'website-design-development') {
        return [
            'heading' => 'Professional website design in Nigeria, delivered remotely',
            'intro' => 'From a business website to a service portal, iWorks can coordinate design, development and launch remotely. Confirm requirements, content, hosting and maintenance scope before the project starts.',
            'steps' => [
                ['Define your website goals', 'List your target audience, key pages, essential features and enquiry or purchase actions.'],
                ['Design for mobile and desktop', 'Review responsive layouts, navigation, accessibility and performance priorities.'],
                ['Launch and support', 'Review the completed website, connect the agreed domain and hosting and plan ongoing updates.'],
            ],
            'questions' => [
                ['Can a website be designed for my business remotely?', 'Yes. Discovery, project reviews, content submission and delivery can take place remotely.'],
            ],
        ];
    }
    return [];
}
