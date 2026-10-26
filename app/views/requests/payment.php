<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    Make Payment | EcoCycle
</title>

<link
    rel="stylesheet"
    href="assets/css/style.css"
>

<style>

.main{
    margin-left:230px;
    padding:105px 45px 50px;
    max-width:800px;
}

.box{
    background:white;
    border:1px solid #e5e5e5;
    border-radius:16px;
    padding:30px;
    box-shadow:0 10px 25px rgba(0,0,0,0.04);
}

.title{
    font-size:28px;
    font-weight:bold;
    margin-bottom:10px;
}

.subtitle{
    color:#666;
    margin-bottom:25px;
    line-height:1.7;
}

.item-info{
    background:#f7faf8;
    border:1px solid #dfece3;
    border-radius:10px;
    padding:16px;
    margin-bottom:20px;
}

.item-info p{
    margin:6px 0;
    font-size:14px;
    color:#555;
}

.item-info strong{
    color:#222;
}

.amount-banner{
    background:#eaf7ef;
    border:1px solid #d4efd9;
    color:#0f7c4b;
    border-radius:10px;
    padding:14px 16px;
    margin-bottom:24px;
    font-size:18px;
    font-weight:bold;
}

.payment-methods{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
    margin-bottom:24px;
}

.method-option{
    position:relative;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:16px 18px;
    border:1px solid #dfe9e2;
    border-radius:12px;
    background:#fafcfb;
    cursor:pointer;
    transition:0.2s ease;
}

.method-option:hover{
    border-color:#168a45;
}

.method-option input{
    position:absolute;
    opacity:0;
    pointer-events:none;
}

.method-option.selected{
    border-color:#168a45;
    background:#edf9f1;
    box-shadow:0 0 0 3px rgba(22,138,69,0.08);
}

.method-brand{
    display:flex;
    align-items:center;
    gap:12px;
    font-weight:bold;
    color:#1f2a37;
}

.method-logo{
    width:42px;
    height:42px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:12px;
    font-weight:900;
    font-size:16px;
}

.bkash{
    background:#fdf4d9;
    color:#d99a00;
}

.nagad{
    background:#eaf5ff;
    color:#0f67d6;
}

.field-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:18px;
    margin-bottom:18px;
}

.field{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.field label{
    font-size:13px;
    font-weight:bold;
    color:#333;
}

.field input,
.field select{
    width:100%;
    height:46px;
    padding:0 12px;
    border:1px solid #cfe0d5;
    border-radius:10px;
    box-sizing:border-box;
    outline:none;
    background:white;
}

.field input:focus,
.field select:focus{
    border-color:#168a45;
}

.actions{
    display:flex;
    gap:12px;
    margin-top:25px;
}

.button{
    border:none;
    background:#168a45;
    color:white;
    padding:12px 20px;
    border-radius:8px;
    cursor:pointer;
    font-weight:bold;
    text-decoration:none;
}

.button:hover{
    background:#117239;
}

.secondary{
    background:#777;
}

.secondary:hover{
    background:#666;
}

.alert{
    padding:13px 15px;
    border-radius:8px;
    margin-bottom:20px;
    font-size:14px;
}

.alert-error{
    background:#fdecec;
    color:#c62828;
    border:1px solid #f5caca;
}

.alert-success{
    background:#eaf7ef;
    color:#168a45;
    border:1px solid #cdebd7;
}

.success-actions{
    margin-top:20px;
}

@media(max-width:650px)
{
    .main{
        margin-left:70px;
        padding:100px 20px 40px;
    }

    .payment-methods,
    .field-grid{
        grid-template-columns:1fr;
    }

    .box{
        padding:20px;
    }
}

</style>

</head>

<body>

<?php require APP_PATH . '/views/layouts/header_sidebar.php'; ?>


<main class="main">

<div class="box">

    <div class="title">
        Make Payment
    </div>


    <div class="subtitle">

        <?php

        if($isOfferPayment)
        {
            echo "Complete the payment for the accepted wanted offer.";
        }
        else
        {
            echo "Complete the payment to purchase this item.";
        }

        ?>

    </div>


    <?php

    if($paymentError !== "")
    {
    ?>

        <div class="alert alert-error">

            <?php

            echo htmlspecialchars(
                $paymentError
            );

            ?>

        </div>

    <?php
    }


    if($successMessage !== "")
    {
    ?>

        <div class="alert alert-success">

            <?php

            echo htmlspecialchars(
                $successMessage
            );

            ?>

        </div>


        <div class="success-actions">

            <a
                href="index.php?url=transactions"
                class="button"
            >
                View Transactions
            </a>

            <?php if($deliveryJobId > 0) { ?>

                <a
                    href="index.php?url=delivery/track"
                    class="button secondary"
                >
                    Track Delivery
                </a>

            <?php } ?>


            <?php

            if($isOfferPayment)
            {
            ?>

                <a
                    href="index.php?url=notifications"
                    class="button secondary"
                >
                    Notifications
                </a>

            <?php
            }
            else
            {
            ?>

                <a
                    href="index.php?url=items/browse"
                    class="button secondary"
                >
                    Browse Items
                </a>

            <?php
            }

            ?>

        </div>

    <?php
    }
    else
    {
    ?>


        <!-- =========================================
             ITEM / WANTED OFFER
        ========================================== -->

        <div class="item-info">

            <p>

                <strong>
                    Item:
                </strong>

                <?php

                echo htmlspecialchars(
                    $itemTitle
                );

                ?>

            </p>


            <p>

                <strong>

                    <?php

                    if($isOfferPayment)
                    {
                        echo "Offered By:";
                    }
                    else
                    {
                        echo "Seller:";
                    }

                    ?>

                </strong>


                <?php

                if($isOfferPayment)
                {
                    echo htmlspecialchars(
                        $sellerName
                    );
                }
                else
                {
                    echo htmlspecialchars(
                        $item["seller_name"]
                        ?? "User"
                    );
                }

                ?>

            </p>

        </div>


        <!-- =========================================
             AMOUNT
        ========================================== -->

        <div class="amount-banner">

            Amount:

            <?php

            echo number_format(
                $itemPrice,
                2
            );

            ?>

            BDT

        </div>


        <!-- =========================================
             PAYMENT FORM
        ========================================== -->

        <form
            method="POST"

            action="<?php

            if($isOfferPayment)
            {
                echo "index.php?url=payment&offer_id=" .
                    $offer_id;
            }
            else
            {
                echo "index.php?url=payment&item_id=" .
                    $item_id;
            }

            ?>"
        >


            <!-- =====================================
                 PAYMENT METHODS
            ====================================== -->

            <div class="payment-methods">

                <?php

                if(
                    isset(
                        $paymentNumbers["Bkash"]
                    )
                )
                {
                ?>

                    <label
                        class="method-option selected"
                    >

                        <span class="method-brand">

                            <span
                                class="method-logo bkash"
                            >
                                B
                            </span>

                            Bkash

                        </span>

                        <span>
                            ✓
                        </span>

                        <input
                            type="radio"
                            name="payment_method"
                            value="Bkash"
                            checked
                        >

                    </label>

                <?php
                }


                if(
                    isset(
                        $paymentNumbers["Nagad"]
                    )
                )
                {
                ?>

                    <label
                        class="method-option

                        <?php

                        echo !isset(
                            $paymentNumbers["Bkash"]
                        )
                            ? "selected"
                            : "";

                        ?>"
                    >

                        <span class="method-brand">

                            <span
                                class="method-logo nagad"
                            >
                                N
                            </span>

                            Nagad

                        </span>

                        <span>
                            ✓
                        </span>

                        <input
                            type="radio"
                            name="payment_method"
                            value="Nagad"

                            <?php

                            echo !isset(
                                $paymentNumbers["Bkash"]
                            )
                                ? "checked"
                                : "";

                            ?>

                        >

                    </label>

                <?php
                }

                ?>

            </div>


            <!-- =====================================
                 PAYMENT FIELDS
            ====================================== -->

            <div class="field-grid">

                <div class="field">

                    <label>
                        Amount
                    </label>

                    <input
                        type="number"
                        name="amount"

                        value="<?php

                        echo htmlspecialchars(
                            number_format(
                                $itemPrice,
                                2,
                                ".",
                                ""
                            )
                        );

                        ?>"

                        min="1"
                        step="0.01"
                        required
                    >

                </div>


                <div class="field">

                    <label>
                        Your Sender Number
                    </label>

                    <input
                        type="text"
                        name="sender_number"
                        placeholder="01XXXXXXXXX"
                        maxlength="11"
                        required
                    >

                </div>

            </div>


            <div class="field-grid">


                <!-- =================================
                     RECEIVER NUMBER

                     ONLY ONE:
                     FIXED INPUT

                     BOTH:
                     DROPDOWN
                ================================== -->

                <div class="field">

                    <label>

                        <?php

                        if($isOfferPayment)
                        {
                            echo "Offerer Receiver Number";
                        }
                        else
                        {
                            echo "Seller Receiver Number";
                        }

                        ?>

                    </label>


                    <?php

                    $hasBkash =
                        isset(
                            $paymentNumbers["Bkash"]
                        ) &&
                        trim(
                            $paymentNumbers["Bkash"]
                        ) !== "";


                    $hasNagad =
                        isset(
                            $paymentNumbers["Nagad"]
                        ) &&
                        trim(
                            $paymentNumbers["Nagad"]
                        ) !== "";


                    $paymentMethodCount =
                        ($hasBkash ? 1 : 0) +
                        ($hasNagad ? 1 : 0);


                    /* =================================
                       BOTH BKASH + NAGAD
                    ================================== */

                    if(
                        $paymentMethodCount === 2
                    )
                    {
                    ?>

                        <select
                            name="receiver_number"
                            id="receiver_number"
                            required
                        >

                            <option
                                value="<?php

                                echo htmlspecialchars(
                                    $paymentNumbers["Bkash"]
                                );

                                ?>"
                            >

                                Bkash -
                                <?php

                                echo htmlspecialchars(
                                    $paymentNumbers["Bkash"]
                                );

                                ?>

                            </option>


                            <option
                                value="<?php

                                echo htmlspecialchars(
                                    $paymentNumbers["Nagad"]
                                );

                                ?>"
                            >

                                Nagad -
                                <?php

                                echo htmlspecialchars(
                                    $paymentNumbers["Nagad"]
                                );

                                ?>

                            </option>

                        </select>

                    <?php

                    }


                    /* =================================
                       ONLY ONE METHOD
                    ================================== */

                    else
                    {
                        $fixedReceiver = "";


                        if($hasBkash)
                        {
                            $fixedReceiver =
                                $paymentNumbers["Bkash"];
                        }

                        elseif($hasNagad)
                        {
                            $fixedReceiver =
                                $paymentNumbers["Nagad"];
                        }

                    ?>

                        <input
                            type="text"
                            name="receiver_number"
                            id="receiver_number"
                            readonly

                            value="<?php

                            echo htmlspecialchars(
                                $fixedReceiver
                            );

                            ?>"
                        >

                    <?php
                    }

                    ?>

                </div>


                <!-- =================================
                     PIN
                ================================== -->

                <div class="field">

                    <label>
                        PIN
                    </label>

                    <input
                        type="password"
                        name="pin"
                        placeholder="****"
                        maxlength="4"
                        inputmode="numeric"
                        required
                    >

                </div>

            </div>


            <!-- =====================================
                 ACTIONS
            ====================================== -->

            <div class="actions">

                <button
                    type="submit"
                    class="button"
                >
                    Confirm Payment
                </button>


                <?php

                if($isOfferPayment)
                {
                ?>

                    <a
                        href="index.php?url=offers/view&offer_id=<?php echo $offer_id; ?>"
                        class="button secondary"
                    >
                        Cancel
                    </a>

                <?php
                }
                else
                {
                ?>

                    <a
                        href="index.php?url=items/view&id=<?php echo $item_id; ?>"
                        class="button secondary"
                    >
                        Cancel
                    </a>

                <?php
                }

                ?>

            </div>

        </form>


    <?php
    }

    ?>

</div>

</main>


<script>

/* =====================================================
   PAYMENT NUMBERS
===================================================== */

const sellerNumbers = {

    Bkash:
        <?php

        echo json_encode(
            $paymentNumbers["Bkash"]
            ?? ""
        );

        ?>,

    Nagad:
        <?php

        echo json_encode(
            $paymentNumbers["Nagad"]
            ?? ""
        );

        ?>

};


/* =====================================================
   UPDATE RECEIVER
===================================================== */

function updateReceiverNumber()
{
    const selected =
        document.querySelector(
            'input[name="payment_method"]:checked'
        );


    const receiver =
        document.getElementById(
            "receiver_number"
        );


    if(!selected || !receiver)
    {
        return;
    }


    /*
     * If both payment methods exist,
     * receiver is a SELECT dropdown.
     */

    if(
        receiver.tagName === "SELECT"
    )
    {
        receiver.value =
            sellerNumbers[
                selected.value
            ]
            || "";

        return;
    }


    /*
     * If only one method exists,
     * receiver stays fixed.
     */

    receiver.value =
        sellerNumbers[
            selected.value
        ]
        || "";
}


/* =====================================================
   PAYMENT METHOD CLICK
===================================================== */

document
.querySelectorAll(".method-option")
.forEach(function(option)
{
    option.addEventListener(
        "click",
        function()
        {
            document
            .querySelectorAll(".method-option")
            .forEach(function(el)
            {
                el.classList.remove(
                    "selected"
                );
            });


            option.classList.add(
                "selected"
            );


            const input =
                option.querySelector(
                    "input"
                );


            if(input)
            {
                input.checked = true;
            }


            updateReceiverNumber();
        }
    );
});


/* =====================================================
   PAYMENT METHOD CHANGE
===================================================== */

document
.querySelectorAll(
    'input[name="payment_method"]'
)
.forEach(function(input)
{
    input.addEventListener(
        "change",
        updateReceiverNumber
    );
});


/* =====================================================
   INITIAL RECEIVER
===================================================== */

updateReceiverNumber();

</script>

</body>

</html>
