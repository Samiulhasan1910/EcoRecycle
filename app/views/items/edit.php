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
    href="assets/css/style.css"
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


<?php require APP_PATH . '/views/layouts/header_sidebar.php'; ?>


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
