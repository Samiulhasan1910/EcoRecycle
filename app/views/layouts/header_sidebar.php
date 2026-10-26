<?php

// Delivery men get their own header/sidebar
if(($_SESSION['role'] ?? '') === 'delivery_man'){
    include __DIR__ . '/header_sidebar_delivery.php';
    return;
}

require_once APP_PATH . '/models/Notification.php';

$name = $_SESSION["name"] ?? "User";

$user_id = (int)($_SESSION["user_id"] ?? 0);

$unread_count = $user_id > 0 ? (new Notification())->countUnreadForUser($user_id) : 0;

?>

<nav class="navbar">

    <div class="logo">
        Eco<span>Cycle</span>
    </div>

    <div class="user">

        <div class="user-circle">
            <?php echo strtoupper(substr($name,0,1)); ?>
        </div>

        <span class="user-name">
            <?php echo e($name); ?>
        </span>

    </div>

</nav>


<aside class="sidebar">

    <div class="menu-title">
        Main Menu
    </div>

    <div class="menu">

        <a href="<?php echo url('dashboard'); ?>" class="<?php echo active('dashboard'); ?>">
            🏠 &nbsp; Dashboard
        </a>

        <a href="<?php echo url('items/browse'); ?>" class="<?php echo active('items/browse'); ?>">
            🔎 &nbsp; Browse Items
        </a>

        <a href="<?php echo url('items/create'); ?>" class="<?php echo active('items/create'); ?>">
            ➕ &nbsp; Post Item
        </a>

        <a href="<?php echo url('items/mine'); ?>" class="<?php echo active('items/mine', 'items/sell', 'items/donate', 'wanted/mine'); ?>">
            📋 &nbsp; My Items
        </a>

        <a href="<?php echo url('wanted/create'); ?>" class="<?php echo active('wanted/create'); ?>">
            📋 &nbsp; Wanted Posts
        </a>

        <a href="<?php echo url('requests/mine'); ?>" class="<?php echo active('requests/mine'); ?>">
            📦 &nbsp; My Requests
        </a>

        <a href="<?php echo url('transactions'); ?>" class="<?php echo active('transactions'); ?>">
            💳 &nbsp; Transactions
        </a>

        <a href="<?php echo url('delivery/track'); ?>" class="<?php echo active('delivery/track'); ?>">
            🚚 &nbsp; Delivery Track
        </a>

    </div>


    <div class="menu-title">
        Account
    </div>


    <div class="menu">

        <a href="<?php echo url('notifications'); ?>" class="notification-link <?php echo active('notifications'); ?>">

            <span>
                🔔 &nbsp; Notifications
            </span>

            <?php if($unread_count > 0): ?>
                <span class="notification-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>

        </a>

        <a href="<?php echo url('profile'); ?>" class="<?php echo active('profile'); ?>">
            👤 &nbsp; My Profile
        </a>

        <a href="<?php echo url('auth/logout'); ?>" class="logout">
            🚪 &nbsp; Logout
        </a>

    </div>

</aside>
