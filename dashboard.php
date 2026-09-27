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


/* ===============================
   RECOMMENDED ITEMS
   =============================== */

$sql = "SELECT *
        FROM items
        WHERE status='Available'
        AND (user_id IS NULL OR user_id != '$user_id')
        AND type IN ('Sell', 'Donate')
        ORDER BY item_id DESC
        LIMIT 6";

$itemsResult = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<title>Dashboard | EcoCycle</title>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<!-- SAME HEADER + SIDEBAR CSS -->
<link rel="stylesheet" href="style.css">


<!-- DASHBOARD ONLY CSS -->
<style>

.main{
    margin-left:230px;
    padding:105px 45px 50px;
    max-width:1300px;
}

.page-header{
    margin-bottom:28px;
}

.page-header h1{
    font-size:29px;
    font-weight:600;
    margin-bottom:7px;
}

.page-header p{
    color:#777;
    font-size:14px;
}

.search-box{
    background:white;
    border:1px solid #e5e5e5;
    border-radius:12px;
    padding:18px;
    margin-bottom:35px;
}

.search-form{
    display:flex;
    gap:10px;
}

.search-input{
    flex:1;
    height:46px;
    padding:0 15px;
    border:1px solid #d8d8d8;
    border-radius:7px;
    outline:none;
    font-size:14px;
}

.search-input:focus{
    border-color:#168a45;
}

.search-select{
    height:46px;
    padding:0 12px;
    border:1px solid #d8d8d8;
    border-radius:7px;
    outline:none;
    font-size:14px;
    background:white;
    color:#444;
    width:180px;
}

.search-select:focus{
    border-color:#168a45;
}

.search-location{
    height:46px;
    padding:0 15px;
    border:1px solid #d8d8d8;
    border-radius:7px;
    outline:none;
    font-size:14px;
    width:180px;
}

.search-location:focus{
    border-color:#168a45;
}

.search-button{
    height:46px;
    padding:0 25px;
    border:none;
    border-radius:7px;
    background:#168a45;
    color:white;
    font-size:14px;
    font-weight:bold;
    cursor:pointer;
}

.search-button:hover{
    background:#117239;
}

.section-title{
    font-size:19px;
    font-weight:600;
    margin-bottom:16px;
}

.quick-actions{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:18px;
    margin-bottom:38px;
}

.action-card{
    background:white;
    border:1px solid #e5e5e5;
    border-radius:12px;
    padding:24px;
    text-decoration:none;
    color:#222;
    min-height:140px;
    transition:0.2s;
}

.action-card:hover{
    border-color:#168a45;
    transform:translateY(-2px);
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
}

.action-icon{
    width:43px;
    height:43px;
    background:#eaf7ef;
    color:#168a45;
    border-radius:9px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
    margin-bottom:14px;
}

.action-card h3{
    font-size:16px;
    margin-bottom:7px;
}

.action-card p{
    font-size:13px;
    color:#777;
    line-height:1.5;
}

.item-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:18px;
}

.item-card{
    background:white;
    border:1px solid #e5e5e5;
    border-radius:12px;
    padding:15px;
    transition:0.2s;
    overflow:hidden;
}

.item-card:hover{
    border-color:#168a45;
    box-shadow:0 6px 18px rgba(0,0,0,0.06);
}

.item-image{
    width:100%;
    height:180px;
    object-fit:cover;
    border-radius:8px;
    display:block;
    margin-bottom:15px;
}

.no-image{
    width:100%;
    height:180px;
    background:#f1f3f2;
    border-radius:8px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#888;
    font-size:13px;
    margin-bottom:15px;
}

.item-title{
    font-size:17px;
    font-weight:600;
    margin-bottom:15px;
}

.item-detail{
    font-size:13px;
    color:#666;
    margin-bottom:8px;
}

.item-detail strong{
    color:#333;
}

.item-type{
    display:inline-block;
    margin-top:5px;
    padding:5px 10px;
    border-radius:20px;
    background:#eaf7ef;
    color:#168a45;
    font-size:11px;
    font-weight:bold;
}

.view-button{
    display:block;
    text-align:center;
    margin-top:17px;
    padding:9px;
    background:#168a45;
    color:white;
    border-radius:6px;
    text-decoration:none;
    font-size:13px;
    font-weight:bold;
}

.view-button:hover{
    background:#117239;
}

.no-items{
    grid-column:1/-1;
    background:white;
    border:1px solid #e5e5e5;
    border-radius:12px;
    padding:35px;
    text-align:center;
    color:#777;
    font-size:14px;
}

@media(max-width:950px){

    .item-grid{
        grid-template-columns:repeat(2,1fr);
    }

}

@media(max-width:800px){

    .main{
        margin-left:70px;
        padding:100px 20px 50px;
    }

    .item-grid{
        grid-template-columns:1fr;
    }

}

@media(max-width:550px){

    .quick-actions{
        grid-template-columns:1fr;
    }

    .search-form{
        flex-direction:column;
    }

    .search-input,
    .search-select,
    .search-location,
    .search-button{
        width:100%;
    }

}

</style>

</head>


<body>


<!-- =================================
     MY ITEMS ER MOTO COMMON HEADER
     + SIDEBAR
     ================================= -->

<?php include("header_sidebar.php"); ?>


<!-- =================================
     DASHBOARD CONTENT
     ================================= -->

<main class="main">


    <div class="page-header">

        <h1>Dashboard</h1>

        <p>
            Manage your EcoCycle activities from one place.
        </p>

    </div>



    <!-- SEARCH -->

    <div class="search-box">

        <form
            method="get"
            action="browse_items.php"
            class="search-form"
        >

            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="Search items..."
            >

            <select
                name="category"
                class="search-select"
            >

                <option value="">
                    All Categories
                </option>

                <option value="Electronics">
                    Electronics
                </option>

                <option value="Furniture">
                    Furniture
                </option>

                <option value="Clothing">
                    Clothing
                </option>

                <option value="Books">
                    Books
                </option>

                <option value="Home Appliances">
                    Home Appliances
                </option>

                <option value="Kitchen">
                    Kitchen
                </option>

                <option value="Sports">
                    Sports
                </option>

                <option value="Toys">
                    Toys
                </option>

                <option value="Others">
                    Others
                </option>

            </select>

            <input
                type="text"
                name="location"
                class="search-location"
                placeholder="Location..."
            >

            <button
                type="submit"
                class="search-button"
            >
                Search
            </button>

        </form>

    </div>



    <!-- QUICK ACTIONS -->

    <h2 class="section-title">
        Quick Actions
    </h2>

    <div class="quick-actions">

        <a
            href="post_item.php"
            class="action-card"
        >

            <div class="action-icon">
                ➕
            </div>

            <h3>
                Post an Item
            </h3>

            <p>
                Sell or donate an item
                you no longer need.
            </p>

        </a>


        <a
            href="post_wanted.php"
            class="action-card"
        >

            <div class="action-icon">
                📋
            </div>

            <h3>
                Wanted Posts
            </h3>

            <p>
                Create or view wanted posts
                for items you need.
            </p>

        </a>

    </div>



    <!-- RECOMMENDED ITEMS -->

    <h2 class="section-title">
        Recommended Items
    </h2>

    <div class="item-grid">

        <?php

        if(
            $itemsResult &&
            mysqli_num_rows($itemsResult) > 0
        )
        {

            while(
                $item =
                mysqli_fetch_assoc($itemsResult)
            )
            {

        ?>

        <div class="item-card">


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


            <div class="item-title">

                <?php
                echo htmlspecialchars(
                    $item["title"]
                );
                ?>

            </div>


            <div class="item-detail">

                <strong>
                    Category:
                </strong>

                <?php
                echo htmlspecialchars(
                    $item["category"]
                );
                ?>

            </div>


            <div class="item-detail">

                <strong>
                    Location:
                </strong>

                <?php
                echo htmlspecialchars(
                    $item["location"]
                );
                ?>

            </div>


            <div class="item-detail">

                <strong>
                    Price:
                </strong>

                <?php

                if(
                    strtolower($item["type"])
                    == "donate"
                )
                {
                    echo "Free";
                }
                else
                {
                    echo htmlspecialchars(
                        $item["price"]
                    );

                    echo " BDT";
                }

                ?>

            </div>


            <span class="item-type">

                <?php
                echo htmlspecialchars(
                    $item["type"]
                );
                ?>

            </span>


            <a
                href="view_item.php?id=<?php
                    echo $item["item_id"];
                ?>"
                class="view-button"
            >
                View Item
            </a>


        </div>

        <?php

            }

        }
        else
        {

        ?>

            <div class="no-items">

                No recommended items available
                at the moment.

            </div>

        <?php

        }

        ?>

    </div>


</main>

</body>
</html>
