<?php

session_start();

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = $_SESSION["user_id"];
$name = $_SESSION["name"] ?? "User";


/* =========================
   GET MY WANTED POSTS
   ========================= */

$sql = "SELECT *
        FROM wanted_posts
        WHERE user_id='$user_id'
        ORDER BY wanted_id DESC";

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>

<html>

<head>

<title>My Wanted Posts | EcoCycle</title>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>


<!-- COMMON NAVBAR + SIDEBAR CSS -->

<link
    rel="stylesheet"
    href="style.css"
>


<!-- =========================
     WANTED ITEMS PAGE CSS ONLY
     ========================= -->

<style>

/* =========================
   MAIN
   ========================= */

.main{
    margin-left:230px;

    padding:105px 45px 50px;

    max-width:1300px;
}


/* =========================
   BACK BUTTON
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

.header{
    margin-bottom:25px;
}

.header h1{
    font-size:28px;

    margin-bottom:6px;
}

.header p{
    color:#777;

    font-size:14px;
}


/* =========================
   MESSAGE
   ========================= */

.message{
    padding:12px;

    margin-bottom:20px;

    border-radius:7px;

    font-size:14px;

    font-weight:bold;
}

.message.success{
    background:#eaf7ef;

    color:#176b3b;

    border:1px solid #bde5cb;
}

.message.error{
    background:#fff0f0;

    color:#c62828;

    border:1px solid #ffd2d2;
}


/* =========================
   GRID
   ========================= */

.grid{
    display:grid;

    grid-template-columns:repeat(3,1fr);

    gap:20px;
}


/* =========================
   CARD
   ========================= */

.card{
    background:white;

    border:1px solid #e5e5e5;

    border-radius:12px;

    overflow:hidden;

    transition:0.2s;
}

.card:hover{
    border-color:#168a45;

    box-shadow:
        0 5px 15px rgba(0,0,0,0.06);
}


/* =========================
   IMAGE
   ========================= */

.image,
.no-image{
    width:100%;

    height:190px;
}

.image{
    object-fit:cover;

    display:block;
}

.no-image{
    background:#eef2ef;

    display:flex;

    align-items:center;

    justify-content:center;

    color:#888;

    font-size:13px;
}


/* =========================
   CONTENT
   ========================= */

.content{
    padding:18px;
}

.title{
    font-size:18px;

    font-weight:bold;

    margin-bottom:13px;
}

.detail{
    font-size:13px;

    color:#666;

    margin-bottom:8px;

    line-height:1.5;
}

.detail strong{
    color:#333;
}


/* =========================
   DESCRIPTION
   ========================= */

.description{
    font-size:13px;

    color:#666;

    margin-top:10px;

    margin-bottom:12px;

    line-height:1.5;
}

.description strong{
    color:#333;
}


/* =========================
   STATUS
   ========================= */

.status{
    display:inline-block;

    background:#eaf7ef;

    color:#168a45;

    padding:5px 10px;

    border-radius:20px;

    font-size:11px;

    font-weight:bold;

    margin-top:3px;
}


/* =========================
   BUTTONS
   ========================= */

.buttons{
    display:flex;

    gap:8px;

    margin-top:17px;
}

.buttons a{
    flex:1;

    text-align:center;

    padding:9px 5px;

    border-radius:6px;

    text-decoration:none;

    font-size:13px;

    font-weight:bold;
}

.view{
    background:#168a45;

    color:white;
}

.edit{
    background:#eee;

    color:#333;
}

.delete{
    background:#d93025;

    color:white;
}

.view:hover{
    background:#117239;
}

.edit:hover{
    background:#ddd;
}

.delete:hover{
    background:#b71c1c;
}


/* =========================
   EMPTY
   ========================= */

.empty{
    grid-column:1/-1;

    background:white;

    border:1px solid #e5e5e5;

    border-radius:12px;

    padding:50px;

    text-align:center;

    color:#777;
}


/* =========================
   RESPONSIVE
   ========================= */

@media(max-width:950px)
{
    .grid{
        grid-template-columns:repeat(2,1fr);
    }
}


@media(max-width:750px)
{
    .main{
        margin-left:70px;

        padding-left:20px;

        padding-right:20px;
    }

    .grid{
        grid-template-columns:1fr;
    }
}


@media(max-width:550px)
{
    .buttons{
        flex-direction:column;
    }
}

</style>

</head>


<body>


<!-- =========================
     COMMON HEADER + SIDEBAR
     ========================= -->

<?php include("header_sidebar.php"); ?>


<!-- =========================
     MAIN
     ========================= -->

<main class="main">


    <!-- BACK -->

    <a
        href="my_items.php"
        class="back"
    >

        ← Back

    </a>


    <!-- HEADER -->

    <div class="header">

        <h1>
            My Wanted Posts
        </h1>

        <p>
            Items you have posted as wanted.
        </p>

    </div>


    <!-- =========================
         DELETE MESSAGE
         ========================= -->

    <?php

    if(isset($_GET["deleted"]))
    {

        if($_GET["deleted"] == "1")
        {

    ?>

            <div class="message success">

                Deleted successfully!

            </div>

    <?php

        }
        else
        {

    ?>

            <div class="message error">

                Delete failed!

            </div>

    <?php

        }

    }

    ?>


    <!-- =========================
         WANTED POSTS GRID
         ========================= -->

    <div class="grid">


    <?php

    if(
        $result &&
        mysqli_num_rows($result) > 0
    )
    {

        while(
            $item = mysqli_fetch_assoc($result)
        )
        {

    ?>


        <!-- =========================
             CARD
             ========================= -->

        <div class="card">


            <!-- IMAGE -->

            <?php

            if(
                !empty($item["image"]) &&
                file_exists(
                    "uploads/".$item["image"]
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
                    class="image"
                    alt="Wanted Item Image"
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


            <!-- CONTENT -->

            <div class="content">


                <!-- TITLE -->

                <div class="title">

                    <?php

                    echo htmlspecialchars(
                        $item["title"]
                    );

                    ?>

                </div>


                <!-- CATEGORY -->

                <div class="detail">

                    <strong>
                        Category:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $item["category"]
                    );

                    ?>

                </div>


                <!-- LOCATION -->

                <div class="detail">

                    <strong>
                        Location:
                    </strong>

                    <?php

                    echo htmlspecialchars(
                        $item["location"]
                    );

                    ?>

                </div>


                <!-- DESCRIPTION -->

                <?php

                if(
                    isset($item["description"]) &&
                    trim($item["description"]) != ""
                )
                {

                ?>

                    <div class="description">

                        <strong>
                            Requirements:
                        </strong>

                        <?php

                        echo nl2br(
                            htmlspecialchars(
                                $item["description"]
                            )
                        );

                        ?>

                    </div>

                <?php

                }

                ?>


                <!-- STATUS -->

                <span class="status">

                    <?php

                    echo htmlspecialchars(
                        $item["status"]
                    );

                    ?>

                </span>


                <!-- BUTTONS -->

                <div class="buttons">


                    <!-- VIEW -->

                    <a
                        href="view_wanted.php?id=<?php
                        echo $item["wanted_id"];
                        ?>"
                        class="view"
                    >

                        View

                    </a>


                    <!-- EDIT -->

                    <?php if (strtolower(trim($item["status"] ?? "")) !== "accepted"): ?>
                        <a
                            href="edit_wanted.php?id=<?php
                            echo $item["wanted_id"];
                            ?>"
                            class="edit"
                        >

                            Edit

                        </a>
                    <?php endif; ?>


                    <!-- DELETE -->

                    <a
                        href="delete_wanted.php?id=<?php
                        echo $item["wanted_id"];
                        ?>"
                        class="delete"
                        onclick="return confirm(
                            'Are you sure you want to delete this wanted post?'
                        );"
                    >

                        Delete

                    </a>


                </div>


            </div>


        </div>


    <?php

        }

    }
    else
    {

    ?>


        <!-- EMPTY -->

        <div class="empty">

            You have not posted any wanted items yet.

        </div>


    <?php

    }

    ?>


    </div>


</main>


</body>

</html>
