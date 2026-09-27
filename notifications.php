<?php

session_start();

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = (int)$_SESSION["user_id"];


// =====================================================
// DELETE NOTIFICATION
// =====================================================

if(isset($_GET["clear_id"]) && is_numeric($_GET["clear_id"]))
{
    $clear_id = (int)$_GET["clear_id"];

    $sql = "
        DELETE FROM notifications
        WHERE notification_id = {$clear_id}
        AND user_id = {$user_id}
    ";

    mysqli_query($conn, $sql);

    header("Location: notifications.php");
    exit();
}


// =====================================================
// MARK AS READ
// =====================================================

if(isset($_GET["read_id"]) && is_numeric($_GET["read_id"]))
{
    $read_id = (int)$_GET["read_id"];

    $sql = "
        UPDATE notifications
        SET status = 'Read'
        WHERE notification_id = {$read_id}
        AND user_id = {$user_id}
    ";

    mysqli_query($conn, $sql);

    header("Location: notifications.php");
    exit();
}


// =====================================================
// GET ALL NOTIFICATIONS
// =====================================================

$sql = "
    SELECT
        n.notification_id,
        n.user_id,
        n.request_id,
        n.notification_type,
        n.message,
        n.status

    FROM notifications n

    WHERE n.user_id = {$user_id}

    ORDER BY n.notification_id DESC
";

$result = mysqli_query($conn, $sql);

if(!$result)
{
    die("Notification query error: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Notifications</title>

<link rel="stylesheet" href="style.css">

<style>

.notifications-container
{
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
}

.notifications-title
{
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 25px;
}

.notification-card
{
    background: #ffffff;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.notification-card.unread
{
    border-left: 5px solid #007bff;
}

.notification-card.read
{
    border-left: 5px solid #aaa;
}

.notification-top
{
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.notification-status
{
    font-size: 13px;
    font-weight: bold;
}

.unread-text
{
    color: #007bff;
}

.read-text
{
    color: #777;
}

.delete-btn
{
    color: #dc3545;
    text-decoration: none;
    font-size: 14px;
    font-weight: bold;
}

.notification-message
{
    font-size: 17px;
    font-weight: 500;
    line-height: 1.5;
    margin-bottom: 15px;
}

.notification-details
{
    background: #f7f7f7;
    border-radius: 7px;
    padding: 14px;
    margin-top: 10px;
}

.notification-details p
{
    margin: 6px 0;
    font-size: 14px;
}

.notification-actions
{
    display: flex;
    gap: 10px;
    margin-top: 18px;
    flex-wrap: wrap;
}

.action-btn
{
    display: inline-block;
    padding: 9px 18px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    font-weight: bold;
}

.accept-btn
{
    background: #28a745;
    color: white;
}

.accept-btn:hover
{
    background: #218838;
}

.reject-btn
{
    background: #dc3545;
    color: white;
}

.reject-btn:hover
{
    background: #c82333;
}

.view-offer-btn
{
    background: #007bff;
    color: white;
}

.view-offer-btn:hover
{
    background: #0069d9;
}

.mark-read-btn
{
    display: inline-block;
    margin-top: 12px;
    color: #007bff;
    text-decoration: none;
    font-size: 13px;
}

.request-status
{
    margin-top: 15px;
    font-weight: bold;
}

.status-accepted
{
    color: #28a745;
}

.status-rejected
{
    color: #dc3545;
}

.status-pending
{
    color: #f0ad4e;
}

.notification-type
{
    margin-top: 12px;
    color: #666;
    font-size: 13px;
}

.no-notifications
{
    text-align: center;
    padding: 50px 20px;
    color: #777;
}

</style>

</head>


<body>

<?php include("header_sidebar.php"); ?>


<div class="notifications-container">

    <div class="notifications-title">
        Notifications
    </div>


<?php

if(mysqli_num_rows($result) == 0)
{
?>

    <div class="no-notifications">
        No notifications found.
    </div>

<?php
}
else
{

    while($notification = mysqli_fetch_assoc($result))
    {

        $notification_id =
            (int)$notification["notification_id"];

        $request_id =
            (int)$notification["request_id"];

        $notification_type =
            strtolower(
                trim(
                    $notification["notification_type"] ?? ""
                )
            );

        $message =
            $notification["message"] ?? "";

        $status =
            strtolower(
                trim(
                    $notification["status"] ?? ""
                )
            );


        $isUnread =
            ($status === "unread");


        // =================================================
        // SAFE MESSAGE
        // =================================================

        $safeMessage =
            htmlspecialchars(
                $message,
                ENT_QUOTES,
                "UTF-8"
            );


        // =================================================
        // VARIABLES
        // =================================================

        $item_title = "";
        $item_category = "";
        $item_type = "";

        $requester_name = "";

        $requester_id = 0;
        $item_id = 0;
        $owner_id = 0;

        $request_type = "";
        $request_status = "";


        // =================================================
        // WANTED OFFER
        // =================================================

        $wanted_offer_id = 0;


        // =================================================
        // DETERMINE NOTIFICATION TYPE
        // =================================================

        $isWantedOffer = false;

        if($notification_type === "wanted_offer")
        {
            $isWantedOffer = true;
        }


        /*
         * Some old wanted-offer notifications may have
         * notification_type = "offer".
         */

        if(
            $notification_type === "offer"
            ||
            $notification_type === "wanted offer"
        )
        {
            $isWantedOffer = true;
        }


        // =================================================
        // WANTED OFFER
        //
        // request_id = wanted_offers.offer_id
        //
        // We only use offer_id here.
        // No assumed columns such as offerer_id/status.
        // =================================================

        if($isWantedOffer && $request_id > 0)
        {
            $wanted_offer_id = $request_id;
        }


        // =================================================
        // DONATE / REQUEST FLOW
        // =================================================

        if(!$isWantedOffer && $request_id > 0)
        {

            $requestSql = "
                SELECT

                    r.request_id,

                    r.user_id AS requester_id,

                    r.item_id,

                    r.request_type,

                    r.status AS request_status,

                    i.user_id AS owner_id,

                    i.title AS item_title,

                    i.category AS item_category,

                    i.type AS item_type,

                    u.name AS requester_name

                FROM requests r

                LEFT JOIN items i
                    ON i.item_id = r.item_id

                LEFT JOIN users u
                    ON u.user_id = r.user_id

                WHERE r.request_id = {$request_id}

                LIMIT 1
            ";

            $requestResult =
                mysqli_query(
                    $conn,
                    $requestSql
                );


            if($requestResult)
            {

                if(mysqli_num_rows($requestResult) > 0)
                {

                    $requestData =
                        mysqli_fetch_assoc(
                            $requestResult
                        );


                    $requester_id =
                        (int)$requestData["requester_id"];

                    $item_id =
                        (int)$requestData["item_id"];

                    $owner_id =
                        (int)$requestData["owner_id"];

                    $item_title =
                        $requestData["item_title"] ?? "";

                    $item_category =
                        $requestData["item_category"] ?? "";

                    $item_type =
                        $requestData["item_type"] ?? "";

                    $requester_name =
                        $requestData["requester_name"] ?? "";

                    $request_type =
                        strtolower(
                            trim(
                                $requestData["request_type"] ?? ""
                            )
                        );

                    $request_status =
                        strtolower(
                            trim(
                                $requestData["request_status"] ?? ""
                            )
                        );
                }
            }
        }


        // =================================================
        // OWNER DONATE REQUEST
        // =================================================

        $showOwnerButtons =
        (
            !$isWantedOffer
            &&
            in_array($notification_type, ["request", "ngo_request"], true)
            &&
            $request_id > 0
            &&
            $item_id > 0
            &&
            $owner_id == $user_id
            &&
            in_array($request_type, ["donate", "ngo donation"], true)
            &&
            $request_status === "pending"
        );


        // =================================================
        // WANTED OFFER VIEW BUTTON
        // =================================================

        $showViewOffer =
        (
            $isWantedOffer
            &&
            $wanted_offer_id > 0
        );


?>

    <div class="notification-card <?php
        echo $isUnread ? "unread" : "read";
    ?>">


        <!-- =============================================
             TOP
        ============================================== -->

        <div class="notification-top">

            <div class="notification-status">

                <?php if($isUnread): ?>

                    <span class="unread-text">
                        Unread
                    </span>

                <?php else: ?>

                    <span class="read-text">
                        Read
                    </span>

                <?php endif; ?>

            </div>


            <a
                href="notifications.php?clear_id=<?php echo $notification_id; ?>"
                class="delete-btn"
                onclick="return confirm('Delete this notification?');"
            >
                Delete
            </a>

        </div>


        <!-- =============================================
             MESSAGE
        ============================================== -->

        <div class="notification-message">

            <?php echo $safeMessage; ?>

        </div>


<?php

        // =================================================
        // REQUEST DETAILS
        // =================================================

        if(
            !$isWantedOffer
            &&
            $item_id > 0
        )
        {
?>

        <div class="notification-details">

<?php

            if($item_title !== "")
            {
?>

            <p>
                <strong>Item:</strong>
                <?php
                echo htmlspecialchars(
                    $item_title,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </p>

<?php
            }


            if($requester_name !== "")
            {
?>

            <p>
                <strong>From:</strong>
                <?php
                echo htmlspecialchars(
                    $requester_name,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </p>

<?php
            }


            if($item_category !== "")
            {
?>

            <p>
                <strong>Category:</strong>
                <?php
                echo htmlspecialchars(
                    $item_category,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </p>

<?php
            }


            if($item_type !== "")
            {
?>

            <p>
                <strong>Type:</strong>
                <?php
                echo htmlspecialchars(
                    $item_type,
                    ENT_QUOTES,
                    "UTF-8"
                );
                ?>
            </p>

<?php
            }

?>

        </div>

<?php
        }


        // =================================================
        // ACCEPT / REJECT
        // =================================================

        if($showOwnerButtons)
        {
?>

        <div class="notification-actions">

            <a
                href="<?php echo $request_type === 'ngo donation' ? 'ngo_request_action.php' : 'request_action.php'; ?>?request_id=<?php echo $request_id; ?>&action=accept&notification_id=<?php echo $notification_id; ?>"
                class="action-btn accept-btn"
                onclick="return confirm('Are you sure you want to accept this request?');"
            >
                Accept
            </a>


            <a
                href="<?php echo $request_type === 'ngo donation' ? 'ngo_request_action.php' : 'request_action.php'; ?>?request_id=<?php echo $request_id; ?>&action=reject&notification_id=<?php echo $notification_id; ?>"
                class="action-btn reject-btn"
                onclick="return confirm('Are you sure you want to reject this request?');"
            >
                Reject
            </a>

        </div>

<?php
        }

        // =================================================
        // VIEW OFFER
        // =================================================

        if($showViewOffer)
        {
?>

        <div class="notification-actions">

            <a
                href="view_offer.php?offer_id=<?php echo $wanted_offer_id; ?>"
                class="action-btn view-offer-btn"
            >
                View Offer
            </a>

        </div>

<?php
        }


        // =================================================
        // REQUEST STATUS
        // =================================================

        if(
            !$isWantedOffer
            &&
            !$showOwnerButtons
            &&
            $request_id > 0
            &&
            $request_type !== ""
        )
        {

            if($request_status === "accepted")
            {
?>

        <div class="request-status status-accepted">
            Request Accepted
        </div>

<?php
            }
            elseif($request_status === "rejected")
            {
?>

        <div class="request-status status-rejected">
            Request Rejected
        </div>

<?php
            }
            elseif($request_status === "pending")
            {
?>

        <div class="request-status status-pending">
            Request Pending
        </div>

<?php
            }
        }


        // =================================================
        // NOTIFICATION TYPE
        // =================================================

?>

        <div class="notification-type">

<?php

        if($notification_type === "request")
        {
            echo "Request notification";
        }
        elseif($isWantedOffer)
        {
            echo "Wanted offer notification";
        }
        else
        {
            echo htmlspecialchars(
                $notification["notification_type"] ?? "",
                ENT_QUOTES,
                "UTF-8"
            );
        }

?>

        </div>


        <!-- =============================================
             MARK AS READ
        ============================================== -->

<?php

        if($isUnread)
        {
?>

        <a
            href="notifications.php?read_id=<?php echo $notification_id; ?>"
            class="mark-read-btn"
        >
            Mark as read
        </a>

<?php
        }

?>

    </div>

<?php

    }
}

?>

</div>

</body>

</html>
