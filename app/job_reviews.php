<?php
declare(strict_types=1);

/**
 * Order-linked client feedback. One review per completed order.
 * Public quotes require explicit customer opt-in and never expose
 * customer email, phone, job number, or the full account name.
 */

function ensure_job_review_schema(): void
{
    static $checked = false;
    if ($checked || !is_configured()) {
        return;
    }
    $checked = true;
    if (schema_table_exists('job_reviews')) {
        return;
    }
    if (!iworks_schema_mutation_allowed()) {
        throw new RuntimeException('The site requires a controlled database schema update.');
    }
    db_execute(
        "CREATE TABLE IF NOT EXISTS job_reviews (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            job_id INT UNSIGNED NOT NULL,
            client_id INT UNSIGNED NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            feedback TEXT NULL,
            display_name VARCHAR(100) NOT NULL,
            is_public TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uniq_job_review_job (job_id),
            KEY idx_job_review_public (is_public, created_at),
            CONSTRAINT fk_job_review_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
            CONSTRAINT fk_job_review_client FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function job_review_display_name(string $name): string
{
    $parts = preg_split('/\s+/u', trim($name)) ?: [];
    $parts = array_values(array_filter($parts, static fn($part) => $part !== ''));
    if ($parts === []) {
        return 'Verified customer';
    }
    $first = (string) $parts[0];
    if (count($parts) < 2) {
        return $first;
    }
    preg_match('/^./us', (string) $parts[count($parts) - 1], $initial);
    $firstLetter = $initial[0] ?? '';
    return trim($first . ($firstLetter !== '' ? ' ' . $firstLetter . '.' : ''));
}

function validate_job_review_input(int $rating, string $feedback, bool $public): array
{
    if ($rating < 1 || $rating > 5) {
        throw new RuntimeException('Choose one of the five satisfaction emojis.');
    }
    $feedback = trim(strip_tags($feedback));
    $length = function_exists('mb_strlen') ? mb_strlen($feedback, 'UTF-8') : strlen($feedback);
    if ($length > 1400) {
        throw new RuntimeException('Keep your feedback under 1,400 characters.');
    }
    if ($public && $length < 15) {
        throw new RuntimeException('Please write at least 15 characters to publish a public review.');
    }
    if ($public && (preg_match('/[\w.+-]+@[\w.-]+\.[A-Za-z]{2,}/', $feedback) || preg_match('/https?:\/\/|www\./i', $feedback))) {
        throw new RuntimeException('Remove email addresses and website links before publishing your feedback.');
    }
    // Preserve the historical ten-point database scale without rewriting existing reviews.
    // New 1..5 face selections are stored as 2,4,6,8,10. Public views map back to 1..5.
    return ['rating' => $rating * 2, 'feedback' => $feedback, 'is_public' => $public ? 1 : 0];
}

function job_review_five_point_score(int $storedRating): int
{
    // Existing 1–10 feedback maps fairly to the new five-choice display.
    return max(1, min(5, (int) round($storedRating / 2, 0, PHP_ROUND_HALF_UP)));
}

function job_review_emoji(int $storedRating): string
{
    $options = [1=>'😠', 2=>'🙁', 3=>'😐', 4=>'🙂', 5=>'😍'];
    return $options[job_review_five_point_score($storedRating)];
}

function job_review_label(int $storedRating): string
{
    $options = [1=>'Very unhappy', 2=>'Unhappy', 3=>'Okay', 4=>'Happy', 5=>'Excellent'];
    return $options[job_review_five_point_score($storedRating)];
}

function job_review_for_order(int $jobId): ?array
{
    if ($jobId <= 0 || !schema_table_exists('job_reviews')) {
        return null;
    }
    return db_one('SELECT * FROM job_reviews WHERE job_id = :job_id LIMIT 1', ['job_id' => $jobId]);
}

function submit_job_review(int $jobId, array $client, int $rating, string $feedback, bool $publish): void
{
    $clientId = (int) ($client['id'] ?? 0);
    if ($clientId <= 0 || (string) ($client['role'] ?? '') !== 'client') {
        throw new RuntimeException('Only the customer who placed the order can rate it.');
    }
    $job = get_job($jobId);
    if (!$job || (int) ($job['client_id'] ?? 0) !== $clientId) {
        throw new RuntimeException('This order does not belong to your account.');
    }
    if ((string) ($job['status'] ?? '') !== 'closed') {
        throw new RuntimeException('Reviews are available after an order is completed and closed.');
    }
    if (job_review_for_order($jobId)) {
        throw new RuntimeException('You have already reviewed this order.');
    }
    $review = validate_job_review_input($rating, $feedback, $publish);
    db_execute(
        'INSERT INTO job_reviews (job_id, client_id, rating, feedback, display_name, is_public, created_at)
         VALUES (:job_id, :client_id, :rating, :feedback, :display_name, :is_public, :created_at)',
        [
            'job_id' => $jobId,
            'client_id' => $clientId,
            'rating' => $review['rating'],
            'feedback' => $review['feedback'],
            'display_name' => job_review_display_name((string) ($client['full_name'] ?? '')),
            'is_public' => $review['is_public'],
            'created_at' => now(),
        ]
    );
}

function list_public_job_reviews(int $limit = 4): array
{
    if (!is_configured() || !schema_table_exists('job_reviews')) {
        return [];
    }
    $limit = max(1, min(12, $limit));
    return db_all(
        "SELECT r.rating, r.feedback, r.display_name, r.created_at, s.name AS service_name
         FROM job_reviews r
         INNER JOIN jobs j ON j.id = r.job_id AND j.client_id = r.client_id
         INNER JOIN services s ON s.id = j.service_id
         WHERE r.is_public = 1 AND j.status = 'closed'
           AND LENGTH(TRIM(COALESCE(r.feedback, ''))) >= 15
         ORDER BY r.created_at DESC, r.id DESC
         LIMIT " . $limit
    );
}
