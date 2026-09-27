<?php
session_start();

if (($_SESSION["role"] ?? "") !== "ngo" || !isset($_SESSION["ngo_id"])) {
    header("Location: login.php");
    exit();
}

include("db.php");

$ngo_id = (int)$_SESSION["ngo_id"];

$wantedNotificationColumn = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'wanted_id'");
if ($wantedNotificationColumn && mysqli_num_rows($wantedNotificationColumn) === 0) {
    mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN wanted_id INT NULL");
}

$pickupNotificationsSql = "
    INSERT INTO notifications (user_id, ngo_id, message, status, pickup_id)
    SELECT NULL, r.ngo_id,
           CONCAT('Delivery update for pickup #', p.pickup_id, ': ', p.status),
           'Unread', p.pickup_id
    FROM pickups p
    INNER JOIN requests r ON r.request_id = p.request_id
    WHERE r.ngo_id = $ngo_id
    AND NOT EXISTS (
        SELECT 1 FROM notifications n
        WHERE n.ngo_id = r.ngo_id AND n.pickup_id = p.pickup_id
    )";
mysqli_query($conn, $pickupNotificationsSql);

$name = $_SESSION["name"] ?? "Helping Hands NGO";
$words = explode(" ", $name);
$initials = "";

foreach ($words as $word) {
    if (!empty($word)) {
        $initials .= strtoupper($word[0]);
    }
}

$initials = substr($initials, 0, 2);


/* =========================
    DASHBOARD COUNTS
========================= */

/* Total Donations */
$total_sql = "SELECT COUNT(*) AS total
              FROM items
              WHERE type = 'Donate'
              AND ngo_id = $ngo_id";

$total_result = mysqli_query($conn, $total_sql);
$total = mysqli_fetch_assoc($total_result)['total'];


/* Pending Requests */
$pending_sql = "SELECT COUNT(*) AS total
                FROM items
                WHERE type = 'Donate'
                AND ngo_id = $ngo_id
                AND status = 'Pending'";

$pending_result = mysqli_query($conn, $pending_sql);
$pending = mysqli_fetch_assoc($pending_result)['total'];


/* Accepted Donations */
$accepted_sql = "SELECT COUNT(*) AS total
                 FROM items
                 WHERE type = 'Donate'
                 AND ngo_id = $ngo_id
                 AND status = 'Accepted'";

$accepted_result = mysqli_query($conn, $accepted_sql);
$accepted = mysqli_fetch_assoc($accepted_result)['total'];


/* Completed Donations */
$completed_sql = "SELECT COUNT(*) AS total
                  FROM items
                  WHERE type = 'Donate'
                  AND ngo_id = $ngo_id
                  AND status = 'Completed'";

$completed_result = mysqli_query($conn, $completed_sql);
$completed = mysqli_fetch_assoc($completed_result)['total'];

/* =========================
   HANDLE ACCEPT REQUESTS
========================= */
if (isset($_GET['accept_wanted_id'])) {
    $wantedId = (int)($_GET['accept_wanted_id'] ?? 0);

    if ($wantedId > 0) {
        $accept_wanted_sql = "UPDATE wanted_posts
                             SET status = 'Accepted', ngo_id = $ngo_id
                             WHERE wanted_id = $wantedId
                             AND (ngo_id IS NULL OR ngo_id = $ngo_id)
                             AND status != 'Completed'";

        mysqli_query($conn, $accept_wanted_sql);
    }

    header("Location: ngo_dashboard.php");
    exit();
}

if (isset($_GET['accept_item_id'])) {
    $itemId = (int)($_GET['accept_item_id'] ?? 0);

    if ($itemId > 0) {
        mysqli_begin_transaction($conn);

        $accept_item_sql = "UPDATE items
                            SET status = 'Accepted', ngo_id = $ngo_id
                            WHERE item_id = $itemId
                            AND ngo_id = $ngo_id
                            AND status = 'Pending'";

        mysqli_query($conn, $accept_item_sql);

        if (mysqli_affected_rows($conn) === 1) {
            $itemResult = mysqli_query($conn, "SELECT user_id FROM items WHERE item_id = $itemId LIMIT 1");
            $item = $itemResult ? mysqli_fetch_assoc($itemResult) : null;

            if ($item && !empty($item['user_id'])) {
                $existingRequest = mysqli_query($conn, "SELECT request_id FROM requests WHERE item_id = $itemId LIMIT 1");
                if ($existingRequest && mysqli_num_rows($existingRequest) === 0) {
                    mysqli_query(
                        $conn,
                        "INSERT INTO requests
                        (user_id, item_id, ngo_id, request_type, status, created_at)
                        VALUES (" . (int)$item['user_id'] . ", $itemId, $ngo_id, 'Donation', 'Pending', NOW())"
                    );
                }
            }
        }

        mysqli_commit($conn);
    }

    header("Location: ngo_dashboard.php");
    exit();
}

/* Recent notifications from users, delivery updates and wanted posts. */
$recent_notifications_sql = "SELECT notification_id, message, status, request_id, pickup_id, item_id, wanted_id
                              FROM notifications
                              WHERE ngo_id = $ngo_id
                              ORDER BY notification_id DESC
                              LIMIT 4";
$recent_notifications_result = mysqli_query($conn, $recent_notifications_sql);
$unread_notifications_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM notifications WHERE ngo_id = $ngo_id AND LOWER(status) = 'unread'");
$unread_notifications = $unread_notifications_result ? (int)(mysqli_fetch_assoc($unread_notifications_result)['total'] ?? 0) : 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>NGO Dashboard - Reuse & Donate</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

<style>

/* =========================
   RESET
========================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #f8faf9;
    color: #111;
}


/* =========================
   SIDEBAR
========================= */

.sidebar {

    position: fixed;

    left: 0;
    top: 0;

    width: 263px;
    height: 100vh;

    background: #004d3a;

    color: white;

    padding: 28px 16px;

    z-index: 1000;
}


/* LOGO */

.logo-area {

    text-align: center;

    margin-bottom: 38px;
}

.logo-icon {

    width: 70px;
    height: 70px;

    margin: 0 auto 12px;

    display: flex;
    align-items: center;
    justify-content: center;

    font-size: 52px;

    color: white;
}

.logo-area h2 {

    font-size: 22px;

    margin-bottom: 12px;
}

.logo-area p {

    font-size: 16px;
}


/* MENU */

.menu {

    list-style: none;
}

.menu li {

    margin-bottom: 9px;
}

.menu a {

    display: flex;

    align-items: center;

    gap: 18px;

    padding: 16px 18px;

    color: white;

    text-decoration: none;

    border-radius: 9px;

    font-size: 16px;

    transition: 0.2s;
}

.menu a:hover {

    background: #087b52;
}

    position: absolute;

    left: 20px;
    right: 20px;

    bottom: 22px;

    border: 1px solid #159b64;

    border-radius: 10px;

    padding: 25px 15px;

    display: flex;

    align-items: center;

    gap: 18px;

    color: white;
}

.sidebar-bottom i {

    font-size: 28px;

    color: #22bd78;
}

.sidebar-bottom p {

    font-size: 14px;

    line-height: 1.5;
}


/* =========================
   MAIN
========================= */

.main {

    margin-left: 263px;

    min-height: 100vh;
}


/* =========================
   TOP BAR
========================= */

.topbar {

    height: 90px;

    background: white;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 30px;

    border-bottom: 1px solid #e6e6e6;
}


/* HAMBURGER */

.hamburger {

    font-size: 25px;

    cursor: pointer;

    color: #222;
}


/* TOP RIGHT */

.top-right {

    display: flex;

    align-items: center;

    gap: 15px;
}


/* BELL */

.notification {

    position: relative;

    font-size: 25px;

    margin-right: 5px;
}

.notification-count {

    position: absolute;

    top: -10px;
    right: -9px;

    width: 22px;
    height: 22px;

    background: #ff3d3d;

    color: white;

    border-radius: 50%;

    font-size: 12px;

    display: flex;

    align-items: center;
    justify-content: center;

    font-weight: bold;
}


/* PROFILE CIRCLE */

.profile {

    width: 40px;
    height: 40px;

    border-radius: 50%;

    background: #006b4f;

    color: white;

    display: flex;

    align-items: center;
    justify-content: center;

    font-weight: bold;

    font-size: 16px;
}


/* NGO NAME */

.ngo-name {

    font-size: 16px;

    font-weight: bold;

    margin-left: 0;
}

.dropdown {

    font-size: 14px;

    margin-left: 2px;
}


/* =========================
   CONTENT
========================= */

.content {

    padding: 28px 27px;
}


/* =========================
   WELCOME BOX
========================= */

.welcome-box {

    min-height: 160px;

    background: linear-gradient(
        90deg,
        #f1f8f2,
        #eef8ef
    );

    border: 1px solid #e5ebe6;

    border-radius: 14px;

    padding: 28px 34px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    margin-bottom: 25px;

    overflow: hidden;
}

.welcome-text h1 {

    font-size: 29px;

    margin-bottom: 13px;

    font-weight: 700;
}

.welcome-text p {

    font-size: 17px;

    color: #222;
}


/* WELCOME ILLUSTRATION */

.welcome-illustration {

    width: 42%;

    height: 155px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #168a45;

    font-size: 100px;
}


/* =========================
   STAT CARDS
========================= */

.cards {

    display: grid;

    grid-template-columns: repeat(4, 1fr);

    gap: 20px;

    margin-bottom: 27px;
}

.card {

    min-height: 145px;

    border-radius: 13px;

    padding: 25px;

    display: flex;

    align-items: center;

    gap: 20px;

    border: 1px solid #e8e8e8;

    background: white;
}


/* CARD BACKGROUNDS */

.card:nth-child(1) {
    background: #f7fbf8;
}

.card:nth-child(2) {
    background: #fffbf2;
}

.card:nth-child(3) {
    background: #f3f9ff;
}

.card:nth-child(4) {
    background: #faf7ff;
}


/* CARD ICON CIRCLE */

.card-icon {

    width: 68px;
    height: 68px;

    min-width: 68px;

    border-radius: 50%;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 29px;
}


/* ICON COLORS */

.card:nth-child(1) .card-icon {

    background: #dff3e5;

    color: #15915d;
}

.card:nth-child(2) .card-icon {

    background: #ffedc9;

    color: #ef8b00;
}

.card:nth-child(3) .card-icon {

    background: #dceeff;

    color: #1670c9;
}

.card:nth-child(4) .card-icon {

    background: #e9ddff;

    color: #7650d8;
}


/* CARD TEXT */

.card-info h3 {

    font-size: 16px;

    margin-bottom: 15px;
}

.card-info .number {

    font-size: 30px;

    font-weight: bold;

    margin-bottom: 7px;
}

.card-info p {

    font-size: 14px;

    color: #333;
}


/* =========================
   BOTTOM SECTION
========================= */

.bottom-section {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 20px;
}


/* COMMON BOX */

.dashboard-box {

    background: white;

    border: 1px solid #e5e5e5;

    border-radius: 14px;

    overflow: hidden;
}


/* BOX HEADER */

.box-header {

    height: 70px;

    padding: 0 28px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    border-bottom: 1px solid #e5e5e5;
}

.box-header h2 {

    font-size: 20px;
}

.view-all {

    color: #00804d;

    text-decoration: none;

    font-size: 14px;

    font-weight: 500;
}


/* =========================
   NOTIFICATIONS
========================= */

.notifications {

    padding: 0 28px;
}

.notification-item {

    min-height: 77px;

    display: flex;

    align-items: center;

    gap: 18px;

    border-bottom: 1px solid #eeeeee;
}

.notification-link {
    color: inherit;
    text-decoration: none;
    transition: background-color 0.2s ease;
}

.notification-link:hover {
    background: #f7fbf8;
}

.notification-arrow {
    color: #159b64;
    margin-left: auto;
    padding-left: 10px;
}

.notification-item:last-child {

    border-bottom: none;
}


/* NOTIFICATION ICON */

.notification-icon {

    width: 45px;
    height: 45px;

    min-width: 45px;

    border-radius: 50%;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 19px;
}

.notification-icon.green {

    background: #e5f5ea;

    color: #15915d;
}

.notification-icon.orange {

    background: #fff1d5;

    color: #ef8b00;
}

.notification-icon.blue {

    background: #e2f0ff;

    color: #1670c9;
}


.notification-text {

    flex: 1;
}

.notification-text h4 {

    font-size: 15px;

    font-weight: normal;

    margin-bottom: 8px;
}

.notification-text p {

    font-size: 13px;

    color: #555;
}


/* BOTTOM NOTIFICATION LINK */

.notification-footer {

    text-align: center;

    padding: 18px;
}

.notification-footer a {

    color: #00804d;

    text-decoration: none;

    font-size: 14px;

    font-weight: 500;
}


/* =========================
   QUICK ACTIONS
========================= */

.quick-actions {

    padding: 15px;
}

.action-grid {

    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 18px;
}

.action-card {

    min-height: 150px;

    border-radius: 11px;

    padding: 20px;

    display: flex;

    align-items: center;

    gap: 15px;

    text-decoration: none;

    color: #111;

    border: 1px solid #e4e4e4;

    position: relative;

    transition: 0.2s;
}

.action-card:hover {

    transform: translateY(-2px);

    box-shadow: 0 4px 15px rgba(0,0,0,0.07);
}


/* ACTION BACKGROUNDS */

.action-card.green {

    background: #f2faf4;
}

.action-card.blue {

    background: #f1f8ff;
}

.action-card.orange {

    background: #fff9eb;
}

.action-card.purple {

    background: #faf6ff;
}

.action-card.cyan {

    background: #f0fbfb;
}

.action-card.pink {

    background: #fff3f7;
}


/* ACTION ICON */

.action-icon {

    width: 55px;
    height: 55px;

    min-width: 55px;

    border-radius: 50%;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 23px;
}

.green .action-icon {

    background: #dff3e5;

    color: #159b64;
}

.blue .action-icon {

    background: #dceeff;

    color: #1670c9;
}

.orange .action-icon {

    background: #ffedc9;

    color: #e88a00;
}

.purple .action-icon {

    background: #e9ddff;

    color: #7650d8;
}

.cyan .action-icon {

    background: #d9f3f3;

    color: #0793a0;
}

.pink .action-icon {

    background: #f9dce9;

    color: #d32977;
}


/* ACTION TEXT */

.action-info h3 {

    font-size: 15px;

    margin-bottom: 12px;

    color: #006b4f;
}

.blue .action-info h3 {

    color: #0862a9;
}

.purple .action-info h3 {

    color: #2c285c;
}

.cyan .action-info h3 {

    color: #078994;
}

.pink .action-info h3 {

    color: #c72570;
}

.action-info p {

    font-size: 13px;

    line-height: 1.6;

    color: #222;
}


/* ARROW */

.action-arrow {

    position: absolute;

    right: 18px;

    bottom: 18px;

    font-size: 22px;

    color: #00804d;
}


/* =========================
   FOOTER
========================= */

.footer {

    text-align: center;

    padding: 25px;

    color: #555;

    font-size: 13px;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width: 1200px) {

    .cards {

        grid-template-columns: repeat(2, 1fr);
    }

    .bottom-section {

        grid-template-columns: 1fr;
    }
}


@media(max-width: 800px) {

    .sidebar {

        width: 220px;
    }

    .main {

        margin-left: 220px;
    }

    .ngo-name {

        display: none;
    }

    .welcome-illustration {

        display: none;
    }
}


@media(max-width: 600px) {

    .sidebar {

        position: relative;

        width: 100%;

        height: auto;
    }

    .main {

        margin-left: 0;
    }

    .sidebar-bottom {

        position: relative;

        left: auto;
        right: auto;
        bottom: auto;

        margin-top: 25px;
    }

    .cards {

        grid-template-columns: 1fr;
    }

    .action-grid {

        grid-template-columns: 1fr;
    }

    .welcome-box {

        padding: 25px;
    }

    .welcome-text h1 {

        font-size: 23px;
    }

    .topbar {

        padding: 0 18px;
    }

    .content {

        padding: 18px;
    }
}

</style>
<link rel="stylesheet" href="ngo_sidebar.css">

</head>


<body>


<!-- ==================================================
     SIDEBAR
================================================== -->

<aside class="sidebar ngo-sidebar">


    <!-- LOGO -->

    <div class="logo-area">

        <div class="logo-icon">

            <i class="fa-solid fa-hand-holding-heart"></i>

        </div>

        <h2>Helping Hands NGO</h2>

        <p>NGO Panel</p>

    </div>


    <!-- MENU -->

    <ul class="menu">

        <li>

            <a href="ngo_dashboard.php" class="active">

                <i class="fa-solid fa-house"></i>

                <span>Dashboard</span>

            </a>

        </li>


        <li>

            <a href="ngo_donation_requests.php">

                <i class="fa-solid fa-gift"></i>

                <span>Donation Requests</span>

            </a>

        </li>


        <li>

            <a href="ngo_accepted_donations.php">

                <i class="fa-solid fa-circle-check"></i>

                <span>Accepted Donations</span>

            </a>

        </li>


        <li>

            <a href="ngo_browse_wanted_posts.php">

                <i class="fa-solid fa-magnifying-glass"></i>

                <span>Browse Wanted</span>

            </a>

        </li>


        <li>

            <a href="ngo_create_post.php">

                <i class="fa-solid fa-pen-to-square"></i>

                <span>Create Post</span>

            </a>

        </li>


        <li>

            <a href="ngo_pickups.php">

                <i class="fa-solid fa-truck"></i>

                <span>Pickup Status</span>

            </a>

        </li>


        <li>

            <a href="ngo_notifications.php">

                <i class="fa-solid fa-bell"></i>

                <span>Notifications</span>

                <?php if ($unread_notifications > 0): ?><span class="menu-notification"><?php echo $unread_notifications; ?></span><?php endif; ?>

            </a>

        </li>


        <li>

            <a href="ngo_profile.php">

                <i class="fa-solid fa-user"></i>

                <span>NGO Profile</span>

            </a>

        </li>


        <li>

            <a href="logout.php">

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>Logout</span>

            </a>

        </li>

    </ul>


    <!-- SIDEBAR BOTTOM -->

    <div class="sidebar-bottom">

        <i class="fa-solid fa-hand-holding-heart"></i>

        <p>
            Together we can<br>
            make a difference.
        </p>

    </div>

</aside>


<!-- ==================================================
     MAIN
================================================== -->

<main class="main">


    <!-- TOP BAR -->

    <header class="topbar">


        <div class="hamburger">

            <i class="fa-solid fa-bars"></i>

        </div>


        <div class="top-right">


            <!-- NOTIFICATION -->

            <div class="notification">

                <i class="fa-regular fa-bell"></i>

                    <?php if ($unread_notifications > 0): ?>
                        <span class="notification-count">
                            <?php echo $unread_notifications; ?>
                        </span>
                    <?php endif; ?>


            <!-- PROFILE -->

            <div class="profile">

                <?php echo htmlspecialchars($initials); ?>

            </div>


            <div class="ngo-name">

                <?php echo htmlspecialchars($name); ?>

            </div>


            <i class="fa-solid fa-chevron-down dropdown"></i>


        </div>


    </header>


    <!-- ==================================================
         CONTENT
    ================================================== -->

    <div class="content">


        <!-- ================= WELCOME ================= -->

        <section class="welcome-box">


            <div class="welcome-text">

                <h1>

                    Welcome back,
                    <?php echo htmlspecialchars($name); ?>! 👋

                </h1>


                <p>

                    Together we can make a difference in people's lives.

                </p>

            </div>


            <!-- Simple illustration -->

            <div class="welcome-illustration">

                <i class="fa-solid fa-hand-holding-heart"></i>

            </div>


        </section>



        <!-- ================= STAT CARDS ================= -->

        <section class="cards">


            <!-- CARD 1 -->

            <div class="card">

                <div class="card-icon">

                    <i class="fa-solid fa-gift"></i>

                </div>

                <div class="card-info">

                    <h3>Total Donations</h3>

                    <div class="number">

                        <?php echo $total; ?>

                    </div>

                    <p>All time</p>

                </div>

            </div>



            <!-- CARD 2 -->

            <div class="card">

                <div class="card-icon">

                    <i class="fa-solid fa-hourglass-half"></i>

                </div>

                <div class="card-info">

                    <h3>Pending Requests</h3>

                    <div class="number">

                        <?php echo $pending; ?>

                    </div>

                    <p>New requests</p>

                </div>

            </div>



            <!-- CARD 3 -->

            <div class="card">

                <div class="card-icon">

                    <i class="fa-solid fa-circle-check"></i>

                </div>

                <div class="card-info">

                    <h3>Accepted Donations</h3>

                    <div class="number">

                        <?php echo $accepted; ?>

                    </div>

                    <p>Total accepted</p>

                </div>

            </div>



            <!-- CARD 4 -->

            <div class="card">

                <div class="card-icon">

                    <i class="fa-solid fa-truck"></i>

                </div>

                <div class="card-info">

                    <h3>Completed Donations</h3>

                    <div class="number">

                        <?php echo $completed; ?>

                    </div>

                    <p>Successfully completed</p>

                </div>

            </div>


        </section>



        <!-- ==================================================
             BOTTOM SECTION
        ================================================== -->

        <section class="bottom-section">


            <!-- ==================================================
                 RECENT NOTIFICATIONS
            ================================================== -->

            <div class="dashboard-box">


                <div class="box-header">

                    <h2>Recent Notifications</h2>

                    <a
                        href="ngo_notifications.php"
                        class="view-all"
                    >

                        View All

                    </a>

                </div>


                <div class="notifications">
                    <?php if ($recent_notifications_result && mysqli_num_rows($recent_notifications_result) > 0): ?>
                        <?php while ($notification = mysqli_fetch_assoc($recent_notifications_result)): ?>
                            <?php
                                $notificationMessage = $notification['message'] ?? 'New notification';
                                $notificationIcon = stripos($notificationMessage, 'wanted') !== false
                                    ? 'fa-bullhorn'
                                    : (stripos($notificationMessage, 'delivery') !== false || !empty($notification['pickup_id'])
                                        ? 'fa-truck'
                                        : 'fa-gift');
                            ?>
                            <a class="notification-item notification-link" href="ngo_notification_view.php?id=<?php echo (int)$notification['notification_id']; ?>">
                                <div class="notification-icon orange">
                                    <i class="fa-solid <?php echo $notificationIcon; ?>"></i>
                                </div>
                                <div class="notification-text">
                                    <h4><?php echo htmlspecialchars($notificationMessage); ?></h4>
                                    <p><?php echo strtolower($notification['status'] ?? '') === 'unread' ? 'New notification' : 'Notification'; ?></p>
                                </div>
                                <i class="fa-solid fa-arrow-right notification-arrow"></i>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="notification-item">
                            <div class="notification-icon blue"><i class="fa-solid fa-bell-slash"></i></div>
                            <div class="notification-text"><h4>No notifications yet.</h4><p>You're all caught up.</p></div>
                        </div>
                    <?php endif; ?>

                </div>


                <div class="notification-footer">

                    <a href="ngo_notifications.php">

                        View All Notifications

                        <i class="fa-solid fa-arrow-right"></i>

                    </a>

                </div>


            </div>



            <!-- ==================================================
                 QUICK ACTIONS
            ================================================== -->

            <div class="dashboard-box">


                <div class="box-header">

                    <h2>Quick Actions</h2>

                </div>


                <div class="quick-actions">

                    <div class="action-grid">


                        <!-- ACTION 1 -->

                        <a
                            href="ngo_donation_requests.php"
                            class="action-card green"
                        >

                            <div class="action-icon">

                                <i class="fa-solid fa-gift"></i>

                            </div>

                            <div class="action-info">

                                <h3>
                                    View Donation Requests
                                </h3>

                                <p>
                                    See all new donation requests
                                    and take action
                                </p>

                            </div>

                            <div class="action-arrow">

                                <i class="fa-solid fa-arrow-right"></i>

                            </div>

                        </a>



                        <!-- ACTION 2 -->

                        <a
                            href="ngo_accepted_donations.php"
                            class="action-card blue"
                        >

                            <div class="action-icon">

                                <i class="fa-solid fa-circle-check"></i>

                            </div>

                            <div class="action-info">

                                <h3>
                                    View Accepted Donations
                                </h3>

                                <p>
                                    See all donations you
                                    have accepted
                                </p>

                            </div>

                            <div class="action-arrow">

                                <i class="fa-solid fa-arrow-right"></i>

                            </div>

                        </a>



                        <!-- ACTION 3 -->

                        <a
                            href="ngo_pickups.php"
                            class="action-card orange"
                        >

                            <div class="action-icon">

                                <i class="fa-solid fa-truck"></i>

                            </div>

                            <div class="action-info">

                                <h3>
                                    View Pickup Status
                                </h3>

                                <p>
                                    Track pickup and delivery
                                    status
                                </p>

                            </div>

                            <div class="action-arrow">

                                <i class="fa-solid fa-arrow-right"></i>

                            </div>

                        </a>



                        <!-- ACTION 4 -->

                        <a
                            href="ngo_profile.php"
                            class="action-card purple"
                        >

                            <div class="action-icon">

                                <i class="fa-solid fa-user"></i>

                            </div>

                            <div class="action-info">

                                <h3>
                                    NGO Profile
                                </h3>

                                <p>
                                    Manage your NGO profile
                                    and information
                                </p>

                            </div>

                            <div class="action-arrow">

                                <i class="fa-solid fa-arrow-right"></i>

                            </div>

                        </a>



                        <a
                            href="ngo_browse_wanted_posts.php"
                            class="action-card cyan"
                        >

                            <div class="action-icon">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </div>

                            <div class="action-info">
                                <h3>Browse Wanted Posts</h3>
                                <p>Browse all wanted posts from people in need</p>
                            </div>

                            <div class="action-arrow">
                                <i class="fa-solid fa-arrow-right"></i>
                            </div>

                        </a>



                        <a
                            href="ngo_create_post.php"
                            class="action-card pink"
                        >

                            <div class="action-icon">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </div>

                            <div class="action-info">
                                <h3>Create Post</h3>
                                <p>Create a new post to help people in need</p>
                            </div>

                            <div class="action-arrow">
                                <i class="fa-solid fa-arrow-right"></i>
                            </div>

                        </a>


                    </div>

                </div>


            </div>


        </section>


    </div>


    <!-- ================= FOOTER ================= -->

    <footer class="footer">

        © 2025 Helping Hands NGO.
        All rights reserved.

    </footer>


</main>


</body>

</html>
