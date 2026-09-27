<?php

session_start();

if (!isset($_SESSION["ngo_id"])) {
    header("Location: login.php");
    exit();
}

include("db.php");

$ngo_id = (int)$_SESSION["ngo_id"];


/* =========================
   GET NGO PICKUP REQUESTS
   ========================= */

$sql = "SELECT 
            p.pickup_id, 
            p.request_id, 
            p.delivery_id, 
            p.date, 
            p.time, 
            p.status, 

            r.ngo_id, 
            r.item_id, 

            i.title, 
            i.category, 
            i.location, 

            d.name AS delivery_name, 
            d.phone AS delivery_phone, 
            d.location AS delivery_location 

        FROM pickups p 

        INNER JOIN requests r 
            ON p.request_id = r.request_id 

        INNER JOIN items i 
            ON r.item_id = i.item_id 

        INNER JOIN delivery_man d 
            ON p.delivery_id = d.delivery_id 

        WHERE r.ngo_id = $ngo_id 

        ORDER BY p.pickup_id DESC";


$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Pickup Status | EcoCycle</title>


<!-- FONT AWESOME -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<style>

/* =========================
   RESET
========================= */

*{
    box-sizing:border-box;
}

body{
    margin:0;

    font-family:Arial, sans-serif;

    background:#f5f7f6;

    color:#222;
}


/* =========================
   SIDEBAR
========================= */

.sidebar{

    position:fixed;

    left:0;
    top:0;

    width:230px;
    height:100vh;

    background:#064d3b;

    color:white;

    padding:25px 12px;

    z-index:1000;
}


/* =========================
   LOGO
========================= */

.logo-area{

    text-align:center;

    margin-bottom:30px;
}

.logo-icon{

    font-size:42px;

    margin-bottom:8px;
}

.logo-area h2{

    margin:0;

    font-size:20px;
}

.logo-area p{

    margin:7px 0 0;

    font-size:13px;

    color:#d8eee5;
}


/* =========================
   MENU
========================= */

.menu{

    margin-top:10px;
}

.menu a{

    display:flex;

    align-items:center;

    gap:14px;

    color:white;

    text-decoration:none;

    padding:13px 13px;

    margin-bottom:5px;

    border-radius:8px;

    font-size:14px;

    transition:0.2s;
}

.menu a i{

    width:22px;

    text-align:center;

    font-size:17px;
}

.menu a:hover{

    background:#0b7458;
}


/* ACTIVE */

.menu a.active{

    background:#0b9b6c;

    font-weight:bold;
}


/* =========================
   NOTIFICATION
========================= */

.notification{

    margin-left:auto;

    background:#ef4444;

    width:22px;
    height:22px;

    border-radius:50%;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:11px;

    font-weight:bold;
}


/* =========================
   SIDEBAR BOTTOM
========================= */

.sidebar-bottom{

    position:absolute;

    bottom:25px;

    left:12px;
    right:12px;

    border:1px solid #168a68;

    border-radius:8px;

    padding:17px 10px;

    text-align:center;

    color:#d9f4e9;

    font-size:13px;

    line-height:1.5;
}

.sidebar-bottom i{

    font-size:27px;

    margin-bottom:8px;
}


/* =========================
   TOP BAR
========================= */

.topbar{

    position:fixed;

    top:0;

    left:230px;

    right:0;

    height:70px;

    background:white;

    border-bottom:1px solid #e5e5e5;

    display:flex;

    align-items:center;

    justify-content:flex-end;

    padding:0 30px;

    z-index:900;
}

.top-right{

    display:flex;

    align-items:center;

    gap:20px;
}


/* =========================
   BELL
========================= */

.bell{

    position:relative;

    font-size:20px;

    color:#333;
}

.bell-badge{

    position:absolute;

    top:-8px;

    right:-8px;

    width:19px;
    height:19px;

    background:#ef4444;

    color:white;

    border-radius:50%;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:10px;

    font-weight:bold;
}


/* =========================
   PROFILE
========================= */

.profile-mini{

    display:flex;

    align-items:center;

    gap:10px;

    font-weight:bold;

    font-size:14px;
}

.profile-circle{

    width:38px;
    height:38px;

    border-radius:50%;

    background:#08734f;

    color:white;

    display:flex;

    align-items:center;

    justify-content:center;

    font-weight:bold;

    font-size:13px;
}


/* =========================
   MAIN
========================= */

.main{

    margin-left:230px;

    padding:105px 45px 50px;

}


/* =========================
   BACK
========================= */

.back{

    display:inline-flex;

    align-items:center;

    gap:7px;

    color:#168a45;

    text-decoration:none;

    font-size:14px;

    font-weight:bold;

    margin-bottom:20px;
}

.back:hover{

    text-decoration:underline;
}


/* =========================
   PAGE HEADER
========================= */

.page-header{

    margin-bottom:25px;
}

.page-header h1{

    margin:0 0 7px;

    font-size:28px;
}

.page-header p{

    margin:0;

    color:#777;

    font-size:14px;
}


/* =========================
   TABLE CARD
========================= */

.table-card{

    background:white;

    border:1px solid #ddd;

    border-radius:12px;

    padding:20px;

    overflow-x:auto;
}


/* =========================
   TABLE
========================= */

table{

    width:100%;

    border-collapse:collapse;

    min-width:950px;
}

thead th{

    background:#064d3b;

    color:white;

    padding:14px 12px;

    font-size:13px;

    text-align:left;

    white-space:nowrap;
}

tbody td{

    border-bottom:1px solid #eee;

    padding:14px 12px;

    font-size:13px;

    color:#444;

    vertical-align:middle;
}

tbody tr:hover{

    background:#f5faf7;
}


/* =========================
   ITEM
========================= */

.item-name{

    font-weight:bold;

    color:#222;
}


/* =========================
   LOCATION
========================= */

.location{

    display:flex;

    align-items:flex-start;

    gap:6px;

    line-height:1.4;
}

.location i{

    color:#168a45;

    margin-top:2px;
}


/* =========================
   DELIVERY PERSON
========================= */

.delivery-name{

    font-weight:bold;

    color:#333;
}


/* =========================
   PHONE
========================= */

.phone{

    white-space:nowrap;
}


/* =========================
   STATUS
========================= */

.status{

    display:inline-flex;

    align-items:center;

    gap:6px;

    padding:6px 11px;

    border-radius:20px;

    font-size:12px;

    font-weight:bold;

    white-space:nowrap;
}


/* PENDING */

.status.pending{

    background:#fff4d6;

    color:#856404;
}


/* ACCEPTED */

.status.accepted{

    background:#eaf7ef;

    color:#176b3b;
}


/* COLLECTED */

.status.collected{

    background:#e8f1ff;

    color:#004085;
}


/* DELIVERED */

.status.delivered{

    background:#eaf7ef;

    color:#28a745;
}


/* CANCELLED */

.status.cancelled{

    background:#fff0f0;

    color:#dc3545;
}


/* UNKNOWN */

.status.default{

    background:#eeeeee;

    color:#555;
}


/* =========================
   NO DATA
========================= */

.no-data{

    text-align:center;

    padding:60px 20px !important;

    color:#777;

    font-size:14px;
}

.no-data i{

    display:block;

    font-size:45px;

    color:#9ab5a7;

    margin-bottom:12px;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width:900px){

    .sidebar{

        width:70px;

        padding:20px 8px;
    }


    .logo-area h2,
    .logo-area p,
    .menu a span,
    .sidebar-bottom{

        display:none;
    }


    .logo-icon{

        font-size:30px;
    }


    .menu a{

        justify-content:center;

        padding:14px 5px;
    }


    .menu a i{

        font-size:18px;
    }


    .notification{

        display:none;
    }


    .topbar{

        left:70px;

        padding:0 20px;
    }


    .main{

        margin-left:70px;

        padding:100px 20px 40px;
    }

}


@media(max-width:550px){

    .profile-mini > span{

        display:none;
    }

    .top-right{

        gap:10px;
    }

}

    </style>
    <link rel="stylesheet" href="ngo_sidebar.css">

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar ngo-sidebar">


    <!-- LOGO -->

    <div class="logo-area">

        <div class="logo-icon">

            <i class="fa-solid fa-hand-holding-heart"></i>

        </div>

        <h2>

            Helping Hands NGO

        </h2>

        <p>

            NGO Panel

        </p>

    </div>


    <!-- MENU -->

    <nav class="menu">


        <!-- DASHBOARD -->

        <a href="ngo_dashboard.php">

            <i class="fa-solid fa-house"></i>

            <span>
                Dashboard
            </span>

        </a>


        <!-- DONATION REQUESTS -->

        <a href="ngo_donation_requests.php">

            <i class="fa-solid fa-gift"></i>

            <span>
                Donation Requests
            </span>

        </a>


        <!-- ACCEPTED DONATIONS -->

        <a href="ngo_accepted_donations.php">

            <i class="fa-solid fa-circle-check"></i>

            <span>
                Accepted Donations
            </span>

        </a>


        <!-- PICKUP STATUS ACTIVE -->

        <a
href="ngo_pickups.php"
            class="active"
        >

            <i class="fa-solid fa-truck"></i>

            <span>
                Pickup Status
            </span>

        </a>


        <!-- BROWSE WANTED POSTS -->

        <a href="ngo_browse_wanted_posts.php">

            <i class="fa-solid fa-magnifying-glass"></i>

            <span>
                Browse Wanted Posts
            </span>

        </a>


        <!-- CREATE POST -->

        <a href="ngo_create_post.php">

            <i class="fa-solid fa-square-plus"></i>

            <span>
                Create Post
            </span>

        </a>


        <!-- NOTIFICATIONS -->

        <a href="ngo_notifications.php">

            <i class="fa-solid fa-bell"></i>

            <span>
                Notifications
            </span>

            <span class="notification">
                4
            </span>

        </a>


        <!-- NGO PROFILE -->

        <a href="ngo_profile.php">

            <i class="fa-solid fa-user"></i>

            <span>
                NGO Profile
            </span>

        </a>


        <!-- LOGOUT -->

        <a href="logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>
                Logout
            </span>

        </a>


    </nav>


    <!-- SIDEBAR BOTTOM -->

    <div class="sidebar-bottom">

        <i class="fa-solid fa-hand-holding-heart"></i>

        <br>

        Together we can

        <br>

        make a difference.

    </div>


</aside>



<!-- =====================================================
     TOP BAR
===================================================== -->

<header class="topbar">

    <div class="top-right">


        <!-- BELL -->

        <div class="bell">

            <i class="fa-solid fa-bell"></i>

            <span class="bell-badge">
                4
            </span>

        </div>


        <!-- PROFILE -->

        <div class="profile-mini">

            <div class="profile-circle">
                HH
            </div>

            <span>
                Helping Hands NGO
            </span>

            <i class="fa-solid fa-chevron-down"></i>

        </div>

    </div>

</header>



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="main">


    <!-- BACK BUTTON -->

    <a
        href="ngo_dashboard.php"
        class="back"
    >

        <i class="fa-solid fa-arrow-left"></i>

        Back to Dashboard

    </a>


    <!-- PAGE HEADER -->

    <div class="page-header">

        <h1>
            Pickup Status
        </h1>

        <p>
            Track the status of your donation pickups.
        </p>

    </div>



    <!-- =================================================
         TABLE
    ================================================= -->

    <div class="table-card">

        <table>

            <thead>

                <tr>

                    <th>
                        Item
                    </th>

                    <th>
                        Category
                    </th>

                    <th>
                        Pickup Location
                    </th>

                    <th>
                        Delivery Person
                    </th>

                    <th>
                        Phone
                    </th>

                    <th>
                        Delivery Location
                    </th>

                    <th>
                        Date
                    </th>

                    <th>
                        Time
                    </th>

                    <th>
                        Status
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php

            if (
                $result &&
                mysqli_num_rows($result) > 0
            ) {

                while (
                    $row =
                    mysqli_fetch_assoc($result)
                ) {

                    $status =
                        strtolower(
                            trim(
                                $row["status"]
                            )
                        );

            ?>


                <tr>


                    <!-- ITEM -->

                    <td>

                        <div class="item-name">

                            <?php

                            echo htmlspecialchars(
                                $row["title"]
                            );

                            ?>

                        </div>

                    </td>


                    <!-- CATEGORY -->

                    <td>

                        <?php

                        echo htmlspecialchars(
                            $row["category"]
                        );

                        ?>

                    </td>


                    <!-- PICKUP LOCATION -->

                    <td>

                        <div class="location">

                            <i class="fa-solid fa-location-dot"></i>

                            <span>

                                <?php

                                echo htmlspecialchars(
                                    $row["location"]
                                );

                                ?>

                            </span>

                        </div>

                    </td>


                    <!-- DELIVERY PERSON -->

                    <td>

                        <div class="delivery-name">

                            <?php

                            echo htmlspecialchars(
                                $row["delivery_name"]
                            );

                            ?>

                        </div>

                    </td>


                    <!-- PHONE -->

                    <td>

                        <div class="phone">

                            <i class="fa-solid fa-phone"></i>

                            <?php

                            echo htmlspecialchars(
                                $row["delivery_phone"]
                            );

                            ?>

                        </div>

                    </td>


                    <!-- DELIVERY LOCATION -->

                    <td>

                        <div class="location">

                            <i class="fa-solid fa-location-dot"></i>

                            <span>

                                <?php

                                echo htmlspecialchars(
                                    $row["delivery_location"]
                                );

                                ?>

                            </span>

                        </div>

                    </td>


                    <!-- DATE -->

                    <td>

                        <?php

                        echo htmlspecialchars(
                            $row["date"]
                        );

                        ?>

                    </td>


                    <!-- TIME -->

                    <td>

                        <?php

                        echo htmlspecialchars(
                            $row["time"]
                        );

                        ?>

                    </td>


                    <!-- STATUS -->

                    <td>


                    <?php

                    if ($status == "pending") {

                        echo '
                        <span class="status pending">
                            <i class="fa-solid fa-clock"></i>
                            Pending
                        </span>
                        ';

                    }

                    elseif ($status == "accepted") {

                        echo '
                        <span class="status accepted">
                            <i class="fa-solid fa-circle-check"></i>
                            Accepted
                        </span>
                        ';

                    }

                    elseif ($status == "collected") {

                        echo '
                        <span class="status collected">
                            <i class="fa-solid fa-box"></i>
                            Collected
                        </span>
                        ';

                    }

                    elseif ($status == "delivered") {

                        echo '
                        <span class="status delivered">
                            <i class="fa-solid fa-check-double"></i>
                            Delivered
                        </span>
                        ';

                    }

                    elseif ($status == "cancelled") {

                        echo '
                        <span class="status cancelled">
                            <i class="fa-solid fa-xmark"></i>
                            Cancelled
                        </span>
                        ';

                    }

                    else {

                        echo '
                        <span class="status default">
                            ' .
                            htmlspecialchars(
                                $row["status"]
                            )
                            . '
                        </span>
                        ';

                    }

                    ?>


                    </td>


                </tr>


            <?php

                }

            }

            else {

            ?>


                <tr>

                    <td
                        colspan="9"
                        class="no-data"
                    >

                        <i class="fa-solid fa-truck"></i>

                        No pickup requests found.

                    </td>

                </tr>


            <?php

            }

            ?>


            </tbody>

        </table>

    </div>


</main>


</body>

</html>
