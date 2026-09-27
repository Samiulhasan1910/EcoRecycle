<?php

session_start();

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = $_SESSION["user_id"];
$wanted_id = $_GET["id"] ?? "";

if($wanted_id == "")
{
    header("Location: wanted_items.php?deleted=0");
    exit();
}

$sql = "DELETE FROM wanted_posts
        WHERE wanted_id='$wanted_id'
        AND user_id='$user_id'";

if(mysqli_query($conn,$sql))
{
    header("Location: wanted_items.php?deleted=1");
    exit();
}
else
{
    header("Location: wanted_items.php?deleted=0");
    exit();
}

?>