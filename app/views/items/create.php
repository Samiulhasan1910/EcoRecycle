<!DOCTYPE html>
<html>

<head>

    <title>Post Item | EcoCycle</title>

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <!-- COMMON CSS -->
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
        padding:105px 45px 50px;
        max-width:1100px;
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
        padding:12px;
        border-radius:7px;
        margin-bottom:20px;
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
        padding:0 12px;
        font-size:14px;
        background:white;
        box-sizing:border-box;
    }

    input:focus,
    select:focus{
        outline:none;
        border-color:#168a45;
    }


    /* =========================
       IMAGE INPUT
       ========================= */

    input[type="file"]{
        width:100%;
        height:45px;
        padding:7px 10px;
        cursor:pointer;
        line-height:28px;
        display:block;
    }

    input[type="file"]::file-selector-button{
        height:31px;
        padding:0 14px;
        margin-right:10px;
        border:1px solid #ccc;
        border-radius:5px;
        background:#f5f5f5;
        cursor:pointer;
        font-size:13px;
    }

    input[type="file"]::file-selector-button:hover{
        background:#eaf7ef;
        border-color:#168a45;
    }


    /* =========================
       ROW
       ========================= */

    .row{
        display:grid;
        grid-template-columns:1fr 1fr;
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
        cursor:pointer;
        text-align:center;
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
        color:#777;
        font-size:12px;
        margin-bottom:15px;
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
        grid-template-columns:180px 1fr;
        gap:15px;
        align-items:center;
    }

    .payment-option{
        display:flex;
        align-items:center;
        gap:8px;
        height:45px;
        padding:0 12px;
        border:1px solid #ccc;
        border-radius:7px;
        background:white;
        font-size:14px;
        margin:0;
        cursor:pointer;
        box-sizing:border-box;
    }

    .payment-option input{
        width:auto;
        height:auto;
        margin:0;
        cursor:pointer;
    }

    .payment-number-input{
        width:100%;
        height:45px;
        border:1px solid #ccc;
        border-radius:7px;
        padding:0 12px;
        font-size:14px;
        box-sizing:border-box;
    }

    .payment-number-input:focus{
        outline:none;
        border-color:#168a45;
    }

    .payment-number-input:disabled{
        background:#f5f5f5;
        cursor:not-allowed;
    }

    .ngo-field{
        display:none;
    }

    .ngo-field select{
        width:100%;
        height:45px;
        border:1px solid #ccc;
        border-radius:7px;
        padding:0 12px;
        font-size:14px;
        background:white;
        box-sizing:border-box;
    }


    /* =========================
       IMAGE PREVIEW
       ========================= */

    .preview{
        margin-top:12px;
    }

    .preview img{
        width:220px;
        height:160px;
        object-fit:cover;
        border-radius:8px;
        display:none;
    }


    /* =========================
       BUTTONS
       ========================= */

    .buttons{
        display:flex;
        gap:10px;
    }

    button,
    .cancel{
        height:45px;
        padding:0 25px;
        border-radius:7px;
        font-size:14px;
        font-weight:bold;
        box-sizing:border-box;
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
        justify-content:center;
    }


    /* =========================
       MOBILE
       ========================= */

    @media(max-width:800px)
    {
        .main{
            margin-left:70px;
            padding:100px 20px;
        }

        .row{
            grid-template-columns:1fr;
        }
    }


    @media(max-width:550px)
    {
        .form-card{
            padding:20px;
        }

        .buttons{
            flex-direction:column;
        }

        .payment-row{
            grid-template-columns:1fr;
            gap:8px;
        }
    }

    </style>

</head>


<body>


<?php require APP_PATH . '/views/layouts/header_sidebar.php'; ?>


<main class="main">


    <!-- HEADER -->

    <div class="header">

        <h1>
            Post an Item
        </h1>

        <p>
            Sell or donate an item on EcoCycle.
        </p>

    </div>


    <!-- FORM CARD -->

    <div class="form-card">


        <!-- MESSAGE -->

        <?php

        if($message != "")
        {

        ?>

            <div
                class="message <?php echo $messageType; ?>"
            >

                <?php
                echo htmlspecialchars($message);
                ?>

            </div>

        <?php

        }

        ?>


        <!-- FORM -->

        <form
            method="post"
            enctype="multipart/form-data"
        >


            <!-- ITEM TITLE -->

            <div class="form-group">

                <label>
                    Item Title
                </label>

                <input
                    type="text"
                    name="title"
                    placeholder="Example: Study Table"
                    value="<?php echo htmlspecialchars($_POST["title"] ?? ""); ?>"
                    required
                >

            </div>


            <!-- CATEGORY + LOCATION -->

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

                </div>


                <div class="form-group">

                    <label>
                        Location
                    </label>

                    <input
                        type="text"
                        name="location"
                        placeholder="Example: Mirpur, Dhaka"
                        value="<?php echo htmlspecialchars($_POST["location"] ?? ""); ?>"
                        required
                    >

                </div>


            </div>


            <!-- LISTING TYPE -->

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
                            onclick="showSell()"
                            <?php
                            if(($_POST["type"] ?? "") == "Sell")
                            {
                                echo "checked";
                            }
                            ?>
                            required
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
                            onclick="showDonate()"
                            <?php
                            if(($_POST["type"] ?? "") == "Donate")
                            {
                                echo "checked";
                            }
                            ?>
                        >

                        <label for="donate">
                            🎁 Donate
                        </label>

                    </div>


                </div>

            </div>


            <!-- PRICE -->

            <div class="form-group">

                <label>
                    Price
                </label>

                <input
                    type="number"
                    name="price"
                    id="price"
                    min="0"
                    placeholder="Enter price in BDT"
                    value="<?php echo htmlspecialchars($_POST["price"] ?? ""); ?>"
                >

            </div>


            <!-- PAYMENT -->

            <div class="form-group ngo-field" id="ngo-section">
                <label>
                    Select NGO
                </label>

                <select name="ngo_id" id="ngo_id">
                    <option value="">Choose an NGO</option>
                    <?php foreach($ngoOptions as $ngo): ?>
                        <option value="<?php echo (int)$ngo["ngo_id"]; ?>">
                            <?php echo htmlspecialchars($ngo["name"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div
                class="form-group"
                id="payment-section"
            >

                <label>
                    Payment Method
                </label>


                <div class="payment-box">


                    <div class="payment-note">

                        You can provide Bkash, Nagad, or both payment numbers.

                    </div>


                    <div class="payment-stack">


                        <!-- BKASH -->

                        <div class="payment-row">


                            <label
                                class="payment-option"
                                for="bkash_method"
                            >

                                <input
                                    type="checkbox"
                                    name="payment_methods[]"
                                    value="Bkash"
                                    id="bkash_method"
                                    <?php
                                    if(
                                        !empty($_POST["bkash_number"])
                                    )
                                    {
                                        echo "checked";
                                    }
                                    ?>
                                >

                                <span>
                                    Bkash
                                </span>

                            </label>


                            <input
                                type="text"
                                name="bkash_number"
                                id="bkash_number"
                                class="payment-number-input"
                                placeholder="01XXXXXXXXX"
                                maxlength="11"
                                value="<?php echo htmlspecialchars($_POST["bkash_number"] ?? ""); ?>"
                            >


                        </div>


                        <!-- NAGAD -->

                        <div class="payment-row">


                            <label
                                class="payment-option"
                                for="nagad_method"
                            >

                                <input
                                    type="checkbox"
                                    name="payment_methods[]"
                                    value="Nagad"
                                    id="nagad_method"
                                    <?php
                                    if(
                                        !empty($_POST["nagad_number"])
                                    )
                                    {
                                        echo "checked";
                                    }
                                    ?>
                                >

                                <span>
                                    Nagad
                                </span>

                            </label>


                            <input
                                type="text"
                                name="nagad_number"
                                id="nagad_number"
                                class="payment-number-input"
                                placeholder="01XXXXXXXXX"
                                maxlength="11"
                                value="<?php echo htmlspecialchars($_POST["nagad_number"] ?? ""); ?>"
                            >


                        </div>


                    </div>


                </div>

            </div>


            <!-- IMAGE -->

            <div class="form-group">

                <label>
                    Item Image
                </label>


                <input
                    type="file"
                    name="image"
                    accept=".jpg,.jpeg,.png,.webp"
                    onchange="previewImage(event)"
                    required
                >


                <div class="preview">

                    <img
                        id="preview"
                        alt="Image Preview"
                    >

                </div>


            </div>


            <!-- BUTTONS -->

            <div class="buttons">


                <button type="submit">
                    Post Item
                </button>


                <a
                    href="index.php?url=dashboard"
                    class="cancel"
                >
                    Cancel
                </a>


            </div>


        </form>


    </div>


</main>


<script>


/* =====================================================
   SHOW SELL
   ===================================================== */

function showSell()
{
    document.getElementById("payment-section").style.display =
        "block";

    document.getElementById("ngo-section").style.display =
        "none";

    var price =
        document.getElementById("price");

    price.disabled = false;

    if(price.value === "0")
    {
        price.value = "";
    }
}


/* =====================================================
   SHOW DONATE
   ===================================================== */

function showDonate()
{
    document.getElementById("payment-section").style.display =
        "none";

    document.getElementById("ngo-section").style.display =
        "block";

    var price =
        document.getElementById("price");

    price.value = "0";

    price.disabled = true;


    /* Clear payment fields */

    document.getElementById("bkash_number").value = "";

    document.getElementById("nagad_number").value = "";

    document.getElementById("bkash_method").checked = false;

    document.getElementById("nagad_method").checked = false;
}


/* =====================================================
   PAYMENT CHECKBOX
   ===================================================== */

function updatePaymentInputs()
{
    var bkashMethod =
        document.getElementById("bkash_method");

    var nagadMethod =
        document.getElementById("nagad_method");

    var bkashNumber =
        document.getElementById("bkash_number");

    var nagadNumber =
        document.getElementById("nagad_number");


    if(bkashMethod.checked)
    {
        bkashNumber.disabled = false;
    }
    else
    {
        bkashNumber.disabled = true;
        bkashNumber.value = "";
    }


    if(nagadMethod.checked)
    {
        nagadNumber.disabled = false;
    }
    else
    {
        nagadNumber.disabled = true;
        nagadNumber.value = "";
    }
}


/* =====================================================
   IMAGE PREVIEW
   ===================================================== */

function previewImage(event)
{
    var preview =
        document.getElementById("preview");


    if(event.target.files[0])
    {
        preview.src =
            URL.createObjectURL(
                event.target.files[0]
            );

        preview.style.display =
            "block";
    }
}


/* =====================================================
   PAGE LOAD
   ===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function()
    {

        var bkashMethod =
            document.getElementById("bkash_method");

        var nagadMethod =
            document.getElementById("nagad_method");


        bkashMethod.addEventListener(
            "change",
            updatePaymentInputs
        );


        nagadMethod.addEventListener(
            "change",
            updatePaymentInputs
        );


        updatePaymentInputs();


        /* -----------------------------
           RESTORE LISTING TYPE
           ----------------------------- */

        var selectedType =
            document.querySelector(
                'input[name="type"]:checked'
            );


        if(selectedType)
        {
            if(selectedType.value === "Sell")
            {
                showSell();
            }
            else if(selectedType.value === "Donate")
            {
                showDonate();
            }
        }
        else
        {
            document.getElementById(
                "payment-section"
            ).style.display = "none";
            document.getElementById(
                "ngo-section"
            ).style.display = "none";
        }

    }
);

</script>


</body>

</html>
