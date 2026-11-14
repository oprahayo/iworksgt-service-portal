<?php
/**
 * Licensed, illustrative business photography from Unsplash.
 * Uses direct Unsplash CDN transformations (not the Unsplash API), fixed photo IDs,
 * responsive width parameters and compact quality to avoid oversized assets.
 *
 * Photographs depict stock subjects, not iWorks staff, clients or actual deliverables.
 * See assets/photo-sources.md for photographers, source URLs and the Unsplash License.
 */
if (!function_exists('iworks_stock_photo_url')) {
    function iworks_stock_photo_url(string $subject, int $width = 960): string
    {
        $ids = [
            'consultation' => 'photo-1758611972678-bc3b29b4718f',
            'registration' => 'photo-1772588627327-1eeddcf73c11',
            'digital' => 'photo-1778146476147-5f8d4bd03c79',
            'tracking' => 'photo-1651684195895-38708dc94cfa',
            'meeting' => 'photo-1739298061707-cefee19941b7',
            'strategy' => 'photo-1758518729908-d4220a678d81',
            'developer' => 'photo-1730130054404-c2bd8e7038c2',
        ];
        $width = max(320, min($width, 1440));
        $photo = $ids[$subject] ?? $ids['consultation'];
        return 'https://images.unsplash.com/' . $photo . '?auto=format&fit=crop&w=' . $width . '&q=75';
    }
}

/**
 * Local copies of selected, real Pexels photographs installed by the
 * photography-only cPanel updater. Never use these assets as client work,
 * employee portraits or an endorsement. See assets/photo-sources.md.
 *
 * The Website Editor stays authoritative: a custom uploaded image wins.
 * Only an empty selection or the retired Paperless keyboard placeholder
 * is replaced. Missing files fail gracefully to the old presentation.
 */
if (!function_exists('iworks_authentic_photo_asset')) {
    function iworks_authentic_photo_asset(string $key): string
    {
        $files = [
            'hero-desktop' => 'hero-professional-desktop.jpg',
            'hero-mobile' => 'hero-professional-mobile.jpg',
            'registration' => 'registration-documents.jpg',
            'trademark' => 'trademark-branding.jpg',
            'operations' => 'operations-team.jpg',
            'digital' => 'digital-designer.jpg',
        ];
        return isset($files[$key])
            ? '/assets/photography/' . $files[$key] . '?v=20261114'
            : '';
    }
}

if (!function_exists('iworks_authentic_photo_url')) {
    function iworks_authentic_photo_url(string $key): string
    {
        $url = iworks_authentic_photo_asset($key);
        if ($url === '') {
            return '';
        }
        // Works on the existing shared codebase and does not probe HTTP.
        $path = explode('?', $url, 2)[0];
        return is_file(dirname(__DIR__) . $path) ? $url : '';
    }
}

if (!function_exists('iworks_is_retired_paperless_photo')) {
    function iworks_is_retired_paperless_photo(string $url): bool
    {
        return str_contains($url, '1772909461_e4c73dc4.png');
    }
}

if (!function_exists('iworks_select_authentic_photo')) {
    function iworks_select_authentic_photo(string $key, string $existing = ''): string
    {
        $existing = trim($existing);
        // Preserve independently selected, real CMS photographs.
        if ($existing !== '' && !iworks_is_retired_paperless_photo($existing)) {
            return $existing;
        }
        return iworks_authentic_photo_url($key) ?: $existing;
    }
}

if (!function_exists('iworks_blog_photo_url')) {
    function iworks_blog_photo_url(array $post): string
    {
        $existing = trim((string) ($post['featured_image']['url'] ?? ''));
        $topic = strtolower(trim((string) ($post['slug'] ?? '') . ' ' . (string) ($post['title'] ?? '')));
        if (str_contains($topic, 'trademark')) {
            return iworks_select_authentic_photo('trademark', $existing);
        }
        if (str_contains($topic, 'cac') || str_contains($topic, 'registration')) {
            return iworks_select_authentic_photo('registration', $existing);
        }
        if (str_contains($topic, 'remote') || str_contains($topic, 'operations') || str_contains($topic, 'workflow')) {
            return iworks_select_authentic_photo('operations', $existing);
        }
        return $existing;
    }
}

if (!function_exists('iworks_service_photo_url')) {
    function iworks_service_photo_url(array $service, string $existing = ''): string
    {
        $topic = strtolower(trim((string) ($service['slug'] ?? '') . ' ' . (string) ($service['name'] ?? '')));
        if (str_contains($topic, 'trademark')) {
            return iworks_select_authentic_photo('trademark', $existing);
        }
        if (str_contains($topic, 'business name')
            || str_contains($topic, 'business-name')
            || str_contains($topic, 'private limited')
            || str_contains($topic, 'private-limited')
            || str_contains($topic, 'company registration')
            || str_contains($topic, 'company-registration')) {
            return iworks_select_authentic_photo('registration', $existing);
        }
        if (str_contains($topic, 'website design') || str_contains($topic, 'website-design')
            || str_contains($topic, 'graphics design') || str_contains($topic, 'graphics-design')) {
            return iworks_select_authentic_photo('digital', $existing);
        }
        return $existing;
    }
}
