<?php

$currentPage = basename($_SERVER["PHP_SELF"]);

$name = $_SESSION["name"] ?? "User";

$user_id = $_SESSION["user_id"] ?? 0;


/* =========================
   UNREAD NOTIFICATION COUNT
========================= */

$unread_count = 0;

if($user_id > 0)
{
    $notificationSql = "SELECT COUNT(*) AS unread_count
                         FROM notifications
                         WHERE user_id='$user_id'
                         AND status='Unread'";

    $notificationResult = mysqli_query($conn, $notificationSql);

    if($notificationResult)
    {
        $notificationData = mysqli_fetch_assoc($notificationResult);

        $unread_count = $notificationData["unread_count"];
    }
}

?>

<?php
// If delivery man, load a delivery-specific header/sidebar
if(($_SESSION['role'] ?? '') === 'delivery_man'){
    include __DIR__ . '/header_sidebar_delivery.php';
    return;
}

?>

<nav class="navbar">

    <div class="logo">
        Eco<span>Cycle</span>
    </div>

    <div class="user">

        <div class="user-circle">

            <?php
            echo strtoupper(
                substr($name,0,1)
            );
            ?>

        </div>

        <span class="user-name">

            <?php
            echo htmlspecialchars($name);
            ?>

        </span>

    </div>

    <!-- top navbar delivery button removed to restore original navbar -->

</nav>


<aside class="sidebar">

    <div class="menu-title">
        Main Menu
    </div>

    <div class="menu">

        <a
            href="dashboard.php"
            class="<?php
                echo ($currentPage == 'dashboard.php')
                ? 'active'
                : '';
            ?>"
        >
            🏠 &nbsp; Dashboard
        </a>


        <a
            href="browse_items.php"
            class="<?php
                echo ($currentPage == 'browse_items.php')
                ? 'active'
                : '';
            ?>"
        >
            🔎 &nbsp; Browse Items
        </a>


        <a
            href="post_item.php"
            class="<?php
                echo ($currentPage == 'post_item.php')
                ? 'active'
                : '';
            ?>"
        >
            ➕ &nbsp; Post Item
        </a>


        <a
            href="my_items.php"
            class="<?php
                echo (
                    $currentPage == 'my_items.php' ||
                    $currentPage == 'sell_items.php' ||
                    $currentPage == 'donate_items.php' ||
                    $currentPage == 'wanted_items.php'
                )
                ? 'active'
                : '';
            ?>"
        >
            📋 &nbsp; My Items
        </a>


        <a
            href="post_wanted.php"
            class="<?php
                echo ($currentPage == 'post_wanted.php')
                ? 'active'
                : '';
            ?>"
        >
            📋 &nbsp; Wanted Posts
        </a>


        <a
            href="my_requests.php"
            class="<?php
                echo ($currentPage == 'my_requests.php')
                ? 'active'
                : '';
            ?>"
        >
            📦 &nbsp; My Requests
        </a>


        <a
            href="transactions.php"
            class="<?php
                echo ($currentPage == 'transactions.php')
                ? 'active'
                : '';
            ?>"
        >
            💳 &nbsp; Transactions
        </a>

        <a
    href="delivery_track.php"
    class="<?php
        echo ($currentPage == 'delivery_track.php')
        ? 'active'
        : '';
    ?>"
>
    🚚 &nbsp; Delivery Track
</a>
    </div>


    <div class="menu-title">
        Account
    </div>


    <div class="menu">

        <!-- =========================
             NOTIFICATIONS
        ========================== -->

        <a
            href="notifications.php"
            class="notification-link <?php
                echo ($currentPage == 'notifications.php')
                ? 'active'
                : '';
            ?>"
        >

            <span>
                🔔 &nbsp; Notifications
            </span>

            <?php

            if($unread_count > 0)
            {
                echo '<span class="notification-badge">'
                     . $unread_count .
                     '</span>';
            }

            ?>

        </a>


        <a
            href="profile.php"
            class="<?php
                echo ($currentPage == 'profile.php')
                ? 'active'
                : '';
            ?>"
        >
            👤 &nbsp; My Profile
        </a>


        <a
            href="logout.php"
            class="logout"
        >
            🚪 &nbsp; Logout
        </a>

    </div>

</aside>


<!-- styles moved to style.css -->