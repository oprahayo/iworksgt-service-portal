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
