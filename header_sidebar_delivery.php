<?php
// Delivery-man specific header and sidebar
$currentPage = basename($_SERVER["PHP_SELF"]);
$name = $_SESSION["name"] ?? "Delivery";
$delivery_id = (int)($_SESSION["delivery_id"] ?? $_SESSION["user_id"] ?? 0);
?>
<link rel="stylesheet" href="style_delivery.css">

<nav class="navbar delivery-navbar">
    <div class="logo">
        Eco<span>Cycle</span>
    </div>
    <div class="user">
        <div class="user-circle">
            <?php echo strtoupper(substr($name,0,1)); ?>
        </div>
        <span class="user-name"><?php echo htmlspecialchars($name); ?></span>
    </div>
</nav>

<aside class="sidebar delivery-sidebar">
    <div class="menu-title">Delivery Menu</div>
    <div class="menu">
        <a href="delivery_dashboard.php" class="<?php echo ($currentPage=='delivery_dashboard.php')? 'active':''; ?>">🚚 &nbsp; Dashboard</a>
        <a href="delivery_assigned.php" class="<?php echo ($currentPage=='delivery_assigned.php')? 'active':''; ?>">📦 &nbsp; Assigned Deliveries</a>
        <a href="availability.php" class="<?php echo ($currentPage=='availability.php')? 'active':''; ?>">🕒 &nbsp; Availability</a>
        <a href="earnings.php" class="<?php echo ($currentPage=='earnings.php')? 'active':''; ?>">💰 &nbsp; Earnings</a>
        <a href="deliveries.php" class="<?php echo ($currentPage=='deliveries.php')? 'active':''; ?>">📍 &nbsp; Delivery History</a>
    </div>

    <div class="menu-title">Account</div>
    <div class="menu">
        <a href="delivery_profile.php" class="<?php echo ($currentPage=='delivery_profile.php')? 'active':''; ?>">👤 &nbsp; My Profile</a>
        <a href="logout.php" class="logout">🚪 &nbsp; Logout</a>
    </div>
</aside>
