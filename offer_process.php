<?php

session_start();


/* =========================
   LOGIN CHECK
   ========================= */

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}


include("db.php");


$user_id = (int)$_SESSION["user_id"];


/* =========================
   GET FORM DATA
   ========================= */

$wanted_id = isset($_POST["wanted_id"])
    ? (int)$_POST["wanted_id"]
    : 0;

$item_id = isset($_POST["item_id"])
    ? (int)$_POST["item_id"]
    : 0;

$is_free = isset($_POST["is_free"])
    ? 1
    : 0;

$price = trim(
    $_POST["price"] ?? ""
);

$payment_method = trim(
    $_POST["payment_method"] ?? ""
);

$payment_number = trim(
    $_POST["payment_number"] ?? ""
);


/* =========================
   VALIDATE IDS
   ========================= */

if($wanted_id <= 0 || $item_id <= 0)
{
    header(
        "Location: view_wanted.php?id="
        .$wanted_id
        ."&msg=invalid_offer"
    );

    exit();
}


/* =========================
   GET WANTED POST
   ========================= */

$wantedQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM wanted_posts
     WHERE wanted_id='$wanted_id'
     LIMIT 1"
);


if(
    !$wantedQuery ||
    mysqli_num_rows($wantedQuery) == 0
)
{
    header(
        "Location: wanted_items.php?msg=wanted_not_found"
    );

    exit();
}


$wanted = mysqli_fetch_assoc(
    $wantedQuery
);


/* =========================
   OWNER CANNOT OFFER
   ========================= */

if($wanted["user_id"] == $user_id)
{
    header(
        "Location: view_wanted.php?id="
        .$wanted_id
        ."&msg=own_wanted"
    );

    exit();
}


/* =========================
   DUPLICATE OFFER CHECK
   ========================= */

$duplicateQuery = mysqli_query(
    $conn,
    "SELECT offer_id
     FROM wanted_offers
     WHERE wanted_id='$wanted_id'
     AND offerer_user_id='$user_id'
     LIMIT 1"
);


if(
    $duplicateQuery &&
    mysqli_num_rows($duplicateQuery) > 0
)
{
    header(
        "Location: view_wanted.php?id="
        .$wanted_id
        ."&msg=duplicate_offer"
    );

    exit();
}


/* =========================
   VERIFY ITEM BELONGS TO USER
   ========================= */

$itemQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM items
     WHERE item_id='$item_id'
     AND user_id='$user_id'
     LIMIT 1"
);


if(
    !$itemQuery ||
    mysqli_num_rows($itemQuery) == 0
)
{
    header(
        "Location: view_wanted.php?id="
        .$wanted_id
        ."&msg=item_not_owned"
    );

    exit();
}


$item = mysqli_fetch_assoc(
    $itemQuery
);


/* =========================
   PAYMENT VALIDATION
   ========================= */

if($is_free)
{
    $price_sql = "NULL";

    $method_sql = "NULL";

    $number_sql = "NULL";
}
else
{

    if(
        $price === "" ||
        !is_numeric($price) ||
        (float)$price <= 0
    )
    {
        header(
            "Location: offer_wanted.php?wanted_id="
            .$wanted_id
            ."&msg=missing_price"
        );

        exit();
    }


    if($payment_method === "")
    {
        header(
            "Location: offer_wanted.php?wanted_id="
            .$wanted_id
            ."&msg=missing_payment"
        );

        exit();
    }


    if($payment_number === "")
    {
        header(
            "Location: offer_wanted.php?wanted_id="
            .$wanted_id
            ."&msg=missing_payment_number"
        );

        exit();
    }


    $price_safe = mysqli_real_escape_string(
        $conn,
        $price
    );

    $payment_method_safe =
        mysqli_real_escape_string(
            $conn,
            $payment_method
        );

    $payment_number_safe =
        mysqli_real_escape_string(
            $conn,
            $payment_number
        );


    $price_sql =
        "'" . $price_safe . "'";

    $method_sql =
        "'" . $payment_method_safe . "'";

    $number_sql =
        "'" . $payment_number_safe . "'";
}


/* =========================
   INSERT OFFER
   ========================= */

$insertSql =
    "INSERT INTO wanted_offers
    (
        wanted_id,
        offerer_user_id,
        item_id,
        price,
        is_free,
        payment_method,
        payment_number,
        status,
        payment_visible
    )
    VALUES
    (
        '$wanted_id',
        '$user_id',
        '$item_id',
        $price_sql,
        '$is_free',
        $method_sql,
        $number_sql,
        'Pending',
        0
    )";


$insertResult = mysqli_query(
    $conn,
    $insertSql
);


if(!$insertResult)
{
    header(
        "Location: view_wanted.php?id="
        .$wanted_id
        ."&msg=offer_failed"
    );

    exit();
}


/* =========================
   GET OFFER ID
   ========================= */

$offer_id = mysqli_insert_id(
    $conn
);


/* =========================
   NOTIFY WANTED POST OWNER
   ========================= */

$owner_id = (int)$wanted["user_id"];


/* Get offerer's name */

$nameQuery = mysqli_query(
    $conn,
    "SELECT name
     FROM users
     WHERE user_id='$user_id'
     LIMIT 1"
);


if(
    $nameQuery &&
    mysqli_num_rows($nameQuery) > 0
)
{
    $nameRow =
        mysqli_fetch_assoc(
            $nameQuery
        );

    $offerer_name =
        $nameRow["name"];
}
else
{
    $offerer_name =
        "Someone";
}


/* Notification message */

$messageText =
    $offerer_name .
    " offered an item for your wanted post: " .
    $wanted["title"];


$message =
    mysqli_real_escape_string(
        $conn,
        $messageText
    );


/* Insert notification */

$notificationSql =
    "INSERT INTO notifications
    (
        user_id,
        request_id,
        notification_type,
        message,
        status
    )
    VALUES
    (
        '$owner_id',
        '$offer_id',
        'wanted_offer',
        '$message',
        'Unread'
    )";


mysqli_query(
    $conn,
    $notificationSql
);


/* =========================
   SUCCESS
   ========================= */

header(
    "Location: view_wanted.php?id="
    .$wanted_id
    ."&msg=offer_sent"
);

exit();

?>