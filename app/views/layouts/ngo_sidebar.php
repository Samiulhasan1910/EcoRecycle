<aside class="sidebar ngo-sidebar">
    <div class="logo-area">
        <div class="logo-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
        <h2>Helping Hands NGO</h2>
        <p>NGO Panel</p>
    </div>
    <nav class="menu">
        <a href="<?php echo url('ngo/dashboard'); ?>" class="<?php echo active('ngo/dashboard'); ?>"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
        <a href="<?php echo url('ngo/donation-requests'); ?>" class="<?php echo active('ngo/donation-requests'); ?>"><i class="fa-solid fa-gift"></i><span>Donation Requests</span></a>
        <a href="<?php echo url('ngo/accepted-donations'); ?>" class="<?php echo active('ngo/accepted-donations'); ?>"><i class="fa-solid fa-circle-check"></i><span>Accepted Donations</span></a>
        <a href="<?php echo url('ngo/browse-wanted'); ?>" class="<?php echo active('ngo/browse-wanted'); ?>"><i class="fa-solid fa-magnifying-glass"></i><span>Browse Wanted</span></a>
        <a href="<?php echo url('ngo/create-post'); ?>" class="<?php echo active('ngo/create-post'); ?>"><i class="fa-solid fa-pen-to-square"></i><span>Create Post</span></a>
        <a href="<?php echo url('ngo/pickups'); ?>" class="<?php echo active('ngo/pickups'); ?>"><i class="fa-solid fa-truck"></i><span>Pickup Status</span></a>
        <a href="<?php echo url('ngo/notifications'); ?>" class="<?php echo active('ngo/notifications'); ?>"><i class="fa-solid fa-bell"></i><span>Notifications</span><span class="menu-notification">4</span></a>
        <a href="<?php echo url('ngo/profile'); ?>" class="<?php echo active('ngo/profile'); ?>"><i class="fa-solid fa-user"></i><span>NGO Profile</span></a>
        <a href="<?php echo url('auth/logout'); ?>"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
    </nav>
    <div class="sidebar-bottom">
        <i class="fa-solid fa-hand-holding-heart"></i>
        Together we can<br>
        make a difference.
    </div>
</aside>
