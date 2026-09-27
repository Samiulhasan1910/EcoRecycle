
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

?>

<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <title>Browse Items - EcoCycle</title>

    <link rel="stylesheet" href="style.css">

    <style>

        .main{
            margin-left:230px;
            padding:105px 45px 50px;
            max-width:1300px;
        }

        .header{
            margin-bottom:25px;
        }

        .header h1{
            font-size:28px;
            margin-bottom:7px;
        }

        .header p{
            color:#777;
            font-size:14px;
        }

        .search-box{
            background:white;
            border:1px solid #e5e5e5;
            border-radius:12px;
            padding:20px;
            margin-bottom:30px;
        }

        .search-form{
            display:grid;
            grid-template-columns:2fr 1fr 1fr auto;
            gap:12px;
        }

        .search-form input,
        .search-form select{
            width:100%;
            height:43px;
            border:1px solid #ccc;
            border-radius:7px;
            padding:0 12px;
            font-size:14px;
            background:white;
        }

        .search-form input:focus,
        .search-form select:focus{
            outline:none;
            border-color:#168a45;
        }

        .search-btn{
            height:43px;
            padding:0 22px;
            border:none;
            border-radius:7px;
            background:#168a45;
            color:white;
            font-size:14px;
            font-weight:bold;
            cursor:pointer;
        }

        .search-btn:hover{
            background:#117239;
        }

        .section-title{
            font-size:20px;
            font-weight:bold;
            margin-bottom:18px;
        }

        .section-title span{
            color:#168a45;
        }

        .item-grid{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:20px;
            margin-bottom:40px;
        }

        .card{
            background:white;
            border:1px solid #e5e5e5;
            border-radius:12px;
            overflow:hidden;
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

        .type{
            display:inline-block;
            background:#eaf7ef;
            color:#168a45;
            padding:5px 10px;
            border-radius:20px;
            font-size:11px;
            font-weight:bold;
            margin-top:3px;
        }

        .wanted-type{
            background:#fff4df;
            color:#b36b00;
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

        .view:hover{
            background:#117239;
        }

        .wanted-view{
            background:#b36b00;
            color:white;
        }

        .wanted-view:hover{
            background:#955800;
        }

        .empty{
            background:white;
            border:1px solid #e5e5e5;
            border-radius:12px;
            padding:35px;
            text-align:center;
            color:#777;
            font-size:14px;
            grid-column:1/-1;
        }

        .loading{
            background:white;
            border:1px solid #e5e5e5;
            border-radius:12px;
            padding:35px;
            text-align:center;
            color:#777;
            grid-column:1/-1;
        }

        @media(max-width:950px){

            .item-grid{
                grid-template-columns:repeat(2,1fr);
            }

            .search-form{
                grid-template-columns:1fr 1fr;
            }

        }

        @media(max-width:750px){

            .main{
                margin-left:70px;
                padding-left:20px;
                padding-right:20px;
            }

            .item-grid{
                grid-template-columns:1fr;
            }

            .search-form{
                grid-template-columns:1fr;
            }

        }

    </style>

</head>

<body>

<?php include("header_sidebar.php"); ?>


<div class="main">

    <div class="header">

        <h1>Browse</h1>

        <p>
            Explore available items and wanted posts from other users.
        </p>

    </div>


    <div class="search-box">

        <form
            id="searchForm"
            class="search-form"
        >

            <input
                type="text"
                id="search"
                placeholder="Search items or wanted posts..."
            >


            <select id="category">

                <option value="">
                    All Categories
                </option>

                <option value="Electronics">
                    Electronics
                </option>

                <option value="Books">
                    Books
                </option>

                <option value="Clothing">
                    Clothing
                </option>

                <option value="Furniture">
                    Furniture
                </option>

                <option value="Others">
                    Others
                </option>

            </select>


            <input
                type="text"
                id="location"
                placeholder="Location"
            >


            <button
                type="submit"
                class="search-btn"
            >
                Search
            </button>

        </form>

    </div>


    <div id="searchInfo"></div>


    <div id="itemGrid">

        <div class="loading">
            Loading...
        </div>

    </div>

</div>


<script>

function loadItems()
{

    var search =
        document.getElementById("search").value;

    var category =
        document.getElementById("category").value;

    var location =
        document.getElementById("location").value;

    var itemGrid =
        document.getElementById("itemGrid");

    var searchInfo =
        document.getElementById("searchInfo");


    itemGrid.innerHTML =
        '<div class="loading">Loading...</div>';


    var xhr =
        new XMLHttpRequest();


    xhr.open(
        "GET",
        "search_items.php?search=" +
        encodeURIComponent(search) +
        "&category=" +
        encodeURIComponent(category) +
        "&location=" +
        encodeURIComponent(location),
        true
    );


    xhr.onreadystatechange = function()
    {

        if(xhr.readyState === 4)
        {

            if(xhr.status === 200)
            {

                itemGrid.innerHTML =
                    xhr.responseText;


                if(
                    search !== "" ||
                    category !== "" ||
                    location !== ""
                )
                {
                    searchInfo.innerHTML =
                        '<p style="color:#777;font-size:13px;margin-bottom:15px;">Search results</p>';
                }
                else
                {
                    searchInfo.innerHTML = "";
                }

            }
            else
            {

                itemGrid.innerHTML =
                    '<div class="empty">Failed to load results.</div>';

            }

        }

    };


    xhr.send();

}


document
.getElementById("searchForm")
.addEventListener(
    "submit",
    function(event)
    {

        event.preventDefault();

        loadItems();

    }
);


window.onload = function()
{
    loadItems();
};

</script>


</body>

</html>