<?php
declare(strict_types=1);

// No network, database, or personal information is used by these tests.
$storedReview = null;
$fakeJob = ['id' => 4, 'client_id' => 15, 'status' => 'closed', 'job_code' => 'JOB-4'];
$lastQuery = '';
function is_configured(): bool { return true; }
function schema_table_exists(string $table): bool { return true; }
function now(): string { return '2026-11-12 11:00:00'; }
function get_job(int $jobId): ?array { global $fakeJob; return $jobId === 4 ? $fakeJob : null; }
function db_one(string $sql, array $params = []): ?array {
    global $storedReview;
    return str_contains($sql, 'FROM job_reviews') ? $storedReview : null;
}
function db_execute(string $sql, array $params = []): int {
    global $storedReview, $lastQuery;
    $lastQuery = $sql;
    if (str_contains($sql, 'INSERT INTO job_reviews')) {
        $storedReview = $params;
    }
    return 1;
}
function db_all(string $sql, array $params = []): array { global $lastQuery; $lastQuery = $sql; return []; }
function expect_review(bool $valid, string $message): void { if (!$valid) { throw new RuntimeException($message); } }
function expect_rejected(callable $fn, string $message): void {
    try { $fn(); } catch (RuntimeException $e) { return; }
    throw new RuntimeException($message);
}
require dirname(__DIR__) . '/app/job_reviews.php';
$client = ['id' => 15, 'role' => 'client', 'full_name' => 'Aisha Johnson'];
expect_review(job_review_display_name('Aisha Johnson') === 'Aisha J.', 'Only abbreviated names may be public.');
expect_review(job_review_display_name('') === 'Verified customer', 'Empty names must be safe.');
expect_rejected(static fn() => validate_job_review_input(0, 'Excellent experience with the team.', true), 'Score below 1 must fail.');
expect_rejected(static fn() => validate_job_review_input(11, 'Excellent experience with the team.', true), 'Score over 10 must fail.');
expect_rejected(static fn() => validate_job_review_input(8, 'Too short', true), 'Public comments must be substantial.');
expect_rejected(static fn() => validate_job_review_input(8, 'Contact me on hello@example.com for details', true), 'Public emails should be disallowed.');
expect_review(validate_job_review_input(3, '', false)['is_public'] === 0, 'Private score-only feedback should be valid.');
$fakeJob['status'] = 'in_progress';
expect_rejected(static fn() => submit_job_review(4, $client, 9, 'Professional assistance and prompt delivery.', true), 'Unfinished order must not accept ratings.');
$fakeJob['status'] = 'closed';
expect_rejected(static fn() => submit_job_review(4, ['id'=>16, 'role'=>'client'], 9, 'Professional assistance and prompt delivery.', true), 'Other clients cannot rate a job.');
expect_rejected(static fn() => submit_job_review(4, ['id'=>15, 'role'=>'admin'], 9, 'Professional assistance and prompt delivery.', true), 'Staff cannot post a client review.');
submit_job_review(4, $client, 9, 'Professional assistance and prompt delivery.', true);
expect_review($storedReview['job_id'] === 4 && $storedReview['client_id'] === 15, 'Review must belong to the completed order and its owner.');
expect_review($storedReview['rating'] === 9 && $storedReview['is_public'] === 1, 'Score and consent must be saved accurately.');
expect_review($storedReview['display_name'] === 'Aisha J.', 'Public full surname must not be stored.');
expect_rejected(static fn() => submit_job_review(4, $client, 10, 'Everything was completed.', true), 'Duplicate reviews must be blocked.');
list_public_job_reviews(4);
expect_review(str_contains($lastQuery, 'r.is_public = 1'), 'Public carousel must exclude nonconsented feedback.');
expect_review(str_contains($lastQuery, "j.status = 'closed'"), 'Public carousel must exclude unfinished orders.');
expect_review(str_contains($lastQuery, 'INNER JOIN jobs'), 'Only genuine jobs can supply testimonials.');
$jobView = file_get_contents(dirname(__DIR__) . '/views/job.php');
expect_review(str_contains($jobView, 'form_action" value="submit_order_review"'), 'Completed order should display a feedback form.');
expect_review(str_contains($jobView, 'publish_consent'), 'Publication consent must be explicit.');
echo "job_review_workflow_test passed\n";
