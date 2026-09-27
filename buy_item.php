<?php

session_start();

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = (int)$_SESSION["user_id"];

$item_id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;

if($item_id <= 0)
{
    header("Location: browse_items.php");
    exit();
}


/* =====================================================
   GET SELL ITEM
===================================================== */

$sql = "
    SELECT *
    FROM items
    WHERE item_id = {$item_id}
    LIMIT 1
";

$result = mysqli_query($conn, $sql);

if(!$result)
{
    die("Database Error: " . mysqli_error($conn));
}

if(mysqli_num_rows($result) == 0)
{
    header("Location: browse_items.php");
    exit();
}

$item = mysqli_fetch_assoc($result);


/* =====================================================
   ONLY SELL ITEM
===================================================== */

if(strtolower(trim($item["type"] ?? "")) !== "sell")
{
    header("Location: view_item.php?id={$item_id}");
    exit();
}


/* =====================================================
   OWNER CANNOT BUY OWN ITEM
===================================================== */

if((int)$item["user_id"] === $user_id)
{
    header("Location: view_item.php?id={$item_id}");
    exit();
}


/* =====================================================
   ITEM MUST BE AVAILABLE
===================================================== */

if(strtolower(trim($item["status"] ?? "")) !== "available")
{
    header("Location: view_item.php?id={$item_id}");
    exit();
}


/* =====================================================
   PRICE CHECK
===================================================== */

$price = floatval($item["price"] ?? 0);

if($price <= 0)
{
    header("Location: view_item.php?id={$item_id}");
    exit();
}


/* =====================================================
   PAYMENT PAGE
===================================================== */

header(
    "Location: payment.php?item_id=" . $item_id
);

exit();

?>