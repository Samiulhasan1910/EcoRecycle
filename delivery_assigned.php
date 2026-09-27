<?php
require 'delivery_guard.php';
$jobs = [];
$result = mysqli_query($conn, "SELECT d.delivery_id, d.status, i.title AS item_name, i.location AS pickup_location, u.name AS donor_name FROM deliveries d LEFT JOIN items i ON i.item_id = d.item_id LEFT JOIN users u ON u.user_id = d.receiver_id WHERE d.assigned_delivery_man_id = $deliveryId ORDER BY d.updated_at DESC, d.delivery_id DESC");
if ($result) {
	while ($row = mysqli_fetch_assoc($result)) {
		$jobs[] = $row;
	}
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Assigned Deliveries</title>
	<link rel="stylesheet" href="style_delivery.css">
</head>
<body>
<?php include 'header_sidebar_delivery.php'; ?>
<main class="main assigned-page">
	<section class="delivery-dashboard-heading">
		<p class="eyebrow">Your workload</p>
		<h1>Assigned Deliveries</h1>
		<p>Accept and update pickups assigned by an NGO.</p>
	</section>
	<section class="assigned-request-list">
		<?php if ($jobs): ?>
			<?php foreach ($jobs as $job): ?>
				<article class="assigned-request-card">
					<div class="assigned-request-main">
						<p class="eyebrow">Delivery #<?php echo (int)$job['delivery_id']; ?></p>
						<h2><?php echo htmlspecialchars($job['item_name'] ?? 'Donation'); ?></h2>
						<div class="assigned-request-meta">
							<span>Pickup: <?php echo htmlspecialchars($job['pickup_location'] ?? 'Not provided'); ?></span>
							<span>Donor: <?php echo htmlspecialchars($job['donor_name'] ?? 'User'); ?></span>
						</div>
					</div>
					<div class="assigned-request-actions">
						<span class="request-status"><?php echo htmlspecialchars($job['status']); ?></span>
						<?php if ($job['status'] === 'Assigned'): ?>
							<form method="post" action="delivery_job_response.php">
								<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
								<input type="hidden" name="delivery_id" value="<?php echo (int)$job['delivery_id']; ?>">
								<button class="request-accept" type="submit" name="action" value="accept">Accept Delivery</button>
							</form>
						<?php elseif ($job['status'] === 'Charge Accepted' || $job['status'] === 'Accepted'): ?>
							<form method="post" action="delivery_progress_action.php">
								<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
								<input type="hidden" name="delivery_id" value="<?php echo (int)$job['delivery_id']; ?>">
								<button class="request-accept" type="submit" name="action" value="arrived">Arrived at Pickup</button>
							</form>
						<?php elseif ($job['status'] === 'Arrived at Pickup'): ?>
							<form method="post" action="delivery_progress_action.php">
								<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
								<input type="hidden" name="delivery_id" value="<?php echo (int)$job['delivery_id']; ?>">
								<button class="request-accept" type="submit" name="action" value="picked_up">Picked Up</button>
							</form>
						<?php elseif ($job['status'] === 'Picked Up'): ?>
							<form method="post" action="delivery_progress_action.php">
								<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
								<input type="hidden" name="delivery_id" value="<?php echo (int)$job['delivery_id']; ?>">
								<button class="request-accept" type="submit" name="action" value="complete">Complete Delivery</button>
							</form>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		<?php else: ?>
			<p class="empty-state">No assigned deliveries yet.</p>
		<?php endif; ?>
	</section>
</main>
</body>
</html>
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Assigned Deliveries</title><link rel="stylesheet" href="style_delivery.css"><style>.job{background:#fff;border:1px solid #dce8e0;border-radius:12px;padding:20px;margin:14px 0}.job h2{margin:0 0 8px}.job p{color:#68746d}.accept{background:#087b52;color:#fff;border:0;border-radius:7px;padding:10px 16px;cursor:pointer;font-weight:700}.status{display:inline-block;margin:8px 0;padding:6px 10px;border-radius:15px;background:#e9f6ed;color:#126b37;font-weight:700;font-size:13px}</style></head><body><?php include 'header_sidebar_delivery.php'; ?><main class="main"><section class="delivery-dashboard-heading"><p class="eyebrow">Your workload</p><h1>Assigned Deliveries</h1><p>Accept a pickup assigned by an NGO.</p></section><?php if ($jobs): ?><?php foreach ($jobs as $job): ?><article class="job"><h2><?php echo htmlspecialchars($job['item_name'] ?? 'Donation'); ?></h2><p>Pickup: <?php echo htmlspecialchars($job['pickup_location'] ?? 'Not provided'); ?><br>Donor: <?php echo htmlspecialchars($job['donor_name'] ?? 'User'); ?></p><span class="status"><?php echo htmlspecialchars($job['status']); ?></span><?php if ($job['status'] === 'Assigned'): ?><form method="post" action="delivery_job_response.php"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>"><input type="hidden" name="delivery_id" value="<?php echo (int)$job['delivery_id']; ?>"><button class="accept" type="submit" name="action" value="accept">Accept Delivery</button></form><?php endif; ?></article><?php endforeach; ?><?php else: ?><p>No assigned deliveries yet.</p><?php endif; ?></main></body></html>
