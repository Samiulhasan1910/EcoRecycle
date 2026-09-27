<?php
session_start();
if (!isset($_SESSION['ngo_id'])) { header('Location: login.php'); exit; }
include 'db.php';
$ngoId = (int)$_SESSION['ngo_id'];
$requestId = (int)($_POST['request_id'] ?? 0);
$deliveryManId = (int)($_POST['delivery_man_id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $requestId <= 0 || $deliveryManId <= 0) { header('Location: ngo_accepted_donations.php?error=invalid_assignment'); exit; }
mysqli_begin_transaction($conn);
try {
    $requestResult = mysqli_query($conn, "SELECT request_id, user_id, item_id FROM requests WHERE request_id = $requestId AND ngo_id = $ngoId AND request_type = 'Donation' AND status = 'Accepted' FOR UPDATE");
    $request = $requestResult ? mysqli_fetch_assoc($requestResult) : null;
    $staffResult = mysqli_query($conn, "SELECT delivery_id FROM delivery_man WHERE delivery_id = $deliveryManId AND verification_status = 'Approved' AND availability = 'Available' AND is_blocked = 0 LIMIT 1");
    if (!$request || !$staffResult || mysqli_num_rows($staffResult) === 0) throw new Exception('Select an approved and available delivery man.');
    mysqli_query($conn, "UPDATE requests SET delivery_id = $deliveryManId, assigned_at = NOW(), updated_at = NOW() WHERE request_id = $requestId");
    $deliveryResult = mysqli_query($conn, "SELECT delivery_id FROM deliveries WHERE request_id = $requestId LIMIT 1");
    if ($deliveryResult && mysqli_num_rows($deliveryResult) > 0) {
        $delivery = mysqli_fetch_assoc($deliveryResult);
        mysqli_query($conn, "UPDATE deliveries SET assigned_delivery_man_id = $deliveryManId, status = 'Assigned', updated_at = NOW() WHERE delivery_id = " . (int)$delivery['delivery_id']);
    } else {
        mysqli_query($conn, "INSERT INTO deliveries (request_id, delivery_type, status, assigned_delivery_man_id, source_table, source_request_id, item_id, receiver_id, charge_status, charge_payment_status, updated_at) VALUES ($requestId, 'delivery', 'Assigned', $deliveryManId, 'requests', $requestId, " . (int)$request['item_id'] . ", " . (int)$request['user_id'] . ", 'Not Proposed', 'Unpaid', NOW())");
    }
    $pickupResult = mysqli_query($conn, "SELECT pickup_id FROM pickups WHERE request_id = $requestId LIMIT 1");
    if ($pickupResult && mysqli_num_rows($pickupResult) > 0) {
        mysqli_query($conn, "UPDATE pickups SET delivery_id = $deliveryManId, status = 'Assigned' WHERE request_id = $requestId");
    } else {
        mysqli_query($conn, "INSERT INTO pickups (request_id, date, time, status, delivery_id) VALUES ($requestId, CURDATE(), CURTIME(), 'Assigned', $deliveryManId)");
    }
    mysqli_query($conn, "INSERT INTO notifications (user_id, delivery_id, item_id, notification_type, message, status) VALUES (NULL, $deliveryManId, " . (int)$request['item_id'] . ", 'delivery', 'A donation pickup has been assigned to you.', 'Unread')");
    mysqli_commit($conn);
    header('Location: ngo_accepted_donations.php?assigned=1');
} catch (Throwable $error) {
    mysqli_rollback($conn);
    header('Location: ngo_accepted_donations.php?error=' . urlencode($error->getMessage()));
}
exit;
