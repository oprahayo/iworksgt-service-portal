<?php
declare(strict_types=1);

// Verify both inner-page forms without a database, network or active user.
function normalize_page_block_type(string $type): string { return $type; }
function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function public_url(string $page, array $params = []): string { return '/' . $page . ($params === [] ? '' : '?' . http_build_query($params)); }
function portal_url(string $page): string { return '/portal/' . $page; }
function csrf_token(): string { return 'test-csrf-value'; }
function flash(string $kind): ?string { return null; }
function assert_public(bool $valid, string $reason): void { if (!$valid) { throw new RuntimeException($reason); } }

$layout = [
  ['type'=>'hero','data'=>['headline'=>'Ask iWorks about a service', 'subheadline'=>'Talk to our team.']],
  ['type'=>'contact_form','data'=>['email'=>'hello@example.com','phone'=>'+234 806 000 0000', 'submitLabel'=>'Send enquiry']],
];
ob_start();
require __DIR__ . '/../views/public-contact-clean.php';
$contact = (string) ob_get_clean();
assert_public(str_contains($contact,'Ask iWorks about a service'), 'CMS title must remain editable.');
assert_public(str_contains($contact,'mailto:hello@example.com'), 'Contact email should come from CMS.');
assert_public(!str_contains($contact,'+234 806 000 0000'), 'Dummy telephone number must not appear.');
assert_public(str_contains($contact,'name="form_action" value="website_lead"'), 'Existing lead handler must remain wired.');
assert_public(str_contains($contact,'name="csrf_token" value="test-csrf-value"'), 'CSRF token must be included.');
foreach (['full_name','email','phone','company_name','message'] as $field) {
    assert_public(str_contains($contact,'name="'.$field.'"'), 'Missing original backend field '.$field);
}
assert_public(substr_count($contact,'<section') === 2, 'Contact should have exactly two short sections.');

$layout = [['type'=>'hero','data'=>['headline'=>'Track your order progress in seconds.']]];
$publicTrackResult = [
  'job_code'=>'JOB-2026-001',
  'service_name'=>'Business registration',
  'status'=>'processing',
  'payment_status'=>'paid',
];
ob_start();
require __DIR__ . '/../views/public-track-clean.php';
$track = (string) ob_get_clean();
assert_public(str_contains($track,'Track your order'), 'Tracking title should render.');
assert_public(str_contains($track,'name="job_code"'), 'Job lookup code field missing.');
assert_public(str_contains($track,'name="identifier"'), 'Job lookup customer verification field missing.');
assert_public(str_contains($track,'name="csrf_token" value="test-csrf-value"'), 'Track CSRF missing.');
assert_public(str_contains($track,'JOB-2026-001'), 'Verified tracking result should render.');
assert_public(str_contains($track,'Business registration'), 'Tracking service summary missing.');
assert_public(str_contains($track,'/portal/login'), 'Authenticated details link should remain.');
assert_public(!str_contains($track,'name="message"'), 'Track form should not include the contact form.');
assert_public(substr_count($track,'<section') === 3, 'Track page should have form and one concise new-service promotion.');
assert_public(str_contains($track,'iw-launch-track-promo') && str_contains($track,'/services'), 'Track must show relevant catalogue CTA.');

echo "public_inner_forms_test passed\n";