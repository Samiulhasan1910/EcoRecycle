<?php
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "user") {
    header("Location: login.php");
    exit();
}

include("db.php");

$userId = (int)$_SESSION["user_id"];
$requestId = (int)($_GET["request_id"] ?? 0);
$notificationId = (int)($_GET["notification_id"] ?? 0);
$action = strtolower(trim($_GET["action"] ?? ""));

if ($requestId <= 0 || !in_array($action, ["accept", "reject"], true)) {
    header("Location: notifications.php");
    exit();
}

mysqli_begin_transaction($conn);

try {
    $result = mysqli_query(
        $conn,
        "SELECT r.request_id, r.ngo_id, r.item_id, r.status, i.title, i.status AS item_status
         FROM requests r
         INNER JOIN items i ON i.item_id = r.item_id
         WHERE r.request_id = $requestId
         AND r.request_type = 'NGO Donation'
         AND r.user_id = $userId
         AND i.user_id = $userId
         FOR UPDATE"
    );
    $request = $result ? mysqli_fetch_assoc($result) : null;

    if (!$request || strtolower($request["status"] ?? "") !== "pending") {
        throw new Exception("This NGO request is no longer pending.");
    }

    $ngoId = (int)$request["ngo_id"];
    $itemId = (int)$request["item_id"];
    $itemTitle = $request["title"] ?? "item";

    if ($action === "accept") {
        if (strtolower($request["item_status"] ?? "") !== "available") {
            throw new Exception("This donation is no longer available.");
        }
        if (!mysqli_query($conn, "UPDATE requests SET status = 'Accepted' WHERE request_id = $requestId AND status = 'Pending'")) {
            throw new Exception(mysqli_error($conn));
        }
        if (!mysqli_query($conn, "UPDATE items SET status = 'Donated', ngo_id = $ngoId WHERE item_id = $itemId AND user_id = $userId AND status = 'Available'")) {
            throw new Exception(mysqli_error($conn));
        }

        require_once __DIR__ . "/delivery_workflow.php";
        createDeliveryJob($conn, "requests", $requestId, $itemId, $userId);
        $message = "The donor accepted your request for '$itemTitle'. A delivery request is now open.";
    } else {
        if (!mysqli_query($conn, "UPDATE requests SET status = 'Rejected' WHERE request_id = $requestId AND status = 'Pending'")) {
            throw new Exception(mysqli_error($conn));
        }
        $message = "The donor rejected your request for '$itemTitle'.";
    }

    $messageSafe = mysqli_real_escape_string($conn, $message);
    if (!mysqli_query($conn, "INSERT INTO notifications (user_id, ngo_id, item_id, request_id, notification_type, message, status) VALUES (NULL, $ngoId, $itemId, $requestId, 'ngo_request_response', '$messageSafe', 'Unread')")) {
        throw new Exception(mysqli_error($conn));
    }

    if ($notificationId > 0) {
        mysqli_query($conn, "UPDATE notifications SET status = 'Read' WHERE notification_id = $notificationId AND user_id = $userId");
    }

    mysqli_commit($conn);
    header("Location: notifications.php?success=ngo_request_$action");
    exit();
} catch (Throwable $error) {
    mysqli_rollback($conn);
    header("Location: notifications.php?error=" . urlencode($error->getMessage()));
    exit();
}
