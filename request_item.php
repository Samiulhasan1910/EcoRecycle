<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();


// =====================================================
// LOGIN CHECK
// =====================================================

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}


include("db.php");


$user_id = (int)$_SESSION["user_id"];

$message = "";


// =====================================================
// GET ITEM ID
// =====================================================

$item_id = 0;


if(
    isset($_POST["item_id"]) &&
    is_numeric($_POST["item_id"])
)
{
    $item_id = (int)$_POST["item_id"];
}
elseif(
    isset($_GET["id"]) &&
    is_numeric($_GET["id"])
)
{
    $item_id = (int)$_GET["id"];
}


if($item_id <= 0)
{
    header("Location: browse_items.php");
    exit();
}


// =====================================================
// ONLY POST REQUEST
// =====================================================

if($_SERVER["REQUEST_METHOD"] !== "POST")
{
    header(
        "Location: view_item.php?id=".$item_id
    );

    exit();
}


// =====================================================
// GET ITEM
// =====================================================

$itemSql = "
    SELECT *
    FROM items
    WHERE item_id = {$item_id}
    LIMIT 1
";


$itemResult = mysqli_query(
    $conn,
    $itemSql
);


if(!$itemResult)
{
    die(
        "ITEM DATABASE ERROR: "
        . mysqli_error($conn)
    );
}


if(mysqli_num_rows($itemResult) == 0)
{
    header("Location: browse_items.php");
    exit();
}


$item = mysqli_fetch_assoc(
    $itemResult
);

if(
    strtolower(trim($item["type"] ?? "")) === "donate"
    && !empty($item["ngo_id"])
)
{
    header("Location: browse_items.php");
    exit();
}


// =====================================================
// GET OWNER
// =====================================================

$owner_id = (int)$item["user_id"];


// =====================================================
// OWNER CANNOT REQUEST OWN ITEM
// =====================================================

if($owner_id === $user_id)
{
    header(
        "Location: view_item.php?id=".$item_id
    );

    exit();
}


// =====================================================
// CHECK ITEM TYPE
// =====================================================

$itemType = strtolower(
    trim(
        $item["type"] ?? ""
    )
);


if($itemType !== "donate")
{
    header(
        "Location: view_item.php?id=".$item_id
    );

    exit();
}


// =====================================================
// CHECK ITEM STATUS
// =====================================================

$itemStatus = strtolower(
    trim(
        $item["status"] ?? ""
    )
);


if($itemStatus !== "available")
{
    header(
        "Location: view_item.php?id=".$item_id
    );

    exit();
}


// =====================================================
// CHECK PREVIOUS REQUEST
// =====================================================

$checkSql = "
    SELECT
        request_id,
        status

    FROM requests

    WHERE item_id = {$item_id}

    AND user_id = {$user_id}

    AND request_type = 'Donate'

    ORDER BY request_id DESC

    LIMIT 1
";


$checkResult = mysqli_query(
    $conn,
    $checkSql
);


if(!$checkResult)
{
    die(
        "REQUEST CHECK ERROR: "
        . mysqli_error($conn)
    );
}


// =====================================================
// IF PREVIOUS REQUEST EXISTS
// =====================================================

if(mysqli_num_rows($checkResult) > 0)
{

    $oldRequest =
        mysqli_fetch_assoc(
            $checkResult
        );


    $oldStatus = strtolower(
        trim(
            $oldRequest["status"] ?? ""
        )
    );


    // =================================================
    // PENDING
    // =================================================

    if($oldStatus === "pending")
    {
        $message =
            "You have already sent a request for this item.";
    }


    // =================================================
    // ACCEPTED
    // =================================================

    elseif($oldStatus === "accepted")
    {
        $message =
            "Your request for this item has already been accepted.";
    }


    // =================================================
    // REJECTED
    // =================================================

    elseif($oldStatus === "rejected")
    {
        $message =
            "Your previous request for this item was rejected by the owner. You cannot request this item again.";
    }


    // =================================================
    // ANY OTHER STATUS
    // =================================================

    else
    {
        $message =
            "You already have a request for this item.";
    }

}


// =====================================================
// CREATE NEW REQUEST
// =====================================================

if($message === "")
{

    $insertSql = "
        INSERT INTO requests
        (
            user_id,
            item_id,
            ngo_id,
            request_type,
            status
        )

        VALUES
        (
            {$user_id},
            {$item_id},
            NULL,
            'Donate',
            'Pending'
        )
    ";


    $insertResult = mysqli_query(
        $conn,
        $insertSql
    );


    if(!$insertResult)
    {
        die(
            "REQUEST INSERT ERROR: "
            . mysqli_error($conn)
        );
    }


    // =================================================
    // GET NEW REQUEST ID
    // IMPORTANT:
    // MUST BE IMMEDIATELY AFTER INSERT
    // =================================================

    $request_id =
        (int)mysqli_insert_id($conn);


    if($request_id <= 0)
    {
        die(
            "REQUEST ID ERROR: "
            . "New request ID could not be generated."
        );
    }


    // =================================================
    // GET REQUESTER NAME
    // =================================================

    $requesterName =
        $_SESSION["name"] ?? "Someone";


    // =================================================
    // GET ITEM TITLE
    // =================================================

    $itemTitle =
        $item["title"] ?? "your donated item";


    // =================================================
    // CREATE NOTIFICATION MESSAGE
    // =================================================

    $notificationMessage =
        $requesterName
        . " has requested your donated item: "
        . $itemTitle
        . ".";


    $notificationMessageSafe =
        mysqli_real_escape_string(
            $conn,
            $notificationMessage
        );


    // =================================================
    // INSERT OWNER NOTIFICATION
    // =================================================

    $notificationSql = "
        INSERT INTO notifications
        (
            user_id,
            request_id,
            notification_type,
            message,
            status
        )

        VALUES
        (
            {$owner_id},
            {$request_id},
            'request',
            '{$notificationMessageSafe}',
            'Unread'
        )
    ";


    $notificationResult = mysqli_query(
        $conn,
        $notificationSql
    );


    if(!$notificationResult)
    {
        die(
            "NOTIFICATION INSERT ERROR: "
            . mysqli_error($conn)
        );
    }


    // =================================================
    // SUCCESS MESSAGE
    // =================================================

    $message =
        "Request sent successfully!";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>
Request Item | EcoCycle
</title>


<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>


<link
    rel="stylesheet"
    href="style.css"
>


<style>

.main{
    margin-left:230px;
    padding:105px 45px 50px;
    max-width:1000px;
}

.back{
    display:inline-block;
    margin-bottom:18px;
    text-decoration:none;
    color:#168a45;
    font-size:14px;
    font-weight:bold;
}

.back:hover{
    text-decoration:underline;
}


.message{
    max-width:650px;
    padding:13px 16px;
    border-radius:8px;
    margin-bottom:18px;
    font-size:14px;
    font-weight:bold;
}


.success-message{
    background:#eaf7ef;
    color:#168a45;
    border:1px solid #cdebd8;
}


.warning-message{
    background:#fff8e6;
    color:#a87500;
    border:1px solid #f0dfa8;
}


.item-card{
    background:white;
    border:1px solid #ddd;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 5px 20px rgba(0,0,0,0.05);
    max-width:650px;
}


.image-area{
    width:100%;
    height:350px;
    background:#f1f3f2;
    display:flex;
    align-items:center;
    justify-content:center;
}


.item-image{
    width:100%;
    height:100%;
    object-fit:contain;
}


.no-image{
    color:#888;
    font-size:14px;
}


.item-content{
    padding:28px;
}


.item-title{
    font-size:25px;
    font-weight:bold;
    margin-bottom:22px;
}


.item-detail{
    font-size:14px;
    color:#666;
    margin-bottom:13px;
    line-height:1.5;
}


.item-detail strong{
    color:#222;
}


.price{
    color:#168a45;
    font-weight:bold;
}


.action-box{
    margin-top:22px;
    padding:18px;
    background:#f5faf7;
    border:1px solid #dceee3;
    border-radius:8px;
}


.action-box h3{
    font-size:16px;
    margin:0 0 8px;
}


.action-box p{
    font-size:13px;
    color:#666;
    margin:0 0 15px;
    line-height:1.6;
}


.action-btn{
    display:inline-block;
    background:#168a45;
    color:white;
    padding:10px 20px;
    border-radius:7px;
    text-decoration:none;
    font-size:13px;
    font-weight:bold;
    border:none;
    cursor:pointer;
}


.action-btn:hover{
    background:#117239;
}


@media(max-width:750px)
{
    .main{
        margin-left:70px;
        padding-left:20px;
        padding-right:20px;
    }
}


@media(max-width:550px)
{
    .image-area{
        height:260px;
    }


    .item-content{
        padding:20px;
    }


    .item-title{
        font-size:22px;
    }
}

</style>

</head>


<body>


<?php include("header_sidebar.php"); ?>


<main class="main">


<!-- =================================================
     BACK TO ITEM
================================================= -->

<a
    href="view_item.php?id=<?php echo $item_id; ?>"
    class="back"
>
    ← Back to Item
</a>


<!-- =================================================
     MESSAGE
================================================= -->

<?php

if($message !== "")
{

    if(
        $message ===
        "Request sent successfully!"
    )
    {

?>

<div class="message success-message">

<?php

echo htmlspecialchars(
    $message
);

?>

</div>

<?php

    }
    else
    {

?>

<div class="message warning-message">

<?php

echo htmlspecialchars(
    $message
);

?>

</div>

<?php

    }

}

?>


<!-- =================================================
     ITEM CARD
================================================= -->

<div class="item-card">


<!-- IMAGE -->

<div class="image-area">

<?php

if(
    !empty($item["image"]) &&
    file_exists(
        "uploads/" . $item["image"]
    )
)
{

?>

<img
    src="uploads/<?php
        echo htmlspecialchars(
            $item["image"]
        );
    ?>"
    class="item-image"
    alt="Item Image"
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

</div>


<!-- ITEM CONTENT -->

<div class="item-content">


<div class="item-title">

<?php

echo htmlspecialchars(
    $item["title"] ?? "Item"
);

?>

</div>


<div class="item-detail">

<strong>Category:</strong>

<?php

echo htmlspecialchars(
    $item["category"] ?? ""
);

?>

</div>


<div class="item-detail">

<strong>Type:</strong>

<?php

echo htmlspecialchars(
    $item["type"] ?? ""
);

?>

</div>


<div class="item-detail">

<strong>Price:</strong>

<span class="price">
    Free
</span>

</div>


<div class="item-detail">

<strong>Location:</strong>

<?php

echo htmlspecialchars(
    $item["location"] ?? ""
);

?>

</div>


<div class="item-detail">

<strong>Status:</strong>

<?php

echo htmlspecialchars(
    $item["status"] ?? ""
);

?>

</div>


<!-- =================================================
     RESULT BOX
================================================= -->

<?php

if(
    $message ===
    "Request sent successfully!"
)
{

?>

<div class="action-box">

<h3>
    Request Sent Successfully
</h3>


<p>

Your request has been sent to the item owner.

You can check your request status from
<strong>My Requests</strong>.

</p>


<a
    href="my_requests.php"
    class="action-btn"
>
    Go to My Requests
</a>


</div>

<?php

}
else
{

?>

<div class="action-box">

<h3>
    Request Status
</h3>


<p>

<?php

echo htmlspecialchars(
    $message
);

?>

</p>


<a
    href="view_item.php?id=<?php echo $item_id; ?>"
    class="action-btn"
>
    Back to Item
</a>


</div>

<?php

}

?>


</div>

</div>

</main>

</body>

</html>
