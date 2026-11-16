<?php
declare(strict_types=1);

/**
 * Financial transaction overview for admin/finance dashboards.
 * Read-only queries on existing invoices + job_payments; deliberately does
 * not reuse live chat traffic, estimates, or mix NGN and USD in one amount.
 */
function iworks_transaction_dashboard_summary(): array
{
    if (!has_role(['admin', 'finance'])) {
        throw new RuntimeException('Transaction overview is restricted to finance staff.');
    }

    $counts = db_one(
        "SELECT
            (SELECT COUNT(*) FROM invoices WHERE status IN ('issued','paid')) AS orders,
            (SELECT COUNT(*) FROM invoices WHERE status IN ('issued','paid') AND DATE(created_at) = CURDATE()) AS orders_today,
            (SELECT COUNT(*) FROM job_payments WHERE status = 'confirmed') AS confirmed,
            (SELECT COUNT(*) FROM job_payments WHERE status = 'submitted') AS pending"
    ) ?? [];

    $dailyRows = db_all(
        "SELECT DATE(COALESCE(confirmed_at, reviewed_at, created_at)) AS day,
                COUNT(*) AS total
         FROM job_payments
         WHERE status = 'confirmed'
           AND DATE(COALESCE(confirmed_at, reviewed_at, created_at)) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(COALESCE(confirmed_at, reviewed_at, created_at))
         ORDER BY day ASC"
    );
    $daily = [];
    foreach ($dailyRows as $row) {
        $key = (string)($row['day'] ?? '');
        if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $key)) {
            $daily[$key] = max(0, (int)($row['total'] ?? 0));
        }
    }
    $activity = [];
    for ($offset = 6; $offset >= 0; --$offset) {
        $day = date('Y-m-d', strtotime("-{$offset} days"));
        $activity[] = ['day' => $day, 'total' => $daily[$day] ?? 0];
    }

    // p.amount is recorded from the invoice amount, so group by the original
    // invoice currency. Never present a mixed-currency sum as "revenue".
    $receipts = db_all(
        "SELECT COALESCE(NULLIF(i.currency, ''), 'UNKNOWN') AS currency,
                COUNT(*) AS transactions,
                COALESCE(SUM(p.amount), 0) AS total
         FROM job_payments p
         JOIN invoices i ON i.id = p.invoice_id
         WHERE p.status = 'confirmed'
         GROUP BY COALESCE(NULLIF(i.currency, ''), 'UNKNOWN')
         ORDER BY currency ASC"
    );

    $recent = db_all(
        "SELECT p.id, p.status, p.amount, p.created_at,
                i.invoice_number, i.currency,
                pm.display_name AS method_name
         FROM job_payments p
         JOIN invoices i ON i.id = p.invoice_id
         JOIN payment_methods pm ON pm.id = p.payment_method_id
         ORDER BY p.created_at DESC, p.id DESC LIMIT 6"
    );

    return [
        'orders' => max(0, (int)($counts['orders'] ?? 0)),
        'orders_today' => max(0, (int)($counts['orders_today'] ?? 0)),
        'confirmed' => max(0, (int)($counts['confirmed'] ?? 0)),
        'pending' => max(0, (int)($counts['pending'] ?? 0)),
        'activity' => $activity,
        'receipts' => $receipts,
        'recent' => $recent,
    ];
}
