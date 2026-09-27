<?php
session_start();

if (!isset($_SESSION["ngo_id"])) {
	header("Location: login.php");
	exit();
}

include("db.php");

$ngo_id = (int)$_SESSION["ngo_id"];
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
	if (($_POST["profile_action"] ?? "update") === "delete") {
		mysqli_begin_transaction($conn);
		$deleteOk = true;

		$deleteRequests = mysqli_query($conn, "SELECT request_id FROM requests WHERE ngo_id=$ngo_id");
		if ($deleteRequests) {
			while ($request = mysqli_fetch_assoc($deleteRequests)) {
				$requestId = (int)$request["request_id"];
				if (!mysqli_query($conn, "DELETE FROM pickups WHERE request_id=$requestId")) {
					$deleteOk = false;
				}
			}
		} else {
			$deleteOk = false;
		}

		$deleteOk = $deleteOk && mysqli_query($conn, "DELETE FROM requests WHERE ngo_id=$ngo_id");
		$deleteOk = $deleteOk && mysqli_query($conn, "DELETE FROM notifications WHERE ngo_id=$ngo_id");
		$deleteOk = $deleteOk && mysqli_query($conn, "UPDATE items SET ngo_id=NULL WHERE ngo_id=$ngo_id");
		$deleteOk = $deleteOk && mysqli_query($conn, "UPDATE wanted_posts SET ngo_id=NULL WHERE ngo_id=$ngo_id");
		$deleteOk = $deleteOk && mysqli_query($conn, "DELETE FROM ngos WHERE ngo_id=$ngo_id");

		if ($deleteOk) {
			mysqli_commit($conn);
			session_unset();
			session_destroy();
			header("Location: login.php?profile_deleted=1");
			exit();
		}

		mysqli_rollback($conn);
		$message = "Profile could not be deleted. Please try again.";
		$message_type = "error";
	} else {
	$name = trim($_POST["name"] ?? "");
	$email = trim($_POST["email"] ?? "");
	$phone = trim($_POST["phone"] ?? "");
	$location = trim($_POST["location"] ?? "");
	$organization_type = trim($_POST["organization_type"] ?? "");
	$established_year = (int)($_POST["established_year"] ?? 0);
	$document_sql = "";

	if ($name === "" || $email === "" || $phone === "" || $location === "") {
		$message = "Please fill in all required fields.";
		$message_type = "error";
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$message = "Please enter a valid email address.";
		$message_type = "error";
	} else {
		$name_safe = mysqli_real_escape_string($conn, $name);
		$email_safe = mysqli_real_escape_string($conn, $email);
		$phone_safe = mysqli_real_escape_string($conn, $phone);
		$location_safe = mysqli_real_escape_string($conn, $location);
		$type_safe = mysqli_real_escape_string($conn, $organization_type);

		if (isset($_FILES["verification_document"]) && $_FILES["verification_document"]["error"] !== UPLOAD_ERR_NO_FILE) {
			$file = $_FILES["verification_document"];
			$file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
			$allowed_extensions = ["pdf", "jpg", "jpeg", "png"];

			if ($file["error"] !== UPLOAD_ERR_OK || !in_array($file_extension, $allowed_extensions, true)) {
				$message = "Only PDF, JPG, JPEG and PNG files are allowed.";
				$message_type = "error";
			} else {
				$upload_folder = __DIR__ . "/uploads/ngo_documents/";
				$database_folder = "uploads/ngo_documents/";

				if (!is_dir($upload_folder)) {
					mkdir($upload_folder, 0777, true);
				}

				$file_name = "ngo_" . time() . "_" . uniqid() . "." . $file_extension;
				$file_path = $upload_folder . $file_name;
				$database_path = $database_folder . $file_name;

				if (!move_uploaded_file($file["tmp_name"], $file_path)) {
					$message = "Document upload failed.";
					$message_type = "error";
				} else {
					$database_path_safe = mysqli_real_escape_string($conn, $database_path);
					$document_sql = ", verification_document='$database_path_safe'";
				}
			}
		}

		$sql = "UPDATE ngos SET
				name='$name_safe', email='$email_safe', phone='$phone_safe',
				location='$location_safe', organization_type='$type_safe',
				established_year=$established_year$document_sql
				WHERE ngo_id=$ngo_id";

		if ($message === "") {
			if (mysqli_query($conn, $sql)) {
			$_SESSION["name"] = $name;
			$_SESSION["email"] = $email;
			$message = "Profile updated successfully.";
			$message_type = "success";
			} else {
				$message = "Database error: " . mysqli_error($conn);
				$message_type = "error";
			}
		}
	}
	}
}

$profileResult = mysqli_query($conn, "SELECT name, email, phone, location,
	organization_type, established_year
	FROM ngos WHERE ngo_id=$ngo_id LIMIT 1");
$profile = $profileResult ? mysqli_fetch_assoc($profileResult) : null;

if (!$profile) {
	die("NGO profile not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>NGO Profile</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
	<style>
		* { box-sizing: border-box; }
		body { margin: 0; font-family: Arial, sans-serif; background: #f5f7f6; color: #222; }
		.sidebar { position: fixed; left: 0; top: 0; width: 263px; height: 100vh; padding: 28px 16px; background: #004d3a; color: white; overflow-y: auto; }
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
		.menu-notification { margin-left: auto; width: 27px; height: 27px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: #ff3d3d; color: white; font-size: 13px; font-weight: bold; }
		.main { margin-left: 263px; min-height: 100vh; }
		.topbar { height: 72px; background: white; border-bottom: 1px solid #e5e9e6; display: flex; align-items: center; justify-content: space-between; padding: 0 32px; }
		.topbar-title { color: #004d3a; font-size: 20px; font-weight: bold; }
		.topbar-right { display: flex; align-items: center; gap: 18px; }
		.topbar-bell { color: #164f3d; font-size: 20px; position: relative; }
		.topbar-count { position: absolute; top: -8px; right: -8px; width: 17px; height: 17px; border-radius: 50%; background: #ef4444; color: white; font-size: 10px; display: flex; align-items: center; justify-content: center; }
		.topbar-avatar { width: 38px; height: 38px; border-radius: 50%; background: #006b4f; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; }
		.container { max-width: 1000px; margin: 0; padding: 34px 38px; }
		.back { color: #0f766e; text-decoration: none; font-weight: bold; }
		.page-heading { margin: 0 0 25px; }
		.page-heading h1 { color: #18221e; margin: 0 0 8px; font-size: 30px; }
		.page-heading p { margin: 0; color: #68736d; }
		.profile-hero { background: linear-gradient(110deg, #eef8f1, #f7fbf8); border: 1px solid #e0ebe3; border-radius: 10px 10px 0 0; padding: 25px 28px; display: flex; align-items: center; gap: 20px; }
		.org-avatar { width: 86px; height: 86px; border-radius: 50%; background: white; border: 1px solid #d8e6dc; display: flex; align-items: center; justify-content: center; color: #087b52; font-size: 43px; }
		.profile-hero h2 { margin: 0 0 8px; }
		.profile-hero p { margin: 4px 0; color: #087b52; }
		.hero-actions { margin-left: auto; display: flex; align-items: center; gap: 10px; }
		.edit-toggle { min-height: 42px; padding: 11px 16px; border: 0; border-radius: 6px; background: #006b4f; color: white; font: inherit; font-weight: bold; cursor: pointer; white-space: nowrap; }
		.bottom-back { display: inline-block; margin-top: 20px; padding: 11px 16px; border-radius: 6px; background: #006b4f; color: white; font-weight: bold; }
		.bottom-back:hover { background: #087b52; }
		.info-section { background: white; border: 1px solid #e0e6e2; border-top: 0; padding: 25px 28px; }
		.info-section h2 { margin: 0 0 18px; font-size: 20px; }
		.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
		.info-card { border: 1px solid #e1e8e3; border-radius: 8px; padding: 15px; }
		.info-card strong { display: block; font-size: 12px; color: #68736d; margin-bottom: 6px; }
		.info-card span { font-size: 15px; }
		.edit-section { margin-top: 20px; }
		.edit-section h2 { color: #004d3a; font-size: 20px; }
		.profile-form-box { background: white; border: 1px solid #ddd; padding: 25px; }
		.profile-form-box form { padding: 0; border: 0; }
		.profile-action-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 20px; }
		.profile-action-row button { margin-top: 0; }
		.edit-section.hidden { display: none; }
		.delete-profile-form { display: flex; justify-content: flex-end; margin: 0; border: 0; background: transparent; }
		.delete-profile-button { background: #c62828; }
		.delete-profile-button:hover { background: #a91f1f; }
		label { display: block; margin: 14px 0 6px; font-weight: bold; }
		input { width: 100%; padding: 10px; border: 1px solid #bbb; font: inherit; }
		button { margin-top: 20px; padding: 11px 18px; border: 0; background: #198754; color: white; cursor: pointer; }
		.message { padding: 10px; margin-bottom: 15px; }
		.success { background: #d1e7dd; color: #0f5132; }
		.error { background: #f8d7da; color: #842029; }
		@media (max-width: 700px) { .sidebar { position: relative; width: 100%; height: auto; } .main { margin-left: 0; } .container { margin: 0 auto; padding: 25px 20px; } .info-grid { grid-template-columns: 1fr; } .profile-hero { align-items: flex-start; flex-direction: column; } .topbar { padding: 0 20px; } }
	</style>
	<link rel="stylesheet" href="ngo_sidebar.css">
</head>
<body>
	<aside class="sidebar ngo-sidebar">
		<div class="logo-area">
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
			<li><a href="ngo_notifications.php"><i class="fa-solid fa-bell"></i><span>Notifications</span><span class="menu-notification">4</span></a></li>
			<li><a href="ngo_profile.php" class="active"><i class="fa-solid fa-user"></i><span>NGO Profile</span></a></li>
			<li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
		</ul>
	</aside>
	<div class="main">
		<header class="topbar">
			<div class="topbar-title">NGO Profile</div>
			<div class="topbar-right">
				<div class="topbar-bell"><i class="fa-solid fa-bell"></i><span class="topbar-count">4</span></div>
				<div class="topbar-avatar">NG</div>
			</div>
		</header>
		<main class="container">
			<div class="page-heading">
				<h1>NGO Profile</h1>
				<p>View and manage your organization information.</p>
			</div>

			<section class="profile-hero">
				<div class="org-avatar"><i class="fa-solid fa-hand-holding-heart"></i></div>
				<div>
					<h2><?php echo htmlspecialchars($profile["name"]); ?></h2>
					<p><i class="fa-solid fa-circle-check"></i> NGO Account</p>
				</div>
				<div class="hero-actions">
					<button class="edit-toggle" type="button" onclick="toggleEditForm()">
						<i class="fa-solid fa-pen-to-square"></i> Edit Profile
					</button>
				</div>
			</section>

			<section class="info-section">
				<h2>Organization Information</h2>
				<div class="info-grid">
					<div class="info-card"><strong>NGO Name</strong><span><?php echo htmlspecialchars($profile["name"]); ?></span></div>
					<div class="info-card"><strong>Email Address</strong><span><?php echo htmlspecialchars($profile["email"]); ?></span></div>
					<div class="info-card"><strong>Phone Number</strong><span><?php echo htmlspecialchars($profile["phone"]); ?></span></div>
					<div class="info-card"><strong>Address</strong><span><?php echo htmlspecialchars($profile["location"]); ?></span></div>
					<div class="info-card"><strong>Organization Type</strong><span><?php echo htmlspecialchars($profile["organization_type"] ?? "Not provided"); ?></span></div>
					<div class="info-card"><strong>Established Year</strong><span><?php echo (int)($profile["established_year"] ?? 0); ?></span></div>
				</div>
			</section>

			<section id="edit-section" class="edit-section<?php echo $message === "" ? " hidden" : ""; ?>">
				<h2>Edit Profile</h2>

		<?php if ($message !== ""): ?>
			<div class="message <?php echo htmlspecialchars($message_type); ?>">
				<?php echo htmlspecialchars($message); ?>
			</div>
		<?php endif; ?>

			<div class="profile-form-box">
			<form id="profile-update-form" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="profile_action" value="update">
			<label for="name">NGO Name</label>
			<input id="name" name="name" value="<?php echo htmlspecialchars($profile["name"]); ?>" required>

			<label for="email">Email</label>
			<input id="email" name="email" type="email" value="<?php echo htmlspecialchars($profile["email"]); ?>" required>

			<label for="phone">Phone</label>
			<input id="phone" name="phone" value="<?php echo htmlspecialchars($profile["phone"]); ?>" required>

			<label for="location">Location</label>
			<input id="location" name="location" value="<?php echo htmlspecialchars($profile["location"]); ?>" required>

			<label for="organization_type">Organization Type</label>
			<input id="organization_type" name="organization_type" value="<?php echo htmlspecialchars($profile["organization_type"] ?? ""); ?>">

			<label for="established_year">Established Year</label>
			<input id="established_year" name="established_year" type="number" value="<?php echo (int)($profile["established_year"] ?? 0); ?>">

			<label for="verification_document">Upload Verification Document Again</label>
			<input id="verification_document" name="verification_document" type="file" accept=".pdf,.jpg,.jpeg,.png">

				</form>

			<div class="profile-action-row">
			<button type="submit" form="profile-update-form">Update Profile</button>
			<form class="delete-profile-form" method="POST" onsubmit="return confirm('Delete your NGO profile permanently? This cannot be undone.');">
				<input type="hidden" name="profile_action" value="delete">
				<button class="delete-profile-button" type="submit"><i class="fa-solid fa-trash"></i> Delete Profile</button>
			</form>
			</div>
			</div>
			</section>
			<a class="bottom-back" href="ngo_dashboard.php">&larr; Back to Dashboard</a>
		</main>
	</div>
	<script>
		function toggleEditForm() {
			document.getElementById("edit-section").classList.toggle("hidden");
		}
	</script>
</body>
</html>
