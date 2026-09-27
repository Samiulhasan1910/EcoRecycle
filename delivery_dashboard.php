<?php
require 'delivery_guard.php';

$summaryResult = mysqli_query($conn, "SELECT COUNT(*) AS assigned_total, COALESCE(SUM(status = 'Charge Pending'), 0) AS awaiting_acceptance, COALESCE(SUM(status IN ('Charge Proposed', 'Charge Accepted', 'Payment Confirmed', 'Arrived at Pickup', 'Picked Up')), 0) AS active_total, COALESCE(SUM(status = 'Completed'), 0) AS completed_total FROM deliveries WHERE assigned_delivery_man_id = $deliveryId");
$summary = $summaryResult ? mysqli_fetch_assoc($summaryResult) : [];

$jobs = [];
$jobsResult = mysqli_query($conn, "SELECT d.delivery_id, d.status, COALESCE(i.title, wp.title) AS item_name, COALESCE(i.location, wo.location) AS pickup_location, u.name AS receiver_name FROM deliveries d LEFT JOIN items i ON i.item_id = d.item_id LEFT JOIN transactions t ON d.source_table = 'transactions' AND t.transaction_id = d.source_request_id LEFT JOIN wanted_offers wo ON wo.offer_id = t.request_id LEFT JOIN wanted_posts wp ON wp.wanted_id = wo.wanted_id LEFT JOIN users u ON u.user_id = d.receiver_id WHERE d.assigned_delivery_man_id = $deliveryId AND d.status <> 'Completed' ORDER BY d.updated_at DESC, d.delivery_id DESC LIMIT 5");
if ($jobsResult) while ($job = mysqli_fetch_assoc($jobsResult)) $jobs[] = $job;

$openJobs = [];
$openResult = mysqli_query($conn, "SELECT d.delivery_id, COALESCE(i.title, wp.title) AS item_name, COALESCE(i.location, wo.location) AS pickup_location FROM deliveries d LEFT JOIN items i ON i.item_id = d.item_id LEFT JOIN transactions t ON d.source_table = 'transactions' AND t.transaction_id = d.source_request_id LEFT JOIN wanted_offers wo ON wo.offer_id = t.request_id LEFT JOIN wanted_posts wp ON wp.wanted_id = wo.wanted_id LEFT JOIN delivery_job_responses r ON r.delivery_id = d.delivery_id AND r.delivery_man_id = $deliveryId INNER JOIN delivery_man dm ON dm.delivery_id = $deliveryId WHERE d.status = 'Open' AND r.response_id IS NULL AND dm.verification_status = 'Approved' AND dm.availability = 'Available' AND dm.is_blocked = 0 ORDER BY d.created_at ASC");
if ($openResult) while ($job = mysqli_fetch_assoc($openResult)) $openJobs[] = $job;

function deliveryStatusClass($status) {
    return 'status-' . strtolower(str_replace(' ', '-', trim((string)$status)));
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Delivery Dashboard | EcoCycle</title>
    <link rel="stylesheet" href="style_delivery.css">
</head>
<body>
<?php include 'header_sidebar_delivery.php'; ?>
<main class="main">
    <section class="delivery-dashboard-heading">
        <p class="eyebrow">Delivery workspace</p>
        <h1>Welcome, <?php echo htmlspecialchars($name); ?></h1>
        <p>Review assigned requests, update your availability, and complete pickups from one place.</p>
    </section>
    <section class="delivery-stat-grid">
        <article class="delivery-stat-card assigned-card"><span class="stat-label">Assigned</span><strong><?php echo (int)($summary['assigned_total'] ?? 0); ?></strong></article>
        <article class="delivery-stat-card accepted-card"><span class="stat-label">Need acceptance</span><strong><?php echo (int)($summary['awaiting_acceptance'] ?? 0); ?></strong></article>
        <article class="delivery-stat-card active-card"><span class="stat-label">Active pickups</span><strong><?php echo (int)($summary['active_total'] ?? 0); ?></strong></article>
        <article class="delivery-stat-card completed-card"><span class="stat-label">Completed</span><strong><?php echo (int)($summary['completed_total'] ?? 0); ?></strong></article>
    </section>
    <?php if (isset($_GET['message'])): ?><p class="success-message"><?php echo htmlspecialchars($_GET['message']); ?></p><?php endif; ?>
    <?php if (isset($_GET['error'])): ?><p class="workflow-error"><?php echo htmlspecialchars($_GET['error']); ?></p><?php endif; ?>
    <section class="recent-requests">
        <div class="section-heading"><div><p class="eyebrow">Open requests</p><h2>Available delivery jobs</h2></div></div>
        <div class="request-table-wrap"><table class="request-table"><thead><tr><th>Job</th><th>Item</th><th>Pickup</th><th>Action</th></tr></thead><tbody>
        <?php if ($openJobs): foreach ($openJobs as $job): ?><tr><td>#<?php echo (int)$job['delivery_id']; ?></td><td><?php echo htmlspecialchars($job['item_name'] ?? 'Unavailable'); ?></td><td><?php echo htmlspecialchars($job['pickup_location'] ?? 'N/A'); ?></td><td><form class="inline-action-form" method="post" action="delivery_job_response.php"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>"><input type="hidden" name="delivery_id" value="<?php echo (int)$job['delivery_id']; ?>"><button class="request-accept" name="action" value="accept">Accept</button> <button class="request-reject" name="action" value="reject">Reject</button></form></td></tr><?php endforeach; else: ?><tr><td colspan="4" class="empty-state">No open delivery job is available right now.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
    <section class="recent-requests">
        <div class="section-heading"><div><p class="eyebrow">Your work</p><h2>Current deliveries</h2></div><a class="detail-secondary" href="delivery_jobs.php">View all</a></div>
        <div class="request-table-wrap"><table class="request-table"><thead><tr><th>Job</th><th>Item</th><th>Pickup</th><th>Receiver</th><th>Status</th><th></th></tr></thead><tbody>
        <?php if ($jobs): foreach ($jobs as $job): ?>
            <tr><td>#<?php echo (int)$job['delivery_id']; ?></td><td><?php echo htmlspecialchars($job['item_name'] ?? 'Unavailable'); ?></td><td><?php echo htmlspecialchars($job['pickup_location'] ?? 'N/A'); ?></td><td><?php echo htmlspecialchars($job['receiver_name'] ?? 'N/A'); ?></td><td><span class="request-status <?php echo deliveryStatusClass($job['status']); ?>"><?php echo htmlspecialchars($job['status']); ?></span></td><td><a class="detail-secondary" href="delivery_jobs.php">Open</a></td></tr>
        <?php endforeach; else: ?>
            <tr><td colspan="6" class="empty-state">No active delivery has been assigned to you yet.</td></tr>
        <?php endif; ?>
        </tbody></table></div>
    </section>
</main>
</body>
</html>
