<?php
/** @var array<string,array{ready:bool,exists:bool,missing:array}> $schemaReport */
$readyCount = count(array_filter($schemaReport, static fn(array $state): bool => $state['ready']));
$totalCount = count($schemaReport);
?>
<div class="iw-ops-page-head">
  <div><span class="iw-ops-eyebrow">PLATFORM MAINTENANCE</span><h1>System health</h1><p>Read-only check of essential application tables and columns. No customer records are modified here.</p></div>
  <a class="btn btn-outline-secondary" href="<?= e(url('dashboard')) ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Dashboard</a>
</div>
<div class="row g-3 mb-4">
  <div class="col-sm-6"><div class="card iw-ops-status"><div class="card-body"><span>Schema checks passing</span><strong><?= (int) $readyCount ?> / <?= (int) $totalCount ?></strong><small>Checked against this release's module requirements</small></div></div></div>
  <div class="col-sm-6"><div class="card iw-ops-status"><div class="card-body"><span>Requires review</span><strong><?= (int) ($totalCount - $readyCount) ?></strong><small>Missing tables or required fields</small></div></div></div>
</div>
<?php if ($readyCount === $totalCount): ?>
  <div class="alert alert-success"><i class="bi bi-check-circle" aria-hidden="true"></i> All monitored tables and columns are present. This does not verify external APIs, passwords, mail delivery or payment callbacks.</div>
<?php else: ?>
  <div class="alert alert-warning"><strong>Action required:</strong> Your database does not match all expected tables/columns. Back up the database first, then use the CLI audit below. A missing field in a core table needs a separate reviewed migration.</div>
<?php endif; ?>
<div class="card iw-ops-health-table">
  <div class="card-header bg-white"><h2 class="h6 mb-0">Database readiness</h2></div>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th scope="col">Module / table</th><th scope="col">Status</th><th scope="col">Missing fields</th></tr></thead>
      <tbody>
        <?php foreach ($schemaReport as $schemaTable => $state): ?>
        <tr>
          <th scope="row"><code><?= e($schemaTable) ?></code></th>
          <td>
            <?php if ($state['ready']): ?><span class="badge text-bg-success">Ready</span>
            <?php elseif (!$state['exists']): ?><span class="badge text-bg-danger">Table missing</span>
            <?php else: ?><span class="badge text-bg-warning">Fields missing</span><?php endif; ?>
          </td>
          <td class="small text-muted"><?= e($state['ready'] ? '—' : implode(', ', $state['missing'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<div class="card mt-3">
  <div class="card-body">
    <h2 class="h6">Safe content module repair</h2>
    <p class="text-muted small">Use cPanel Terminal or SSH from the deployed application directory. The default command only checks. The repair command adds missing Insights/editorial tables without replacing existing content.</p>
    <pre class="iw-ops-command"><code>php scripts/repair_public_content_schema.php --check</code></pre>
    <pre class="iw-ops-command"><code>php scripts/repair_public_content_schema.php --apply</code></pre>
    <p class="text-muted small mb-0">If other core tables or columns are missing, stop and review a full schema upgrade after a database backup. This page cannot safely infer how to migrate your existing customer data.</p>
  </div>
</div>
