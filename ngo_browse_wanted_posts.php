<?php

session_start();

$role = $_SESSION["role"] ?? "";
if (!in_array($role, ["user", "delivery_man", "ngo"], true)) {
    header("Location: login.php");
    exit();
}

include("db.php");

$ngo_id = (int)($_SESSION["ngo_id"] ?? 0);

$ngo_name = $_SESSION["name"] ?? ($role === "delivery_man" ? "Delivery" : "User");
$backLink = $role === "ngo" ? "ngo_dashboard.php" : ($role === "delivery_man" ? "delivery_dashboard.php" : "dashboard.php");


// =========================
// SEARCH VALUES
// =========================

$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";

$category = isset($_GET["category"])
    ? trim($_GET["category"])
    : "";

$location = isset($_GET["location"])
    ? trim($_GET["location"])
    : "";


// =========================
// MAIN QUERY
// =========================

$sql = "SELECT wanted_id, title, category, location, description, status, ngo_name
     FROM (
         SELECT wp.wanted_id,
             wp.title,
             wp.category,
             wp.location,
             wp.description,
             wp.status,
             NULL AS ngo_name
         FROM wanted_posts AS wp
         WHERE LOWER(wp.status) = 'active'

         UNION ALL

         SELECT i.item_id AS wanted_id,
             i.title,
             i.category,
             i.location,
             NULL AS description,
             'Active' AS status,
             n.name AS ngo_name
         FROM items AS i
         LEFT JOIN ngos AS n ON n.ngo_id = i.ngo_id
          WHERE i.type = 'Donate'
              AND LOWER(i.status) = 'available'
     ) AS all_posts
     WHERE 1";


if ($search != "") {

    $searchSafe = mysqli_real_escape_string(
        $conn,
        $search
    );

    $sql .= " AND (
                title LIKE '%$searchSafe%'
                OR description LIKE '%$searchSafe%'
                OR category LIKE '%$searchSafe%'
                OR location LIKE '%$searchSafe%'
              )";
}


if ($category != "") {

    $categorySafe = mysqli_real_escape_string(
        $conn,
        $category
    );

    $sql .= " AND LOWER(TRIM(category)) = LOWER(TRIM('$categorySafe'))";
}


if ($location != "") {

    $locationSafe = mysqli_real_escape_string(
        $conn,
        $location
    );

    $sql .= " AND location = '$locationSafe'";
}


// =========================
// SORT
// =========================

$sql .= " ORDER BY wanted_id DESC";


$result = mysqli_query(
    $conn,
    $sql
);


if (!$result) {

    die(
        "Query Error: " .
        mysqli_error($conn)
    );

}


// =========================
// CATEGORY LIST
// =========================

$category_sql = "
    SELECT category FROM wanted_posts
    WHERE category IS NOT NULL AND category != ''
    UNION
    SELECT category FROM items
    WHERE type = 'Donate' AND status = 'Available'
    AND category IS NOT NULL AND category != ''
    ORDER BY category ASC
";

$category_result = mysqli_query(
    $conn,
    $category_sql
);


// =========================
// LOCATION LIST
// =========================

$location_sql = "
    SELECT DISTINCT location
    FROM wanted_posts
    WHERE location IS NOT NULL
    AND location != ''
    ORDER BY location ASC
";

$location_result = mysqli_query(
    $conn,
    $location_sql
);


// =========================
// NGO INITIALS
// =========================

$words = preg_split(
    '/\s+/',
    trim($ngo_name)
);

$initials = "";

foreach ($words as $word) {

    if ($word != "") {

        $initials .= strtoupper(
            substr($word, 0, 1)
        );

    }

}

$initials = substr(
    $initials,
    0,
    2
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Browse Wanted Posts | EcoCycle
</title>


<!-- FONT AWESOME -->

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<style>

/* =====================================================
   RESET
===================================================== */

*{
    box-sizing:border-box;
}


body{

    margin:0;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:#f5f7f6;

    color:#222;
}


/* =====================================================
   SIDEBAR
===================================================== */

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


/* =====================================================
   LOGO AREA
===================================================== */

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

    font-weight:bold;
}


.logo-area p{

    margin:7px 0 0;

    font-size:13px;

    color:#d8eee5;
}


/* =====================================================
   MENU
===================================================== */

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


/* =====================================================
   ACTIVE MENU
===================================================== */

.menu a.active{

    background:#0b9b6c;

    font-weight:bold;
}


/* =====================================================
   NOTIFICATION BADGE
===================================================== */

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


/* =====================================================
   SIDEBAR BOTTOM
===================================================== */

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


/* =====================================================
   TOP BAR
===================================================== */

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


/* =====================================================
   BELL
===================================================== */

.bell{

    position:relative;

    font-size:20px;

    color:#333;

    cursor:pointer;
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


/* =====================================================
   PROFILE
===================================================== */

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


/* =====================================================
   MAIN CONTENT
===================================================== */

.main{

    margin-left:230px;

    padding:

        105px
        45px
        50px;

}


/* =====================================================
   PAGE HEADER
===================================================== */

.page-header{

    display:flex;

    align-items:center;

    justify-content:space-between;

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


/* =====================================================
   BACK BUTTON
===================================================== */

.back-btn{

    display:inline-flex;

    align-items:center;

    gap:7px;

    background:white;

    color:#168a45;

    border:1px solid #168a45;

    padding:10px 16px;

    border-radius:7px;

    text-decoration:none;

    font-size:13px;

    font-weight:bold;
}


.back-btn:hover{

    background:#168a45;

    color:white;
}


/* =====================================================
   SEARCH CARD
===================================================== */

.search-box{

    background:white;

    border:1px solid #ddd;

    border-radius:12px;

    padding:22px;

    margin-bottom:30px;
}


.search-box h2{

    margin:0 0 18px;

    font-size:18px;
}


.form-row{

    display:flex;

    gap:12px;

    flex-wrap:wrap;
}


.form-row input,
.form-row select{

    flex:1;

    min-width:190px;

    height:45px;

    padding:10px 12px;

    border:1px solid #ccc;

    border-radius:7px;

    font-size:14px;

    background:white;
}


.form-row input:focus,
.form-row select:focus{

    outline:none;

    border-color:#168a45;
}


/* =====================================================
   SEARCH BUTTON
===================================================== */

.search-btn{

    height:45px;

    padding:0 22px;

    background:#168a45;

    color:white;

    border:none;

    border-radius:7px;

    cursor:pointer;

    font-size:14px;

    font-weight:bold;
}


.search-btn:hover{

    background:#117239;
}


/* =====================================================
   CLEAR BUTTON
===================================================== */

.clear-btn{

    height:45px;

    padding:0 22px;

    background:#6c757d;

    color:white;

    text-decoration:none;

    border-radius:7px;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:14px;

    font-weight:bold;
}


.clear-btn:hover{

    background:#565e64;
}


/* =====================================================
   POSTS HEADER
===================================================== */

.posts-header{

    display:flex;

    justify-content:space-between;

    align-items:center;

    margin-bottom:18px;
}


.posts-header h2{

    margin:0;

    font-size:20px;
}


.posts-count{

    color:#777;

    font-size:13px;
}


/* =====================================================
   POSTS GRID
===================================================== */

.posts-grid{

    display:grid;

    grid-template-columns:
        repeat(
            auto-fill,
            minmax(290px,1fr)
        );

    gap:25px;
}


/* =====================================================
   POST CARD
===================================================== */

.post-card{

    background:white;

    border:1px solid #ddd;

    border-radius:12px;

    overflow:hidden;

    transition:0.2s;

}


.post-card:hover{

    transform:translateY(-3px);

    box-shadow:
        0 6px 18px
        rgba(0,0,0,0.08);
}


/* =====================================================
   IMAGE
===================================================== */

.post-image{

    width:100%;

    height:200px;

    object-fit:cover;

    display:block;
}


.no-image{

    width:100%;

    height:200px;

    background:#eaf1ed;

    display:flex;

    flex-direction:column;

    align-items:center;

    justify-content:center;

    color:#789;

}


.no-image i{

    font-size:45px;

    margin-bottom:10px;

    color:#9ab5a7;
}


.no-image span{

    font-size:13px;
}


/* =====================================================
   POST CONTENT
===================================================== */

.post-content{

    padding:20px;
}


.post-content h3{

    margin:0 0 12px;

    font-size:20px;

    color:#222;
}


/* =====================================================
   CATEGORY
===================================================== */

.category{

    display:inline-block;

    background:#eaf7ef;

    color:#168a45;

    padding:6px 11px;

    border-radius:20px;

    font-size:12px;

    font-weight:bold;

    margin-bottom:12px;
}


/* =====================================================
   INFO
===================================================== */

.info{

    display:flex;

    align-items:flex-start;

    gap:7px;

    margin:9px 0;

    color:#555;

    font-size:13px;

    line-height:1.5;
}


.info i{

    color:#168a45;

    margin-top:2px;
}


/* =====================================================
   DESCRIPTION
===================================================== */

.description{

    margin-top:15px;

    padding-top:15px;

    border-top:1px solid #eee;

    color:#666;

    line-height:1.6;

    font-size:13px;
}


.description strong{

    color:#333;

    display:block;

    margin-bottom:5px;
}


/* =====================================================
   STATUS
===================================================== */

.status{

    margin-top:15px;
}


.status-badge{

    display:inline-block;

    padding:6px 10px;

    border-radius:20px;

    background:#eaf7ef;

    color:#176b3b;

    font-size:12px;

    font-weight:bold;
}


/* =====================================================
   NO RESULT
===================================================== */

.no-result{

    background:white;

    border:1px solid #ddd;

    border-radius:12px;

    padding:60px 20px;

    text-align:center;

    color:#777;
}


.no-result i{

    font-size:50px;

    color:#9ab5a7;

    margin-bottom:15px;
}


.no-result h2{

    margin:0 0 8px;

    color:#444;

    font-size:20px;
}


.no-result p{

    margin:0;

    font-size:13px;
}


/* =====================================================
   MOBILE
===================================================== */

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

        padding:
            100px
            20px
            40px;
    }

}


@media(max-width:650px){

    .page-header{

        flex-direction:column;

        align-items:flex-start;

        gap:15px;
    }


    .form-row{

        flex-direction:column;
    }


    .form-row input,
    .form-row select,
    .search-btn,
    .clear-btn{

        width:100%;

        min-width:100%;
    }


    .posts-grid{

        grid-template-columns:1fr;
    }


    .profile-mini span{

        display:none;
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


        <!-- Dashboard -->

        <a href="<?php echo htmlspecialchars($backLink); ?>">

            <i class="fa-solid fa-house"></i>

            <span>
                Dashboard
            </span>

        </a>


        <!-- Donation Requests -->

        <a href="ngo_donation_requests.php">

            <i class="fa-solid fa-gift"></i>

            <span>
                Donation Requests
            </span>

        </a>


        <!-- Accepted Donations -->

        <a href="ngo_accepted_donations.php">

            <i class="fa-solid fa-circle-check"></i>

            <span>
                Accepted Donations
            </span>

        </a>


        <!-- Pickup Status -->

<a href="ngo_pickups.php">

            <i class="fa-solid fa-truck"></i>

            <span>
                Pickup Status
            </span>

        </a>


        <!-- Browse Wanted Posts ACTIVE -->

        <a
            href="ngo_browse_wanted_posts.php"
            class="active"
        >

            <i class="fa-solid fa-magnifying-glass"></i>

            <span>
                Browse Wanted Posts
            </span>

        </a>


        <!-- Create Post -->

        <a href="ngo_create_post.php">

            <i class="fa-solid fa-square-plus"></i>

            <span>
                Create Post
            </span>

        </a>


        <!-- Notifications -->

        <a href="ngo_notifications.php">

            <i class="fa-solid fa-bell"></i>

            <span>
                Notifications
            </span>

            <span class="notification">
                4
            </span>

        </a>


        <!-- NGO Profile -->

        <a href="ngo_profile.php">

            <i class="fa-solid fa-user"></i>

            <span>
                NGO Profile
            </span>

        </a>


        <!-- Logout -->

        <a href="logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>
                Logout
            </span>

        </a>


    </nav>


    <!-- BOTTOM -->

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


        <!-- Notification -->

        <div class="bell">

            <i class="fa-solid fa-bell"></i>

            <span class="bell-badge">
                4
            </span>

        </div>


        <!-- Profile -->

        <div class="profile-mini">


            <div class="profile-circle">

                <?php

                echo htmlspecialchars(
                    $initials
                );

                ?>

            </div>


            <span>

                <?php

                echo htmlspecialchars(
                    $ngo_name
                );

                ?>

            </span>


            <i class="fa-solid fa-chevron-down"></i>


        </div>


    </div>


</header>



<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<main class="main">


    <!-- PAGE HEADER -->

    <div class="page-header">


        <div>

            <h1>
                Browse Wanted Posts
            </h1>

            <p>
                See what EcoCycle users are looking for.
            </p>

        </div>


        <a
            href="<?php echo htmlspecialchars($backLink); ?>"
            class="back-btn"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Back to Dashboard

        </a>


    </div>



    <!-- =================================================
         SEARCH
    ================================================= -->

    <div class="search-box">


        <h2>

            Search Wanted Posts

        </h2>


        <form
            method="GET"
            action=""
        >


            <div class="form-row">


                <!-- SEARCH -->

                <input
                    type="text"
                    name="search"
                    placeholder="Search by title or description..."
                    value="<?php
                    echo htmlspecialchars(
                        $search
                    );
                    ?>"
                >


                <!-- CATEGORY -->

                <select name="category">

                    <option value="">

                        All Categories

                    </option>


                    <?php

                    if (
                        $category_result &&
                        mysqli_num_rows(
                            $category_result
                        ) > 0
                    ) {

                        while (
                            $cat =
                            mysqli_fetch_assoc(
                                $category_result
                            )
                        ) {

                    ?>

                        <option
                            value="<?php
                            echo htmlspecialchars(
                                $cat["category"]
                            );
                            ?>"
                            <?php

                            if (
                                $category ===
                                $cat["category"]
                            ) {

                                echo "selected";

                            }

                            ?>
                        >

                            <?php

                            echo htmlspecialchars(
                                $cat["category"]
                            );

                            ?>

                        </option>


                    <?php

                        }

                    }

                    ?>

                </select>



                <!-- LOCATION -->

                <select name="location">

                    <option value="">

                        All Locations

                    </option>


                    <?php

                    if (
                        $location_result &&
                        mysqli_num_rows(
                            $location_result
                        ) > 0
                    ) {

                        while (
                            $loc =
                            mysqli_fetch_assoc(
                                $location_result
                            )
                        ) {

                    ?>

                        <option
                            value="<?php
                            echo htmlspecialchars(
                                $loc["location"]
                            );
                            ?>"
                            <?php

                            if (
                                $location ===
                                $loc["location"]
                            ) {

                                echo "selected";

                            }

                            ?>
                        >

                            <?php

                            echo htmlspecialchars(
                                $loc["location"]
                            );

                            ?>

                        </option>


                    <?php

                        }

                    }

                    ?>

                </select>



                <!-- SEARCH BUTTON -->

                <button
                    type="submit"
                    class="search-btn"
                >

                    <i class="fa-solid fa-magnifying-glass"></i>

                    &nbsp; Search

                </button>



                <!-- CLEAR -->

                <a
                    href="ngo_browse_wanted_posts.php"
                    class="clear-btn"
                >

                    Clear

                </a>


            </div>


        </form>


    </div>



    <!-- =================================================
         POSTS HEADER
    ================================================= -->

    <div class="posts-header">


        <h2>

            Wanted Posts

        </h2>


        <span class="posts-count">

            <?php

            if ($result) {

                echo mysqli_num_rows(
                    $result
                );

            } else {

                echo "0";

            }

            ?>

            post(s) found

        </span>


    </div>



    <!-- =================================================
         WANTED POSTS
    ================================================= -->

    <?php

    if (
        $result &&
        mysqli_num_rows($result) > 0
    ) {

    ?>


        <div class="posts-grid">


        <?php

        while (
            $row =
            mysqli_fetch_assoc($result)
        ) {

        ?>


            <div class="post-card">


                <!-- =========================
                     IMAGE
                ========================= -->

                <?php

                if (
                    !empty(
                        $row["image"]
                    )
                ) {

                    /*
                     * Image database-e sudhu filename
                     * thakle uploads/ add korbe.
                     */

                    $image = $row["image"];


                    if (
                        strpos(
                            $image,
                            "uploads/"
                        ) === 0
                    ) {

                        $image_path = $image;

                    }

                    elseif (
                        strpos(
                            $image,
                            "/"
                        ) === 0
                    ) {

                        $image_path = $image;

                    }

                    elseif (
                        strpos(
                            $image,
                            "http://"
                        ) === 0 ||
                        strpos(
                            $image,
                            "https://"
                        ) === 0
                    ) {

                        $image_path = $image;

                    }

                    else {

                        $image_path =
                            "uploads/" .
                            $image;

                    }

                ?>

                    <img
                        src="<?php
                        echo htmlspecialchars(
                            $image_path
                        );
                        ?>"
                        alt="Wanted Item"
                        class="post-image"
                    >

                <?php

                }

                else {

                ?>

                    <div class="no-image">

                        <i class="fa-regular fa-image"></i>

                        <span>
                            No Image Available
                        </span>

                    </div>

                <?php

                }

                ?>


                <!-- =========================
                     CONTENT
                ========================= -->

                <div class="post-content">


                    <!-- TITLE -->

                    <h3>

                        <?php

                        echo htmlspecialchars(
                            $row["title"]
                        );

                        ?>

                    </h3>



                    <!-- CATEGORY -->

                    <?php

                    if (
                        !empty(
                            $row["category"]
                        )
                    ) {

                    ?>

                        <span class="category">

                            <i class="fa-solid fa-tag"></i>

                            <?php

                            echo htmlspecialchars(
                                $row["category"]
                            );

                            ?>

                        </span>

                    <?php

                    }

                    ?>



                    <!-- LOCATION -->

                    <?php

                    if (
                        !empty(
                            $row["location"]
                        )
                    ) {

                    ?>

                        <div class="info">

                            <i class="fa-solid fa-location-dot"></i>

                            <div>

                                <strong>
                                    Location:
                                </strong>

                                <?php

                                echo htmlspecialchars(
                                    $row["location"]
                                );

                                ?>

                            </div>

                        </div>

                    <?php

                    }

                    ?>



                    <!-- DESCRIPTION -->

                    <?php

                    if (
                        !empty(
                            $row["description"]
                        )
                    ) {

                    ?>

                        <div class="description">

                            <strong>
                                Description
                            </strong>

                            <?php

                            echo nl2br(
                                htmlspecialchars(
                                    $row["description"]
                                )
                            );

                            ?>

                        </div>

                    <?php

                    }

                    ?>



                    <!-- STATUS -->

                    <?php

                    if (
                        isset(
                            $row["status"]
                        ) &&
                        $row["status"] != ""
                    ) {

                    ?>

                        <div class="status">

                            <span class="status-badge">

                                <i class="fa-solid fa-circle-check"></i>

                                <?php

                                echo htmlspecialchars(
                                    $row["status"]
                                );

                                ?>

                            </span>

                        </div>

                    <?php

                    }


                    ?>

                </div>


            </div>


        <?php

        }

        ?>


        </div>


    <?php

    }

    else {

    ?>


        <!-- NO POSTS -->

        <div class="no-result">


            <i class="fa-regular fa-folder-open"></i>


            <h2>

                No Wanted Posts Found

            </h2>


            <p>

                There are no wanted posts matching your search.

            </p>


        </div>


    <?php

    }


    ?>


</main>


</body>

</html>
