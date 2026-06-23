<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

require_login();
require_roles(['admin', 'finance']);

$type = trim($_GET['type'] ?? '');

$exports = [
    'agents' => [
        'filename' => 'agents.csv',
        'query' => "SELECT id, full_name, email, phone, bank_name, bank_account_name, bank_account_number, bank_swift, bank_currency, status, created_at FROM users WHERE role = 'agent' ORDER BY created_at DESC",
    ],
    'jobs' => [
        'filename' => 'jobs.csv',
        'query' => 'SELECT j.id, j.job_code, j.title, j.status, j.payment_status, j.amount, j.currency, j.commission_percent, j.commission_amount, j.created_at, c.full_name AS client_name, a.full_name AS agent_name, s.name AS service_name
                    FROM jobs j
                    JOIN users c ON c.id = j.client_id
                    LEFT JOIN users a ON a.id = j.agent_id
                    JOIN services s ON s.id = j.service_id
                    ORDER BY j.created_at DESC',
    ],
    'invoices' => [
        'filename' => 'invoices.csv',
        'query' => 'SELECT i.id, i.invoice_number, i.amount, i.currency, i.status, i.issued_at, i.due_at, c.full_name AS client_name, j.job_code
                    FROM invoices i
                    JOIN users c ON c.id = i.client_id
                    JOIN jobs j ON j.id = i.job_id
                    ORDER BY i.created_at DESC',
    ],
    'payments' => [
        'filename' => 'payments.csv',
        'query' => 'SELECT p.id, p.reference, p.amount, p.status, p.created_at, p.confirmed_at, pm.display_name AS payment_method, c.full_name AS client_name, i.invoice_number, j.job_code
                    FROM job_payments p
                    JOIN payment_methods pm ON pm.id = p.payment_method_id
                    JOIN users c ON c.id = p.client_id
                    JOIN invoices i ON i.id = p.invoice_id
                    JOIN jobs j ON j.id = p.job_id
                    ORDER BY p.created_at DESC',
    ],
    'payouts' => [
        'filename' => 'commission_payouts.csv',
        'query' => 'SELECT p.id, p.commission_amount, p.status, p.created_at, p.approved_at, p.paid_at, u.full_name AS agent_name, j.job_code
                    FROM commission_payouts p
                    JOIN users u ON u.id = p.agent_id
                    JOIN jobs j ON j.id = p.job_id
                    ORDER BY p.created_at DESC',
    ],
    'withdrawals' => [
        'filename' => 'withdrawals.csv',
        'query' => 'SELECT w.id, w.amount, w.currency, w.status, w.requested_at, w.reviewed_at, w.paid_at, w.note, w.admin_note, u.full_name AS agent_name
                    FROM withdrawal_requests w
                    JOIN users u ON u.id = w.agent_id
                    ORDER BY w.requested_at DESC',
    ],
];

if (!isset($exports[$type])) {
    http_response_code(400);
    exit('Invalid export type.');
}

$config = $exports[$type];
$rows = db_all($config['query']);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $config['filename'] . '"');

$output = fopen('php://output', 'wb');

if (!$output) {
    http_response_code(500);
    exit('Could not open output stream.');
}

if (empty($rows)) {
    fputcsv($output, ['No data']);
    fclose($output);
    exit;
}

fputcsv($output, array_keys($rows[0]));

foreach ($rows as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
