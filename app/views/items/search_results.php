<?php
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
    count($itemResult) > 0
)
{

    echo '<div class="item-grid">';


    foreach($itemResult as $item)
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
                    href="index.php?url=items/view&id='.
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
    count($wantedResult) > 0
)
{

    echo '<div class="item-grid">';


    foreach($wantedResult as $wanted)
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
                    href="index.php?url=wanted/view&id='.
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
