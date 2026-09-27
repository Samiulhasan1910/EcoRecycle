
<?php

session_start();

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = $_SESSION["user_id"];
$item_id = $_GET["id"] ?? "";

if($item_id == "")
{
    header("Location: my_items.php");
    exit();
}


/* =========================
   DELETE ITEM
   ========================= */

$sql = "DELETE FROM items
        WHERE item_id='$item_id'
        AND user_id='$user_id'";


if(mysqli_query($conn, $sql))
{
    header("Location: my_items.php?deleted=1");
    exit();
}
else
{
    header("Location: my_items.php?deleted=0");
    exit();
}

?>
