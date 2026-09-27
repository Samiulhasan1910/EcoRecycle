<?php

session_start();

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = (int)$_SESSION["user_id"];


/* =========================
   CHECK WANTED ID
========================= */

if(!isset($_GET["id"]) || !is_numeric($_GET["id"]))
{
    header("Location: wanted_items.php");
    exit();
}

$wanted_id = (int)$_GET["id"];


/* =========================
   GET WANTED POST
========================= */

$sql = "SELECT *
        FROM wanted_posts
        WHERE wanted_id='$wanted_id'
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if(!$result || mysqli_num_rows($result) == 0)
{
    header("Location: wanted_items.php?msg=wanted_not_found");
    exit();
}

$wanted = mysqli_fetch_assoc($result);


/* =========================
   GET WANTED POST OWNER ID
========================= */

$wanted_owner_id = isset($wanted["user_id"])
    ? (int)$wanted["user_id"]
    : 0;


/* =========================
   CHECK IF THIS IS MY POST
========================= */

$is_owner = false;

if($wanted_owner_id > 0 && $wanted_owner_id === $user_id)
{
    $is_owner = true;
}


/* =========================
   BACK PAGE
========================= */

$backPage = "wanted_items.php";

$from = $_GET["from"] ?? "";

if($from === "browse")
{
    $backPage = "browse_items.php";
}
elseif($from === "wanted")
{
    $backPage = "wanted_items.php";
}
else
{
    $referrer = $_SERVER["HTTP_REFERER"] ?? "";

    if($referrer !== "")
    {
        $refPath = parse_url($referrer, PHP_URL_PATH);
        $refFile = basename($refPath);

        if(
            in_array(
                $refFile,
                array(
                    "browse_items.php",
                    "search_items.php"
                ),
                true
            )
        )
        {
            $backPage = "browse_items.php";
        }
    }
}


/* =========================
   MESSAGE
========================= */

$msg = $_GET["msg"] ?? "";


/* =========================
   CHECK EXISTING OFFER
   ONLY FOR OTHER USERS
========================= */

$existing_offer_id = 0;

if(!$is_owner)
{
    $dupSql =
        "SELECT offer_id
         FROM wanted_offers
         WHERE wanted_id='$wanted_id'
         AND offerer_user_id='$user_id'
         LIMIT 1";

    $dupRes = mysqli_query($conn, $dupSql);

    if(
        $dupRes &&
        mysqli_num_rows($dupRes) > 0
    )
    {
        $existingOffer = mysqli_fetch_assoc($dupRes);

        $existing_offer_id =
            (int)$existingOffer["offer_id"];
    }
}

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>View Wanted Post | EcoCycle</title>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<link
    rel="stylesheet"
    href="style.css"
>

<style>

/* =========================
   MAIN
========================= */

.main{
    margin-left:230px;
    padding:105px 45px 50px;
    max-width:1000px;
}


/* =========================
   ALERT
========================= */

.alert{
    padding:13px 16px;
    border-radius:8px;
    margin-bottom:18px;
    font-size:14px;
    font-weight:500;
}

.alert-success{
    background:#e6f6ec;
    border:1px solid #c9ead6;
    color:#145a33;
}

.alert-error{
    background:#fff3f3;
    border:1px solid #f2c0c0;
    color:#7a1f1f;
}


/* =========================
   BACK
========================= */

.back{
    display:inline-block;
    color:#168a45;
    text-decoration:none;
    font-size:14px;
    font-weight:bold;
    margin-bottom:20px;
}

.back:hover{
    color:#117239;
    text-decoration:underline;
}


/* =========================
   HEADER
========================= */

.page-header{
    margin-bottom:25px;
}

.page-header h1{
    font-size:28px;
    margin-bottom:7px;
}

.page-header p{
    color:#777;
    font-size:14px;
}


/* =========================
   CARD
========================= */

.wanted-card{
    background:white;
    border:1px solid #ddd;
    border-radius:12px;
    padding:30px;
    max-width:800px;
}


/* =========================
   IMAGE
========================= */

.wanted-image{
    width:100%;
    max-width:450px;
    height:300px;
    object-fit:cover;
    border-radius:10px;
    display:block;
    margin-bottom:25px;
}

.no-image{
    width:100%;
    max-width:450px;
    height:300px;
    background:#f1f3f2;
    border-radius:10px;

    display:flex;
    align-items:center;
    justify-content:center;

    color:#888;
    font-size:14px;

    margin-bottom:25px;
}


/* =========================
   TITLE
========================= */

.wanted-title{
    font-size:24px;
    font-weight:bold;
    margin-bottom:20px;
}


/* =========================
   DETAILS
========================= */

.detail{
    font-size:14px;
    color:#666;
    margin-bottom:12px;
}

.detail strong{
    color:#333;
}


/* =========================
   STATUS
========================= */

.status{
    display:inline-block;

    margin-top:8px;

    padding:6px 12px;

    border-radius:20px;

    background:#eaf7ef;
    color:#168a45;

    font-size:12px;
    font-weight:bold;
}


/* =========================
   OWNER NOTE
========================= */

.owner-note{
    margin-top:25px;

    padding:14px;

    background:#f5f7f6;

    border-radius:8px;

    color:#666;

    font-size:13px;
}

.owner-note a{
    color:#168a45;
    font-weight:bold;
    text-decoration:none;
}

.owner-note a:hover{
    text-decoration:underline;
}


/* =========================
   OFFER BUTTON
========================= */

.offer-btn{
    display:inline-block;

    margin-top:18px;

    background:#168a45;
    color:#fff;

    padding:10px 16px;

    border-radius:8px;

    text-decoration:none;

    font-size:14px;
    font-weight:bold;
}

.offer-btn:hover{
    background:#117239;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width:800px)
{
    .main{
        margin-left:70px;
        padding:100px 20px 50px;
    }

    .wanted-card{
        max-width:100%;
    }
}

@media(max-width:550px)
{
    .wanted-card{
        padding:20px;
    }

    .wanted-image,
    .no-image{
        height:220px;
    }
}

</style>

</head>


<body>


<?php include("header_sidebar.php"); ?>


<main class="main">


<!-- =========================
     MESSAGE
========================= -->

<?php if($msg === "offer_sent"): ?>

    <div class="alert alert-success">
        Offer sent successfully.
    </div>

<?php elseif($msg === "duplicate_offer"): ?>

    <div class="alert alert-error">
        You have already made an offer for this wanted post.
    </div>

<?php elseif($msg === "own_wanted"): ?>

    <div class="alert alert-error">
        You cannot offer on your own wanted post.
    </div>

<?php elseif($msg === "item_not_owned"): ?>

    <div class="alert alert-error">
        You can only offer your own item.
    </div>

<?php elseif($msg === "offer_failed"): ?>

    <div class="alert alert-error">
        Offer failed. Please try again.
    </div>

<?php elseif($msg === "invalid_offer"): ?>

    <div class="alert alert-error">
        Invalid offer data.
    </div>

<?php elseif($msg === "missing_price"): ?>

    <div class="alert alert-error">
        Please enter a valid price.
    </div>

<?php elseif($msg === "missing_payment"): ?>

    <div class="alert alert-error">
        Please select a payment method.
    </div>

<?php elseif($msg === "missing_payment_number"): ?>

    <div class="alert alert-error">
        Please enter your payment number.
    </div>

<?php elseif($msg === "wanted_not_found"): ?>

    <div class="alert alert-error">
        Wanted post not found.
    </div>

<?php endif; ?>


<!-- =========================
     BACK
========================= -->

<a
    href="<?php echo htmlspecialchars($backPage); ?>"
    class="back"
>
    ← Back
</a>


<!-- =========================
     PAGE HEADER
========================= -->

<div class="page-header">

    <h1>
        Wanted Post Details
    </h1>

    <p>
        Details of the requested item.
    </p>

</div>


<!-- =========================
     WANTED CARD
========================= -->

<div class="wanted-card">


<!-- =========================
     IMAGE
========================= -->

<?php

if(
    !empty($wanted["image"]) &&
    file_exists("uploads/".$wanted["image"])
)
{

?>

    <img
        src="uploads/<?php
            echo htmlspecialchars(
                $wanted["image"]
            );
        ?>"
        class="wanted-image"
        alt="Wanted Item"
    >

<?php

}
else
{

?>

    <div class="no-image">
        No Image
    </div>

<?php

}

?>


<!-- =========================
     TITLE
========================= -->

<div class="wanted-title">

<?php

echo htmlspecialchars(
    $wanted["title"]
);

?>

</div>


<!-- =========================
     CATEGORY
========================= -->

<div class="detail">

<strong>
    Category:
</strong>

<?php

echo htmlspecialchars(
    $wanted["category"]
);

?>

</div>


<!-- =========================
     LOCATION
========================= -->

<div class="detail">

<strong>
    Preferred Location:
</strong>

<?php

echo htmlspecialchars(
    $wanted["location"]
);

?>

</div>


<!-- =========================
     DESCRIPTION
========================= -->

<?php

if(
    isset($wanted["description"]) &&
    trim($wanted["description"]) !== ""
)
{

?>

<div class="detail">

<strong>
    Description / Requirements:
</strong>

<br>

<?php

echo nl2br(
    htmlspecialchars(
        $wanted["description"]
    )
);

?>

</div>

<?php

}

?>


<!-- =========================
     STATUS
========================= -->

<div class="detail">

<strong>
    Status:
</strong>

<span class="status">

<?php

echo htmlspecialchars(
    $wanted["status"]
);

?>

</span>

</div>


<!-- =================================================
     OWNER / OTHER USER SECTION
================================================= -->

<?php if($is_owner === true): ?>


    <!-- =========================
         MY OWN WANTED POST
    ========================= -->

    <div class="owner-note">

        This is your wanted post.

    </div>


<?php elseif($is_owner === false): ?>


    <!-- =========================
         OTHER USER'S WANTED POST
    ========================= -->

    <?php if($existing_offer_id > 0): ?>


        <!-- =========================
             ALREADY OFFERED
        ========================= -->

        <div class="owner-note">

            You have already made an offer
            for this wanted post.

            <br><br>

            <a
                href="view_offer.php?offer_id=<?php
                    echo $existing_offer_id;
                ?>"
            >
                View your offer
            </a>

        </div>


    <?php else: ?>


        <!-- =========================
             OFFER MY ITEM
        ========================= -->

        <a
            href="offer_wanted.php?wanted_id=<?php
                echo $wanted_id;
            ?>"
            class="offer-btn"
        >
            Offer My Item
        </a>


    <?php endif; ?>


<?php endif; ?>


</div>


</main>


</body>

</html>