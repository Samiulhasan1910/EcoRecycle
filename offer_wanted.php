<?php

session_start();

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = (int)$_SESSION["user_id"];


/* =====================================================
   GET WANTED ID
===================================================== */

$wanted_id = isset($_GET["wanted_id"])
    ? (int)$_GET["wanted_id"]
    : 0;

if($wanted_id <= 0)
{
    header("Location: wanted_items.php");
    exit();
}


/* =====================================================
   GET WANTED POST
===================================================== */

$wantedQuery = mysqli_query(
    $conn,
    "SELECT *
     FROM wanted_posts
     WHERE wanted_id='$wanted_id'
     LIMIT 1"
);

if(
    !$wantedQuery ||
    mysqli_num_rows($wantedQuery) == 0
)
{
    header(
        "Location: wanted_items.php?msg=wanted_not_found"
    );
    exit();
}

$wanted = mysqli_fetch_assoc($wantedQuery);


/* =====================================================
   CANNOT OFFER ON OWN POST
===================================================== */

if((int)$wanted["user_id"] === $user_id)
{
    header(
        "Location: view_wanted.php?id="
        .$wanted_id
        ."&msg=own_wanted"
    );
    exit();
}


/* =====================================================
   CHECK DUPLICATE OFFER
===================================================== */

$duplicateQuery = mysqli_query(
    $conn,
    "SELECT offer_id
     FROM wanted_offers
     WHERE wanted_id='$wanted_id'
     AND offerer_user_id='$user_id'
     LIMIT 1"
);

if(
    $duplicateQuery &&
    mysqli_num_rows($duplicateQuery) > 0
)
{
    header(
        "Location: view_wanted.php?id="
        .$wanted_id
        ."&msg=duplicate_offer"
    );
    exit();
}


/* =====================================================
   FORM VARIABLES
===================================================== */

$message = "";
$message_type = "";

$old_price = "";
$old_is_free = 0;

$old_bkash = "";
$old_nagad = "";

$old_bkash_checked = 0;
$old_nagad_checked = 0;

$old_location = "";


/* =====================================================
   FORM SUBMIT
===================================================== */

if($_SERVER["REQUEST_METHOD"] === "POST")
{

    /* =================================================
       GET POST VALUES
    ================================================= */

    $posted_wanted_id = isset($_POST["wanted_id"])
        ? (int)$_POST["wanted_id"]
        : 0;

    $is_free = isset($_POST["is_free"])
        ? 1
        : 0;

    $price = trim(
        $_POST["price"] ?? ""
    );

    $location = trim(
        $_POST["location"] ?? ""
    );

    $bkash_number = trim(
        $_POST["bkash_number"] ?? ""
    );

    $nagad_number = trim(
        $_POST["nagad_number"] ?? ""
    );

    $bkash_selected =
        isset($_POST["bkash_selected"])
        ? 1
        : 0;

    $nagad_selected =
        isset($_POST["nagad_selected"])
        ? 1
        : 0;


    /* =================================================
       KEEP OLD VALUES
    ================================================= */

    $old_price = $price;
    $old_is_free = $is_free;

    $old_location = $location;

    $old_bkash = $bkash_number;
    $old_nagad = $nagad_number;

    $old_bkash_checked =
        $bkash_selected;

    $old_nagad_checked =
        $nagad_selected;


    /* =================================================
       CHECK WANTED ID
    ================================================= */

    if($posted_wanted_id !== $wanted_id)
    {
        $message =
            "Invalid wanted post.";

        $message_type =
            "error";
    }


    /* =================================================
       LOCATION VALIDATION
    ================================================= */

    if($message_type === "")
    {

        if($location === "")
        {
            $message =
                "Please enter your item location.";

            $message_type =
                "error";
        }

        elseif(strlen($location) > 255)
        {
            $message =
                "Location must be less than 255 characters.";

            $message_type =
                "error";
        }

    }


    /* =================================================
       DUPLICATE CHECK AGAIN
       IMPORTANT FOR POST REQUEST
    ================================================= */

    if($message_type === "")
    {

        $duplicateCheck = mysqli_query(
            $conn,
            "SELECT offer_id
             FROM wanted_offers
             WHERE wanted_id='$wanted_id'
             AND offerer_user_id='$user_id'
             LIMIT 1"
        );

        if(
            $duplicateCheck &&
            mysqli_num_rows($duplicateCheck) > 0
        )
        {
            header(
                "Location: view_wanted.php?id="
                .$wanted_id
                ."&msg=duplicate_offer"
            );

            exit();
        }

    }


    /* =================================================
       IMAGE VALIDATION
    ================================================= */

    $extension = "";

    if($message_type === "")
    {

        if(
            !isset($_FILES["image"]) ||
            $_FILES["image"]["error"] !== UPLOAD_ERR_OK
        )
        {
            $message =
                "Please upload your item picture.";

            $message_type =
                "error";
        }

    }


    if($message_type === "")
    {

        $image = $_FILES["image"];


        /* ---------------------------------------------
           MAX 5MB
        --------------------------------------------- */

        if(
            $image["size"] >
            5 * 1024 * 1024
        )
        {
            $message =
                "Image must be less than 5MB.";

            $message_type =
                "error";
        }


        /* ---------------------------------------------
           ALLOWED EXTENSIONS
        --------------------------------------------- */

        $allowed_extensions =
            array(
                "jpg",
                "jpeg",
                "png",
                "webp"
            );

        $extension =
            strtolower(
                pathinfo(
                    $image["name"],
                    PATHINFO_EXTENSION
                )
            );


        if(
            $message_type === "" &&
            !in_array(
                $extension,
                $allowed_extensions,
                true
            )
        )
        {
            $message =
                "Only JPG, PNG and WEBP images are allowed.";

            $message_type =
                "error";
        }


        /* ---------------------------------------------
           CHECK REAL MIME TYPE
        --------------------------------------------- */

        if($message_type === "")
        {

            $finfo =
                finfo_open(
                    FILEINFO_MIME_TYPE
                );

            $real_type =
                finfo_file(
                    $finfo,
                    $image["tmp_name"]
                );

            finfo_close($finfo);


            $allowed_mime =
                array(
                    "image/jpeg",
                    "image/png",
                    "image/webp"
                );


            if(
                !in_array(
                    $real_type,
                    $allowed_mime,
                    true
                )
            )
            {
                $message =
                    "Invalid image file.";

                $message_type =
                    "error";
            }

        }

    }


    /* =================================================
       PAYMENT DATA DEFAULT
    ================================================= */

    $price_sql = "NULL";
    $method_sql = "NULL";
    $number_sql = "NULL";


    /* =================================================
       PAYMENT VALIDATION
    ================================================= */

    if($message_type === "")
    {

        /* =============================================
           FREE / DONATE OFFER
        ============================================= */

        if($is_free == 1)
        {
            $price_sql = "NULL";
            $method_sql = "NULL";
            $number_sql = "NULL";
        }


        /* =============================================
           PAID OFFER
        ============================================= */

        else
        {

            /* -----------------------------------------
               PRICE
            ----------------------------------------- */

            if(
                $price === "" ||
                !is_numeric($price) ||
                (float)$price <= 0
            )
            {
                $message =
                    "Please enter a valid price.";

                $message_type =
                    "error";
            }


            /* -----------------------------------------
               PAYMENT METHOD
            ----------------------------------------- */

            if(
                $message_type === "" &&
                !$bkash_selected &&
                !$nagad_selected
            )
            {
                $message =
                    "Please select Bkash or Nagad.";

                $message_type =
                    "error";
            }


            /* -----------------------------------------
               BKASH
            ----------------------------------------- */

            if(
                $message_type === "" &&
                $bkash_selected
            )
            {

                if(
                    !preg_match(
                        "/^01[0-9]{9}$/",
                        $bkash_number
                    )
                )
                {
                    $message =
                        "Please enter a valid Bkash number.";

                    $message_type =
                        "error";
                }

            }


            /* -----------------------------------------
               NAGAD
            ----------------------------------------- */

            if(
                $message_type === "" &&
                $nagad_selected
            )
            {

                if(
                    !preg_match(
                        "/^01[0-9]{9}$/",
                        $nagad_number
                    )
                )
                {
                    $message =
                        "Please enter a valid Nagad number.";

                    $message_type =
                        "error";
                }

            }


            /* =========================================
               CREATE PAYMENT DATA
            ========================================= */

            if($message_type === "")
            {

                $payment_methods =
                    array();

                $payment_numbers =
                    array();


                /* -------------------------------------
                   BKASH
                ------------------------------------- */

                if($bkash_selected)
                {
                    $payment_methods[] =
                        "Bkash";

                    $payment_numbers[] =
                        "Bkash: " . $bkash_number;
                }


                /* -------------------------------------
                   NAGAD
                ------------------------------------- */

                if($nagad_selected)
                {
                    $payment_methods[] =
                        "Nagad";

                    $payment_numbers[] =
                        "Nagad: " . $nagad_number;
                }


                $payment_method =
                    implode(
                        ", ",
                        $payment_methods
                    );

                $payment_number =
                    implode(
                        ", ",
                        $payment_numbers
                    );


                /* -------------------------------------
                   ESCAPE DATA
                ------------------------------------- */

                $price_safe =
                    mysqli_real_escape_string(
                        $conn,
                        $price
                    );

                $method_safe =
                    mysqli_real_escape_string(
                        $conn,
                        $payment_method
                    );

                $number_safe =
                    mysqli_real_escape_string(
                        $conn,
                        $payment_number
                    );


                $price_sql =
                    "'" .
                    $price_safe .
                    "'";

                $method_sql =
                    "'" .
                    $method_safe .
                    "'";

                $number_sql =
                    "'" .
                    $number_safe .
                    "'";
            }

        }

    }


    /* =================================================
       UPLOAD IMAGE
    ================================================= */

    $image_name = "";
    $image_path = "";


    if($message_type === "")
    {

        $upload_dir =
            "uploads/";


        /* ---------------------------------------------
           CREATE UPLOAD DIRECTORY
        --------------------------------------------- */

        if(!is_dir($upload_dir))
        {
            if(
                !mkdir(
                    $upload_dir,
                    0777,
                    true
                )
            )
            {
                $message =
                    "Unable to create upload directory.";

                $message_type =
                    "error";
            }
        }

    }


    /* =================================================
       MOVE IMAGE
    ================================================= */

    if($message_type === "")
    {

        $image_name =
            "offer_" .
            time() .
            "_" .
            uniqid() .
            "." .
            $extension;


        $image_path =
            $upload_dir .
            $image_name;


        if(
            !move_uploaded_file(
                $image["tmp_name"],
                $image_path
            )
        )
        {
            $message =
                "Image upload failed.";

            $message_type =
                "error";
        }

    }


    /* =================================================
       INSERT OFFER
    ================================================= */

    if($message_type === "")
    {

        /* ---------------------------------------------
           ESCAPE IMAGE
        --------------------------------------------- */

        $image_safe =
            mysqli_real_escape_string(
                $conn,
                $image_name
            );


        /* ---------------------------------------------
           ESCAPE LOCATION
        --------------------------------------------- */

        $location_safe =
            mysqli_real_escape_string(
                $conn,
                $location
            );


        /* =============================================
           INSERT QUERY
        ============================================= */

        $insertSql =
            "INSERT INTO wanted_offers
            (
                wanted_id,
                offerer_user_id,
                item_id,
                image,
                location,
                price,
                is_free,
                payment_method,
                payment_number,
                status,
                payment_visible
            )
            VALUES
            (
                '$wanted_id',
                '$user_id',
                NULL,
                '$image_safe',
                '$location_safe',
                $price_sql,
                '$is_free',
                $method_sql,
                $number_sql,
                'Pending',
                0
            )";


        /* =============================================
           EXECUTE
        ============================================= */

        $insertResult =
            mysqli_query(
                $conn,
                $insertSql
            );


        /* =============================================
           INSERT FAILED
        ============================================= */

        if(!$insertResult)
        {

            /* Delete uploaded image */

            if(
                $image_path !== "" &&
                file_exists($image_path)
            )
            {
                unlink($image_path);
            }


            /* SHOW REAL DATABASE ERROR */

            $message =
                "Offer failed: " .
                mysqli_error($conn);

            $message_type =
                "error";
        }


        /* =============================================
           INSERT SUCCESS
        ============================================= */

        else
        {

            /* -----------------------------------------
               GET AUTO GENERATED OFFER ID
            ----------------------------------------- */

            $offer_id =
                mysqli_insert_id($conn);


            /* =========================================
               GET OFFERER NAME
            ========================================= */

            $offerer_name =
                "Someone";


            $nameQuery =
                mysqli_query(
                    $conn,
                    "SELECT name
                     FROM users
                     WHERE user_id='$user_id'
                     LIMIT 1"
                );


            if(
                $nameQuery &&
                mysqli_num_rows($nameQuery) > 0
            )
            {

                $nameRow =
                    mysqli_fetch_assoc(
                        $nameQuery
                    );


                if(
                    isset($nameRow["name"]) &&
                    trim($nameRow["name"]) !== ""
                )
                {
                    $offerer_name =
                        $nameRow["name"];
                }

            }


            /* =========================================
               WANTED OWNER
            ========================================= */

            $owner_id =
                (int)$wanted["user_id"];


            /* =========================================
               NOTIFICATION TEXT
            ========================================= */

            $notificationText =
                $offerer_name .
                " offered an item for your wanted post: " .
                $wanted["title"];


            $notificationSafe =
                mysqli_real_escape_string(
                    $conn,
                    $notificationText
                );


            /* =========================================
               INSERT NOTIFICATION
            ========================================= */

            $notificationResult =
                mysqli_query(
                    $conn,
                    "INSERT INTO notifications
                    (
                        user_id,
                        request_id,
                        notification_type,
                        message,
                        status
                    )
                    VALUES
                    (
                        '$owner_id',
                        '$offer_id',
                        'wanted_offer',
                        '$notificationSafe',
                        'Unread'
                    )"
                );


            /* =========================================
               NOTIFICATION ERROR
            ========================================= */

            if(!$notificationResult)
            {
                /*
                 * Offer has already been inserted.
                 * So don't delete the offer.
                 * Just continue to success page.
                 */
            }


            /* =========================================
               REDIRECT SUCCESS
            ========================================= */

            header(
                "Location: view_wanted.php?id="
                .$wanted_id
                ."&msg=offer_sent"
            );

            exit();

        }

    }

}

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>Offer My Item | EcoCycle</title>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<link
    rel="stylesheet"
    href="style.css"
>


<style>

/* =====================================================
   MAIN
===================================================== */

.main{
    margin-left:230px;
    padding:105px 45px 50px;
    max-width:950px;
}


/* =====================================================
   BACK
===================================================== */

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


/* =====================================================
   PAGE HEADER
===================================================== */

.page-header{
    margin-bottom:20px;
}

.page-header h1{
    font-size:28px;
    margin-bottom:7px;
}

.page-header p{
    color:#777;
    font-size:14px;
}


/* =====================================================
   CARD
===================================================== */

.offer-card{
    background:white;

    border:1px solid #ddd;

    border-radius:12px;

    padding:25px;

    max-width:750px;
}


/* =====================================================
   WANTED INFO
===================================================== */

.wanted-info{
    background:#f5f7f6;

    border-radius:9px;

    padding:13px 15px;

    margin-bottom:20px;

    font-size:13px;

    color:#666;
}

.wanted-info strong{
    color:#333;
}


/* =====================================================
   FORM GROUP
===================================================== */

.form-group{
    margin-bottom:16px;
}

.form-group label{
    display:block;

    font-size:13px;

    font-weight:600;

    margin-bottom:6px;

    color:#444;
}

.form-group input{
    width:100%;

    box-sizing:border-box;

    padding:10px 11px;

    border:1px solid #ddd;

    border-radius:7px;

    font-size:13px;

    outline:none;
}

.form-group input:focus{
    border-color:#168a45;
}


/* =====================================================
   LOCATION
===================================================== */

.location-group{
    margin-bottom:18px;
}

.location-group label{
    display:block;

    font-size:13px;

    font-weight:600;

    margin-bottom:6px;

    color:#444;
}

.location-group input{
    width:100%;

    box-sizing:border-box;

    padding:11px 12px;

    border:1px solid #ddd;

    border-radius:7px;

    font-size:13px;

    outline:none;
}

.location-group input:focus{
    border-color:#168a45;
}

.location-help{
    margin:5px 0 0;

    font-size:11px;

    color:#888;
}


/* =====================================================
   IMAGE UPLOAD
===================================================== */

.image-upload{
    width:100%;

    max-width:420px;

    margin-bottom:18px;
}

.image-upload label{
    display:block;

    font-size:13px;

    font-weight:600;

    color:#444;

    margin-bottom:6px;
}

.image-upload input[type="file"]{
    width:100%;

    box-sizing:border-box;

    padding:8px;

    border:1px solid #ddd;

    border-radius:7px;

    background:#fafafa;

    font-size:12px;
}

.image-upload p{
    margin:5px 0 0;

    font-size:11px;

    color:#888;
}


/* =====================================================
   FREE CHECKBOX
===================================================== */

.free-box{
    display:flex;

    align-items:center;

    gap:8px;

    margin:12px 0 18px;

    font-size:13px;
}

.free-box input{
    width:15px;
    height:15px;

    cursor:pointer;
}


/* =====================================================
   PAYMENT SECTION
===================================================== */

.payment-section{
    margin-top:5px;

    padding:18px;

    background:#f8faf9;

    border:1px solid #e1e7e3;

    border-radius:10px;
}

.payment-title{
    font-size:15px;

    font-weight:bold;

    color:#333;

    margin-bottom:15px;
}


/* =====================================================
   PAYMENT GRID
===================================================== */

.payment-grid{
    display:grid;

    grid-template-columns:1fr 1fr;

    gap:25px;
}


/* =====================================================
   COLUMN TITLE
===================================================== */

.payment-column-title{
    font-size:12px;

    font-weight:bold;

    color:#666;

    margin-bottom:9px;
}


/* =====================================================
   PAYMENT METHOD
===================================================== */

.payment-option{
    height:43px;

    display:flex;

    align-items:center;

    gap:8px;

    padding:0 10px;

    box-sizing:border-box;

    border:1px solid #ddd;

    background:white;

    border-radius:7px;

    margin-bottom:10px;
}

.payment-option input{
    width:15px;

    height:15px;

    cursor:pointer;
}

.payment-option label{
    font-size:13px;

    cursor:pointer;
}


/* =====================================================
   PAYMENT NUMBER
===================================================== */

.payment-number-box{
    height:43px;

    margin-bottom:10px;
}

.payment-number-box input{
    width:100%;

    height:43px;

    box-sizing:border-box;

    padding:0 11px;

    border:1px solid #ddd;

    border-radius:7px;

    background:white;

    font-size:13px;

    outline:none;
}

.payment-number-box input:focus{
    border-color:#168a45;
}


/* =====================================================
   DISABLED
===================================================== */

.payment-number-box input:disabled{
    background:#eeeeee;

    color:#aaa;

    cursor:not-allowed;
}

.form-group input:disabled{
    background:#eeeeee;

    color:#aaa;

    cursor:not-allowed;
}


/* =====================================================
   SUBMIT
===================================================== */

.submit-btn{
    margin-top:20px;

    border:none;

    background:#168a45;

    color:white;

    padding:11px 22px;

    border-radius:7px;

    font-size:13px;

    font-weight:bold;

    cursor:pointer;
}

.submit-btn:hover{
    background:#117239;
}


/* =====================================================
   ERROR MESSAGE
===================================================== */

.error-message{
    background:#fff3f3;

    border:1px solid #f2c0c0;

    color:#7a1f1f;

    padding:11px 13px;

    border-radius:8px;

    margin-bottom:18px;

    font-size:13px;
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media(max-width:800px)
{
    .main{
        margin-left:70px;

        padding:100px 20px 50px;
    }

    .offer-card{
        max-width:100%;
    }
}


@media(max-width:600px)
{
    .payment-grid{
        grid-template-columns:1fr;

        gap:5px;
    }

    .image-upload{
        max-width:100%;
    }
}


@media(max-width:550px)
{
    .offer-card{
        padding:20px;
    }
}

</style>

</head>


<body>


<?php include("header_sidebar.php"); ?>


<main class="main">


<!-- =================================================
     BACK
================================================= -->

<a
    href="view_wanted.php?id=<?php echo $wanted_id; ?>"
    class="back"
>
    ← Back to Wanted Post
</a>


<!-- =================================================
     HEADER
================================================= -->

<div class="page-header">

    <h1>
        Offer My Item
    </h1>

    <p>
        Offer your item for this wanted post.
    </p>

</div>


<!-- =================================================
     CARD
================================================= -->

<div class="offer-card">


    <!-- =================================================
         ERROR MESSAGE
    ================================================= -->

    <?php if($message_type === "error"): ?>

        <div class="error-message">

            <?php
            echo htmlspecialchars(
                $message
            );
            ?>

        </div>

    <?php endif; ?>


    <!-- =================================================
         WANTED INFO
    ================================================= -->

    <div class="wanted-info">

        <strong>
            Wanted Item:
        </strong>

        <?php
        echo htmlspecialchars(
            $wanted["title"]
        );
        ?>

        <br>

        <strong>
            Category:
        </strong>

        <?php
        echo htmlspecialchars(
            $wanted["category"]
        );
        ?>

        <br>

        <strong>
            Wanted Location:
        </strong>

        <?php
        echo htmlspecialchars(
            $wanted["location"]
        );
        ?>

    </div>


    <!-- =================================================
         FORM
    ================================================= -->

    <form
        method="POST"
        enctype="multipart/form-data"
        action="offer_wanted.php?wanted_id=<?php echo $wanted_id; ?>"
    >


        <input
            type="hidden"
            name="wanted_id"
            value="<?php echo $wanted_id; ?>"
        >


        <!-- =================================================
             IMAGE
        ================================================= -->

        <div class="image-upload">

            <label for="image">
                Upload My Item Picture
            </label>

            <input
                type="file"
                name="image"
                id="image"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                required
            >

            <p>
                JPG, PNG or WEBP — maximum 5MB
            </p>

        </div>


        <!-- =================================================
             LOCATION
        ================================================= -->

        <div class="location-group">

            <label for="location">
                My Item Location
            </label>

            <input
                type="text"
                name="location"
                id="location"
                maxlength="255"
                placeholder="Enter the location of your item"
                value="<?php
                echo htmlspecialchars(
                    $old_location
                );
                ?>"
                required
            >

            <p class="location-help">
                Enter where your offered item is currently located.
            </p>

        </div>


        <!-- =================================================
             FREE / DONATE
        ================================================= -->

        <div class="free-box">

            <input
                type="checkbox"
                name="is_free"
                id="is_free"
                value="1"
                <?php
                if($old_is_free == 1)
                {
                    echo "checked";
                }
                ?>
            >

            <label for="is_free">
                Offer for Free / Donate
            </label>

        </div>


        <!-- =================================================
             PAYMENT SECTION
        ================================================= -->

        <div
            id="paymentSection"
            class="payment-section"
        >

            <div class="payment-title">
                Payment Information
            </div>


            <!-- PRICE -->

            <div class="form-group">

                <label for="price">
                    Price
                </label>

                <input
                    type="number"
                    name="price"
                    id="price"
                    step="0.01"
                    min="0.01"
                    placeholder="Enter price"
                    value="<?php
                    echo htmlspecialchars(
                        $old_price
                    );
                    ?>"
                >

            </div>


            <!-- PAYMENT GRID -->

            <div class="payment-grid">


                <!-- LEFT -->

                <div>

                    <div class="payment-column-title">
                        Payment Method
                    </div>


                    <!-- BKASH -->

                    <div class="payment-option">

                        <input
                            type="checkbox"
                            name="bkash_selected"
                            id="bkash_selected"
                            value="1"
                            <?php
                            if(
                                $old_bkash_checked
                            )
                            {
                                echo "checked";
                            }
                            ?>
                        >

                        <label for="bkash_selected">
                            Bkash
                        </label>

                    </div>


                    <!-- NAGAD -->

                    <div class="payment-option">

                        <input
                            type="checkbox"
                            name="nagad_selected"
                            id="nagad_selected"
                            value="1"
                            <?php
                            if(
                                $old_nagad_checked
                            )
                            {
                                echo "checked";
                            }
                            ?>
                        >

                        <label for="nagad_selected">
                            Nagad
                        </label>

                    </div>

                </div>


                <!-- RIGHT -->

                <div>

                    <div class="payment-column-title">
                        Payment Number
                    </div>


                    <!-- BKASH NUMBER -->

                    <div class="payment-number-box">

                        <input
                            type="text"
                            name="bkash_number"
                            id="bkash_number"
                            maxlength="11"
                            placeholder="Bkash Number"
                            value="<?php
                            echo htmlspecialchars(
                                $old_bkash
                            );
                            ?>"
                        >

                    </div>


                    <!-- NAGAD NUMBER -->

                    <div class="payment-number-box">

                        <input
                            type="text"
                            name="nagad_number"
                            id="nagad_number"
                            maxlength="11"
                            placeholder="Nagad Number"
                            value="<?php
                            echo htmlspecialchars(
                                $old_nagad
                            );
                            ?>"
                        >

                    </div>

                </div>


            </div>


        </div>


        <!-- =================================================
             SUBMIT
        ================================================= -->

        <button
            type="submit"
            class="submit-btn"
        >
            Send Offer
        </button>


    </form>


</div>


</main>


<script>

/* =====================================================
   ELEMENTS
===================================================== */

var freeCheckbox =
    document.getElementById(
        "is_free"
    );

var paymentSection =
    document.getElementById(
        "paymentSection"
    );

var priceInput =
    document.getElementById(
        "price"
    );

var bkashCheckbox =
    document.getElementById(
        "bkash_selected"
    );

var bkashNumber =
    document.getElementById(
        "bkash_number"
    );

var nagadCheckbox =
    document.getElementById(
        "nagad_selected"
    );

var nagadNumber =
    document.getElementById(
        "nagad_number"
    );


/* =====================================================
   FREE TOGGLE
===================================================== */

function toggleFree()
{

    if(freeCheckbox.checked)
    {

        /* PRICE */

        priceInput.disabled =
            true;

        priceInput.value =
            "";


        /* BKASH */

        bkashCheckbox.disabled =
            true;

        bkashCheckbox.checked =
            false;

        bkashNumber.disabled =
            true;

        bkashNumber.value =
            "";


        /* NAGAD */

        nagadCheckbox.disabled =
            true;

        nagadCheckbox.checked =
            false;

        nagadNumber.disabled =
            true;

        nagadNumber.value =
            "";

    }

    else
    {

        /* PRICE */

        priceInput.disabled =
            false;


        /* PAYMENT METHODS */

        bkashCheckbox.disabled =
            false;

        nagadCheckbox.disabled =
            false;


        updatePaymentNumbers();

    }

}


/* =====================================================
   PAYMENT NUMBERS
===================================================== */

function updatePaymentNumbers()
{

    if(freeCheckbox.checked)
    {
        return;
    }


    /* BKASH */

    if(bkashCheckbox.checked)
    {

        bkashNumber.disabled =
            false;

    }

    else
    {

        bkashNumber.disabled =
            true;

        bkashNumber.value =
            "";

    }


    /* NAGAD */

    if(nagadCheckbox.checked)
    {

        nagadNumber.disabled =
            false;

    }

    else
    {

        nagadNumber.disabled =
            true;

        nagadNumber.value =
            "";

    }

}


/* =====================================================
   EVENTS
===================================================== */

freeCheckbox.addEventListener(
    "change",
    toggleFree
);

bkashCheckbox.addEventListener(
    "change",
    updatePaymentNumbers
);

nagadCheckbox.addEventListener(
    "change",
    updatePaymentNumbers
);


/* =====================================================
   INITIAL
===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function()
    {
        toggleFree();
    }
);

</script>


</body>

</html>