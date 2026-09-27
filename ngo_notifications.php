<?php
session_start();

if (!isset($_SESSION["ngo_id"])) {
	header("Location: login.php");
	exit();
}

include("db.php");

$ngo_id = (int)$_SESSION["ngo_id"];

$wantedNotificationColumn = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'wanted_id'");
if ($wantedNotificationColumn && mysqli_num_rows($wantedNotificationColumn) === 0) {
	mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN wanted_id INT NULL");
}

$ngoColumnCheck = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'ngo_id'");

if ($ngoColumnCheck && mysqli_num_rows($ngoColumnCheck) === 0) {
	mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN ngo_id INT NULL AFTER user_id");
}

foreach (["item_id" => "INT NULL", "pickup_id" => "INT NULL"] as $column => $definition) {
	$columnCheck = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE '$column'");
	if ($columnCheck && mysqli_num_rows($columnCheck) === 0) {
		mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN $column $definition");
	}
}

/* Add delivery updates to the same NGO inbox without creating duplicates. */
$pickupNotificationsSql = "
	INSERT INTO notifications (user_id, ngo_id, item_id, message, status, pickup_id)
	SELECT NULL, r.ngo_id, r.item_id,
	       CONCAT('Delivery update for pickup #', p.pickup_id, ': ', p.status),
	       'Unread', p.pickup_id
	FROM pickups p
	INNER JOIN requests r ON r.request_id = p.request_id
	WHERE r.ngo_id = $ngo_id
	AND NOT EXISTS (
		SELECT 1 FROM notifications n
		WHERE n.ngo_id = r.ngo_id AND n.pickup_id = p.pickup_id
	)
";
mysqli_query($conn, $pickupNotificationsSql);

/* Recover user donation posts that were created before NGO notifications were enabled. */
$userDonationNotificationsSql = "
	INSERT INTO notifications (user_id, ngo_id, item_id, notification_type, message, status)
	SELECT NULL, $ngo_id, i.item_id, 'ngo_donation',
	       CONCAT('User donation post available: ', i.title), 'Unread'
	FROM items i
	WHERE i.type = 'Donate'
	AND i.user_id IS NOT NULL
	AND (i.ngo_id = $ngo_id OR i.ngo_id IS NULL)
	AND NOT EXISTS (
		SELECT 1 FROM notifications n
		WHERE n.ngo_id = $ngo_id
		AND n.item_id = i.item_id
		AND n.notification_type = 'ngo_donation'
	)
";
mysqli_query($conn, $userDonationNotificationsSql);

	$sql = "SELECT message, notification_id, status, wanted_id, item_id, pickup_id, request_id
		FROM notifications
		WHERE ngo_id=$ngo_id
		ORDER BY notification_id DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>NGO Notifications</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
	<style>
		* { box-sizing: border-box; }
		body { margin: 0; font-family: Arial, sans-serif; background: #f8faf9; color: #18221e; }
		.sidebar { position: fixed; left: 0; top: 0; width: 263px; height: 100vh; padding: 28px 16px; background: #004d3a; color: white; overflow-y: auto; z-index: 5; }
		.logo-area { text-align: center; margin-bottom: 24px; }
		.logo-icon { width: 70px; height: 70px; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; font-size: 52px; }
		.logo-area h2 { margin: 0 0 12px; font-size: 22px; }
		.logo-area p { margin: 0; font-size: 16px; }
		.menu { list-style: none; margin: 0; padding: 0; }
		.menu li { margin-bottom: 5px; }
		.menu a { display: flex; align-items: center; gap: 10px; padding: 11px 12px; color: white; text-decoration: none; border-radius: 9px; font-size: 13px; }
		.menu a:hover, .menu a.active { background: #159b64; }
		.menu a span { white-space: nowrap; }
		.menu i { width: 25px; text-align: center; font-size: 18px; }
		.main { margin-left: 263px; min-height: 100vh; }
		.topbar { height: 72px; background: white; border-bottom: 1px solid #e5e9e6; display: flex; align-items: center; justify-content: space-between; padding: 0 32px; }
		.topbar h2 { margin: 0; color: #004d3a; font-size: 20px; }
		.topbar-right { display: flex; align-items: center; gap: 18px; }
		.bell { position: relative; color: #164f3d; font-size: 21px; }
		.avatar { width: 38px; height: 38px; border-radius: 50%; background: #006b4f; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
		.container { max-width: 1000px; margin: 0; padding: 34px 38px; }
		.page-heading { margin-bottom: 25px; }
		.page-heading h1 { margin: 0 0 8px; color: #18221e; font-size: 30px; }
		.page-heading p { margin: 0; color: #68736d; }
		a { color: #0f766e; font-weight: bold; text-decoration: none; }
		.notification-list { display: grid; gap: 14px; }
		.box { background: white; border: 1px solid #e1e8e3; border-radius: 10px; padding: 18px 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); color: inherit; font-weight: normal; }
		.box:hover { border-color: #159b64; box-shadow: 0 4px 12px rgba(0,77,58,0.12); }
		.box-icon { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 50%; background: #e2f4e9; color: #087b52; display: flex; align-items: center; justify-content: center; }
		.box > span:nth-child(2) { flex: 1; }
		.view-btn { flex: 0 0 auto; background: #087b52; color: white; padding: 8px 15px; border-radius: 7px; font-size: 13px; font-weight: bold; }
		.empty { background: white; border: 1px solid #e1e8e3; border-radius: 10px; padding: 28px; color: #68736d; }
		@media (max-width: 700px) { .sidebar { position: relative; width: 100%; height: auto; } .main { margin-left: 0; } .container { margin: 0 auto; padding: 25px 20px; } .topbar { padding: 0 20px; } }
	</style>
	<link rel="stylesheet" href="ngo_sidebar.css">
</head>
<body>
	<aside class="sidebar ngo-sidebar">
		<div class="logo-area">
			<div class="logo-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
			<h2>Helping Hands NGO</h2>
			<p>NGO Panel</p>
		</div>
		<ul class="menu">
			<li><a href="ngo_dashboard.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
			<li><a href="ngo_donation_requests.php"><i class="fa-solid fa-gift"></i><span>Donation Requests</span></a></li>
			<li><a href="ngo_accepted_donations.php"><i class="fa-solid fa-circle-check"></i><span>Accepted Donations</span></a></li>
			<li><a href="ngo_browse_wanted_posts.php"><i class="fa-solid fa-magnifying-glass"></i><span>Browse Wanted</span></a></li>
			<li><a href="ngo_create_post.php"><i class="fa-solid fa-pen-to-square"></i><span>Create Post</span></a></li>
			<li><a href="ngo_pickups.php"><i class="fa-solid fa-truck"></i><span>Pickup Status</span></a></li>
			<li><a href="ngo_notifications.php" class="active"><i class="fa-solid fa-bell"></i><span>Notifications</span></a></li>
			<li><a href="ngo_profile.php"><i class="fa-solid fa-user"></i><span>NGO Profile</span></a></li>
			<li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
		</ul>
	</aside>
	<div class="main">
		<header class="topbar">
			<h2>NGO Notifications</h2>
			<div class="topbar-right">
				<div class="bell"><i class="fa-solid fa-bell"></i></div>
				<div class="avatar">NG</div>
			</div>
		</header>
		<main class="container">
			<div class="page-heading">
				<h1>Notifications</h1>
				<p>Stay updated about donations and user requests.</p>
			</div>

			<p><a href="ngo_dashboard.php">&larr; Back to Dashboard</a></p>

			<div class="notification-list">
				<?php if ($result && mysqli_num_rows($result) > 0): ?>
					<?php while ($notification = mysqli_fetch_assoc($result)): ?>
						<a class="box" href="ngo_notification_view.php?id=<?php echo (int)$notification["notification_id"]; ?>">
							<span class="box-icon"><i class="fa-solid fa-bell"></i></span>
							<span><?php echo htmlspecialchars($notification["message"] ?? ""); ?></span>
							<span class="view-btn">View</span>
						</a>
					<?php endwhile; ?>
				<?php else: ?>
					<div class="empty">No notifications yet.</div>
				<?php endif; ?>
			</div>
		</main>
	</div>
</body>
</html>
