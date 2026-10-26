<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Earnings | EcoCycle</title>
    <link rel="stylesheet" href="assets/css/style_delivery.css">
</head>
<body>
<?php require APP_PATH . '/views/layouts/header_sidebar_delivery.php'; ?>
<main class="main">
    <section class="delivery-dashboard-heading">
        <p class="eyebrow">Performance</p>
        <h1>Delivery Earnings</h1>
        <p>Earnings are added after a delivery is marked Completed.</p>
    </section>

    <section class="delivery-stat-grid">
        <article class="delivery-stat-card assigned-card"><span class="stat-label">All Assigned Jobs</span><strong><?php echo (int)($totals['assigned_total'] ?? 0); ?></strong></article>
        <article class="delivery-stat-card completed-card"><span class="stat-label">Completed Jobs</span><strong><?php echo (int)($totals['completed_total'] ?? 0); ?></strong></article>
        <article class="delivery-stat-card accepted-card"><span class="stat-label">Active Jobs</span><strong><?php echo (int)($totals['active_total'] ?? 0); ?></strong></article>
        <article class="delivery-stat-card active-card"><span class="stat-label">Total Earnings</span><strong><?php echo number_format((float)($totals['total_earning'] ?? 0), 2); ?> BDT</strong></article>
    </section>

    <section class="recent-requests">
        <div class="section-heading"><div><p class="eyebrow">Latest results</p><h2>Recent Completed Deliveries</h2></div></div>
        <div class="request-table-wrap">
            <table class="request-table">
                <thead><tr><th>Delivery</th><th>Item</th><th>Earning</th><th>Completed at</th></tr></thead>
                <tbody>
                <?php if($recent): foreach($recent as $job): ?>
                    <tr>
                        <td>#<?php echo (int)$job['delivery_id']; ?></td>
                        <td><?php echo htmlspecialchars($job['item_name']); ?></td>
                        <td><?php echo number_format((float)($job['accepted_charge'] ?? 0), 2); ?> BDT</td>
                        <td><?php echo htmlspecialchars($job['delivered_at'] ?? '—'); ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" class="empty-state">No completed delivery yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
