<?php
require 'delivery_guard.php';

$totalsResult = mysqli_query($conn, "
    SELECT
        COUNT(*) AS assigned_total,
        COALESCE(SUM(status = 'Completed'), 0) AS completed_total,
        COALESCE(SUM(status IN ('Charge Pending','Charge Proposed','Charge Accepted','Arrived at Pickup','Picked Up')), 0) AS active_total,
        COALESCE(SUM(CASE WHEN status = 'Completed' THEN accepted_charge ELSE 0 END), 0) AS total_earning
    FROM deliveries
    WHERE assigned_delivery_man_id = $deliveryId
");
$totals = $totalsResult ? (mysqli_fetch_assoc($totalsResult) ?: []) : [];

$recent = mysqli_query($conn, "
    SELECT
        d.delivery_id,
        d.accepted_charge,
        d.delivered_at,
        COALESCE(i.title, wp.title, 'Item') AS item_name
    FROM deliveries d
    LEFT JOIN items i ON i.item_id = d.item_id
    LEFT JOIN transactions t
        ON d.source_table = 'transactions'
       AND t.transaction_id = d.source_request_id
    LEFT JOIN wanted_offers wo ON wo.offer_id = t.request_id
    LEFT JOIN wanted_posts wp ON wp.wanted_id = wo.wanted_id
    WHERE d.assigned_delivery_man_id = $deliveryId
      AND d.status = 'Completed'
    ORDER BY d.delivered_at DESC, d.delivery_id DESC
    LIMIT 10
");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Earnings | EcoCycle</title>
    <link rel="stylesheet" href="style_delivery.css">
</head>
<body>
<?php include 'header_sidebar_delivery.php'; ?>
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
                <?php if($recent && mysqli_num_rows($recent)): while($job=mysqli_fetch_assoc($recent)): ?>
                    <tr>
                        <td>#<?php echo (int)$job['delivery_id']; ?></td>
                        <td><?php echo htmlspecialchars($job['item_name']); ?></td>
                        <td><?php echo number_format((float)($job['accepted_charge'] ?? 0), 2); ?> BDT</td>
                        <td><?php echo htmlspecialchars($job['delivered_at'] ?? '—'); ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="4" class="empty-state">No completed delivery yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
