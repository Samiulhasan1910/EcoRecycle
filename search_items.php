
<?php

session_start();

if(!isset($_SESSION["user_id"]))
{
    exit();
}

include("db.php");

$user_id = $_SESSION["user_id"];


/* =========================
   GET SEARCH VALUES
   ========================= */

$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");
$location = trim($_GET["location"] ?? "");


/* =========================
   ESCAPE VALUES
   ========================= */

$searchSafe = mysqli_real_escape_string(
    $conn,
    $search
);

$categorySafe = mysqli_real_escape_string(
    $conn,
    $category
);

$locationSafe = mysqli_real_escape_string(
    $conn,
    $location
);

$user_id_safe = mysqli_real_escape_string(
    $conn,
    $user_id
);


/* =====================================================
   AVAILABLE ITEMS
   ===================================================== */

$itemConditions = array();


$itemConditions[] =
    "status='Available'";


$itemConditions[] =
    "(user_id IS NULL OR user_id != '$user_id_safe')";


$itemConditions[] =
    "type IN ('Sell','Donate')";

/* SEARCH */

if($search != "")
{
    $itemConditions[] =
        "(
            title LIKE '%$searchSafe%'
            OR description LIKE '%$searchSafe%'
            OR category LIKE '%$searchSafe%'
            OR location LIKE '%$searchSafe%'
        )";
}


/* CATEGORY */

if($category != "")
{
    $itemConditions[] =
        "category='$categorySafe'";
}


/* LOCATION */

if($location != "")
{
    $itemConditions[] =
        "location LIKE '%$locationSafe%'";
}


$itemWhere = implode(
    " AND ",
    $itemConditions
);


/* GET ITEMS */

$itemSql = "
    SELECT *
    FROM items
    WHERE $itemWhere
    ORDER BY item_id DESC
";


$itemResult = mysqli_query(
    $conn,
    $itemSql
);


/* =====================================================
   WANTED POSTS
   ===================================================== */

$wantedConditions = array();


/* ONLY ACTIVE WANTED POSTS */

$wantedConditions[] =
    "status='Active'";


/* DON'T SHOW OWN WANTED POSTS */

$wantedConditions[] =
    "user_id != '$user_id_safe'";


/* SEARCH */

if($search != "")
{
    $wantedConditions[] =
        "(
            title LIKE '%$searchSafe%'
            OR category LIKE '%$searchSafe%'
            OR location LIKE '%$searchSafe%'
            OR description LIKE '%$searchSafe%'
        )";
}


/* CATEGORY */

if($category != "")
{
    $wantedConditions[] =
        "category='$categorySafe'";
}


/* LOCATION */

if($location != "")
{
    $wantedConditions[] =
        "location LIKE '%$locationSafe%'";
}


$wantedWhere = implode(
    " AND ",
    $wantedConditions
);


/* GET WANTED POSTS */

$wantedSql = "
    SELECT *
    FROM wanted_posts
    WHERE $wantedWhere
    ORDER BY wanted_id DESC
";


$wantedResult = mysqli_query(
    $conn,
    $wantedSql
);


/* =====================================================
   AVAILABLE ITEMS TITLE
   ===================================================== */

echo '
<div class="section-title">
    Available <span>Items</span>
</div>
';


/* =====================================================
   DISPLAY AVAILABLE ITEMS
   ===================================================== */

if(
    $itemResult &&
    mysqli_num_rows($itemResult) > 0
)
{

    echo '<div class="item-grid">';


    while(
        $item = mysqli_fetch_assoc($itemResult)
    )
    {

        echo '<div class="card">';


        /* =========================
           IMAGE
           ========================= */

        if(
            !empty($item["image"]) &&
            file_exists(
                "uploads/".$item["image"]
            )
        )
        {

            echo '
                <img
                    src="uploads/'.
                    htmlspecialchars($item["image"]).
                    '"
                    class="image"
                >
            ';

        }
        else
        {

            echo '
                <div class="no-image">
                    No Image
                </div>
            ';

        }


        echo '<div class="content">';


        /* TITLE */

        echo '
            <div class="title">
                '.
                htmlspecialchars(
                    $item["title"]
                ).
                '
            </div>
        ';


        /* CATEGORY */

        echo '
            <div class="detail">
                <strong>Category:</strong>
                '.
                htmlspecialchars(
                    $item["category"]
                ).
                '
            </div>
        ';


        /* LOCATION */

        echo '
            <div class="detail">
                <strong>Location:</strong>
                '.
                htmlspecialchars(
                    $item["location"]
                ).
                '
            </div>
        ';


        /* =========================
           PRICE
           ========================= */

        if(
            strtolower($item["type"]) == "sell"
        )
        {

            echo '
                <div class="detail">
                    <strong>Price:</strong>
                    BDT '.
                    htmlspecialchars(
                        $item["price"]
                    ).
                    '
                </div>
            ';

        }
        else
        {

            echo '
                <div class="detail">
                    <strong>Price:</strong>
                    Free
                </div>
            ';

        }


        /* TYPE */

        echo '
            <span class="type">
                '.
                htmlspecialchars(
                    $item["type"]
                ).
                '
            </span>
        ';


        /* VIEW BUTTON */

        echo '
            <div class="buttons">

                <a
                    href="view_item.php?id='.
                    $item["item_id"].
                    '"
                    class="view"
                >
                    View
                </a>

            </div>
        ';


        echo '</div>';

        echo '</div>';

    }


    echo '</div>';

}
else
{

    echo '
        <div class="item-grid">

            <div class="empty">
                No available items found.
            </div>

        </div>
    ';

}


/* =====================================================
   WANTED POSTS TITLE
   ===================================================== */

echo '
<div class="section-title">
    Wanted <span>Posts</span>
</div>
';


/* =====================================================
   DISPLAY WANTED POSTS
   ===================================================== */

if(
    $wantedResult &&
    mysqli_num_rows($wantedResult) > 0
)
{

    echo '<div class="item-grid">';


    while(
        $wanted =
        mysqli_fetch_assoc($wantedResult)
    )
    {

        echo '<div class="card">';


        /* =========================
           IMAGE
           ========================= */

        if(
            !empty($wanted["image"]) &&
            file_exists(
                "uploads/".$wanted["image"]
            )
        )
        {

            echo '
                <img
                    src="uploads/'.
                    htmlspecialchars(
                        $wanted["image"]
                    ).
                    '"
                    class="image"
                >
            ';

        }
        else
        {

            echo '
                <div class="no-image">
                    Wanted Post
                </div>
            ';

        }


        echo '<div class="content">';


        /* TITLE */

        echo '
            <div class="title">
                '.
                htmlspecialchars(
                    $wanted["title"]
                ).
                '
            </div>
        ';


        /* CATEGORY */

        echo '
            <div class="detail">
                <strong>Category:</strong>
                '.
                htmlspecialchars(
                    $wanted["category"]
                ).
                '
            </div>
        ';


        /* LOCATION */

        echo '
            <div class="detail">
                <strong>Preferred Location:</strong>
                '.
                htmlspecialchars(
                    $wanted["location"]
                ).
                '
            </div>
        ';


        /* DESCRIPTION */

        if(
            isset($wanted["description"]) &&
            trim($wanted["description"]) != ""
        )
        {

            $description =
                trim(
                    $wanted["description"]
                );


            if(strlen($description) > 100)
            {

                $description =
                    substr(
                        $description,
                        0,
                        100
                    ).
                    "...";

            }


            echo '
                <div class="detail">
                    <strong>Requirement:</strong>
                    '.
                    htmlspecialchars(
                        $description
                    ).
                    '
                </div>
            ';

        }


        /* WANTED LABEL */

        echo '
            <span
                class="type wanted-type"
            >
                Wanted
            </span>
        ';


        /* VIEW BUTTON */

        echo '
            <div class="buttons">

                <a
                    href="view_wanted.php?id='.
                    $wanted["wanted_id"].
                    '&from=browse"
                    class="wanted-view"
                >
                    View Wanted Post
                </a>

            </div>
        ';


        echo '</div>';

        echo '</div>';

    }


    echo '</div>';

}
else
{

    echo '
        <div class="item-grid">

            <div class="empty">
                No wanted posts found.
            </div>

        </div>
    ';

}

?>
