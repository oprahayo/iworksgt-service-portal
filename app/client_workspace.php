<?php
declare(strict_types=1);

/**
 * A customer sees one work item per service request, whether it originated
 * as a trademark wizard draft or as an existing job/order.
 * Operational jobs and invoices remain unchanged and accessible to staff.
 */
function iworks_client_state(string $payment, string $workflow, bool $draft = false): string
{
    if ($draft) { return 'draft'; }
    if (in_array(strtolower($workflow), ['closed', 'delivered', 'cancelled'], true)) {
        return in_array(strtolower($workflow), ['cancelled'], true) ? 'cancelled' : 'completed';
    }
    if (!in_array(strtolower($payment), ['paid', 'refunded'], true)) {
        return 'payment';
    }
    return 'progress';
}

function iworks_client_state_label(string $state): string
{
    return [
        'draft' => 'Draft',
        'payment' => 'Awaiting payment',
        'progress' => 'In progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ][$state] ?? 'In progress';
}

function iworks_client_service_items(array $trademarkApplications, array $orders): array
{
    $items = [];
    $linkedJobs = [];
    foreach ($trademarkApplications as $app) {
        if (!is_array($app)) { continue; }
        $id = (int) ($app['id'] ?? 0);
        if ($id <= 0) { continue; }
        $jobId = (int) ($app['job_id'] ?? 0);
        if ($jobId > 0) { $linkedJobs[$jobId] = true; }
        $draft = (string) ($app['status'] ?? 'draft') === 'draft';
        $payment = (string) ($app['payment_status'] ?? 'unpaid');
        $status = iworks_client_state($payment, (string) ($app['job_status'] ?? 'pending'), $draft);
        $name = trim((string) ($app['data']['mark_name'] ?? ''));
        $items[] = [
            'key' => 'trademark:' . $id, 'kind' => 'trademark', 'id' => $id,
            'name' => $name !== '' ? $name : 'Trademark application',
            'service' => 'Trademark registration · Nigeria',
            'reference' => 'TM-' . $id,
            'status' => $status,
            'updated' => (string) ($app['updated_at'] ?? $app['created_at'] ?? ''),
            'detail_url' => $draft || $status === 'payment'
                ? url('trademark', ['id' => $id] + ($draft ? [] : ['step' => 'payment']))
                : url('job', ['id' => $jobId]),
            'action' => $draft ? 'Continue application' : ($status === 'payment' ? 'Continue payment' : 'View progress'),
            'can_delete_draft' => $draft,
            'country' => 'Nigeria',
        ];
    }
    foreach ($orders as $order) {
        if (!is_array($order)) { continue; }
        $id = (int) ($order['id'] ?? 0);
        if ($id <= 0 || isset($linkedJobs[$id])) { continue; }
        $name = trim((string) ($order['title'] ?? $order['service_name'] ?? ''));
        $status = iworks_client_state(
            (string) ($order['payment_status_effective'] ?? $order['payment_status'] ?? 'unpaid'),
            (string) ($order['status'] ?? 'pending')
        );
        $items[] = [
            'key' => 'job:' . $id, 'kind' => 'job', 'id' => $id,
            'name' => $name !== '' ? $name : 'Service request',
            'service' => (string) ($order['service_name'] ?? 'Service order'),
            'reference' => (string) ($order['job_code'] ?? ''),
            'status' => $status,
            'updated' => (string) ($order['updated_at'] ?? $order['created_at'] ?? ''),
            'detail_url' => url('job', ['id' => $id]),
            'action' => $status === 'payment' ? 'Review & pay' : 'View progress',
            'can_delete_draft' => false,
            'country' => '',
        ];
    }
    usort($items, static function (array $a, array $b): int {
        return strcmp((string) $b['updated'], (string) $a['updated'])
            ?: strcmp((string) $a['key'], (string) $b['key']);
    });
    return $items;
}

/** These exclusions match the public services page: no banking product. */
function iworks_client_catalogue(array $services): array
{
    $result = [];
    foreach ($services as $service) {
        if (!is_array($service)) { continue; }
        $name = strtolower(trim((string) ($service['name'] ?? '')));
        $slug = trim((string) ($service['slug'] ?? ''));
        if ($slug === '' || $name === '') { continue; }
        if ($name === 'custom service' && (float) ($service['price'] ?? 0) <= 0) { continue; }
        $excluded = false;
        foreach (['banking','bank account','loan','payment gateway','wallet'] as $term) {
            if (str_contains($name, $term)) { $excluded = true; break; }
        }
        if ($excluded) { continue; }
        $result[] = $service;
    }
    $priority = ['business-name-registration-cac-nigeria','company-registration','trademark','website-design-development'];
    usort($result, static function (array $a, array $b) use ($priority): int {
        $rank = static fn(array $s) => ($index = array_search((string) ($s['slug'] ?? ''), $priority, true)) === false ? 99 : $index;
        return ($rank($a) <=> $rank($b)) ?: strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
    });
    return $result;
}

function iworks_client_service_icon(array $service): string
{
    $name = strtolower((string) ($service['name'] ?? '') . ' ' . (string) ($service['category'] ?? ''));
    foreach ([
        'trademark'=>'bi-patch-check','business name'=>'bi-file-earmark-check',
        'company'=>'bi-buildings','trustee'=>'bi-people','llc'=>'bi-globe',
        'website'=>'bi-window-stack','hosting'=>'bi-hdd-network',
        'domain'=>'bi-globe','email'=>'bi-envelope-at','design'=>'bi-palette',
        'marketing'=>'bi-graph-up-arrow','seo'=>'bi-graph-up-arrow',
        'training'=>'bi-mortarboard','vehicle'=>'bi-truck',
        'tracking'=>'bi-geo-alt','registration'=>'bi-building',
    ] as $term => $icon) {
        if (str_contains($name, $term)) { return $icon; }
    }
    return 'bi-briefcase';
}

/** A pre-existing trademark wizard owns its data; the older generic intake form
 * is redundant only for a job explicitly linked to that completed wizard. */
function iworks_client_trademark_for_job(int $jobId, int $clientId): ?array
{
    if ($jobId < 1 || $clientId < 1 || !schema_table_exists('trademark_applications')) { return null; }
    $row = db_one(
        'SELECT id, status FROM trademark_applications WHERE job_id = :job AND client_id = :client LIMIT 1',
        ['job'=>$jobId, 'client'=>$clientId]
    );
    return is_array($row) ? $row : null;
}
