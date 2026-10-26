<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    View Offer | EcoCycle
</title>

<link
    rel="stylesheet"
    href="assets/css/style.css"
>

<style>

/* =====================================================
   MAIN
===================================================== */

.main
{
    margin-left:230px;

    padding:105px 45px 60px;

    box-sizing:border-box;

    min-height:100vh;
}


/* =====================================================
   CONTAINER
===================================================== */

.offer-container
{
    width:100%;

    max-width:900px;

    margin:0 auto;
}


/* =====================================================
   BACK
===================================================== */

.back
{
    display:inline-block;

    margin-bottom:20px;

    color:#168a45;

    text-decoration:none;

    font-size:14px;

    font-weight:bold;
}

.back:hover
{
    color:#117239;

    text-decoration:underline;
}


/* =====================================================
   PAGE HEADER
===================================================== */

.page-header
{
    margin-bottom:22px;
}

.page-header h1
{
    margin:0 0 6px;

    font-size:28px;

    color:#222;
}

.page-header p
{
    margin:0;

    color:#777;

    font-size:14px;
}


/* =====================================================
   OFFER CARD
===================================================== */

.offer-card
{
    background:#fff;

    border:1px solid #ddd;

    border-radius:12px;

    padding:25px;

    margin-bottom:20px;

    box-sizing:border-box;
}


/* =====================================================
   OFFERER
===================================================== */

.offer-from
{
    display:flex;

    align-items:center;

    gap:10px;

    margin-bottom:20px;
}

.offer-from-label
{
    font-size:13px;

    color:#777;
}

.offer-from-name
{
    font-size:15px;

    font-weight:bold;

    color:#222;
}


/* =====================================================
   OFFER IMAGE
===================================================== */

.offer-image
{
    width:100%;

    max-width:650px;

    margin:0 auto 20px;

    text-align:center;
}

.offer-image img
{
    width:100%;

    max-width:650px;

    height:380px;

    object-fit:cover;

    border-radius:10px;

    border:1px solid #ddd;

    display:block;

    margin:auto;
}


/* =====================================================
   NO IMAGE
===================================================== */

.no-image
{
    width:100%;

    height:250px;

    background:#f5f5f5;

    border:1px solid #ddd;

    border-radius:10px;

    display:flex;

    align-items:center;

    justify-content:center;

    color:#888;

    font-size:14px;
}


/* =====================================================
   ITEM INFO
===================================================== */

.item-info
{
    border-top:1px solid #eee;

    padding-top:18px;
}

.item-title
{
    font-size:21px;

    font-weight:bold;

    color:#222;

    margin-bottom:12px;
}

.item-row
{
    display:flex;

    justify-content:space-between;

    gap:20px;

    padding:10px 0;

    border-bottom:1px solid #f0f0f0;

    font-size:14px;
}

.item-row:last-child
{
    border-bottom:none;
}

.item-label
{
    color:#777;
}

.item-value
{
    color:#222;

    font-weight:600;

    text-align:right;
}


/* =====================================================
   WANTED INFO
===================================================== */

.wanted-box
{
    background:#f5f7f6;

    border-radius:10px;

    padding:16px;

    margin-bottom:20px;
}

.wanted-box-title
{
    font-size:15px;

    font-weight:bold;

    margin-bottom:10px;

    color:#333;
}

.wanted-row
{
    font-size:13px;

    color:#666;

    margin:6px 0;
}

.wanted-row strong
{
    color:#333;
}


/* =====================================================
   STATUS
===================================================== */

.status
{
    display:inline-block;

    padding:7px 15px;

    border-radius:20px;

    font-size:13px;

    font-weight:bold;
}

.status-pending
{
    background:#fff4d6;

    color:#9a6700;
}

.status-accepted
{
    background:#e8f7ed;

    color:#168a45;
}

.status-rejected
{
    background:#fdeaea;

    color:#d93025;
}

.status-paid
{
    background:#e8f7ed;

    color:#168a45;
}


/* =====================================================
   ACTIONS
===================================================== */

.offer-actions
{
    display:flex;

    gap:10px;

    flex-wrap:wrap;

    margin-top:20px;
}

.offer-actions form
{
    margin:0;
}

.offer-btn
{
    display:inline-block;

    padding:11px 20px;

    border:none;

    border-radius:7px;

    text-decoration:none;

    font-size:13px;

    font-weight:bold;

    cursor:pointer;
}

.accept-btn
{
    background:#168a45;

    color:white;
}

.accept-btn:hover
{
    background:#117239;
}

.reject-btn
{
    background:#d93025;

    color:white;
}

.reject-btn:hover
{
    background:#b8231b;
}

.payment-btn
{
    background:#1b5fa5;

    color:white;
}

.payment-btn:hover
{
    background:#154b83;
}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:800px)
{
    .main
    {
        margin-left:70px;

        padding:100px 20px 50px;
    }

    .offer-image img
    {
        height:300px;
    }
}


@media(max-width:600px)
{
    .main
    {
        margin-left:0;

        padding:100px 15px 40px;
    }

    .offer-card
    {
        padding:18px;
    }

    .page-header h1
    {
        font-size:23px;
    }

    .offer-image img
    {
        height:240px;
    }

    .item-row
    {
        flex-direction:column;

        gap:3px;
    }

    .item-value
    {
        text-align:left;
    }
}

</style>

</head>


<body>

<?php require APP_PATH . '/views/layouts/header_sidebar.php'; ?>


<main class="main">

<div class="offer-container">


<!-- =================================================
     BACK TO NOTIFICATIONS
================================================= -->

<a
    href="index.php?url=notifications"
    class="back"
>
    ← Back to Notifications
</a>


<!-- =================================================
     HEADER
================================================= -->

<div class="page-header">

    <h1>
        View Offer
    </h1>

    <p>
        Someone has offered this item for your wanted post.
    </p>

</div>


<!-- =================================================
     OFFER CARD
================================================= -->

<div class="offer-card">


    <!-- OFFER FROM -->

    <div class="offer-from">

        <span class="offer-from-label">
            Offer from:
        </span>

        <span class="offer-from-name">
            <?php
            echo $offerer_name;
            ?>
        </span>

    </div>


    <!-- OFFER IMAGE -->

    <div class="offer-image">

        <?php if($offer_image !== ""): ?>

            <img
                src="uploads/<?php echo $offer_image; ?>"
                alt="Offered Item"
            >

        <?php else: ?>

            <div class="no-image">
                No image available
            </div>

        <?php endif; ?>

    </div>


    <!-- ITEM INFORMATION -->

    <div class="item-info">

        <div class="item-title">
            Offered Item
        </div>


        <?php if($display_price !== ""): ?>

            <div class="item-row">

                <span class="item-label">
                    Price
                </span>

                <span class="item-value">
                    <?php
                    echo $display_price;
                    ?>
                </span>

            </div>

        <?php endif; ?>


        <?php if($display_payment_method !== ""): ?>

            <div class="item-row">

                <span class="item-label">
                    Payment Method
                </span>

                <span class="item-value">
                    <?php
                    echo $display_payment_method;
                    ?>
                </span>

            </div>

        <?php endif; ?>


        <?php if($offer_location !== ""): ?>

            <div class="item-row">

                <span class="item-label">
                    My Item Location
                </span>

                <span class="item-value">
                    <?php
                    echo $offer_location;
                    ?>
                </span>

            </div>

        <?php endif; ?>

    </div>


</div>


<!-- =================================================
     WANTED POST CARD
===================================================== -->

<div class="offer-card">


    <div class="wanted-box">

        <div class="wanted-box-title">
            Your Wanted Post
        </div>


        <div class="wanted-row">

            <strong>
                Wanted Item:
            </strong>

            <?php
            echo $wanted_title;
            ?>

        </div>


        <?php if($wanted_category !== ""): ?>

            <div class="wanted-row">

                <strong>
                    Category:
                </strong>

                <?php
                echo $wanted_category;
                ?>

            </div>

        <?php endif; ?>


        <?php if($wanted_location !== ""): ?>

            <div class="wanted-row">

                <strong>
                    Wanted Location:
                </strong>

                <?php
                echo $wanted_location;
                ?>

            </div>

        <?php endif; ?>

    </div>


    <!-- =================================================
         OFFER STATUS
    ================================================= -->

    <div>

        <strong>
            Offer Status:
        </strong>

        <br><br>


        <!-- =================================================
             PENDING
        ================================================= -->

        <?php if(
            strcasecmp(
                $offer_status,
                "Pending"
            ) === 0
        ): ?>

            <span class="status status-pending">
                Pending
            </span>


            <!-- ONLY OWNER CAN ACCEPT / REJECT -->

            <?php if($isOwner): ?>

                <div class="offer-actions">


                    <!-- ACCEPT -->

                    <form
                        action="index.php?url=offers/action"
                        method="POST"
                    >

                        <input
                            type="hidden"
                            name="offer_id"
                            value="<?php
                            echo $offer_id;
                            ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="accept"
                        >

                        <button
                            type="submit"
                            class="offer-btn accept-btn"
                        >
                            Accept
                        </button>

                    </form>


                    <!-- REJECT -->

                    <form
                        action="index.php?url=offers/action"
                        method="POST"
                    >

                        <input
                            type="hidden"
                            name="offer_id"
                            value="<?php
                            echo $offer_id;
                            ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="reject"
                        >

                        <button
                            type="submit"
                            class="offer-btn reject-btn"
                        >
                            Reject
                        </button>

                    </form>

                </div>

            <?php endif; ?>


        <!-- =================================================
             ACCEPTED
        ================================================= -->

        <?php elseif(
            strcasecmp(
                $offer_status,
                "Accepted"
            ) === 0
        ): ?>

            <span class="status status-accepted">
                Accepted
            </span>


            <!-- =================================================
                 ONLY OFFERER CAN SEE MAKE PAYMENT
                 OWNER WILL NEVER SEE IT
            ================================================= -->

            <?php if($isOfferer): ?>

                <?php if($is_free == 0): ?>

                    <div class="offer-actions">

                        <a
                            href="index.php?url=payment&offer_id=<?php
                            echo $offer_id;
                            ?>"
                            class="offer-btn payment-btn"
                        >
                            Make Payment
                        </a>

                    </div>

                <?php else: ?>

                    <div
                        style="
                            margin-top:15px;
                            padding:12px 15px;
                            background:#e8f7ed;
                            border-radius:8px;
                            color:#168a45;
                            font-size:13px;
                            font-weight:bold;
                        "
                    >
                        Free / Donate Offer Accepted.
                        No payment is required.
                    </div>

                <?php endif; ?>

            <?php endif; ?>


        <!-- =================================================
             REJECTED
        ================================================= -->

        <?php elseif(
            strcasecmp(
                $offer_status,
                "Rejected"
            ) === 0
        ): ?>

            <span class="status status-rejected">
                Rejected
            </span>


        <!-- =================================================
             PAID
        ================================================= -->

        <?php elseif(
            strcasecmp(
                $offer_status,
                "Paid"
            ) === 0
        ): ?>

            <span class="status status-paid">
                Paid
            </span>


        <!-- =================================================
             OTHER STATUS
        ================================================= -->

        <?php else: ?>

            <span class="status">

                <?php
                echo htmlspecialchars(
                    $offer_status
                );
                ?>

            </span>

        <?php endif; ?>

    </div>


</div>


</div>

</main>


</body>

</html>