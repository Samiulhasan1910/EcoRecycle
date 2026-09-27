<?php
$currentNgoPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar ngo-sidebar">
    <div class="logo-area">
        <div class="logo-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
        <h2>Helping Hands NGO</h2>
        <p>NGO Panel</p>
    </div>
    <nav class="menu">
        <a href="ngo_dashboard.php" class="<?php echo $currentNgoPage === 'ngo_dashboard.php' ? 'active' : ''; ?>"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
        <a href="ngo_donation_requests.php" class="<?php echo $currentNgoPage === 'ngo_donation_requests.php' ? 'active' : ''; ?>"><i class="fa-solid fa-gift"></i><span>Donation Requests</span></a>
        <a href="ngo_accepted_donations.php" class="<?php echo $currentNgoPage === 'ngo_accepted_donations.php' ? 'active' : ''; ?>"><i class="fa-solid fa-circle-check"></i><span>Accepted Donations</span></a>
        <a href="ngo_browse_wanted_posts.php" class="<?php echo $currentNgoPage === 'ngo_browse_wanted_posts.php' ? 'active' : ''; ?>"><i class="fa-solid fa-magnifying-glass"></i><span>Browse Wanted</span></a>
        <a href="ngo_create_post.php" class="<?php echo $currentNgoPage === 'ngo_create_post.php' ? 'active' : ''; ?>"><i class="fa-solid fa-pen-to-square"></i><span>Create Post</span></a>
        <a href="ngo_pickups.php" class="<?php echo $currentNgoPage === 'ngo_pickups.php' ? 'active' : ''; ?>"><i class="fa-solid fa-truck"></i><span>Pickup Status</span></a>
        <a href="ngo_notifications.php" class="<?php echo $currentNgoPage === 'ngo_notifications.php' ? 'active' : ''; ?>"><i class="fa-solid fa-bell"></i><span>Notifications</span><span class="menu-notification">4</span></a>
        <a href="ngo_profile.php" class="<?php echo $currentNgoPage === 'ngo_profile.php' ? 'active' : ''; ?>"><i class="fa-solid fa-user"></i><span>NGO Profile</span></a>
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </nav>
    <div class="sidebar-bottom">
        <i class="fa-solid fa-hand-holding-heart"></i>
        Together we can<br>
        make a difference.
    </div>
</aside>
