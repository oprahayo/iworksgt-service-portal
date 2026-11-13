<?php
declare(strict_types=1);

/**
 * Unknown pretty paths must never silently serve the homepage with HTTP 200.
 * Explicit ?page= routes and direct index.php / legacy index.html stay intact.
 */
function iworks_unmatched_pretty_path(string $path, bool $recognized): bool
{
    $path = trim($path, '/');
    return !$recognized && $path !== ''
        && !in_array($path, ['index.php', 'index.html'], true);
}
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
        'case-studies' => ['Client Projects & Case Studies | iWORKS GLOBAL', 'Explore completed business registration and digital service projects, their scope and documented results from iWORKS GLOBAL.'],
        'management' => ['Management & Leadership | iWORKS GLOBAL LTD', 'Meet the people responsible for iWORKS GLOBAL LTD operations and the delivery of business and digital services.'],
        'pricing' => ['Business Services & Pricing | iWorks Global', 'Review published business registration and digital service prices; final quotes and applicable charges are confirmed before payment.'],
        'track' => ['Track Your iWorks Order | Service Progress', 'Find the latest status of your existing iWorks service request using your job reference and contact details.'],
        'terms' => ['Terms and Conditions | iWORKS GLOBAL', 'Read the terms governing service requests, account use, payments and delivery through iWORKS GLOBAL.'],
        'privacy' => ['Privacy Policy | iWORKS GLOBAL', 'Understand how iWORKS GLOBAL handles account information, service requests, documents and contact details.'],
    ];
    return isset($map[$page]) ? ['title'=>$map[$page][0], 'description'=>$map[$page][1]] : [];
}
function iworks_search_service_meta(array $service): array
{
    $slug = strtolower(trim((string) ($service['slug'] ?? '')));
    $map = [
        'business-name-registration-cac-nigeria' => ['Register a Business Name in Nigeria | CAC Support | iWorks', 'Want to register a business name in Nigeria? iWorks helps with CAC name search, online filing preparation, documents and remote tracking.'],
        'cac-business-name' => ['Register a Business Name in Nigeria | CAC Support | iWorks', 'Review Business Name / Enterprise filing support and requirements with iWorks, including name checks, documents and remote tracking.'],
        'company-registration' => ['Register a Company in Nigeria | CAC Incorporation | iWorks', 'Start company registration in Nigeria with structured CAC incorporation support. Prepare company and director details remotely with iWorks Global.'],
        'website-design-development' => ['Website Design in Nigeria | Remote Web Development | iWorks', 'Need a professional website designed in Nigeria? iWorks builds and supports business websites remotely, with responsive design and technical support.'],
        'domain-web-hosting' => ['Domain Registration & Web Hosting in Nigeria | iWorks', 'Register domains and arrange managed website hosting with iWorks Global. Get help with DNS, SSL, business email and hosting support.'],
        'trademark' => ['Trademark Registration Support in Nigeria | iWorks Global', 'Protect your business name or brand with Nigerian trademark filing support, application guidance and remote document coordination.'],
        'incorporated-trustees' => ['NGO & Incorporated Trustees Registration Nigeria | iWorks', 'Get help preparing incorporated trustees and NGO registration requests in Nigeria, including regulatory documentation and filing support.'],
        'launch-a-company-package' => ['Launch a Company in Nigeria | Registration & Branding | iWorks', 'Explore coordinated Nigerian company registration, compliance documentation, branding and online launch support through iWorks.'],
        'graphics-design' => ['Graphic Design & Branding Services in Nigeria | iWorks', 'Business logo, identity and marketing graphics design for Nigerian and international clients, coordinated remotely with iWorks Global.'],
        'seo-smm-digital-marketing' => ['SEO & Digital Marketing Services in Nigeria | iWorks', 'Plan SEO, search visibility and social media marketing support for your business with iWorks Global. Remote delivery available.'],
        'email-list-smtp' => ['Business Email & SMTP Setup in Nigeria | iWorks Global', 'Set up business email delivery, SMTP and email domain configuration with help from iWorks Global.'],
        'form-your-u-s-llc-in-minutes' => ['US LLC Formation Support from Nigeria | iWorks Global', 'Explore U.S. LLC formation support remotely with iWorks. Understand state selection, registered agent, documentation and filing requirements before ordering.'],
        'pro-training' => ['Business & Digital Skills Training in Nigeria | iWorks', 'Explore practical business operations, digital skills and professional training with iWorks. Confirm the programme format, topics and availability.'],
        'social-enterprise-ltd-by-guarantee' => ['Limited by Guarantee Registration in Nigeria | iWorks', 'Need a company limited by guarantee in Nigeria? Explore CAC filing preparation, governance documentation and regulatory considerations with iWorks.'],
        'vehicles-assets-tracking' => ['Vehicle & Asset Tracking Services in Nigeria | iWorks', 'Explore GPS and asset monitoring solutions, deployment requirements, device setup and ongoing support with iWorks Global.'],
    ];
    return isset($map[$slug]) ? ['title'=>$map[$slug][0], 'description'=>$map[$slug][1]] : [];
}
function iworks_search_service_guide(array $service): array
{
    $slug = strtolower(trim((string) ($service['slug'] ?? '')));
    if (in_array($slug, ['business-name-registration-cac-nigeria', 'cac-business-name'], true)) {
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
            'official' => 'https://icrp.cac.gov.ng/support/faq/',
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
            'official' => 'https://icrp.cac.gov.ng/support/faq/',
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

    $roundTwoGuides = [
        'trademark' => [
            'heading' => 'Trademark registration support in Nigeria',
            'intro' => 'Trademark protection starts with identifying your brand, the goods or services involved and potential conflicts with earlier marks. iWorks can help organise your filing request; acceptance and registration depend on the relevant authority.',
            'steps' => [
                ['Define the mark', 'Identify the name, logo or other mark and the goods or services it will cover.'],
                ['Review existing marks', 'Assess the appropriate classification and arrange a trademark search before an application.'],
                ['Prepare and track the filing', 'Submit the required particulars and respond to examination notices or objections if issued.'],
            ],
            'questions' => [
                ['Does registering a company automatically protect its trademark?', 'No. Company registration and trademark protection are different procedures.'],
                ['Can I request trademark filing support remotely?', 'Yes. Information and document coordination can take place online, subject to authority-specific requirements.'],
            ],
        ],
        'domain-web-hosting' => [
            'heading' => 'Business domain registration and web hosting in Nigeria',
            'intro' => 'Your domain name, web hosting and website maintenance are separate parts of a reliable online presence. iWorks can help plan and configure these services for businesses operating in Nigeria or remotely.',
            'steps' => [
                ['Choose a domain', 'Check availability, renewal pricing, ownership details and suitable extensions.'],
                ['Select hosting', 'Match storage, traffic, email and application requirements to a suitable hosting plan.'],
                ['Configure and maintain', 'Connect DNS, SSL and backups, then agree how security patches and renewals will be handled.'],
            ],
            'questions' => [
                ['Do I need hosting to publish a website?', 'Most websites require a hosting or deployment platform; the right option depends on the technology.'],
                ['Can iWorks migrate an existing site?', 'Discuss the existing host, domain ownership, backups and downtime expectations before requesting migration support.'],
            ],
        ],
        'incorporated-trustees' => [
            'heading' => 'How to register an NGO or incorporated trustees in Nigeria',
            'intro' => 'An association, foundation or other qualifying organisation may need an incorporated trustees filing. The process involves governance information, trustees and the applicable documents. iWorks helps coordinate preparation, while CAC decides the application.',
            'steps' => [
                ['Confirm the organisation type', 'Clarify the purpose, proposed name and whether incorporated trustees is an appropriate structure.'],
                ['Organise governance details', 'Prepare trustees and organisation information and any records required by CAC.'],
                ['Submit and respond', 'Complete the required filing steps and follow up on official queries or corrections.'],
            ],
            'questions' => [
                ['Is registering an NGO the same as registering a business name?', 'No. Nonprofit and business registration structures have different legal and reporting requirements.'],
            ],
        ],
        'graphics-design' => [
            'heading' => 'Professional branding and graphic design for Nigerian businesses',
            'intro' => 'A consistent visual identity helps customers recognise your business across websites, proposals and social channels. iWorks can discuss logos, brand guidelines and marketing materials for remote clients.',
            'steps' => [
                ['Share a creative brief', 'Explain your brand values, target customers, desired applications and design references.'],
                ['Review concepts', 'Check legibility, use on mobile screens, colour contrast and relevance to your audience.'],
                ['Approve deliverables', 'Confirm the agreed export formats, usage scope and any ongoing design support.'],
            ],
            'questions' => [
                ['Can I commission graphic design remotely?', 'Yes. Briefing, feedback and delivery can be coordinated online.'],
            ],
        ],
        'seo-smm-digital-marketing' => [
            'heading' => 'SEO and digital marketing services for businesses in Nigeria',
            'intro' => 'Search marketing begins with understanding customer demand, making pages crawlable and publishing useful content. iWorks can help evaluate technical SEO, search intent and social channels without promising specific rankings.',
            'steps' => [
                ['Audit your visibility', 'Review technical search indexing, page content, mobile performance and existing search data.'],
                ['Choose high-intent topics', 'Map services to relevant customer queries, improve page titles and add useful answers.'],
                ['Measure and refine', 'Monitor qualified enquiries, organic clicks and conversions, then adjust content and campaigns.'],
            ],
            'questions' => [
                ['Can anyone guarantee a first-page Google ranking?', 'No. Rankings depend on competition, search relevance and Google systems; meaningful progress requires measurement.'],
            ],
        ],
        'email-list-smtp' => [
            'heading' => 'Business email and SMTP setup for your own domain',
            'intro' => 'Professional email requires more than an inbox. Domain ownership, DNS authentication and appropriate SMTP configuration help businesses send transactional messages more reliably.',
            'steps' => [
                ['Select an email provider', 'Identify mailbox needs, sending volume and the domain you control.'],
                ['Configure authentication', 'Set the required MX, SPF, DKIM and DMARC records according to the provider.'],
                ['Test delivery', 'Check inbound and outbound messages and monitor sending limits and spam reports.'],
            ],
            'questions' => [
                ['Is SMTP the same as buying an email list?', 'No. SMTP is a method of sending email; contact lists require legitimate permission and compliance with applicable rules.'],
            ],
        ],
        'launch-a-company-package' => [
            'heading' => 'Launch your company in Nigeria with coordinated support',
            'intro' => 'Launching a business may involve incorporation, brand identity, a website and subsequent compliance tasks. Agree the exact services included in your package before ordering; statutory approvals and third-party fees vary.',
            'steps' => [
                ['Choose the company structure', 'Clarify the legal registration type, proposed business name and essential filing information.'],
                ['Prepare brand and digital needs', 'List design, domain, website and email requirements within the agreed scope.'],
                ['Review scope and launch', 'Confirm deliverables, dependencies, fees and what happens after registration.'],
            ],
            'questions' => [
                ['Does the package include a bank account?', 'Banking is not included in iWorks service offerings. Ask for the current package scope before purchase.'],
            ],
        ],
        'social-enterprise-ltd-by-guarantee' => [
            'heading' => 'Company limited by guarantee in Nigeria',
            'intro' => 'A company limited by guarantee has different governance and approval considerations from a conventional company limited by shares. iWorks can coordinate documentation and guidance for the applicable Nigerian filing.',
            'steps' => [
                ['Check suitability', 'Clarify the organisation purpose and whether a guarantee structure is legally appropriate.'],
                ['Prepare governance details', 'Organise the proposed company name, members and governing documents required for filing.'],
                ['Track official review', 'Follow the relevant CAC procedures and any additional approvals or official enquiries.'],
            ],
            'questions' => [
                ['Is a limited by guarantee company the same as a charity?', 'Not necessarily. Entity type, objectives and regulatory obligations must be considered separately.'],
            ],
        ],
        'pro-training' => [
            'heading' => 'Practical business and digital skills training',
            'intro' => 'Choose training based on the skills you need in daily operations, such as managing a website, business tools or online customer workflows. Confirm the programme syllabus and delivery format before registering.',
            'steps' => [
                ['Define your goals', 'Identify the business or technical tasks you want to perform independently.'],
                ['Check the learning scope', 'Review session format, learning materials, prerequisites and practical exercises.'],
                ['Apply the skills', 'Use guided projects to practise the tools relevant to your business.'],
            ],
            'questions' => [
                ['Are remote classes available?', 'Ask the iWorks team about the current programme format and schedule before ordering.'],
            ],
        ],
        'vehicles-assets-tracking' => [
            'heading' => 'Vehicle and asset tracking setup in Nigeria',
            'intro' => 'Tracking systems depend on compatible devices, connectivity, installation and an appropriate monitoring platform. iWorks can discuss deployment requirements and support for authorised assets.',
            'steps' => [
                ['Define the assets', 'Confirm the vehicle or equipment type, coverage needs and intended monitoring features.'],
                ['Review equipment and connectivity', 'Check device compatibility, installation needs, data connectivity and ongoing fees.'],
                ['Configure access', 'Test authorised users, alerts, dashboard access and data handling procedures.'],
            ],
            'questions' => [
                ['Can tracking be set up without access to the vehicle?', 'Installation and compatibility depend on the hardware and vehicle; confirm the requirements with the provider.'],
            ],
        ],
        'form-your-u-s-llc-in-minutes' => [
            'heading' => 'How to plan US LLC formation remotely',
            'intro' => 'Forming a US limited liability company requires choosing a state, appointing any required registered agent and filing the correct formation documents. Ongoing tax and reporting obligations vary and should be evaluated separately.',
            'steps' => [
                ['Choose a state', 'Compare formation rules, registered-agent requirements and recurring fees.'],
                ['Prepare ownership details', 'Organise the company name, ownership and contact details required for filing.'],
                ['Plan ongoing compliance', 'Consider annual reports, tax filing, banking eligibility and recordkeeping responsibilities.'],
            ],
            'questions' => [
                ['Does a US LLC automatically provide a US bank account?', 'No. Banking eligibility and due diligence depend on the bank; bank account provision is not part of this iWorks service.'],
            ],
        ],
    ];
    return $roundTwoGuides[$slug] ?? [];
}
