<?php
declare(strict_types=1);
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
$reviews = [
    ['rating'=>10, 'feedback'=>'Brilliant registration support and clear updates.', 'display_name'=>'Ola A.', 'service_name'=>'Business registration'],
    ['rating'=>8, 'feedback'=>'A clear, professional service from start to finish.', 'display_name'=>'Ife J.', 'service_name'=>'Trademark'],
    ['rating'=>5, 'feedback'=>'The communication could have been better overall.', 'display_name'=>'Ada M.', 'service_name'=>'Website design'],
];
function list_public_job_reviews(int $limit): array { global $reviews; return array_slice($reviews, 0, $limit); }
function check_review(bool $valid, string $message): void { if (!$valid) throw new RuntimeException($message); }
ob_start();
require dirname(__DIR__) . '/views/partials/public-review-carousel.php';
$html = ob_get_clean();
check_review(str_contains($html,'4.8'), 'Verified Google score must be shown once above reviews.');
check_review(substr_count($html, 'iw-reviews-google-score') === 1, 'Google rating must be one centered element.');
check_review(substr_count($html, 'class="iw-review-slide"') === 3, 'Three consenting reviews should render.');
check_review(str_contains($html,'Ola A.') && str_contains($html,'Ada M.'), 'Shortened customer names should render.');
check_review(str_contains($html,'5/10'), 'All authentic scores, including mixed reviews, must be allowed.');
check_review(str_contains($html, 'data-iw-review-next'), 'Carousel navigation must work with multiple reviews.');
$reviews = [];
ob_start();
require dirname(__DIR__) . '/views/partials/public-review-carousel.php';
$empty = ob_get_clean();
check_review(!str_contains($empty,'class="iw-review-slide"'), 'Do not fabricate reviews when database is empty.');
check_review(str_contains($empty,'Reviews from completed portal orders'), 'Empty state should be transparent.');
echo "public_review_carousel_test passed\n";
