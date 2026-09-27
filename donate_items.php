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

$sql = "SELECT * FROM items
        WHERE user_id='$user_id'
        AND type='Donate'
        ORDER BY item_id DESC";

$result = mysqli_query($conn,$sql);

?>

<!DOCTYPE html>
<html>

<head>

<title>My Donated Items | EcoCycle</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="style.css">

<style>

/* =========================
   DONATE PAGE CONTENT CSS
========================= */

.main{
    margin-left:230px;
    padding:105px 45px 50px;
    max-width:1300px;
}

/* BACK TEXT */

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

.grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
}

.card{
    background:white;
    border:1px solid #e5e5e5;
    border-radius:12px;
    overflow:hidden;
}

.card:hover{
    border-color:#168a45;
}

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
}

.detail strong{
    color:#333;
}

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

@media(max-width:950px){

    .grid{
        grid-template-columns:repeat(2,1fr);
    }

}

@media(max-width:750px){

    .main{
        margin-left:70px;
        padding-left:20px;
        padding-right:20px;
    }

    .grid{
        grid-template-columns:1fr;
    }

}

</style>

</head>

<body>


<?php include("header_sidebar.php"); ?>


<main class="main">


    <!-- BACK -->

    <a
        href="my_items.php"
        class="back"
    >
        ← Back
    </a>


    <div class="header">

        <h1>
            My Donated Items
        </h1>

        <p>
            Items you have posted for donation.
        </p>

    </div>


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


        <div class="card">


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


            <div class="content">


                <div class="title">

                    <?php

                    echo htmlspecialchars(
                        $item["title"]
                    );

                    ?>

                </div>


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


                <div class="detail">

                    <strong>
                        Price:
                    </strong>

                    Free

                </div>


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


                <span class="status">

                    <?php

                    echo htmlspecialchars(
                        $item["status"]
                    );

                    ?>

                </span>


                <div class="buttons">


                    <a
                        href="view_item.php?id=<?php
                        echo $item["item_id"];
                        ?>"
                        class="view"
                    >
                        View
                    </a>


                    <?php if (!in_array(strtolower(trim($item["status"] ?? "")), ["accepted", "donated"], true)): ?>
                        <a
                            href="edit_item.php?id=<?php
                            echo $item["item_id"];
                            ?>"
                            class="edit"
                        >
                            Edit
                        </a>
                    <?php endif; ?>


                    <a
                        href="delete_item.php?id=<?php
                        echo $item["item_id"];
                        ?>"
                        class="delete"
                        onclick="return confirm(
                            'Are you sure you want to delete this item?'
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


        <div class="empty">

            You have not posted any donated items yet.

        </div>


    <?php

    }

    ?>


    </div>


</main>


</body>

</html>
