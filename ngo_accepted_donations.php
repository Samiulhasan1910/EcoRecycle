<?php

session_start();

if (!isset($_SESSION["ngo_id"])) {
    header("Location: login.php");
    exit();
}

include("db.php");

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$ngo_id = (int)$_SESSION["ngo_id"];


/* =========================
   GET ACCEPTED DONATIONS
   ========================= */

$sql = "SELECT
            r.request_id,
            r.user_id,
            r.item_id,
            r.ngo_id,
            r.request_type,
            r.status,
            i.title,
            i.category,
            i.price,
            i.location,
            d.status AS delivery_status,
            d.assigned_delivery_man_id,
            dm.name AS delivery_name
        FROM requests r
        INNER JOIN items i
        ON r.item_id = i.item_id
        LEFT JOIN deliveries d
        ON d.request_id = r.request_id
        LEFT JOIN delivery_man dm
        ON dm.delivery_id = d.assigned_delivery_man_id
        WHERE r.ngo_id = $ngo_id
        AND r.request_type = 'Donation'
        AND r.status = 'Accepted'
        ORDER BY r.request_id DESC";

$result = mysqli_query($conn, $sql);
$deliveryMenResult = mysqli_query($conn, "SELECT delivery_id, name, phone FROM delivery_man WHERE verification_status = 'Approved' AND availability = 'Available' AND is_blocked = 0 ORDER BY name ASC");
$deliveryMen = [];
if ($deliveryMenResult) {
    while ($deliveryMan = mysqli_fetch_assoc($deliveryMenResult)) {
        $deliveryMen[] = $deliveryMan;
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Accepted Donations</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f8faf9;
            margin: 0;
            color: #222;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 263px;
            height: 100vh;
            padding: 28px 16px;
            background: #004d3a;
            color: white;
            overflow-y: auto;
            z-index: 1000;
        }

        .logo-area {
            text-align: center;
            margin-bottom: 24px;
        }

        .logo-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 52px;
        }

        .logo-area h2 {
            margin: 0 0 12px;
            font-size: 22px;
        }

        .logo-area p {
            margin: 0;
            font-size: 16px;
        }

        .menu {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .menu li {
            margin-bottom: 5px;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 12px;
            color: white;
            text-decoration: none;
            border-radius: 9px;
            font-size: 13px;
        }

        .menu a:hover,
        .menu a.active {
            background: #159b64;
        }

        .menu a span {
            white-space: nowrap;
        }

        .menu i {
            width: 25px;
            text-align: center;
            font-size: 18px;
        }

        .menu-notification {
            margin-left: auto;
            width: 27px;
            height: 27px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #ff3d3d;
            color: white;
            font-size: 13px;
            font-weight: bold;
        }

        .main {
            margin-left: 263px;
            min-height: 100vh;
            padding: 35px;
        }

        .back-link {
            color: #0f766e;
            text-decoration: none;
            font-weight: bold;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin: 22px 0 25px;
        }

        h2 {
            margin: 0;
            color: #004d3a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: #004d3a;
            color: white;
            padding: 12px;
        }

        td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }

        tr:hover {
            background: #f1f1f1;
        }

        .pickup-btn {
            background: #28a745;
            color: white;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 5px;
        }

        .pickup-btn:hover {
            background: #218838;
        }

        .no-data {
            padding: 20px;
            text-align: center;
            color: #666;
        }

        @media (max-width: 700px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main {
                margin-left: 0;
                padding: 20px;
            }

            .page-header {
                align-items: flex-start;
                flex-direction: column;
            }

            table {
                display: block;
                overflow-x: auto;
            }
        }

    </style>
    <link rel="stylesheet" href="ngo_sidebar.css">

</head>


<body>

<aside class="sidebar ngo-sidebar">
    <div class="logo-area">
        <div class="logo-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
        <h2 style="color:white;">Helping Hands NGO</h2>
        <p>NGO Panel</p>
    </div>

    <ul class="menu">
        <li><a href="ngo_dashboard.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
        <li><a href="ngo_donation_requests.php"><i class="fa-solid fa-gift"></i><span>Donation Requests</span></a></li>
        <li><a href="ngo_accepted_donations.php" class="active"><i class="fa-solid fa-circle-check"></i><span>Accepted Donations</span></a></li>
        <li><a href="ngo_browse_wanted_posts.php"><i class="fa-solid fa-magnifying-glass"></i><span>Browse Wanted</span></a></li>
        <li><a href="ngo_create_post.php"><i class="fa-solid fa-pen-to-square"></i><span>Create Post</span></a></li>
        <li><a href="ngo_pickups.php"><i class="fa-solid fa-truck"></i><span>Pickup Status</span></a></li>
        <li><a href="ngo_notifications.php"><i class="fa-solid fa-bell"></i><span>Notifications</span><span class="menu-notification">4</span></a></li>
        <li><a href="ngo_profile.php"><i class="fa-solid fa-user"></i><span>NGO Profile</span></a></li>
        <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a></li>
    </ul>
</aside>

<main class="main">
<a href="ngo_dashboard.php" class="back-link">&larr; Back to Dashboard</a>

<div class="page-header">
    <h2>Accepted Donations</h2>
</div>


<table>

    <tr>

        <th>Item</th>

        <th>Category</th>

        <th>Price</th>

        <th>Location</th>

        <th>Status</th>

        <th>Delivery</th>

        <th>Action</th>

    </tr>


<?php

if (mysqli_num_rows($result) > 0) {

    while ($row = mysqli_fetch_assoc($result)) {

?>

    <tr>

        <td>
            <?php echo htmlspecialchars($row["title"]); ?>
        </td>

        <td>
            <?php echo htmlspecialchars($row["category"]); ?>
        </td>

        <td>
            <?php echo htmlspecialchars($row["price"]); ?>
        </td>

        <td>
            <?php echo htmlspecialchars($row["location"]); ?>
        </td>

        <td>
            <?php echo htmlspecialchars($row["status"]); ?>
        </td>

        <td>
            <?php echo htmlspecialchars($row["delivery_name"] ?? "Not assigned"); ?>
            <br>
            <small><?php echo htmlspecialchars($row["delivery_status"] ?? "Pending"); ?></small>
        </td>

        <td>
            <?php if (!empty($row["delivery_name"])): ?>
                <?php echo htmlspecialchars($row["delivery_name"]); ?><br>
                <small><?php echo htmlspecialchars($row["delivery_status"] ?? "Assigned"); ?></small>
            <?php else: ?>
                <span>Waiting for a delivery man</span>
            <?php endif; ?>
            <a class="pickup-btn" href="ngo_pickups.php">View Pickup Status</a>

        </td>

    </tr>

<?php

    }

} else {

?>

    <tr>

        <td colspan="6" class="no-data">
            No accepted donations found.
        </td>

    </tr>

<?php

}

?>

</table>

</main>

</body>

</html>
