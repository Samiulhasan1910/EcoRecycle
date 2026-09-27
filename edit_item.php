<?php

session_start();

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = (int)$_SESSION["user_id"];
$name = $_SESSION["name"] ?? "User";


/* =========================
   GET ITEM ID
========================= */

$item_id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;

if($item_id <= 0)
{
    header("Location: my_items.php");
    exit();
}


/* =========================
   GET ITEM
========================= */

$sql = "SELECT *
        FROM items
        WHERE item_id='$item_id'
        AND user_id='$user_id'
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if(!$result || mysqli_num_rows($result) == 0)
{
    header("Location: my_items.php");
    exit();
}

$item = mysqli_fetch_assoc($result);


/* =========================
   ITEM TYPE
========================= */

$item_type = strtolower(
    trim($item["type"] ?? "")
);


/* =========================
   BACK PAGE
========================= */

if($item_type == "donate")
{
    $backPage = "donate_items.php";
}
else
{
    $backPage = "sell_items.php";
}


/* =========================
   COMPLETED ITEM CANNOT EDIT
========================= */

$item_status = strtolower(
    trim($item["status"] ?? "")
);

if(
    $item_status === "sold"
    || ($item_type === "donate" && in_array($item_status, ["accepted", "donated"], true))
)
{
    header("Location: " . $backPage);
    exit();
}


/* =========================
   VARIABLES
========================= */

$message = "";
$success = false;


/* =========================
   NGO OPTIONS
========================= */

$ngoOptions = array();


/* =========================
   CHECK NGO COLUMN
========================= */

$ngoCheck = mysqli_query(
    $conn,
    "SHOW COLUMNS FROM items LIKE 'ngo_id'"
);

if($ngoCheck && mysqli_num_rows($ngoCheck) == 0)
{
    mysqli_query(
        $conn,
        "ALTER TABLE items
         ADD COLUMN ngo_id INT NULL AFTER user_id"
    );
}


/* =========================
   GET NGO LIST
========================= */

$ngoListSql = "SELECT ngo_id, name
               FROM ngos
               ORDER BY name ASC";

$ngoListResult = mysqli_query(
    $conn,
    $ngoListSql
);

if($ngoListResult)
{
    while($ngo = mysqli_fetch_assoc($ngoListResult))
    {
        $ngoOptions[] = $ngo;
    }
}


/* =========================
   EXISTING PAYMENT VALUES
========================= */

$old_bkash = "";
$old_nagad = "";

$old_payment_method =
    $item["payment_method"] ?? "";

$old_payment_number =
    $item["payment_number"] ?? "";


/* =========================
   GET OLD BKASH
========================= */

if(
    stripos(
        $old_payment_method,
        "Bkash"
    ) !== false
)
{
    $parts = explode(
        ",",
        $old_payment_number
    );

    foreach($parts as $part)
    {
        $part = trim($part);

        if(
            stripos(
                $part,
                "Bkash:"
            ) === 0
        )
        {
            $old_bkash = trim(
                substr(
                    $part,
                    strlen("Bkash:")
                )
            );
        }
    }


    /* OLD FORMAT */

    if(
        $old_bkash == "" &&
        strtolower(
            trim($old_payment_method)
        ) == "bkash"
    )
    {
        $old_bkash =
            $old_payment_number;
    }
}


/* =========================
   GET OLD NAGAD
========================= */

if(
    stripos(
        $old_payment_method,
        "Nagad"
    ) !== false
)
{
    $parts = explode(
        ",",
        $old_payment_number
    );

    foreach($parts as $part)
    {
        $part = trim($part);

        if(
            stripos(
                $part,
                "Nagad:"
            ) === 0
        )
        {
            $old_nagad = trim(
                substr(
                    $part,
                    strlen("Nagad:")
                )
            );
        }
    }


    /* OLD FORMAT */

    if(
        $old_nagad == "" &&
        strtolower(
            trim($old_payment_method)
        ) == "nagad"
    )
    {
        $old_nagad =
            $old_payment_number;
    }
}


/* =========================
   UPDATE
========================= */

if($_SERVER["REQUEST_METHOD"] == "POST")
{

    /* =========================
       CHECK AGAIN COMPLETED STATUS
    ========================= */

    $checkSoldSql = "SELECT status
                     FROM items
                     WHERE item_id='$item_id'
                     AND user_id='$user_id'
                     LIMIT 1";

    $checkSoldResult =
        mysqli_query(
            $conn,
            $checkSoldSql
        );

    if($checkSoldResult)
    {
        $currentStatus =
            mysqli_fetch_assoc(
                $checkSoldResult
            );

        $currentItemStatus = strtolower(
                trim(
                    $currentStatus["status"]
                    ?? ""
                )
            );

        if(
            $currentItemStatus === "sold"
            || ($item_type === "donate" && in_array($currentItemStatus, ["accepted", "donated"], true))
        )
        {
            header(
                "Location: " . $backPage
            );
            exit();
        }
    }


    /* =========================
       FORM DATA
    ========================= */

    $title = trim(
        $_POST["title"] ?? ""
    );

    $category = trim(
        $_POST["category"] ?? ""
    );

    $location = trim(
        $_POST["location"] ?? ""
    );


    /* =========================
       TYPE CANNOT CHANGE
    ========================= */

    $type = $item["type"];


    /* =========================
       OLD VALUES
    ========================= */

    $price =
        $item["price"] ?? "0";

    $payment_method =
        $item["payment_method"] ?? "";

    $payment_number =
        $item["payment_number"] ?? "";


    /* =========================
       VALIDATION
    ========================= */

    if(
        $title == "" ||
        $category == "" ||
        $location == ""
    )
    {
        $message =
            "Please fill all required fields.";
    }


    /* =========================
       SELL
    ========================= */

    elseif(
        strtolower(trim($type))
        == "sell"
    )
    {

        $price = trim(
            $_POST["price"] ?? ""
        );

        $bkash_number = trim(
            $_POST["bkash_number"] ?? ""
        );

        $nagad_number = trim(
            $_POST["nagad_number"] ?? ""
        );


        /* PRICE */

        if(
            $price == "" ||
            !is_numeric($price) ||
            $price <= 0
        )
        {
            $message =
                "Please enter a valid price.";
        }


        /* PAYMENT */

        elseif(
            $bkash_number == "" &&
            $nagad_number == ""
        )
        {
            $message =
                "Please enter at least one payment number.";
        }


        /* BKASH */

        elseif(
            $bkash_number != "" &&
            !preg_match(
                '/^01[0-9]{9}$/',
                $bkash_number
            )
        )
        {
            $message =
                "Enter a valid Bkash number.";
        }


        /* NAGAD */

        elseif(
            $nagad_number != "" &&
            !preg_match(
                '/^01[0-9]{9}$/',
                $nagad_number
            )
        )
        {
            $message =
                "Enter a valid Nagad number.";
        }


        /* PAYMENT SAVE */

        else
        {

            $methods = array();

            $numbers = array();


            if($bkash_number != "")
            {
                $methods[] = "Bkash";

                $numbers[] =
                    "Bkash: " .
                    $bkash_number;
            }


            if($nagad_number != "")
            {
                $methods[] = "Nagad";

                $numbers[] =
                    "Nagad: " .
                    $nagad_number;
            }


            $payment_method =
                implode(
                    ", ",
                    $methods
                );

            $payment_number =
                implode(
                    ", ",
                    $numbers
                );
        }
    }


    /* =========================
       DONATE
    ========================= */

    elseif(
        strtolower(trim($type))
        == "donate"
    )
    {

        $ngoId = trim(
            $_POST["ngo_id"] ?? ""
        );


        $price = "0";

        $payment_method = "";

        $payment_number = "";


        if(
            $ngoId == "" ||
            !ctype_digit($ngoId) ||
            (int)$ngoId <= 0
        )
        {
            $message =
                "Please select a valid NGO.";
        }
    }


    /* =========================
       IMAGE
    ========================= */

    $newImage =
        $item["image"] ?? "";


    if($message == "")
    {

        if(
            isset($_FILES["image"]) &&
            $_FILES["image"]["error"] == 0
        )
        {

            $extension =
                strtolower(
                    pathinfo(
                        $_FILES["image"]["name"],
                        PATHINFO_EXTENSION
                    )
                );


            $allowed = array(
                "jpg",
                "jpeg",
                "png",
                "webp"
            );


            if(
                !in_array(
                    $extension,
                    $allowed
                )
            )
            {
                $message =
                    "Only JPG, JPEG, PNG or WEBP images are allowed.";
            }


            elseif(
                $_FILES["image"]["size"]
                > 5 * 1024 * 1024
            )
            {
                $message =
                    "Image size must be less than 5 MB.";
            }


            else
            {

                if(!is_dir("uploads"))
                {
                    mkdir(
                        "uploads",
                        0777,
                        true
                    );
                }


                $newImage =
                    time() .
                    "_" .
                    uniqid() .
                    "." .
                    $extension;


                if(
                    !move_uploaded_file(
                        $_FILES["image"]["tmp_name"],
                        "uploads/".$newImage
                    )
                )
                {
                    $message =
                        "Image upload failed.";
                }
            }
        }
    }


    /* =========================
       DATABASE UPDATE
    ========================= */

    if($message == "")
    {

        $title = mysqli_real_escape_string(
            $conn,
            $title
        );

        $category = mysqli_real_escape_string(
            $conn,
            $category
        );

        $location = mysqli_real_escape_string(
            $conn,
            $location
        );

        $price = mysqli_real_escape_string(
            $conn,
            $price
        );

        $payment_method =
            mysqli_real_escape_string(
                $conn,
                $payment_method
            );

        $payment_number =
            mysqli_real_escape_string(
                $conn,
                $payment_number
            );

        $newImage =
            mysqli_real_escape_string(
                $conn,
                $newImage
            );


        /* =========================
           NGO ID
        ========================= */

        $ngoIdValue = "NULL";


        if(
            strtolower(trim($type))
            == "donate"
        )
        {

            $ngoId =
                (int)(
                    $_POST["ngo_id"] ?? 0
                );


            if($ngoId > 0)
            {
                $ngoIdValue =
                    (string)$ngoId;
            }
        }


        /* =========================
           UPDATE QUERY
        ========================= */

        $sql = "UPDATE items SET

                title='$title',

                category='$category',

                price='$price',

                location='$location',

                image='$newImage',

                ngo_id=$ngoIdValue,

                payment_method='$payment_method',

                payment_number='$payment_number'

                WHERE item_id='$item_id'

                AND user_id='$user_id'

                AND LOWER(TRIM(status)) NOT IN ('sold', 'donated')

                AND NOT (LOWER(TRIM(type)) = 'donate' AND LOWER(TRIM(status)) = 'accepted')

                LIMIT 1";


        if(mysqli_query($conn,$sql))
        {

            /* =========================
               DELETE OLD IMAGE
            ========================= */

            if(
                $newImage !=
                ($item["image"] ?? "") &&

                !empty($item["image"]) &&

                file_exists(
                    "uploads/".$item["image"]
                )
            )
            {
                unlink(
                    "uploads/".$item["image"]
                );
            }


            /* =========================
               GET UPDATED ITEM
            ========================= */

            $sql = "SELECT *
                    FROM items
                    WHERE item_id='$item_id'
                    AND user_id='$user_id'
                    LIMIT 1";

            $result =
                mysqli_query(
                    $conn,
                    $sql
                );


            if($result)
            {
                $item =
                    mysqli_fetch_assoc(
                        $result
                    );
            }


            /* =========================
               UPDATE BACK PAGE
            ========================= */

            $item_type = strtolower(
                trim(
                    $item["type"] ?? ""
                )
            );


            if($item_type == "donate")
            {
                $backPage =
                    "donate_items.php";
            }
            else
            {
                $backPage =
                    "sell_items.php";
            }


            $success = true;

            $message =
                "Item updated successfully!";
        }


        else
        {

            /* =========================
               DELETE NEW IMAGE
            ========================= */

            if(
                $newImage !=
                ($item["image"] ?? "") &&

                !empty($newImage) &&

                file_exists(
                    "uploads/".$newImage
                )
            )
            {
                unlink(
                    "uploads/".$newImage
                );
            }


            $message =
                "Failed to update item. "
                . mysqli_error($conn);
        }
    }
}

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Edit Item | EcoCycle
</title>


<link
    rel="stylesheet"
    href="style.css"
>


<style>

/* =========================
   MAIN
========================= */

.main{
    margin-left:230px;

    padding:
        105px
        45px
        50px;

    max-width:1100px;
}


/* =========================
   BACK
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

    margin-bottom:7px;
}

.header p{
    color:#777;

    font-size:14px;
}


/* =========================
   FORM CARD
========================= */

.form-card{
    background:white;

    border:1px solid #ddd;

    border-radius:12px;

    padding:30px;
}


/* =========================
   MESSAGE
========================= */

.message{
    padding:13px;

    border-radius:7px;

    margin-bottom:20px;

    font-size:14px;
}

.message.error{
    background:#fff0f0;

    color:#c62828;
}

.message.success{
    background:#eaf7ef;

    color:#168a45;
}


/* =========================
   FORM
========================= */

.form-group{
    margin-bottom:20px;
}

label{
    display:block;

    font-size:14px;

    font-weight:bold;

    margin-bottom:8px;
}


input,
select{
    width:100%;

    height:45px;

    border:1px solid #ccc;

    border-radius:7px;

    padding:
        0
        12px;

    font-size:14px;
}

input:focus,
select:focus{
    outline:none;

    border-color:#168a45;
}


input[type="file"]{
    width:100%;

    height:45px;

    padding:
        0
        12px;

    display:block;

    line-height:43px;
}


/* =========================
   ROW
========================= */

.row{
    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:18px;
}


/* =========================
   TYPE
========================= */

.type-box{
    display:flex;

    gap:15px;
}

.type-box div{
    flex:1;
}

.type-box input{
    display:none;
}

.type-box label{
    border:1px solid #ccc;

    padding:13px;

    border-radius:7px;

    text-align:center;

    cursor:not-allowed;

    opacity:0.8;
}

.type-box input:checked + label{
    background:#eaf7ef;

    border-color:#168a45;

    color:#168a45;
}


/* =========================
   PAYMENT
========================= */

.payment-box{
    padding:15px;

    background:#fafcfb;

    border:1px solid #ddd;

    border-radius:7px;
}

.payment-note{
    color:#777;

    font-size:12px;

    margin-bottom:15px;
}

.payment-stack{
    display:flex;

    flex-direction:column;

    gap:12px;
}

.payment-row{
    display:grid;

    grid-template-columns:
        180px 1fr;

    gap:15px;

    align-items:center;
}

.payment-option{
    display:flex;

    align-items:center;

    gap:8px;

    margin:0;

    cursor:pointer;
}

.payment-option input[type="checkbox"]{
    width:auto;

    height:auto;

    cursor:pointer;
}

.payment-number-input{
    width:100%;
}


/* =========================
   IMAGE
========================= */

.current-image{
    margin-bottom:15px;
}

.current-image img,
.preview img{
    width:220px;

    height:160px;

    object-fit:cover;

    border-radius:8px;

    border:1px solid #ddd;
}

.no-image{
    color:#888;

    font-size:13px;
}

.preview{
    margin-top:12px;
}

.preview img{
    display:none;
}


/* =========================
   BUTTONS
========================= */

.buttons{
    display:flex;

    gap:10px;

    margin-top:10px;
}

button,
.cancel{
    height:45px;

    padding:
        0
        25px;

    border-radius:7px;

    font-size:14px;

    font-weight:bold;
}

button{
    border:none;

    background:#168a45;

    color:white;

    cursor:pointer;
}

button:hover{
    background:#117239;
}

.cancel{
    border:1px solid #ccc;

    background:white;

    color:#444;

    text-decoration:none;

    display:flex;

    align-items:center;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width:800px)
{

    .main{
        margin-left:70px;

        padding:
            105px
            20px
            40px;
    }

    .row{
        grid-template-columns:1fr;
    }

    .payment-row{
        grid-template-columns:1fr;

        gap:8px;
    }

}


@media(max-width:550px)
{

    .main{
        padding-left:15px;

        padding-right:15px;
    }

}

</style>

</head>


<body>


<?php include("header_sidebar.php"); ?>


<main class="main">


<!-- =========================
     DYNAMIC BACK
========================= -->

<a
    href="<?php echo htmlspecialchars($backPage); ?>"
    class="back"
>

    ← Back

</a>


<!-- =========================
     HEADER
========================= -->

<div class="header">

    <h1>
        Edit Item
    </h1>

    <p>
        Update your item information.
    </p>

</div>


<div class="form-card">


<!-- =========================
     MESSAGE
========================= -->

<?php

if($message != "")
{

?>

<div
    class="message
    <?php
    echo $success
        ? "success"
        : "error";
    ?>"
>

<?php

echo htmlspecialchars(
    $message
);

?>

</div>

<?php

}

?>


<form
    method="post"
    enctype="multipart/form-data"
>


<!-- =========================
     NGO
========================= -->

<?php

if(
    strtolower(
        trim(
            $item["type"] ?? ""
        )
    )
    == "donate"
):

?>

<div class="form-group">

    <label>
        Select NGO
    </label>

    <select
        name="ngo_id"
        required
    >

        <option value="">
            Choose an NGO
        </option>


        <?php

        foreach(
            $ngoOptions
            as $ngo
        ):

        ?>

        <option
            value="<?php
            echo (int)$ngo["ngo_id"];
            ?>"

            <?php

            echo (
                (string)(
                    $item["ngo_id"]
                    ?? ""
                )
                ===
                (string)(
                    $ngo["ngo_id"]
                )
            )
            ? "selected"
            : "";

            ?>
        >

            <?php

            echo htmlspecialchars(
                $ngo["name"]
            );

            ?>

        </option>

        <?php

        endforeach;

        ?>

    </select>

</div>

<?php

endif;

?>


<!-- =========================
     TITLE
========================= -->

<div class="form-group">

    <label>
        Item Title
    </label>

    <input
        type="text"
        name="title"

        value="<?php
        echo htmlspecialchars(
            $item["title"]
        );
        ?>"

        required
    >

</div>


<!-- =========================
     CATEGORY + LOCATION
========================= -->

<div class="row">


<div class="form-group">

    <label>
        Category
    </label>

    <select
        name="category"
        required
    >

        <option value="">
            Select Category
        </option>


        <?php

        $categories = array(

            "Electronics",

            "Furniture",

            "Clothing",

            "Books",

            "Home Appliances",

            "Kitchen",

            "Sports",

            "Toys",

            "Others"

        );


        foreach(
            $categories
            as $category
        )
        {

            $selected =
                (
                    $item["category"]
                    == $category
                )
                ? "selected"
                : "";

        ?>

        <option
            value="<?php
            echo htmlspecialchars(
                $category
            );
            ?>"

            <?php echo $selected; ?>
        >

            <?php

            echo htmlspecialchars(
                $category
            );

            ?>

        </option>

        <?php

        }

        ?>

    </select>

</div>


<div class="form-group">

    <label>
        Location
    </label>

    <input
        type="text"
        name="location"

        value="<?php
        echo htmlspecialchars(
            $item["location"]
        );
        ?>"

        required
    >

</div>


</div>


<!-- =========================
     LISTING TYPE
========================= -->

<div class="form-group">

    <label>
        Listing Type
    </label>


    <div class="type-box">


        <div>

            <input
                type="radio"
                id="sell"
                name="type"
                value="Sell"

                <?php

                if(
                    strtolower(
                        $item["type"]
                    )
                    == "sell"
                )
                {
                    echo "checked";
                }

                ?>

                disabled
            >

            <label for="sell">

                💰 Sell

            </label>

        </div>


        <div>

            <input
                type="radio"
                id="donate"
                name="type"
                value="Donate"

                <?php

                if(
                    strtolower(
                        $item["type"]
                    )
                    == "donate"
                )
                {
                    echo "checked";
                }

                ?>

                disabled
            >

            <label for="donate">

                🎁 Donate

            </label>

        </div>


    </div>

</div>


<!-- =========================
     SELL FIELDS
========================= -->

<?php

if(
    strtolower(
        $item["type"]
    )
    == "sell"
)
{

?>


<div class="form-group">

    <label>
        Price
    </label>

    <input
        type="number"
        name="price"

        min="0"

        value="<?php
        echo htmlspecialchars(
            $item["price"]
        );
        ?>"

        required
    >

</div>


<!-- =========================
     PAYMENT
========================= -->

<div class="form-group">

    <label>
        Payment Method
    </label>


    <div class="payment-box">


        <div class="payment-note">

            You can provide Bkash, Nagad, or both.

        </div>


        <div class="payment-stack">


            <!-- BKASH -->

            <div class="payment-row">

                <label
                    class="payment-option"
                >

                    <input
                        type="checkbox"
                        id="bkash"
                        name="bkash"

                        <?php

                        if(
                            $old_bkash
                            != ""
                        )
                        {
                            echo "checked";
                        }

                        ?>
                    >

                    Bkash

                </label>


                <input
                    type="text"

                    name="bkash_number"

                    id="bkash_number"

                    class="payment-number-input"

                    maxlength="11"

                    value="<?php

                    echo htmlspecialchars(
                        $old_bkash
                    );

                    ?>"

                    placeholder="01XXXXXXXXX"
                >

            </div>


            <!-- NAGAD -->

            <div class="payment-row">

                <label
                    class="payment-option"
                >

                    <input
                        type="checkbox"
                        id="nagad"
                        name="nagad"

                        <?php

                        if(
                            $old_nagad
                            != ""
                        )
                        {
                            echo "checked";
                        }

                        ?>
                    >

                    Nagad

                </label>


                <input
                    type="text"

                    name="nagad_number"

                    id="nagad_number"

                    class="payment-number-input"

                    maxlength="11"

                    value="<?php

                    echo htmlspecialchars(
                        $old_nagad
                    );

                    ?>"

                    placeholder="01XXXXXXXXX"
                >

            </div>


        </div>

    </div>

</div>


<?php

}

else

{

?>

<input
    type="hidden"
    name="price"
    value="0"
>

<?php

}

?>


<!-- =========================
     IMAGE
========================= -->

<div class="form-group">


    <label>
        Current Image
    </label>


    <div class="current-image">


        <?php

        if(
            !empty(
                $item["image"]
            )
            &&
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

            alt="Current Image"
        >

        <?php

        }

        else

        {

        ?>

        <div class="no-image">

            No image uploaded.

        </div>

        <?php

        }

        ?>

    </div>


    <label>
        Change Image
    </label>


    <input
        type="file"

        name="image"

        accept=".jpg,.jpeg,.png,.webp"

        onchange="previewImage(event)"
    >


    <div class="preview">

        <img
            id="preview"
            alt="New Image Preview"
        >

    </div>


</div>


<!-- =========================
     BUTTONS
========================= -->

<div class="buttons">


    <button
        type="submit"
    >

        Update Item

    </button>


    <a
        href="<?php
        echo htmlspecialchars(
            $backPage
        );
        ?>"

        class="cancel"
    >

        Cancel

    </a>


</div>


</form>


</div>


</main>


<script>

function previewImage(event)
{

    var preview =
        document.getElementById(
            "preview"
        );


    if(
        event.target.files[0]
    )
    {

        preview.src =
            URL.createObjectURL(
                event.target.files[0]
            );

        preview.style.display =
            "block";
    }

}

</script>


</body>

</html>
