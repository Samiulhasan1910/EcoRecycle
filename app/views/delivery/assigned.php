<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Assigned Deliveries</title>
	<link rel="stylesheet" href="assets/css/style_delivery.css">
</head>
<body>
<?php require APP_PATH . '/views/layouts/header_sidebar_delivery.php'; ?>
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
							<form method="post" action="index.php?url=delivery/job-response">
								<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
								<input type="hidden" name="delivery_id" value="<?php echo (int)$job['delivery_id']; ?>">
								<button class="request-accept" type="submit" name="action" value="accept">Accept Delivery</button>
							</form>
						<?php elseif ($job['status'] === 'Charge Accepted' || $job['status'] === 'Accepted'): ?>
							<form method="post" action="index.php?url=delivery/progress">
								<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
								<input type="hidden" name="delivery_id" value="<?php echo (int)$job['delivery_id']; ?>">
								<button class="request-accept" type="submit" name="action" value="arrived">Arrived at Pickup</button>
							</form>
						<?php elseif ($job['status'] === 'Arrived at Pickup'): ?>
							<form method="post" action="index.php?url=delivery/progress">
								<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
								<input type="hidden" name="delivery_id" value="<?php echo (int)$job['delivery_id']; ?>">
								<button class="request-accept" type="submit" name="action" value="picked_up">Picked Up</button>
							</form>
						<?php elseif ($job['status'] === 'Picked Up'): ?>
							<form method="post" action="index.php?url=delivery/progress">
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
