<?php

session_start();

include("db.php");


// ============================================================
// ADMIN SECURITY
// ============================================================

if(
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
)
{
    header("Location: login.php");
    exit();
}


// ============================================================
// HELPER FUNCTION
// ============================================================

function getCount($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if(!$result)
    {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);

    return (int)$row["total"];
}


// ============================================================
// DASHBOARD COUNTS - REAL DATABASE
// ============================================================


// Total normal users

$totalUsers = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM users"
);


// Total NGO accounts from users table

$totalNGOUsers = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM ngos"
);


// Total NGO records

$totalNGOs = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM ngos"
);


// Delivery staff

$totalDelivery = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM delivery"
);


// Total items

$totalItems = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM items"
);


// Total wanted posts

$totalWantedPosts = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM wanted_posts"
);


// Total requests

$totalRequests = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM requests"
);


// Pending requests (case-insensitive match)

$pendingRequests = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM requests
     WHERE LOWER(status) = 'pending'"
);


// Approved requests (case-insensitive match)

$approvedRequests = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM requests
     WHERE LOWER(status) = 'approved'"
);


// Total pickups

$totalPickups = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM pickups"
);


// Pending items (case-insensitive match)

$pendingItems = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM items
     WHERE LOWER(status) = 'pending'"
);


// ============================================================
// RECENT REQUESTS
// ============================================================

$recentRequestsSql = "

    SELECT
        requests.request_id,
        requests.request_type,
        requests.status,

        users.name AS user_name,

        items.title AS item_title,

        ngos.name AS ngo_name

    FROM requests

    LEFT JOIN users
        ON requests.user_id = users.user_id

    LEFT JOIN items
        ON requests.item_id = items.item_id

    LEFT JOIN ngos
        ON requests.ngo_id = ngos.ngo_id

    ORDER BY requests.request_id DESC

    LIMIT 5

";

$recentRequests =
    mysqli_query(
        $conn,
        $recentRequestsSql
    );


// ============================================================
// RECENT ITEMS
// ============================================================

$recentItemsSql = "

    SELECT
        items.item_id,
        items.title,
        items.category,
        items.type,
        items.price,
        items.status,
        users.name AS user_name

    FROM items

    LEFT JOIN users
        ON items.user_id = users.user_id

    ORDER BY items.item_id DESC

    LIMIT 5

";

$recentItems =
    mysqli_query(
        $conn,
        $recentItemsSql
    );

?>


<!DOCTYPE html>

<html>

<head>

    <title>Admin Dashboard - EcoCycle</title>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >


<style>


* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}


body {
    background: #f4f8f5;
    color: #222;
}


/* ============================================================
   NAVBAR
============================================================ */

.navbar {

    height: 70px;

    background: white;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 7%;

    box-shadow:
        0 2px 10px rgba(0,0,0,0.08);

}


.logo {

    font-size: 25px;

    font-weight: bold;

    color: #168a45;

}


.logo span {
    color: #222;
}


.admin-name {

    color: #168a45;

    font-weight: bold;

}


.logout-btn {

    background: #168a45;

    color: white;

    padding: 10px 18px;

    border-radius: 7px;

    text-decoration: none;

    font-size: 14px;

}


.logout-btn:hover {
    background: #117239;
}



/* ============================================================
   LAYOUT
============================================================ */

.layout {

    display: flex;

    min-height: calc(100vh - 70px);

}



/* ============================================================
   SIDEBAR
============================================================ */

.sidebar {

    width: 240px;

    background: white;

    padding: 25px 15px;

    box-shadow:
        2px 0 10px rgba(0,0,0,0.05);

}


.sidebar-title {

    font-size: 13px;

    color: #888;

    margin-bottom: 15px;

    padding-left: 12px;

}


.menu-item {

    display: block;

    text-decoration: none;

    color: #444;

    padding: 13px 12px;

    border-radius: 8px;

    margin-bottom: 6px;

    font-size: 14px;

}


.menu-item:hover {

    background: #eaf7ef;

    color: #168a45;

}


.menu-item.active {

    background: #168a45;

    color: white;

}



/* ============================================================
   MAIN CONTENT
============================================================ */

.main {

    flex: 1;

    padding: 35px;

}


.page-title {

    font-size: 28px;

    margin-bottom: 8px;

}


.page-subtitle {

    color: #777;

    margin-bottom: 30px;

}



/* ============================================================
   STATS
============================================================ */

.stats {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(200px, 1fr));

    gap: 18px;

    margin-bottom: 30px;

}


.stat-card {

    background: white;

    padding: 22px;

    border-radius: 12px;

    box-shadow:
        0 3px 12px rgba(0,0,0,0.06);

}


.stat-label {

    color: #777;

    font-size: 14px;

    margin-bottom: 10px;

}


.stat-number {

    font-size: 30px;

    color: #168a45;

    font-weight: bold;

}



/* ============================================================
   SECTION
============================================================ */

.section {

    background: white;

    padding: 25px;

    border-radius: 12px;

    margin-bottom: 25px;

    box-shadow:
        0 3px 12px rgba(0,0,0,0.06);

}


.section-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;

}


.section-title {

    font-size: 19px;

}


.view-all {

    color: #168a45;

    text-decoration: none;

    font-size: 14px;

    font-weight: bold;

}



/* ============================================================
   TABLE
============================================================ */

.table-wrapper {

    overflow-x: auto;

}


table {

    width: 100%;

    border-collapse: collapse;

}


th {

    background: #eaf7ef;

    color: #168a45;

    text-align: left;

    padding: 12px;

    font-size: 13px;

}


td {

    padding: 12px;

    border-bottom: 1px solid #eee;

    font-size: 14px;

}


.empty {

    text-align: center;

    padding: 25px;

    color: #777;

}



/* ============================================================
   STATUS
============================================================ */

.status {

    display: inline-block;

    padding: 5px 10px;

    border-radius: 20px;

    background: #eaf7ef;

    color: #168a45;

    font-size: 12px;

    font-weight: bold;

}



/* ============================================================
   QUICK ACTIONS
============================================================ */

.actions {

    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(180px, 1fr));

    gap: 15px;

}


.action-card {

    display: block;

    background: #f4f8f5;

    border: 1px solid #dceee3;

    padding: 18px;

    border-radius: 10px;

    text-decoration: none;

    color: #222;

}


.action-card:hover {

    background: #eaf7ef;

}


.action-card strong {

    display: block;

    color: #168a45;

    margin-bottom: 6px;

}


.action-card span {

    font-size: 13px;

    color: #777;

}



/* ============================================================
   RESPONSIVE
============================================================ */

@media(max-width:800px)
{

    .layout {
        display: block;
    }


    .sidebar {

        width: 100%;

        display: flex;

        flex-wrap: wrap;

        gap: 5px;

        padding: 12px;

    }


    .sidebar-title {
        display: none;
    }


    .menu-item {

        margin: 0;

        padding: 10px;

    }


    .main {
        padding: 20px;
    }


    .navbar {
        padding: 0 20px;
    }

}


</style>

</head>


<body>


<!-- ===========================================================
     NAVBAR
=========================================================== -->

<nav class="navbar">


    <div class="logo">

        Eco<span>Cycle</span>

    </div>


    <div>

        <span class="admin-name">

            <?php
                echo htmlspecialchars(
                    $_SESSION["name"]
                );
            ?>

        </span>


        &nbsp;&nbsp;


        <a
            href="logout.php"
            class="logout-btn"
        >
            Logout
        </a>

    </div>


</nav>



<!-- ===========================================================
     PAGE LAYOUT
=========================================================== -->

<div class="layout">



    <!-- SIDEBAR -->

    <aside class="sidebar">


        <div class="sidebar-title">

            ADMIN PANEL

        </div>


        <a
            href="admin_dashboard.php"
            class="menu-item active"
        >
            📊 Dashboard
        </a>


        <a
            href="user_management.php"
            class="menu-item"
        >
            👥 User Management
        </a>


        <a
            href="ngo_management.php"
            class="menu-item"
        >
            🏢 NGO Management
        </a>


        <a
            href="delivery_management.php"
            class="menu-item"
        >
            🚚 Delivery Staff
        </a>


        <a
            href="item_management.php"
            class="menu-item"
        >
            📦 Item / Post Management
        </a>


        <a
            href="request_management.php"
            class="menu-item"
        >
            📋 Request Monitoring
        </a>


    </aside>



    <!-- MAIN -->

    <main class="main">


        <h1 class="page-title">

            Admin Dashboard

        </h1>


        <p class="page-subtitle">

            Welcome back,
            <?php
                echo htmlspecialchars(
                    $_SESSION["name"]
                );
            ?>.
            Here is your EcoCycle system overview.

        </p>



        <!-- ===================================================
             STATISTICS
        =================================================== -->

        <div class="stats">


            <div class="stat-card">

                <div class="stat-label">
                    Total Users
                </div>

                <div class="stat-number">
                    <?php echo $totalUsers; ?>
                </div>

            </div>



            <div class="stat-card">

                <div class="stat-label">
                    NGOs
                </div>

                <div class="stat-number">
                    <?php echo $totalNGOs; ?>
                </div>

            </div>



            <div class="stat-card">

                <div class="stat-label">
                    Delivery Staff
                </div>

                <div class="stat-number">
                    <?php echo $totalDelivery; ?>
                </div>

            </div>



            <div class="stat-card">

                <div class="stat-label">
                    Total Items
                </div>

                <div class="stat-number">
                    <?php echo $totalItems; ?>
                </div>

            </div>



            <div class="stat-card">

                <div class="stat-label">
                    Wanted Posts
                </div>

                <div class="stat-number">
                    <?php echo $totalWantedPosts; ?>
                </div>

            </div>



            <div class="stat-card">

                <div class="stat-label">
                    Total Requests
                </div>

                <div class="stat-number">
                    <?php echo $totalRequests; ?>
                </div>

            </div>


        </div>



        <!-- ===================================================
             REQUEST STATUS
        =================================================== -->

        <div class="stats">


            <div class="stat-card">

                <div class="stat-label">
                    Pending Requests
                </div>

                <div class="stat-number">
                    <?php echo $pendingRequests; ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    Approved Requests
                </div>

                <div class="stat-number">
                    <?php echo $approvedRequests; ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    Pending Items
                </div>

                <div class="stat-number">
                    <?php echo $pendingItems; ?>
                </div>

            </div>


            <div class="stat-card">

                <div class="stat-label">
                    Total Pickups
                </div>

                <div class="stat-number">
                    <?php echo $totalPickups; ?>
                </div>

            </div>


        </div>



        <!-- ===================================================
             QUICK ACTIONS
        =================================================== -->

        <div class="section">


            <div class="section-header">

                <h2 class="section-title">

                    Management

                </h2>

            </div>


            <div class="actions">


                <a
                    href="user_management.php"
                    class="action-card"
                >

                    <strong>
                        👥 Manage Users
                    </strong>

                    <span>
                        View and manage registered users
                    </span>

                </a>



                <a
                    href="ngo_management.php"
                    class="action-card"
                >

                    <strong>
                        🏢 Manage NGOs
                    </strong>

                    <span>
                        View and manage NGO accounts
                    </span>

                </a>



                <a
                    href="delivery_management.php"
                    class="action-card"
                >

                    <strong>
                        🚚 Delivery Staff
                    </strong>

                    <span>
                        Manage delivery staff accounts
                    </span>

                </a>



                <a
                    href="item_management.php"
                    class="action-card"
                >

                    <strong>
                        📦 Manage Items & Posts
                    </strong>

                    <span>
                        Monitor items and wanted posts
                    </span>

                </a>



                <a
                    href="request_management.php"
                    class="action-card"
                >

                    <strong>
                        📋 Monitor Requests
                    </strong>

                    <span>
                        Track all system requests
                    </span>

                </a>


            </div>


        </div>



        <!-- ===================================================
             RECENT REQUESTS
        =================================================== -->

        <div class="section">


            <div class="section-header">


                <h2 class="section-title">

                    Recent Requests

                </h2>


                <a
                    href="request_management.php"
                    class="view-all"
                >

                    View All

                </a>


            </div>



            <div class="table-wrapper">


                <table>


                    <tr>

                        <th>ID</th>

                        <th>User</th>

                        <th>Item</th>

                        <th>NGO</th>

                        <th>Request Type</th>

                        <th>Status</th>

                    </tr>



                    <?php

                    if(
                        $recentRequests &&
                        mysqli_num_rows($recentRequests) > 0
                    )
                    {

                        while(
                            $request =
                            mysqli_fetch_assoc(
                                $recentRequests
                            )
                        )
                        {

                    ?>

                    <tr>

                        <td>
                            #<?php
                                echo $request["request_id"];
                            ?>
                        </td>


                        <td>
                            <?php
                                echo htmlspecialchars(
                                    $request["user_name"] ?? "-"
                                );
                            ?>
                        </td>


                        <td>
                            <?php
                                echo htmlspecialchars(
                                    $request["item_title"] ?? "-"
                                );
                            ?>
                        </td>


                        <td>
                            <?php
                                echo htmlspecialchars(
                                    $request["ngo_name"] ?? "-"
                                );
                            ?>
                        </td>


                        <td>
                            <?php
                                echo htmlspecialchars(
                                    $request["request_type"]
                                );
                            ?>
                        </td>


                        <td>

                            <span class="status">

                                <?php
                                    echo htmlspecialchars(
                                        $request["status"]
                                    );
                                ?>

                            </span>

                        </td>


                    </tr>

                    <?php

                        }

                    }

                    else
                    {

                    ?>

                    <tr>

                        <td
                            colspan="6"
                            class="empty"
                        >

                            No requests found.

                        </td>

                    </tr>

                    <?php

                    }

                    ?>


                </table>


            </div>


        </div>



        <!-- ===================================================
             RECENT ITEMS
        =================================================== -->

        <div class="section">


            <div class="section-header">


                <h2 class="section-title">

                    Recent Items

                </h2>


                <a
                    href="item_management.php"
                    class="view-all"
                >

                    View All

                </a>


            </div>



            <div class="table-wrapper">


                <table>


                    <tr>

                        <th>ID</th>

                        <th>Title</th>

                        <th>Owner</th>

                        <th>Category</th>

                        <th>Type</th>

                        <th>Price</th>

                        <th>Status</th>

                    </tr>



                    <?php

                    if(
                        $recentItems &&
                        mysqli_num_rows($recentItems) > 0
                    )
                    {

                        while(
                            $item =
                            mysqli_fetch_assoc(
                                $recentItems
                            )
                        )
                        {

                    ?>

                    <tr>


                        <td>

                            #<?php
                                echo $item["item_id"];
                            ?>

                        </td>


                        <td>

                            <?php
                                echo htmlspecialchars(
                                    $item["title"]
                                );
                            ?>

                        </td>


                        <td>

                            <?php
                                echo htmlspecialchars(
                                    $item["user_name"] ?? "-"
                                );
                            ?>

                        </td>


                        <td>

                            <?php
                                echo htmlspecialchars(
                                    $item["category"]
                                );
                            ?>

                        </td>


                        <td>

                            <?php
                                echo htmlspecialchars(
                                    $item["type"]
                                );
                            ?>

                        </td>


                        <td>

                            <?php
                                echo htmlspecialchars(
                                    $item["price"]
                                );
                            ?>

                        </td>


                        <td>

                            <span class="status">

                                <?php
                                    echo htmlspecialchars(
                                        $item["status"]
                                    );
                                ?>

                            </span>

                        </td>


                    </tr>


                    <?php

                        }

                    }

                    else
                    {

                    ?>


                    <tr>

                        <td
                            colspan="7"
                            class="empty"
                        >

                            No items found.

                        </td>

                    </tr>


                    <?php

                    }

                    ?>


                </table>


            </div>


        </div>


    </main>


</div>


</body>

</html>
