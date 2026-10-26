<?php
// Delivery-man specific header and sidebar
$name = $_SESSION["name"] ?? "Delivery";
?>
<link rel="stylesheet" href="<?php echo asset('css/style_delivery.css'); ?>">

<nav class="navbar delivery-navbar">
    <div class="logo">
        Eco<span>Cycle</span>
    </div>
    <div class="user">
        <div class="user-circle">
            <?php echo strtoupper(substr($name,0,1)); ?>
        </div>
        <span class="user-name"><?php echo e($name); ?></span>
    </div>
</nav>

<aside class="sidebar delivery-sidebar">
    <div class="menu-title">Delivery Menu</div>
    <div class="menu">
        <a href="<?php echo url('delivery/dashboard'); ?>" class="<?php echo active('delivery/dashboard'); ?>">🚚 &nbsp; Dashboard</a>
        <a href="<?php echo url('delivery/assigned'); ?>" class="<?php echo active('delivery/assigned'); ?>">📦 &nbsp; Assigned Deliveries</a>
        <a href="<?php echo url('delivery/availability'); ?>" class="<?php echo active('delivery/availability'); ?>">🕒 &nbsp; Availability</a>
        <a href="<?php echo url('delivery/earnings'); ?>" class="<?php echo active('delivery/earnings'); ?>">💰 &nbsp; Earnings</a>
        <a href="<?php echo url('delivery/history'); ?>" class="<?php echo active('delivery/history'); ?>">📍 &nbsp; Delivery History</a>
    </div>

    <div class="menu-title">Account</div>
    <div class="menu">
        <a href="<?php echo url('delivery/profile'); ?>" class="<?php echo active('delivery/profile'); ?>">👤 &nbsp; My Profile</a>
        <a href="<?php echo url('auth/logout'); ?>" class="logout">🚪 &nbsp; Logout</a>
    </div>
</aside>
