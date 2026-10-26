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
    href="assets/css/style.css"
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


<?php require APP_PATH . '/views/layouts/header_sidebar.php'; ?>


<main class="main">


<!-- =================================================
     BACK
================================================= -->

<a
    href="index.php?url=wanted/view&id=<?php echo $wanted_id; ?>"
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
        action="index.php?url=offers/create&wanted_id=<?php echo $wanted_id; ?>"
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