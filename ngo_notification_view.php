<?php

session_start();

if (!isset($_SESSION["ngo_id"])) {
    header("Location: login.php");
    exit();
}

include("db.php");

$ngo_id = (int)$_SESSION["ngo_id"];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION["csrf_token"];

$wantedNotificationColumn = mysqli_query($conn, "SHOW COLUMNS FROM notifications LIKE 'wanted_id'");
if ($wantedNotificationColumn && mysqli_num_rows($wantedNotificationColumn) === 0) {
    mysqli_query($conn, "ALTER TABLE notifications ADD COLUMN wanted_id INT NULL");
}

if (!isset($_GET["id"])) {
    header("Location: ngo_notifications.php");
    exit();
}

$notification_id = (int)$_GET["id"];


/* =========================
   GET NOTIFICATION
========================= */

$sql = "SELECT
            notification_id,
            notification_type,
            message,
            status,
            request_id,
            pickup_id,
            item_id,
            wanted_id
        FROM notifications
        WHERE notification_id = $notification_id
        AND ngo_id = $ngo_id";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    echo "Notification not found.";
    exit();
}

$notification = mysqli_fetch_assoc($result);

/* =========================
   ACCEPT / REJECT DONATION
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($csrfToken, $_POST["csrf_token"] ?? "")) {
        http_response_code(403);
        exit("Invalid request token.");
    }

    $action = $_POST["action"] ?? "";
    $item_id = (int)($notification["item_id"] ?? 0);

    if ($action === "request_donor" && $item_id > 0) {
        mysqli_begin_transaction($conn);
        try {
            $itemResult = mysqli_query($conn, "SELECT user_id, title, type, status, ngo_id FROM items WHERE item_id = $item_id FOR UPDATE");
            $publicItem = $itemResult ? mysqli_fetch_assoc($itemResult) : null;
            if (!$publicItem
                || strtolower(trim($publicItem["type"] ?? "")) !== "donate"
                || strtolower(trim($publicItem["status"] ?? "")) !== "available"
                || !empty($publicItem["ngo_id"])) {
                throw new Exception("This public donation is no longer available.");
            }

            $donorId = (int)$publicItem["user_id"];
            $existingResult = mysqli_query($conn, "SELECT request_id, status FROM requests WHERE item_id = $item_id AND ngo_id = $ngo_id AND request_type = 'NGO Donation' ORDER BY request_id DESC LIMIT 1");
            $existingRequest = $existingResult ? mysqli_fetch_assoc($existingResult) : null;
            if ($existingRequest && strtolower($existingRequest["status"] ?? "") === "pending") {
                throw new Exception("You have already sent a request to this donor.");
            }

            if (!mysqli_query($conn, "INSERT INTO requests (user_id, item_id, ngo_id, request_type, status) VALUES ($donorId, $item_id, $ngo_id, 'NGO Donation', 'Pending')")) {
                throw new Exception(mysqli_error($conn));
            }
            $requestId = (int)mysqli_insert_id($conn);
            $itemTitle = $publicItem["title"] ?? "item";
            $message = mysqli_real_escape_string($conn, "An NGO requested your public donation '$itemTitle'. Please accept or reject the request.");
            if (!mysqli_query($conn, "INSERT INTO notifications (user_id, item_id, request_id, notification_type, message, status) VALUES ($donorId, $item_id, $requestId, 'ngo_request', '$message', 'Unread')")) {
                throw new Exception(mysqli_error($conn));
            }
            mysqli_query($conn, "UPDATE notifications SET status = 'Read', request_id = $requestId WHERE notification_id = $notification_id AND ngo_id = $ngo_id");
            mysqli_commit($conn);
            header("Location: ngo_notification_view.php?id=$notification_id&result=requested");
            exit();
        } catch (Throwable $error) {
            mysqli_rollback($conn);
            header("Location: ngo_notification_view.php?id=$notification_id&error=" . urlencode($error->getMessage()));
            exit();
        }
    }

    if (($action === "accept" || $action === "reject") && $item_id > 0) {
        mysqli_begin_transaction($conn);

        try {
            $itemResult = mysqli_query(
                $conn,
                "SELECT item_id, user_id, ngo_id, title, type, status
                 FROM items
                 WHERE item_id = $item_id
                 AND type = 'Donate'
                 AND (ngo_id IS NULL OR ngo_id = $ngo_id)
                 FOR UPDATE"
            );
            $actionItem = $itemResult ? mysqli_fetch_assoc($itemResult) : null;

            if (!$actionItem || !in_array(strtolower(trim($actionItem["status"] ?? "")), ["available", "pending", "accepted"], true)) {
                throw new Exception("This donation request is no longer available.");
            }
            if ((int)($actionItem["ngo_id"] ?? 0) !== $ngo_id) {
                throw new Exception("Public donations must be requested from the donor first.");
            }

            $donorId = (int)$actionItem["user_id"];
            $itemTitle = $actionItem["title"] ?? "item";

            if ($action === "accept") {
                if (!mysqli_query($conn, "UPDATE items SET status = 'Accepted', ngo_id = $ngo_id WHERE item_id = $item_id")) {
                    throw new Exception(mysqli_error($conn));
                }

                $requestResult = mysqli_query(
                    $conn,
                    "SELECT request_id FROM requests
                     WHERE item_id = $item_id
                     AND ngo_id = $ngo_id
                     AND LOWER(request_type) IN ('donate', 'donation')
                     LIMIT 1"
                );
                $requestRow = $requestResult ? mysqli_fetch_assoc($requestResult) : null;

                if ($requestRow) {
                    $requestId = (int)$requestRow["request_id"];
                    if (!mysqli_query($conn, "UPDATE requests SET status = 'Accepted' WHERE request_id = $requestId")) {
                        throw new Exception(mysqli_error($conn));
                    }
                } else {
                    if (!mysqli_query($conn, "INSERT INTO requests (user_id, item_id, ngo_id, request_type, status) VALUES ($donorId, $item_id, $ngo_id, 'Donation', 'Accepted')")) {
                        throw new Exception(mysqli_error($conn));
                    }
                    $requestId = (int)mysqli_insert_id($conn);
                }

                require_once __DIR__ . "/delivery_workflow.php";
                createDeliveryJob($conn, "requests", $requestId, $item_id, $donorId);

                $message = mysqli_real_escape_string($conn, "Your donation '$itemTitle' has been accepted by the NGO. A delivery request is now open.");
                if (!mysqli_query($conn, "INSERT INTO notifications (user_id, item_id, request_id, notification_type, message, status) VALUES ($donorId, $item_id, $requestId, 'request', '$message', 'Unread')")) {
                    throw new Exception(mysqli_error($conn));
                }
                mysqli_query($conn, "UPDATE notifications SET status = 'Accepted', request_id = $requestId WHERE notification_id = $notification_id AND ngo_id = $ngo_id");
            } else {
                if ((int)($actionItem["ngo_id"] ?? 0) === $ngo_id) {
                    if (!mysqli_query($conn, "UPDATE items SET status = 'Available', ngo_id = NULL WHERE item_id = $item_id")) {
                        throw new Exception(mysqli_error($conn));
                    }
                }

                $message = mysqli_real_escape_string($conn, "Your donation '$itemTitle' was rejected by the NGO.");
                if (!mysqli_query($conn, "INSERT INTO notifications (user_id, item_id, notification_type, message, status) VALUES ($donorId, $item_id, 'request', '$message', 'Unread')")) {
                    throw new Exception(mysqli_error($conn));
                }
                mysqli_query($conn, "UPDATE notifications SET status = 'Rejected' WHERE notification_id = $notification_id AND ngo_id = $ngo_id");
            }

            mysqli_commit($conn);
            header("Location: ngo_notification_view.php?id=$notification_id&result=$action");
            exit();
        } catch (Throwable $error) {
            mysqli_rollback($conn);
            header("Location: ngo_notification_view.php?id=$notification_id&error=" . urlencode($error->getMessage()));
            exit();
        }
    }
}


/* =========================
   MARK AS READ
========================= */

$update_sql = "UPDATE notifications
               SET status = 'Read'
               WHERE notification_id = $notification_id
               AND ngo_id = $ngo_id";

mysqli_query($conn, $update_sql);

$item = null;

$wanted = null;

if (!empty($notification["wanted_id"])) {
    $wanted_id = (int)$notification["wanted_id"];
    $wanted_sql = "SELECT wanted_id, title, category, location, description, status
                   FROM wanted_posts
                   WHERE wanted_id = $wanted_id";
    $wanted_result = mysqli_query($conn, $wanted_sql);
    if ($wanted_result && mysqli_num_rows($wanted_result) > 0) {
        $wanted = mysqli_fetch_assoc($wanted_result);
    }
}

if (!empty($notification["item_id"])) {
    $item_id = (int)$notification["item_id"];
    $item_sql = "SELECT user_id, ngo_id, title, category, type, price, location, status
                 FROM items
                 WHERE item_id = $item_id
                 AND (ngo_id = $ngo_id OR type = 'Donate')";
    $item_result = mysqli_query($conn, $item_sql);

    if ($item_result && mysqli_num_rows($item_result) > 0) {
        $item = mysqli_fetch_assoc($item_result);
    }
}


/* =========================
   GET REQUEST DETAILS
========================= */

$request = null;
$chargeDelivery = null;

if (!empty($notification["request_id"])) {

    $request_id = (int)$notification["request_id"];

    $request_sql = "SELECT
                        r.request_id,
                        r.user_id,
                        r.item_id,
                        r.request_type,
                        r.status,
                        i.title,
                        i.category,
                        i.price,
                        i.location
                    FROM requests r
                    INNER JOIN items i
                    ON r.item_id = i.item_id
                    WHERE r.request_id = $request_id
                    AND r.ngo_id = $ngo_id";

    $request_result = mysqli_query($conn, $request_sql);

    if ($request_result && mysqli_num_rows($request_result) > 0) {
        $request = mysqli_fetch_assoc($request_result);

        $chargeResult = mysqli_query(
            $conn,
            "SELECT d.delivery_id, d.status, d.proposed_charge, d.charge_status,
                    dm.name AS delivery_name
             FROM deliveries d
             LEFT JOIN delivery_man dm ON dm.delivery_id = d.assigned_delivery_man_id
             WHERE d.request_id = $request_id
             AND d.status = 'Charge Proposed'
             LIMIT 1"
        );
        if ($chargeResult && mysqli_num_rows($chargeResult) > 0) {
            $chargeDelivery = mysqli_fetch_assoc($chargeResult);
        }
    }
}


/* =========================
   GET PICKUP DETAILS
========================= */

$pickup = null;

if (!empty($notification["pickup_id"])) {

    $pickup_id = (int)$notification["pickup_id"];

    $pickup_sql = "SELECT
                       p.pickup_id,
                       p.request_id,
                       p.delivery_id,
                       p.date,
                       p.time,
                       p.status,
                       d.name AS delivery_name,
                       d.phone AS delivery_phone,
                       d.location AS delivery_location
                   FROM pickups p
                   INNER JOIN delivery d
                   ON p.delivery_id = d.delivery_id
                   INNER JOIN requests r
                   ON p.request_id = r.request_id
                   WHERE p.pickup_id = $pickup_id
                   AND r.ngo_id = $ngo_id";

    $pickup_result = mysqli_query($conn, $pickup_sql);

    if ($pickup_result && mysqli_num_rows($pickup_result) > 0) {
        $pickup = mysqli_fetch_assoc($pickup_result);
    }
}

?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<title>Notification View</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

body {
    font-family: Arial, sans-serif;
    background: #f5f6f8;
    margin: 0;
}

.container {
    width: 700px;
    margin: 50px auto;
}

.card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 25px;
}

h2 {
    margin-top: 0;
}

.message {
    background: #f1f8f4;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 25px;
    font-weight: bold;
}

.info {
    margin-bottom: 12px;
}

.label {
    font-weight: bold;
}

.back-btn {
    display: inline-block;
    margin-top: 20px;
    text-decoration: none;
    background: #168a45;
    color: white;
    padding: 10px 18px;
    border-radius: 6px;
}

.action-row { display: flex; gap: 12px; margin-top: 22px; }
.action-row form { margin: 0; }
.action-btn { border: 0; color: white; padding: 10px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; }
.accept-btn { background: #168a45; }
.reject-btn { background: #dc3545; }
.feedback { padding: 12px 15px; border-radius: 7px; margin-bottom: 18px; }
.feedback.success { background: #e7f7ed; color: #126b37; }
.feedback.error { background: #fdebec; color: #a52834; }

</style>
<link rel="stylesheet" href="ngo_sidebar.css">

</head>

<body>

<?php include 'ngo_sidebar.php'; ?>

<div class="container ngo-content">

<div class="card">

<h2>Notification Details</h2>

<?php if (isset($_GET["result"])): ?>
<div class="feedback success"><?php echo $_GET["result"] === "requested" ? "Request sent to the donor successfully." : "Donation request " . ($_GET["result"] === "accept" ? "accepted" : "rejected") . " successfully."; ?></div>
<?php elseif (!empty($_GET["error"])): ?>
<div class="feedback error"><?php echo htmlspecialchars($_GET["error"]); ?></div>
<?php endif; ?>

<div class="message">

<?php echo htmlspecialchars($notification["message"]); ?>

</div>


<?php

/* =========================
   DONATION / REQUEST DETAILS
========================= */

if ($request) {

?>

<h3>Donation Request Details</h3>

<div class="info">
<span class="label">Item:</span>
<?php echo htmlspecialchars($request["title"]); ?>
</div>

<div class="info">
<span class="label">Category:</span>
<?php echo htmlspecialchars($request["category"]); ?>
</div>

<div class="info">
<span class="label">Price:</span>
<?php echo htmlspecialchars($request["price"]); ?>
</div>

<div class="info">
<span class="label">Location:</span>
<?php echo htmlspecialchars($request["location"]); ?>
</div>

<div class="info">
<span class="label">Request Type:</span>
<?php echo htmlspecialchars($request["request_type"]); ?>
</div>

<div class="info">
<span class="label">Request Status:</span>
<?php echo htmlspecialchars($request["status"]); ?>
</div>

<?php

}

elseif ($item) {
?>

<h3>Donation Post Details</h3>

<div class="info"><span class="label">Item:</span> <?php echo htmlspecialchars($item["title"]); ?></div>
<div class="info"><span class="label">Category:</span> <?php echo htmlspecialchars($item["category"]); ?></div>
<div class="info"><span class="label">Location:</span> <?php echo htmlspecialchars($item["location"]); ?></div>
<div class="info"><span class="label">Status:</span> <?php echo htmlspecialchars($item["status"]); ?></div>

<?php
}

elseif ($wanted) {
?>

<h3>Wanted Post Details</h3>
<div class="info"><span class="label">Title:</span> <?php echo htmlspecialchars($wanted["title"]); ?></div>
<div class="info"><span class="label">Category:</span> <?php echo htmlspecialchars($wanted["category"]); ?></div>
<div class="info"><span class="label">Location:</span> <?php echo htmlspecialchars($wanted["location"]); ?></div>
<div class="info"><span class="label">Status:</span> <?php echo htmlspecialchars($wanted["status"]); ?></div>
<div class="info"><span class="label">Description:</span> <?php echo htmlspecialchars($wanted["description"] ?? ""); ?></div>

<?php
}


/* =========================
   PICKUP DETAILS
========================= */

elseif ($pickup) {

?>

<h3>Pickup Details</h3>

<div class="info">
<span class="label">Delivery Person:</span>
<?php echo htmlspecialchars($pickup["delivery_name"]); ?>
</div>

<div class="info">
<span class="label">Phone:</span>
<?php echo htmlspecialchars($pickup["delivery_phone"]); ?>
</div>

<div class="info">
<span class="label">Delivery Location:</span>
<?php echo htmlspecialchars($pickup["delivery_location"]); ?>
</div>

<div class="info">
<span class="label">Pickup Date:</span>
<?php echo htmlspecialchars($pickup["date"]); ?>
</div>

<div class="info">
<span class="label">Pickup Time:</span>
<?php echo htmlspecialchars($pickup["time"]); ?>
</div>

<div class="info">
<span class="label">Pickup Status:</span>
<?php echo htmlspecialchars($pickup["status"]); ?>
</div>

<?php

}

else {

?>

<p>No related request or pickup details found.</p>

<?php

}

?>

<?php
$itemStatus = strtolower(trim($item["status"] ?? ""));
$requestStatus = strtolower(trim($request["status"] ?? ""));
$isDonation = strtolower(trim($item["type"] ?? $request["request_type"] ?? ""));
$canRespond = $item
    && in_array($isDonation, ["donate", "donation"], true)
    && (int)($item["ngo_id"] ?? 0) === $ngo_id
    && (
        in_array($itemStatus, ["available", "pending"], true)
        || ($itemStatus === "accepted" && $requestStatus === "pending")
    )
    && !in_array(strtolower(trim($notification["status"] ?? "")), ["accepted", "rejected"], true);
if ($canRespond):
?>
<div class="action-row">
    <form method="post" onsubmit="return confirm('Accept this donation and send it to available delivery men?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="accept">
        <button type="submit" class="action-btn accept-btn">Accept</button>
    </form>
    <form method="post" onsubmit="return confirm('Reject this donation request?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="reject">
        <button type="submit" class="action-btn reject-btn">Reject</button>
    </form>
</div>
<?php endif; ?>

<?php if ($chargeDelivery): ?>
<div class="message" style="margin-top:22px;">
    Delivery charge proposed by <?php echo htmlspecialchars($chargeDelivery["delivery_name"] ?? "Delivery man"); ?>:
    <?php echo number_format((float)$chargeDelivery["proposed_charge"], 2); ?> BDT
</div>
<div class="action-row">
    <form method="post" action="delivery_charge_response.php" onsubmit="return confirm('Accept this delivery charge?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="delivery_id" value="<?php echo (int)$chargeDelivery["delivery_id"]; ?>">
        <button type="submit" name="action" value="accept" class="action-btn accept-btn">Accept Charge</button>
    </form>
    <form method="post" action="delivery_charge_response.php" onsubmit="return confirm('Reject this delivery charge?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="delivery_id" value="<?php echo (int)$chargeDelivery["delivery_id"]; ?>">
        <button type="submit" name="action" value="reject" class="action-btn reject-btn">Reject Charge</button>
    </form>
</div>
<?php endif; ?>

<?php
$isPublicDonation = $item
    && strtolower(trim($item["type"] ?? "")) === "donate"
    && strtolower(trim($item["status"] ?? "")) === "available"
    && empty($item["ngo_id"])
    && !(strtolower(trim($request["request_type"] ?? "")) === "ngo donation"
        && strtolower(trim($request["status"] ?? "")) === "pending");
if ($isPublicDonation):
?>
<div class="action-row">
    <form method="post" onsubmit="return confirm('Send a donation request to this donor?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="request_donor">
        <button type="submit" class="action-btn accept-btn">Request Donor</button>
    </form>
</div>
<?php endif; ?>

<a href="ngo_notifications.php" class="back-btn">
Back to Notifications
</a>

</div>

</div>

</body>

</html>
