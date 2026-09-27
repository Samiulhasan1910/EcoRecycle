<?php
require 'delivery_guard.php';

$message = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Invalid request token.');
    }

    $nameInput = trim($_POST['name'] ?? '');
    $phoneInput = trim($_POST['phone'] ?? '');
    $locationInput = trim($_POST['location'] ?? '');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($nameInput === '' || $phoneInput === '' || $locationInput === '') {
        $error = 'Name, phone, and location are required.';
    } elseif (!preg_match('/^01[0-9]{9}$/', $phoneInput)) {
        $error = 'Enter a valid 11-digit Bangladeshi phone number.';
    } elseif ($newPassword !== '' && strlen($newPassword) < 6) {
        $error = 'New password must contain at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New password and confirmation do not match.';
    } else {
        if ($newPassword !== '') {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $statement = mysqli_prepare($conn, 'UPDATE delivery_man SET name = ?, phone = ?, location = ?, password = ? WHERE delivery_id = ?');
            mysqli_stmt_bind_param($statement, 'ssssi', $nameInput, $phoneInput, $locationInput, $passwordHash, $deliveryId);
        } else {
            $statement = mysqli_prepare($conn, 'UPDATE delivery_man SET name = ?, phone = ?, location = ? WHERE delivery_id = ?');
            mysqli_stmt_bind_param($statement, 'sssi', $nameInput, $phoneInput, $locationInput, $deliveryId);
        }

        if ($statement && mysqli_stmt_execute($statement)) {
            $_SESSION['name'] = $nameInput;
            $_SESSION['delivery_name'] = $nameInput;
            $message = 'Profile updated successfully.';
        } else {
            $error = 'Profile could not be updated. Please try again.';
        }
    }
}

$profileResult = mysqli_query($conn, "SELECT name, email, phone, location, verification_status, availability FROM delivery_man WHERE delivery_id = $deliveryId LIMIT 1");
$profile = $profileResult ? mysqli_fetch_assoc($profileResult) : null;
if (!$profile) {
    header('Location: logout.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Delivery Profile | EcoCycle</title>
    <link rel="stylesheet" href="style_delivery.css">
</head>
<body>
<?php include 'header_sidebar_delivery.php'; ?>
<main class="main">
    <section class="delivery-dashboard-heading">
        <p class="eyebrow">Account settings</p>
        <h1>My Profile</h1>
        <p>Keep your contact details up to date for pickup assignments.</p>
    </section>

    <section class="profile-layout">
        <aside class="profile-summary">
            <div class="profile-avatar"><?php echo htmlspecialchars(strtoupper(substr($profile['name'], 0, 1))); ?></div>
            <h2><?php echo htmlspecialchars($profile['name']); ?></h2>
            <p><?php echo htmlspecialchars($profile['email']); ?></p>
            <div class="profile-status-row"><span>Verification</span><strong><?php echo htmlspecialchars($profile['verification_status'] ?: 'Pending'); ?></strong></div>
            <div class="profile-status-row"><span>Availability</span><strong><?php echo htmlspecialchars($profile['availability'] ?: 'Offline'); ?></strong></div>
        </aside>

        <form class="profile-form" method="post">
            <?php if ($message): ?><p class="success-message"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
            <?php if ($error): ?><p class="workflow-error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <div class="profile-form-grid">
                <label>Full name<input type="text" name="name" value="<?php echo htmlspecialchars($profile['name']); ?>" required></label>
                <label>Email address<input type="email" value="<?php echo htmlspecialchars($profile['email']); ?>" readonly></label>
                <label>Phone number<input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone']); ?>" required></label>
                <label>Location<input type="text" name="location" value="<?php echo htmlspecialchars($profile['location']); ?>" required></label>
            </div>
            <div class="password-section">
                <h2>Change password <span>(optional)</span></h2>
                <div class="profile-form-grid">
                    <label>New password<input type="password" name="new_password" minlength="6"></label>
                    <label>Confirm new password<input type="password" name="confirm_password" minlength="6"></label>
                </div>
            </div>
            <button class="request-accept" type="submit">Save changes</button>
        </form>
    </section>
</main>
</body>
</html>
