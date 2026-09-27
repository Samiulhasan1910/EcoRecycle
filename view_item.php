<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = (int)$_SESSION["user_id"];


// =====================================================
// GET ITEM ID
// =====================================================

if(
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
)
{
    header("Location: browse_items.php");
    exit();
}

$item_id = (int)$_GET["id"];

if($item_id <= 0)
{
    header("Location: browse_items.php");
    exit();
}


// =====================================================
// GET BACK SOURCE
// =====================================================

$back = "";

if(isset($_GET["back"]))
{
    $back = trim($_GET["back"]);
}


// =====================================================
// GET ITEM
// =====================================================

$sql = "
    SELECT *
    FROM items
    WHERE item_id = {$item_id}
    LIMIT 1
";

$result = mysqli_query(
    $conn,
    $sql
);

if(!$result)
{
    die(
        "ITEM ERROR: "
        . mysqli_error($conn)
    );
}

if(mysqli_num_rows($result) == 0)
{
    header("Location: browse_items.php");
    exit();
}

$item = mysqli_fetch_assoc($result);


// =====================================================
// BASIC ITEM DATA
// =====================================================

$owner_id = (int)$item["user_id"];

$isOwner = ($owner_id === $user_id);

$itemType = strtolower(
    trim($item["type"] ?? "")
);

$itemStatus = strtolower(
    trim($item["status"] ?? "")
);

// A donation addressed to an NGO is private to that NGO and its donor.
if(
    !$isOwner
    && $itemType === "donate"
    && !empty($item["ngo_id"])
)
{
    header("Location: browse_items.php");
    exit();
}


// =====================================================
// GET USER'S LATEST DONATE REQUEST
// =====================================================

$requestStatus = "";

$request_id = 0;

if(
    !$isOwner &&
    $itemType === "donate"
)
{

    $requestSql = "
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

    $requestResult = mysqli_query(
        $conn,
        $requestSql
    );

    if(!$requestResult)
    {
        die(
            "REQUEST STATUS ERROR: "
            . mysqli_error($conn)
        );
    }


if(mysqli_num_rows($requestResult) > 0)
{

        $requestRow =
            mysqli_fetch_assoc(
                $requestResult
            );


        $request_id =
            (int)$requestRow["request_id"];


        $requestStatus =
            strtolower(
                trim(
                    $requestRow["status"] ?? ""
                )
            );

    }

}


// =====================================================
// DONATED ITEMS ARE NOT PUBLIC
//
// Dashboard and Browse already list only Available items.
// This also protects a copied/direct item URL after a donation
// has been accepted. The owner can still see it in My Items and
// the accepted requester can still open it from My Requests.
// =====================================================

if(
    $itemType === "donate" &&
    $itemStatus === "donated" &&
    !$isOwner &&
    strtolower($requestStatus) !== "accepted"
)
{
    header("Location: browse_items.php");
    exit();
}


// =====================================================
// BACK LINK
// =====================================================

/*
=========================================================
IF OPENED FROM MY REQUESTS

BACK -> my_requests.php

IF OPENED FROM NORMAL BROWSE

BACK -> browse_items.php

IF OWNER

BACK -> owner's item page
=========================================================
*/

if($back === "my_requests")
{
    $backLink = "my_requests.php";
    $backText = "← Back to My Requests";
}
elseif($isOwner)
{

    if($itemType === "sell")
    {
        $backLink = "sell_items.php";
    }
    elseif($itemType === "donate")
    {
        $backLink = "donate_items.php";
    }
    else
    {
        $backLink = "my_items.php";
    }

    $backText = "← Back";

}
else
{
    $backLink = "browse_items.php";
    $backText = "← Back";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>

<?php

echo htmlspecialchars(
    $item["title"] ?? "View Item"
);

?>

| EcoCycle

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
    margin-bottom:20px;
    text-decoration:none;
    color:#168a45;
    font-size:14px;
    font-weight:bold;
}

.back:hover{
    text-decoration:underline;
}

.item-card{
    background:#ffffff;
    border:1px solid #ddd;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 5px 20px rgba(0,0,0,0.05);
    max-width:700px;
}

.image-area{
    width:100%;
    height:360px;
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
    font-size:26px;
    font-weight:bold;
    color:#222;
    margin-bottom:22px;
}

.item-detail{
    font-size:14px;
    color:#666;
    margin-bottom:13px;
}

.item-detail strong{
    color:#222;
}

.price{
    color:#168a45;
    font-weight:bold;
}

.action-box{
    margin-top:24px;
    padding:20px;
    border-radius:9px;
    background:#f5faf7;
    border:1px solid #dceee3;
}

.action-box h3{
    margin:0 0 8px;
    font-size:17px;
}

.action-box p{
    margin:0;
    font-size:13px;
    color:#666;
    line-height:1.6;
}

.pending{
    background:#fff8e6;
    border-color:#f0dfa8;
}

.pending h3{
    color:#a87500;
}

.accepted{
    background:#eaf7ef;
    border-color:#cdebd8;
}

.accepted h3{
    color:#168a45;
}

.rejected{
    background:#fdecec;
    border-color:#f5c2c0;
}

.rejected h3{
    color:#d93025;
}

.owner-box{
    margin-top:24px;
    padding:20px;
    background:#f5f5f5;
    border:1px solid #ddd;
    border-radius:9px;
}

.owner-box h3{
    margin:0 0 8px;
    font-size:17px;
}

.owner-box p{
    margin:0;
    font-size:13px;
    color:#666;
    line-height:1.6;
}

.action-btn{
    display:inline-block;
    background:#168a45;
    color:white;
    padding:10px 20px;
    border:none;
    border-radius:7px;
    text-decoration:none;
    font-size:13px;
    font-weight:bold;
    cursor:pointer;
}

.action-btn:hover{
    background:#117239;
}

@media(max-width:750px)
{
    .main{
        margin-left:70px;
        padding:100px 20px 40px;
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
     BACK BUTTON
================================================= -->

<a
    href="<?php echo htmlspecialchars($backLink); ?>"
    class="back"
>

<?php

echo htmlspecialchars($backText);

?>

</a>


<div class="item-card">


<!-- =================================================
     IMAGE
================================================= -->

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


<!-- =================================================
     ITEM CONTENT
================================================= -->

<div class="item-content">


<div class="item-title">

<?php

echo htmlspecialchars(
    $item["title"] ?? "Untitled Item"
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

<?php

if($itemType === "sell")
{

?>

<span class="price">

<?php

echo htmlspecialchars(
    $item["price"] ?? "0"
);

?>

</span>

<?php

}
else
{

?>

<span class="price">
    Free
</span>

<?php

}

?>

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

<strong>Item Status:</strong>

<?php

echo htmlspecialchars(
    $item["status"] ?? ""
);

?>

</div>


<!-- =================================================
     OWNER
================================================= -->

<?php

if($isOwner)
{

?>

<div class="owner-box">


<?php

if($itemType === "sell")
{

?>

<h3>
    Your Sell Item
</h3>

<p>
    This is your own sell item.
</p>

<?php

}
elseif($itemType === "donate")
{

?>

<h3>
    Your Donation Item
</h3>

<p>
    This is your own donated item.
    Other users can request it.
</p>

<?php

}
else
{

?>

<h3>
    Your Item
</h3>

<p>
    This item belongs to you.
</p>

<?php

}

?>


</div>

<?php

}

?>


<!-- =================================================
     SELL
================================================= -->

<?php

if(
    !$isOwner &&
    $itemType === "sell"
)
{

?>

<div class="action-box">


<?php

if($itemStatus === "available")
{

?>

<h3>
    Buy This Item
</h3>

<p>
    This item is available for purchase.
</p>

<br>


<a
    href="payment.php?item_id=<?php echo $item_id; ?>"
    class="action-btn"
>
    Buy Now
</a>


<?php

}
else
{

?>

<h3>
    Item Not Available
</h3>

<p>
    This item is currently not available.
</p>

<?php

}

?>


</div>

<?php

}

?>


<!-- =================================================
     DONATE
================================================= -->

<?php

if(
    !$isOwner &&
    $itemType === "donate"
)
{


// =====================================================
// PENDING
// =====================================================

if($requestStatus === "pending")
{

?>

<div class="action-box pending">

<h3>
    Request Pending
</h3>

<p>
    Your request has been sent to the owner.
    Please wait for the owner to respond.
</p>

</div>

<?php

}


// =====================================================
// ACCEPTED
// =====================================================

elseif($requestStatus === "accepted")
{

?>

<div class="action-box accepted">

<h3>
    Request Accepted
</h3>

<p>
    Your request for this donated item
    has been accepted by the owner.
</p>

</div>

<?php

}


// =====================================================
// REJECTED
// =====================================================

elseif($requestStatus === "rejected")
{

?>

<div class="action-box rejected">

<h3>
    Request Rejected
</h3>

<p>
    Your request for this donated item
    was rejected by the owner.
    You cannot request this item again.
</p>

</div>

<?php

}


// =====================================================
// NO REQUEST
// =====================================================

else
{

if($itemStatus === "available")
{

?>

<div class="action-box">

<h3>
    Request This Item
</h3>

<p>
    This item is available for donation.
    You can send a request to the owner.
</p>

<br>


<form
    action="request_item.php"
    method="POST"
    onsubmit="return confirm('Are you sure you want to request this item?');"
>


<input
    type="hidden"
    name="item_id"
    value="<?php echo $item_id; ?>"
>


<button
    type="submit"
    class="action-btn"
>
    Request Item
</button>


</form>


</div>

<?php

}
else
{

?>

<div class="action-box">

<h3>
    Item Not Available
</h3>

<p>
    This donation item is currently not available.
</p>

</div>

<?php

}

}

}

?>


</div>

</div>

</main>

</body>

</html>
