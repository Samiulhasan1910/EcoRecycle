<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

include('db.php');

$user_id = (int)$_SESSION['user_id'];


// =====================================================
// GET OFFER ID
// =====================================================

$offer_id = isset($_POST['offer_id'])
    ? (int)$_POST['offer_id']
    : (isset($_GET['offer_id']) ? (int)$_GET['offer_id'] : 0);

$action = $_POST['action']
    ?? ($_GET['action'] ?? '');


if ($offer_id <= 0) {
    die('Invalid Offer ID.');
}

if ($action !== 'accept' && $action !== 'reject') {
    die('Invalid Action.');
}


// =====================================================
// GET OFFER
// IMPORTANT: wanted_offers আলাদা করে query করছি
// =====================================================

$offerSql = "
    SELECT *
    FROM wanted_offers
    WHERE offer_id = '$offer_id'
    LIMIT 1
";

$offerResult = mysqli_query($conn, $offerSql);

if (!$offerResult) {
    die(
        'Offer SQL Error: ' .
        mysqli_error($conn)
    );
}

if (mysqli_num_rows($offerResult) == 0) {
    die(
        'Offer not found.<br>' .
        'Received offer_id = ' .
        htmlspecialchars($offer_id)
    );
}

$offer = mysqli_fetch_assoc($offerResult);


$wanted_id = (int)$offer['wanted_id'];
$offerer_id = (int)$offer['offerer_user_id'];


// =====================================================
// GET WANTED POST
// =====================================================

$wantedSql = "
    SELECT *
    FROM wanted_posts
    WHERE wanted_id = '$wanted_id'
    LIMIT 1
";

$wantedResult = mysqli_query($conn, $wantedSql);

if (!$wantedResult) {
    die(
        'Wanted SQL Error: ' .
        mysqli_error($conn)
    );
}

if (mysqli_num_rows($wantedResult) == 0) {
    die(
        'Wanted post not found.<br>' .
        'wanted_id = ' .
        htmlspecialchars($wanted_id)
    );
}

$wanted = mysqli_fetch_assoc($wantedResult);


// =====================================================
// CHECK OWNER
// =====================================================

$wanted_owner = (int)$wanted['user_id'];

if ($wanted_owner !== $user_id) {
    die('You are not authorized to perform this action.');
}


// =====================================================
// ONLY PENDING OFFER CAN BE ACCEPTED / REJECTED
// =====================================================

if ($offer['status'] !== 'Pending') {

    if ($offer['status'] === 'Accepted') {

        /*
         * যদি already Accepted হয়,
         * owner হলে সরাসরি payment page
         */

        header(
            'Location: payment.php?offer_id=' .
            $offer_id
        );

        exit();
    }

    header(
        'Location: wanted_offers_list.php?wanted_id=' .
        $wanted_id .
        '&msg=already_processed'
    );

    exit();
}


// =====================================================
// ACCEPT OFFER
// =====================================================

if ($action === 'accept') {

    mysqli_begin_transaction($conn);

    try {

        // -------------------------------------------------
        // LOCK OFFER
        // -------------------------------------------------

        $checkSql = "
            SELECT *
            FROM wanted_offers
            WHERE offer_id = '$offer_id'
            FOR UPDATE
        ";

        $checkResult = mysqli_query(
            $conn,
            $checkSql
        );

        if (!$checkResult) {
            throw new Exception(
                'Offer check failed: ' .
                mysqli_error($conn)
            );
        }

        if (mysqli_num_rows($checkResult) == 0) {
            throw new Exception(
                'Offer not found during transaction.'
            );
        }

        $currentOffer =
            mysqli_fetch_assoc($checkResult);


        if ($currentOffer['status'] !== 'Pending') {

            throw new Exception(
                'This offer has already been processed.'
            );
        }


        // -------------------------------------------------
        // ACCEPT SELECTED OFFER
        // -------------------------------------------------

        $updateOfferSql = "
            UPDATE wanted_offers
            SET
                status = 'Accepted',
                payment_visible = 1
            WHERE offer_id = '$offer_id'
            AND wanted_id = '$wanted_id'
            AND status = 'Pending'
        ";

        if (!mysqli_query($conn, $updateOfferSql)) {

            throw new Exception(
                'Failed to accept offer: ' .
                mysqli_error($conn)
            );
        }


        // -------------------------------------------------
        // FIND OTHER PENDING OFFERS
        // -------------------------------------------------

        $otherOffersSql = "
            SELECT
                offer_id,
                offerer_user_id
            FROM wanted_offers
            WHERE wanted_id = '$wanted_id'
            AND offer_id != '$offer_id'
            AND status = 'Pending'
        ";

        $otherOffersResult = mysqli_query(
            $conn,
            $otherOffersSql
        );

        if (!$otherOffersResult) {

            throw new Exception(
                'Failed to get other offers: ' .
                mysqli_error($conn)
            );
        }


        $rejectedOfferers = [];

        while (
            $otherOffer =
            mysqli_fetch_assoc($otherOffersResult)
        ) {

            $rejectedOfferers[] = [
                'offer_id' =>
                    (int)$otherOffer['offer_id'],

                'user_id' =>
                    (int)$otherOffer['offerer_user_id']
            ];
        }


        // -------------------------------------------------
        // REJECT OTHER OFFERS
        // -------------------------------------------------

        $rejectOthersSql = "
            UPDATE wanted_offers
            SET
                status = 'Rejected',
                payment_visible = 0
            WHERE wanted_id = '$wanted_id'
            AND offer_id != '$offer_id'
            AND status = 'Pending'
        ";

        if (!mysqli_query($conn, $rejectOthersSql)) {

            throw new Exception(
                'Failed to reject other offers: ' .
                mysqli_error($conn)
            );
        }


        // -------------------------------------------------
        // UPDATE WANTED POST
        // IMPORTANT:
        // এখানে কোনো items table update নেই
        // -------------------------------------------------

        $updateWantedSql = "
            UPDATE wanted_posts
            SET status = 'Accepted'
            WHERE wanted_id = '$wanted_id'
        ";

        if (!mysqli_query($conn, $updateWantedSql)) {

            throw new Exception(
                'Failed to update wanted post: ' .
                mysqli_error($conn)
            );
        }


        // -------------------------------------------------
        // ACCEPTED NOTIFICATION
        // -------------------------------------------------

        $titleSafe = mysqli_real_escape_string(
            $conn,
            $wanted['title']
        );

        $acceptedMessage =
            "Your offer was accepted for wanted post: " .
            $titleSafe;

        $acceptedMessageSafe =
            mysqli_real_escape_string(
                $conn,
                $acceptedMessage
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
                '$offerer_id',
                '$offer_id',
                'wanted_offer',
                '$acceptedMessageSafe',
                'Unread'
            )
        ";

        if (!mysqli_query($conn, $notificationSql)) {

            throw new Exception(
                'Failed to send acceptance notification: ' .
                mysqli_error($conn)
            );
        }


        // -------------------------------------------------
        // REJECTION NOTIFICATIONS
        // -------------------------------------------------

        foreach ($rejectedOfferers as $rejected) {

            $rejectedUserId =
                (int)$rejected['user_id'];

            $rejectedOfferId =
                (int)$rejected['offer_id'];


            $rejectedMessage =
                "Your offer was rejected for wanted post: " .
                $titleSafe;

            $rejectedMessageSafe =
                mysqli_real_escape_string(
                    $conn,
                    $rejectedMessage
                );


            $rejectedNotificationSql = "
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
                    '$rejectedUserId',
                    '$rejectedOfferId',
                    'wanted_offer',
                    '$rejectedMessageSafe',
                    'Unread'
                )
            ";

            if (
                !mysqli_query(
                    $conn,
                    $rejectedNotificationSql
                )
            ) {

                throw new Exception(
                    'Failed to send rejection notification: ' .
                    mysqli_error($conn)
                );
            }
        }


        // -------------------------------------------------
        // COMMIT
        // -------------------------------------------------

        mysqli_commit($conn);


        // =================================================
        // IMPORTANT
        // ACCEPT করার পর সরাসরি PAYMENT PAGE
        // EXACT একই offer_id পাঠানো হচ্ছে
        // =================================================

        header(
            'Location: payment.php?offer_id=' .
            $offer_id
        );

        exit();

    } catch (Exception $e) {

        mysqli_rollback($conn);

        die(
            'Failed to accept offer: ' .
            $e->getMessage()
        );
    }
}


// =====================================================
// REJECT OFFER
// =====================================================

if ($action === 'reject') {

    mysqli_begin_transaction($conn);

    try {

        // -------------------------------------------------
        // LOCK OFFER
        // -------------------------------------------------

        $checkSql = "
            SELECT *
            FROM wanted_offers
            WHERE offer_id = '$offer_id'
            FOR UPDATE
        ";

        $checkResult = mysqli_query(
            $conn,
            $checkSql
        );

        if (!$checkResult) {

            throw new Exception(
                'Offer check failed: ' .
                mysqli_error($conn)
            );
        }

        if (mysqli_num_rows($checkResult) == 0) {

            throw new Exception(
                'Offer not found.'
            );
        }

        $currentOffer =
            mysqli_fetch_assoc($checkResult);


        if ($currentOffer['status'] !== 'Pending') {

            throw new Exception(
                'This offer has already been processed.'
            );
        }


        // -------------------------------------------------
        // REJECT
        // -------------------------------------------------

        $updateOfferSql = "
            UPDATE wanted_offers
            SET
                status = 'Rejected',
                payment_visible = 0
            WHERE offer_id = '$offer_id'
            AND wanted_id = '$wanted_id'
            AND status = 'Pending'
        ";

        if (!mysqli_query($conn, $updateOfferSql)) {

            throw new Exception(
                'Failed to reject offer: ' .
                mysqli_error($conn)
            );
        }


        // -------------------------------------------------
        // NOTIFICATION
        // -------------------------------------------------

        $titleSafe = mysqli_real_escape_string(
            $conn,
            $wanted['title']
        );

        $message =
            "Your offer was rejected for wanted post: " .
            $titleSafe;

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
                '$offerer_id',
                '$offer_id',
                'wanted_offer',
                '$messageSafe',
                'Unread'
            )
        ";

        if (!mysqli_query($conn, $notificationSql)) {

            throw new Exception(
                'Failed to send notification: ' .
                mysqli_error($conn)
            );
        }


        mysqli_commit($conn);


        header(
            'Location: wanted_offers_list.php?wanted_id=' .
            $wanted_id .
            '&msg=rejected'
        );

        exit();

    } catch (Exception $e) {

        mysqli_rollback($conn);

        die(
            'Failed to reject offer: ' .
            $e->getMessage()
        );
    }
}

?>
