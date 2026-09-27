<?php

session_start();

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}

include("db.php");

$name = $_SESSION["name"] ?? "User";

?>

<!DOCTYPE html>
<html>

<head>

<title>My Items | EcoCycle</title>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<link rel="stylesheet" href="style.css">


<style>

/* =========================
   MAIN
   ========================= */

.main{
    margin-left:230px;

    padding:105px 45px 50px;

    max-width:1200px;
}


/* =========================
   HEADER
   ========================= */

.header{
    margin-top:0 !important;

    padding-top:0 !important;

    margin-bottom:25px;
}

.header h1{
    font-size:28px;

    margin-top:0 !important;

    margin-bottom:7px;

    padding-top:0 !important;
}

.header p{
    color:#777;

    font-size:14px;

    margin-top:0;
}


/* =========================
   BOXES
   ========================= */

.boxes{
    display:grid;

    grid-template-columns:repeat(3,1fr);

    gap:25px;
}

.box{
    background:white;

    border:1px solid #e5e5e5;

    border-radius:14px;

    padding:45px 25px;

    text-align:center;

    text-decoration:none;

    color:#222;

    transition:0.2s;
}

.box:hover{
    border-color:#168a45;

    box-shadow:
        0 7px 20px rgba(0,0,0,0.08);

    transform:translateY(-3px);
}


/* =========================
   ICON
   ========================= */

.icon{
    font-size:42px;

    margin-bottom:18px;
}


/* =========================
   BOX TITLE
   ========================= */

.box h2{
    font-size:22px;

    margin-top:0;

    margin-bottom:8px;
}


/* =========================
   BOX TEXT
   ========================= */

.box p{
    color:#777;

    font-size:13px;

    margin-top:0;
}


/* =========================
   RESPONSIVE
   ========================= */

@media(max-width:800px)
{
    .boxes{
        grid-template-columns:1fr;
    }
}

@media(max-width:750px)
{
    .main{
        margin-left:70px;

        padding-left:20px;

        padding-right:20px;
    }
}

</style>

</head>


<body>


<?php include("header_sidebar.php"); ?>


<main class="main">


    <div class="header">

        <h1>
            My Items
        </h1>

        <p>
            Select an option to manage your posts.
        </p>

    </div>


    <div class="boxes">


        <!-- SELL -->

        <a
            href="sell_items.php"
            class="box"
        >

            <div class="icon">
                💰
            </div>

            <h2>
                Sell
            </h2>

            <p>
                View items you posted for sale
            </p>

        </a>


        <!-- DONATE -->

        <a
            href="donate_items.php"
            class="box"
        >

            <div class="icon">
                🎁
            </div>

            <h2>
                Donate
            </h2>

            <p>
                View items you posted for donation
            </p>

        </a>


        <!-- WANTED -->

        <a
            href="wanted_items.php"
            class="box"
        >

            <div class="icon">
                📋
            </div>

            <h2>
                Wanted
            </h2>

            <p>
                View your wanted posts
            </p>

        </a>


    </div>


</main>


</body>

</html>