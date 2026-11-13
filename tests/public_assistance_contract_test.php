<?php
declare(strict_types=1);
/**
 * Regression contract for the public enquiry chat and consultation-request
 * experience. It deliberately does not require database connectivity.
 */
$root = dirname(__DIR__);
$read = static fn(string $p): string => (string) file_get_contents($root . '/' . $p);
$expect = static function (bool $condition, string $description): void {
    if (!$condition) { throw new RuntimeException($description); }
};
$widget = $read('views/partials/public-assistance.php');
$footer = $read('views/partials/public-footer.php');
$header = $read('views/partials/public-header.php');
$js = $read('assets/js/public-assistance.js');
$css = $read('assets/css/public-assistance.css');
$router = $read('index.php');

$expect(str_contains($footer, "require __DIR__ . '/public-assistance.php'"), 'Shared footer must render assistance widget.');
$expect(str_contains($header, 'assets/css/public-assistance.css'), 'Shared public header must load widget styles.');
$expect(str_contains($footer, 'assets/js/public-assistance.js'), 'Shared footer must load widget behavior.');
$expect(!str_contains($footer, 'id="iwPublicHelpDialog"'), 'Legacy support modal should not duplicate the new chat.');
$expect(str_contains($js, 'data-iw-help-open'), 'Existing public help links must still work.');
$expect(str_contains($widget, "csrf_token()") && str_contains($js, "data.append('csrf_token'"), 'Enquiry forms must use server-provided CSRF tokens.');
$expect(str_contains($router, "save_website_lead("), 'Chat and booking requests must reach the existing lead backend.');
$expect(str_contains($js, "liveApi('start'") && str_contains($js, "liveApi('send'") && str_contains($js, "liveApi('poll'"), 'Visitor chat must use persistent live conversation backend.');
$expect(str_contains($js, "'website_lead'"), 'Consultation form must still route to existing lead handler.');
$expect(str_contains($js, "'X-Requested-With': 'XMLHttpRequest'"), 'Lead requests must use server JSON responses.');
$expect(str_contains($widget, 'data-assist-end-accept') && str_contains($widget, 'data-assist-rate-skip'), 'End chat confirmation and rating skip must be available.');
foreach ([1, 2, 3, 4, 5] as $score) {
    $expect(str_contains($widget, 'data-assist-rate="<?= (int)$score ?>"'), 'One through five emoji rating controls must be rendered.');
}
$expect(str_contains($js, "session.status = 'ended'"), 'Chat must end after rating or skip.');
$expect(str_contains($js, '120000'), 'Invitation should be delayed for two minutes.');
$expect(str_contains($js, "nudge-dismissed"), 'Dismissal should persist during the browsing session.');
$expect(str_contains($js, 'document.visibilityState'), 'Hidden pages must not show intrusive prompts.');
$expect(str_contains($css, 'prefers-reduced-motion:reduce'), 'Animations must respect reduced-motion preferences.');
$expect(str_contains($css, '@media(max-width:580px)'), 'Mobile dialog layouts are required.');
$expect(str_contains($widget, 'guest_email[]') === false && str_contains($js, "input.name = 'guest_email[]'"), 'Guest emails should be added through the interactive guest control.');
$expect(str_contains($js, "new Set(guests)"), 'Duplicate guest emails must be rejected.');
$expect(str_contains($js, 'CONSULTATION REQUEST — not a confirmed booking'), 'Requests must not be represented as confirmed appointments.');
$expect(str_contains($widget, 'No meeting has been booked yet'), 'Booking success message must be truthful.');
$expect(str_contains($widget,'iwAssistDateOptions') && str_contains($widget,'iwAssistTimeOptions'), 'Digital date and time selectors must render.');
$expect(!str_contains($widget,'name="date" type="date"') && !str_contains($widget,'name="time" type="time"'), 'Native date/time inputs must be replaced.');
$expect(str_contains($js,'rebuildRequestDays') && str_contains($js,'renderRequestTimes') && str_contains($js,'aria-pressed'), 'Digital slot UI must support accessible selected states.');
$expect(str_contains($js,'requestDays.some') && str_contains($js,'requestTimes.includes'), 'Server-facing request must validate dates and times from offered suggestions.');
$expect(str_contains($widget,'West Africa Time') && str_contains($js,'Slot status: requested'), 'Site must clearly identify Lagos time and unconfirmed request status.');
$expect(str_contains($widget,'😍') && str_contains($widget,'😠'), 'Chat rating must show emotional expressions, not bare 1–5 digits.');
echo "public_assistance_contract_test passed\n";
