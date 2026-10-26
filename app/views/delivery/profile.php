<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Delivery Profile | EcoCycle</title>
    <link rel="stylesheet" href="assets/css/style_delivery.css">
</head>
<body>
<?php require APP_PATH . '/views/layouts/header_sidebar_delivery.php'; ?>
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
