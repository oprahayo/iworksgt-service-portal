<?php
declare(strict_types=1);

/** Optional multi-service associations for existing support tickets. */
function support_links_ensure_schema(): void
{
    if (schema_table_exists('support_ticket_jobs')) { return; }
    db_execute("CREATE TABLE IF NOT EXISTS support_ticket_jobs (
        ticket_id INT UNSIGNED NOT NULL,
        job_id INT UNSIGNED NOT NULL,
        client_id INT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (ticket_id,job_id),
        INDEX idx_support_job (job_id),
        INDEX idx_support_owner (client_id,ticket_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function support_client_job_ids(int $clientId,mixed $raw): array
{
    if (!is_array($raw)) { return []; }
    $ids=array_values(array_unique(array_filter(array_map('intval',$raw),static fn(int $id): bool => $id>0)));
    if (count($ids)>20) { throw new RuntimeException('Choose up to 20 related services per ticket.'); }
    foreach ($ids as $id) {
        $job=get_job($id);
        if (!$job || (int) ($job['client_id'] ?? 0)!==$clientId) {
            throw new RuntimeException('One of the selected services is not in your account.');
        }
    }
    return $ids;
}
function support_link_ticket_jobs(int $ticketId,int $clientId,array $jobIds): void
{
    if (!$jobIds) { return; }
    support_links_ensure_schema();
    $ticket=get_support_ticket($ticketId);
    if (!$ticket || (int) ($ticket['client_id'] ?? 0)!==$clientId) {
        throw new RuntimeException('Ticket ownership could not be confirmed.');
    }
    foreach ($jobIds as $jobId) {
        db_execute('INSERT IGNORE INTO support_ticket_jobs (ticket_id,job_id,client_id,created_at)
            VALUES (:ticket,:job,:client,:created)',
            ['ticket'=>$ticketId,'job'=>$jobId,'client'=>$clientId,'created'=>now()]);
    }
}
function support_linked_jobs(int $ticketId,array $viewer): array
{
    if ($ticketId<=0 || !schema_table_exists('support_ticket_jobs')) { return []; }
    $ticket=get_support_ticket($ticketId);
    if (!$ticket || !can_access_support_ticket($viewer,$ticket)) { return []; }
    return db_all('SELECT j.id,j.job_code,j.title,j.status,s.name AS service_name
        FROM support_ticket_jobs link
        JOIN jobs j ON j.id=link.job_id AND j.client_id=link.client_id
        JOIN services s ON s.id=j.service_id
        WHERE link.ticket_id=:ticket AND link.client_id=:client ORDER BY j.created_at DESC',
        ['ticket'=>$ticketId,'client'=>(int) $ticket['client_id']]);
}
