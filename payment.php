<?php

session_start();

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}

include("db.php");

error_reporting(E_ALL);
ini_set("display_errors", 1);

$user_id = (int)$_SESSION["user_id"];

$item_id = isset($_GET["item_id"])
    ? (int)$_GET["item_id"]
    : 0;

$offer_id = isset($_GET["offer_id"])
    ? (int)$_GET["offer_id"]
    : 0;

$isOfferPayment = ($offer_id > 0);

$paymentError = "";
$successMessage = "";

$item = null;
$offer = null;
$wanted = null;

$sellerId = 0;
$itemTitle = "";
$itemPrice = 0;
$sellerNumberText = "";

$paymentNumbers = array();
$sellerName = "User";
$deliveryJobId = 0;


/* =====================================================
   PAYMENT NUMBER PARSER
===================================================== */

function getPaymentMethodNumbers($paymentNumberText)
{
    $numbers = array();

    if(trim($paymentNumberText) === "")
    {
        return $numbers;
    }

    $parts = explode(",", $paymentNumberText);

    foreach($parts as $part)
    {
        $part = trim($part);

        if($part === "")
        {
            continue;
        }

        if(stripos($part, "Bkash:") !== false)
        {
            $value = trim(
                str_ireplace(
                    "Bkash:",
                    "",
                    $part
                )
            );

            if($value !== "")
            {
                $numbers["Bkash"] = $value;
            }
        }

        elseif(stripos($part, "Nagad:") !== false)
        {
            $value = trim(
                str_ireplace(
                    "Nagad:",
                    "",
                    $part
                )
            );

            if($value !== "")
            {
                $numbers["Nagad"] = $value;
            }
        }
    }


    /*
     * Backward compatibility:
     * If old data contains only a number.
     */

    if(
        empty($numbers) &&
        preg_match(
            '/^01[0-9]{9}$/',
            trim($paymentNumberText)
        )
    )
    {
        $numbers["Payment"] =
            trim($paymentNumberText);
    }


    return $numbers;
}


/* =====================================================
   WANTED OFFER PAYMENT
===================================================== */

if($isOfferPayment)
{
    if($offer_id <= 0)
    {
        header("Location: notifications.php");
        exit();
    }


    /* =================================================
       GET WANTED OFFER
    ================================================= */

    $offerSql = "
        SELECT
            wo.*,
            wp.user_id AS wanted_owner,
            wp.title AS wanted_title,
            wp.location AS wanted_location,
            wp.status AS wanted_post_status
        FROM wanted_offers wo
        LEFT JOIN wanted_posts wp
            ON wp.wanted_id = wo.wanted_id
        WHERE wo.offer_id = {$offer_id}
        LIMIT 1
    ";

    $offerResult =
        mysqli_query(
            $conn,
            $offerSql
        );


    if(!$offerResult)
    {
        die(
            "Database Error: " .
            mysqli_error($conn)
        );
    }


    if(mysqli_num_rows($offerResult) == 0)
    {
        header("Location: notifications.php");
        exit();
    }


    $offer =
        mysqli_fetch_assoc(
            $offerResult
        );


    /* =================================================
       OFFER DATA
    ================================================= */

    $wantedId =
        (int)(
            $offer["wanted_id"]
            ?? 0
        );


    $wantedOwner =
        (int)(
            $offer["wanted_owner"]
            ?? 0
        );


    $sellerId =
        (int)(
            $offer["offerer_user_id"]
            ?? 0
        );


    $itemTitle =
        $offer["wanted_title"]
        ?? "Wanted Item";


    $itemPrice =
        floatval(
            $offer["price"]
            ?? 0
        );


    $sellerNumberText =
        $offer["payment_number"]
        ?? "";


    /* =================================================
       ONLY WANTED OWNER CAN MAKE PAYMENT
    ================================================= */

    if($wantedOwner !== $user_id)
    {
        header(
            "Location: view_offer.php?offer_id=" .
            $offer_id
        );

        exit();
    }


    /* =================================================
       OFFER MUST BE ACCEPTED OR PAID
    ================================================= */

    $offerStatus =
        strtolower(
            trim(
                $offer["status"]
                ?? ""
            )
        );


    if(
        $offerStatus !== "accepted" &&
        $offerStatus !== "paid"
    )
    {
        header(
            "Location: view_offer.php?offer_id=" .
            $offer_id
        );

        exit();
    }


    /* =================================================
       OFFERER PAYMENT NUMBERS
    ================================================= */

    $paymentNumbers =
        getPaymentMethodNumbers(
            $sellerNumberText
        );


    /* =================================================
       GET OFFERER NAME
       
       IMPORTANT:
       users table does NOT have username.
    ================================================= */

    $sellerSql = "
        SELECT
            user_id,
            name
        FROM users
        WHERE user_id = {$sellerId}
        LIMIT 1
    ";


    $sellerResult =
        mysqli_query(
            $conn,
            $sellerSql
        );


    if(!$sellerResult)
    {
        die(
            "Seller Database Error: " .
            mysqli_error($conn)
        );
    }


    if(mysqli_num_rows($sellerResult) > 0)
    {
        $seller =
            mysqli_fetch_assoc(
                $sellerResult
            );

        $sellerName =
            !empty($seller["name"])
            ? $seller["name"]
            : "User";
    }
}


/* =====================================================
   NORMAL SELL ITEM PAYMENT
===================================================== */

elseif($item_id > 0)
{
    /* =================================================
       GET SELL ITEM
    ================================================= */

    $sql = "
        SELECT
            i.*,
            u.name AS seller_name
        FROM items i
        LEFT JOIN users u
            ON u.user_id = i.user_id
        WHERE i.item_id = {$item_id}
        LIMIT 1
    ";


    $result =
        mysqli_query(
            $conn,
            $sql
        );


    if(!$result)
    {
        die(
            "Database Error: " .
            mysqli_error($conn)
        );
    }


    if(mysqli_num_rows($result) == 0)
    {
        header(
            "Location: browse_items.php"
        );

        exit();
    }


    $item =
        mysqli_fetch_assoc(
            $result
        );


    /* =================================================
       ONLY SELL
    ================================================= */

    if(
        strtolower(
            trim(
                $item["type"]
                ?? ""
            )
        ) !== "sell"
    )
    {
        header(
            "Location: view_item.php?id=" .
            $item_id
        );

        exit();
    }


    /* =================================================
       BUYER CANNOT BUY OWN ITEM
    ================================================= */

    if(
        (int)$item["user_id"] === $user_id
    )
    {
        header(
            "Location: view_item.php?id=" .
            $item_id
        );

        exit();
    }


    /* =================================================
       ITEM MUST BE AVAILABLE
    ================================================= */

    if(
        strtolower(
            trim(
                $item["status"]
                ?? ""
            )
        ) !== "available"
    )
    {
        header(
            "Location: view_item.php?id=" .
            $item_id
        );

        exit();
    }


    $sellerId =
        (int)$item["user_id"];


    $itemTitle =
        $item["title"]
        ?? "";


    $itemPrice =
        floatval(
            $item["price"]
            ?? 0
        );


    $sellerNumberText =
        $item["payment_number"]
        ?? "";


    /* =================================================
       PAYMENT NUMBERS
    ================================================= */

    $paymentNumbers =
        getPaymentMethodNumbers(
            $sellerNumberText
        );
}


else
{
    header(
        "Location: browse_items.php"
    );

    exit();
}


/* =====================================================
   PAYMENT SUBMISSION
===================================================== */

if(
    $_SERVER["REQUEST_METHOD"] === "POST"
)
{
    $payment_method =
        trim(
            $_POST["payment_method"]
            ?? ""
        );


    $amount =
        trim(
            $_POST["amount"]
            ?? ""
        );


    $sender_number =
        trim(
            $_POST["sender_number"]
            ?? ""
        );


    $receiver_number =
        trim(
            $_POST["receiver_number"]
            ?? ""
        );


    $pin =
        trim(
            $_POST["pin"]
            ?? ""
        );


    /* =================================================
       METHOD
    ================================================= */

    if(
        !in_array(
            $payment_method,
            array(
                "Bkash",
                "Nagad"
            ),
            true
        )
    )
    {
        $paymentError =
            "Please select Bkash or Nagad.";
    }


    /* =================================================
       AMOUNT
    ================================================= */

    elseif(
        $amount === "" ||
        !is_numeric($amount) ||
        floatval($amount) <= 0
    )
    {
        $paymentError =
            "Please enter a valid payment amount.";
    }


    elseif(
        abs(
            floatval($amount) -
            $itemPrice
        ) > 0.001
    )
    {
        $paymentError =
            "Amount must match the item price: " .
            number_format(
                $itemPrice,
                2,
                ".",
                ""
            ) .
            " BDT.";
    }


    /* =================================================
       PAYMENT NUMBER EXISTS
    ================================================= */

    elseif(
        !isset(
            $paymentNumbers[
                $payment_method
            ]
        )
    )
    {
        $paymentError =
            "Seller has not added a valid " .
            $payment_method .
            " number.";
    }


    /* =================================================
       RECEIVER NUMBER
    ================================================= */

    elseif(
        $receiver_number !==
        $paymentNumbers[
            $payment_method
        ]
    )
    {
        $paymentError =
            "Receiver number does not match the selected payment method.";
    }


    /* =================================================
       SENDER NUMBER
    ================================================= */

    elseif(
        !preg_match(
            '/^01[0-9]{9}$/',
            $sender_number
        )
    )
    {
        $paymentError =
            "Enter a valid 11-digit sender number.";
    }


    /* =================================================
       PIN
    ================================================= */

    elseif(
        !preg_match(
            '/^[0-9]{4}$/',
            $pin
        )
    )
    {
        $paymentError =
            "PIN must be 4 digits.";
    }


    /* =================================================
       WANTED OFFER PAYMENT
    ================================================= */

    if(
        $paymentError === "" &&
        $isOfferPayment
    )
    {
        mysqli_begin_transaction($conn);

        try
        {
            /* =========================================
               LOCK OFFER
            ========================================= */

            $checkSql = "
                SELECT
                    wo.*,
                    wp.user_id AS wanted_owner
                FROM wanted_offers wo
                LEFT JOIN wanted_posts wp
                    ON wp.wanted_id = wo.wanted_id
                WHERE wo.offer_id = {$offer_id}
                LIMIT 1
                FOR UPDATE
            ";


            $checkResult =
                mysqli_query(
                    $conn,
                    $checkSql
                );


            if(
                !$checkResult ||
                mysqli_num_rows(
                    $checkResult
                ) == 0
            )
            {
                throw new Exception(
                    "Offer could not be found."
                );
            }


            $checkOffer =
                mysqli_fetch_assoc(
                    $checkResult
                );


            /* =========================================
               VERIFY OWNER
            ========================================= */

            $lockedOwner =
                (int)(
                    $checkOffer[
                        "wanted_owner"
                    ]
                    ?? 0
                );


            if(
                $lockedOwner !== $user_id
            )
            {
                throw new Exception(
                    "You are not authorized to make this payment."
                );
            }


            /* =========================================
               VERIFY STATUS
            ========================================= */

            $lockedStatus =
                strtolower(
                    trim(
                        $checkOffer["status"]
                        ?? ""
                    )
                );


            if($lockedStatus === "paid")
            {
                throw new Exception(
                    "This offer has already been paid."
                );
            }


            if($lockedStatus !== "accepted")
            {
                throw new Exception(
                    "This offer is not accepted."
                );
            }


            /* =========================================
               OFFERER ID
            ========================================= */

            $lockedSellerId =
                (int)(
                    $checkOffer[
                        "offerer_user_id"
                    ]
                    ?? 0
                );


            if($lockedSellerId <= 0)
            {
                throw new Exception(
                    "Offerer could not be found."
                );
            }


            /* =========================================
               PRICE CHECK
            ========================================= */

            $lockedPrice =
                floatval(
                    $checkOffer["price"]
                    ?? 0
                );


            if(
                abs(
                    floatval($amount) -
                    $lockedPrice
                ) > 0.001
            )
            {
                throw new Exception(
                    "Payment amount does not match the offer price."
                );
            }


            /* =========================================
               DUPLICATE PAYMENT CHECK
            ========================================= */

            $duplicateSql = "
                SELECT transaction_id
                FROM transactions
                WHERE request_id = {$offer_id}
                AND status = 'Completed'
                LIMIT 1
            ";


            $duplicateResult =
                mysqli_query(
                    $conn,
                    $duplicateSql
                );


            if(
                $duplicateResult &&
                mysqli_num_rows(
                    $duplicateResult
                ) > 0
            )
            {
                throw new Exception(
                    "Payment has already been completed for this offer."
                );
            }


            /* =========================================
               SAFE VALUES
            ========================================= */

            $amountSafe =
                mysqli_real_escape_string(
                    $conn,
                    $amount
                );


            $methodSafe =
                mysqli_real_escape_string(
                    $conn,
                    $payment_method
                );


            $receiverSafe =
                mysqli_real_escape_string(
                    $conn,
                    $receiver_number
                );


            // Never store the payment PIN in transaction history.
            $pinLast4 = "****";


            $pinLast4Safe =
                mysqli_real_escape_string(
                    $conn,
                    $pinLast4
                );


            /* =========================================
               SAVE PAYMENT

               Wanted:
               offer_id = wanted_offers.offer_id
               ========================================= */

            $paymentSql = "
                INSERT INTO payments
                (
                    request_id,
                    offer_id,
                    amount,
                    method,
                    status,
                    created_at
                )
                VALUES
                (
                    NULL,
                    {$offer_id},
                    '{$amountSafe}',
                    '{$methodSafe}',
                    'Paid',
                    NOW()
                )
            ";


            if(
                !mysqli_query(
                    $conn,
                    $paymentSql
                )
            )
            {
                throw new Exception(
                    "Payment save error: " .
                    mysqli_error($conn)
                );
            }


            /* =========================================
               TRANSACTION

               Wanted:
               item_id = NULL
               request_id = offer_id
            ========================================= */

            $transactionSql = "
                INSERT INTO transactions
                (
                    buyer_id,
                    seller_id,
                    item_id,
                    request_id,
                    payment_method,
                    amount,
                    receiver_number,
                    pin_last4,
                    status,
                    created_at
                )
                VALUES
                (
                    {$user_id},
                    {$lockedSellerId},
                    NULL,
                    {$offer_id},
                    '{$methodSafe}',
                    '{$amountSafe}',
                    '{$receiverSafe}',
                    '{$pinLast4Safe}',
                    'Completed',
                    NOW()
                )
            ";


            if(
                !mysqli_query(
                    $conn,
                    $transactionSql
                )
            )
            {
                throw new Exception(
                    "Transaction save error: " .
                    mysqli_error($conn)
                );
            }

            /* The wanted-post owner is the receiver of this delivery. */
            $transactionId = (int)mysqli_insert_id($conn);
            require_once __DIR__ . '/delivery_workflow.php';
            $deliveryJobId = createDeliveryJob(
                $conn,
                'transactions',
                $transactionId,
                0,
                $wantedOwner
            );

            /* =========================================
               UPDATE WANTED OFFER

               Accepted -> Paid

               IMPORTANT:
               NO items update here.
            ========================================= */

            $updateOfferSql = "
                UPDATE wanted_offers
                SET
                    status = 'Paid',
                    payment_visible = 1
                WHERE offer_id = {$offer_id}
                AND status = 'Accepted'
            ";


            if(
                !mysqli_query(
                    $conn,
                    $updateOfferSql
                )
            )
            {
                throw new Exception(
                    "Offer status update error: " .
                    mysqli_error($conn)
                );
            }


            if(
                mysqli_affected_rows($conn) !== 1
            )
            {
                throw new Exception(
                    "Offer could not be marked as paid."
                );
            }


            /* =========================================
               OFFERER NOTIFICATION
            ========================================= */

            $buyerName =
                $_SESSION["name"]
                ?? "A user";


            $message =
                $buyerName .
                " has completed payment for your offer on \"" .
                $itemTitle .
                "\". Amount: " .
                number_format(
                    floatval($amount),
                    2
                ) .
                " BDT.";


            $messageSafe =
                mysqli_real_escape_string(
                    $conn,
                    $message
                );


            $notificationSql = "
                INSERT INTO notifications
                (
                    user_id,
                    request_id,
                    notification_type,
                    message,
                    status
                )
                VALUES
                (
                    {$lockedSellerId},
                    {$offer_id},
                    'payment',
                    '{$messageSafe}',
                    'Unread'
                )
            ";


            if(
                !mysqli_query(
                    $conn,
                    $notificationSql
                )
            )
            {
                throw new Exception(
                    "Notification error: " .
                    mysqli_error($conn)
                );
            }


            /* =========================================
               COMMIT
            ========================================= */

            mysqli_commit($conn);


            $successMessage =
                "Payment successful! Your payment has been completed.";


            $offer["status"] = "Paid";

        }
        catch(Exception $e)
        {
            mysqli_rollback($conn);

            $paymentError =
                $e->getMessage();
        }
    }


    /* =================================================
       NORMAL SELL ITEM PAYMENT
    ================================================= */

    elseif(
        $paymentError === "" &&
        !$isOfferPayment &&
        $item_id > 0
    )
    {
        $amountSafe =
            mysqli_real_escape_string(
                $conn,
                $amount
            );


        $methodSafe =
            mysqli_real_escape_string(
                $conn,
                $payment_method
            );


        $receiverSafe =
            mysqli_real_escape_string(
                $conn,
                $receiver_number
            );


        // Never store the payment PIN in transaction history.
        $pinLast4 = "****";


        $pinLast4Safe =
            mysqli_real_escape_string(
                $conn,
                $pinLast4
            );


        mysqli_begin_transaction($conn);

        try
        {
            /* =========================================
               FINAL STATUS CHECK
            ========================================= */

            $checkSql = "
                SELECT status
                FROM items
                WHERE item_id = {$item_id}
                LIMIT 1
                FOR UPDATE
            ";


            $checkResult =
                mysqli_query(
                    $conn,
                    $checkSql
                );


            if(
                !$checkResult ||
                mysqli_num_rows(
                    $checkResult
                ) == 0
            )
            {
                throw new Exception(
                    "Item could not be found."
                );
            }


            $checkItem =
                mysqli_fetch_assoc(
                    $checkResult
                );


            if(
                strtolower(
                    trim(
                        $checkItem["status"]
                    )
                ) !== "available"
            )
            {
                throw new Exception(
                    "This item has already been sold."
                );
            }


            /* =========================================
               SAVE PAYMENT

               Direct Sell:
               request_id = NULL
            ========================================= */

            $paymentSql = "
                INSERT INTO payments
                (
                    request_id,
                    amount,
                    method,
                    status,
                    created_at
                )
                VALUES
                (
                    NULL,
                    '{$amountSafe}',
                    '{$methodSafe}',
                    'Paid',
                    NOW()
                )
            ";


            if(
                !mysqli_query(
                    $conn,
                    $paymentSql
                )
            )
            {
                throw new Exception(
                    "Payment save error: " .
                    mysqli_error($conn)
                );
            }


            /* =========================================
               MARK ITEM SOLD
            ========================================= */

            $updateItemSql = "
                UPDATE items
                SET status = 'Sold'
                WHERE item_id = {$item_id}
                AND status = 'Available'
            ";


            if(
                !mysqli_query(
                    $conn,
                    $updateItemSql
                )
            )
            {
                throw new Exception(
                    "Item status update error: " .
                    mysqli_error($conn)
                );
            }


            if(
                mysqli_affected_rows($conn) !== 1
            )
            {
                throw new Exception(
                    "Item could not be marked as sold."
                );
            }


            /* =========================================
               TRANSACTION

               Direct Sell:
               request_id = NULL
            ========================================= */

            $transactionSql = "
                INSERT INTO transactions
                (
                    buyer_id,
                    seller_id,
                    item_id,
                    request_id,
                    payment_method,
                    amount,
                    receiver_number,
                    pin_last4,
                    status,
                    created_at
                )
                VALUES
                (
                    {$user_id},
                    {$sellerId},
                    {$item_id},
                    NULL,
                    '{$methodSafe}',
                    '{$amountSafe}',
                    '{$receiverSafe}',
                    '{$pinLast4Safe}',
                    'Completed',
                    NOW()
                )
            ";


            if(
                !mysqli_query(
                    $conn,
                    $transactionSql
                )
            )
            {
                throw new Exception(
                    "Transaction save error: " .
                    mysqli_error($conn)
                );
            }

            $transactionId = (int)mysqli_insert_id($conn);

            require_once __DIR__ . '/delivery_workflow.php';
            $deliveryJobId = createDeliveryJob(
                $conn,
                'transactions',
                $transactionId,
                $item_id,
                $user_id
            );


            /* =========================================
               SELLER NOTIFICATION
            ========================================= */

            $buyerName =
                $_SESSION["name"]
                ?? "A user";


            $message =
                $buyerName .
                " has paid for your item: " .
                $itemTitle .
                ". The item is now sold.";


            $messageSafe =
                mysqli_real_escape_string(
                    $conn,
                    $message
                );


            $notificationSql = "
                INSERT INTO notifications
                (
                    user_id,
                    request_id,
                    notification_type,
                    message,
                    status
                )
                VALUES
                (
                    {$sellerId},
                    NULL,
                    'payment',
                    '{$messageSafe}',
                    'Unread'
                )
            ";


            if(
                !mysqli_query(
                    $conn,
                    $notificationSql
                )
            )
            {
                throw new Exception(
                    "Notification error: " .
                    mysqli_error($conn)
                );
            }


            /* =========================================
               SUCCESS
            ========================================= */

            mysqli_commit($conn);


            $successMessage =
                "Payment successful! The item has been marked as sold. " .
                "Your delivery request is now open for available delivery men.";

        }
        catch(Exception $e)
        {
            mysqli_rollback($conn);

            $paymentError =
                $e->getMessage();
        }
    }
}

?>

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
    href="style.css"
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

<?php include("header_sidebar.php"); ?>


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
                href="transactions.php"
                class="button"
            >
                View Transactions
            </a>

            <?php if($deliveryJobId > 0) { ?>

                <a
                    href="delivery_track.php"
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
                    href="notifications.php"
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
                    href="browse_items.php"
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
                echo "payment.php?offer_id=" .
                    $offer_id;
            }
            else
            {
                echo "payment.php?item_id=" .
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
                        href="view_offer.php?offer_id=<?php echo $offer_id; ?>"
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
                        href="view_item.php?id=<?php echo $item_id; ?>"
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
