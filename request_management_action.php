<?php
session_start();
include 'db.php';

$action = $_GET['action'] ?? '';
$requestId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$returnTo = $_GET['return'] ?? 'request_management.php';
$allowedReturns = ['request_management.php', 'my_requests'];
if (!in_array($returnTo, $allowedReturns, true)) {
    $returnTo = 'request_management.php';
}

if ($requestId <= 0) {
    header('Location: ' . $returnTo);
    exit;
}

$canAccessAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$canAccessDelivery = isset($_SESSION['role']) && $_SESSION['role'] === 'delivery_man';

if (!$canAccessAdmin && !$canAccessDelivery) {
    header('Location: login.php');
    exit;
}

if ($canAccessDelivery && !in_array($action, ['accept', 'reject_delivery', 'arrived_at_pickup', 'picked_up_delivery', 'complete_delivery'], true)) {
    header('Location: login.php');
    exit;
}

if ($canAccessDelivery) {
    $token = $_POST['csrf_token'] ?? '';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        header('Location: delivery_dashboard.php');
        exit;
    }
}

if ($canAccessAdmin && in_array($action, ['picked_up', 'complete'], true)) {
    header('Location: request_management.php');
    exit;
}

if ($action === 'cancel') {
    $requestRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id, status, item_id FROM requests WHERE request_id = $requestId LIMIT 1"));
    $isOwner = $requestRow && isset($_SESSION['user_id']) && (int) $requestRow['user_id'] === (int) $_SESSION['user_id'];

    if ($canAccessAdmin || $isOwner) {
        mysqli_query($conn, "UPDATE requests SET status = 'Cancelled' WHERE request_id = $requestId");
        if (!empty($requestRow['item_id'])) {
            mysqli_query($conn, "UPDATE items SET status = 'Available' WHERE item_id = " . (int) $requestRow['item_id']);
        }
        header('Location: ' . ($returnTo === 'my_requests' ? 'my_requests.php' : 'request_management.php'));
        exit;
    }

    header('Location: login.php');
    exit;
}

if ($action === 'approve') {
    mysqli_query($conn, "UPDATE requests SET status = 'Approved' WHERE request_id = $requestId");
    mysqli_query($conn, "UPDATE items SET status = 'Requested' WHERE item_id = (SELECT item_id FROM requests WHERE request_id = $requestId)");
    header('Location: request_management.php');
    exit;
}

if ($action === 'reject') {
    mysqli_query($conn, "UPDATE requests SET status = 'Rejected' WHERE request_id = $requestId");
    mysqli_query($conn, "UPDATE items SET status = 'Available' WHERE item_id = (SELECT item_id FROM requests WHERE request_id = $requestId)");
    header('Location: request_management.php');
    exit;
}

if ($action === 'assign_form') {
    $deliveryResult = mysqli_query($conn, "SELECT delivery_id, name, email, phone, availability FROM delivery WHERE verification_status = 'Approved' AND is_blocked = 0 AND availability = 'Available' ORDER BY name ASC");
    $deliveryList = [];
    if ($deliveryResult) {
        while ($row = mysqli_fetch_assoc($deliveryResult)) {
            $deliveryList[] = $row;
        }
    }

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Assign Delivery Staff</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f8f5; margin: 0; padding: 40px; }
            .card { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 30px; }
            h2 { color: #168a45; margin-top: 0; }
            label { display: block; margin-bottom: 8px; font-weight: bold; color: #213326; }
            select { width: 100%; padding: 12px; border: 1px solid #dfe7e1; border-radius: 8px; margin-bottom: 18px; font-size: 15px; }
            .btn { display: inline-block; padding: 10px 18px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; font-weight: bold; }
            .primary { background: #168a45; color: white; }
            .secondary { background: #edf1ee; color: #203123; }
            .empty { color: #697a6f; padding: 18px 0; }
            .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        </style>
    </head>
    <body>
        <div class="card">
            <h2>Select Delivery Staff</h2>
            <?php if (!empty($deliveryList)): ?>
                <form method="POST" action="request_management_action.php?action=assign&id=<?php echo $requestId; ?>">
                    <label for="delivery_id">Available delivery staff</label>
                    <select name="delivery_id" id="delivery_id" required>
                        <option value="">-- Select delivery staff --</option>
                        <?php foreach ($deliveryList as $delivery): ?>
                            <option value="<?php echo (int) $delivery['delivery_id']; ?>">
                                <?php echo htmlspecialchars($delivery['name']); ?> (<?php echo htmlspecialchars($delivery['phone'] ?? ''); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="actions">
                        <button type="submit" class="btn primary">Assign</button>
                        <a href="request_management.php" class="btn secondary">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <p class="empty">No available delivery staff right now. Please mark someone Available first from the delivery staff list.</p>
                <div class="actions">
                    <a href="request_management.php" class="btn secondary">Back</a>
                </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if ($action === 'assign') {
    $deliveryId = isset($_POST['delivery_id']) ? (int) $_POST['delivery_id'] : 0;

    if ($deliveryId > 0) {
        mysqli_query($conn, "UPDATE requests SET delivery_id = $deliveryId, status = 'Assigned', assigned_at = NOW(), delivery_rejection_reason = NULL, item_received = NULL, pickup_note = NULL WHERE request_id = $requestId");
        mysqli_query($conn, "UPDATE items SET status = 'Requested' WHERE item_id = (SELECT item_id FROM requests WHERE request_id = $requestId)");
        mysqli_query($conn, "UPDATE delivery SET availability = 'Busy' WHERE delivery_id = $deliveryId");

        $notificationMessage = mysqli_real_escape_string($conn, "A new request #$requestId has been assigned to you. Please review and accept it.");
        mysqli_query($conn, "INSERT INTO notifications (user_id, delivery_id, message, status) VALUES (NULL, $deliveryId, '$notificationMessage', 'Unread')");
    }

    header('Location: request_management.php');
    exit;
}

if ($action === 'accept') {
    if (!$canAccessDelivery) {
        header('Location: login.php');
        exit;
    }

    $deliveryId = (int)($_SESSION['delivery_id'] ?? $_SESSION['user_id'] ?? 0);
    mysqli_query($conn, "UPDATE requests SET status = 'Accepted' WHERE request_id = $requestId AND delivery_id = $deliveryId AND status = 'Assigned'");
    mysqli_query($conn, "UPDATE notifications SET status = 'Read' WHERE delivery_id = $deliveryId AND message LIKE '%request #$requestId%' AND status = 'Unread'");
    header('Location: delivery_dashboard.php');
    exit;
}

if ($action === 'reject_delivery') {
    $deliveryId = (int)($_SESSION['delivery_id'] ?? $_SESSION['user_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    if ($reason === '') {
        header('Location: assigned_deliveries.php?id=' . $requestId . '&error=reason_required');
        exit;
    }

    $reasonSafe = mysqli_real_escape_string($conn, $reason);
    $requestResult = mysqli_query($conn, "SELECT item_id FROM requests WHERE request_id = $requestId AND delivery_id = $deliveryId AND status = 'Assigned' LIMIT 1");
    $requestRow = $requestResult ? mysqli_fetch_assoc($requestResult) : null;
    if ($requestRow) {
        mysqli_query($conn, "UPDATE requests SET status = 'Delivery Rejected', delivery_rejection_reason = '$reasonSafe' WHERE request_id = $requestId AND delivery_id = $deliveryId AND status = 'Assigned'");
        mysqli_query($conn, "UPDATE delivery SET availability = 'Available' WHERE delivery_id = $deliveryId");
        mysqli_query($conn, "UPDATE items SET status = 'Requested' WHERE item_id = " . (int)$requestRow['item_id']);
    }
    header('Location: assigned_deliveries.php');
    exit;
}

if ($action === 'arrived_at_pickup') {
    $deliveryId = (int)($_SESSION['delivery_id'] ?? $_SESSION['user_id'] ?? 0);
    mysqli_query($conn, "UPDATE requests SET status = 'Arrived at Pickup', arrived_at = NOW() WHERE request_id = $requestId AND delivery_id = $deliveryId AND status = 'Accepted'");
    header('Location: assigned_deliveries.php?id=' . $requestId);
    exit;
}

if ($action === 'picked_up_delivery') {
    $deliveryId = (int)($_SESSION['delivery_id'] ?? $_SESSION['user_id'] ?? 0);
    $itemReceived = $_POST['item_received'] ?? '';
    $pickupNote = trim($_POST['pickup_note'] ?? '');
    if (!in_array($itemReceived, ['Yes', 'No'], true)) {
        header('Location: assigned_deliveries.php?id=' . $requestId . '&error=item_received_required');
        exit;
    }

    $pickupNote = $pickupNote !== '' ? $pickupNote : ($itemReceived === 'No' ? 'Item was not received.' : 'Collected successfully.');
    $pickupNoteSafe = mysqli_real_escape_string($conn, $pickupNote);
    if ($itemReceived === 'No') {
        mysqli_query($conn, "UPDATE requests SET status = 'Pickup Failed', item_received = 'No', pickup_note = '$pickupNoteSafe', picked_up_at = NOW() WHERE request_id = $requestId AND delivery_id = $deliveryId AND status = 'Arrived at Pickup'");
        if (mysqli_affected_rows($conn) > 0) {
            mysqli_query($conn, "UPDATE delivery SET availability = 'Available' WHERE delivery_id = $deliveryId");
        }
    } else {
        mysqli_query($conn, "UPDATE requests SET status = 'Picked Up', item_received = 'Yes', pickup_note = '$pickupNoteSafe', picked_up_at = NOW() WHERE request_id = $requestId AND delivery_id = $deliveryId AND status = 'Arrived at Pickup'");
        mysqli_query($conn, "UPDATE items SET status = 'Collected' WHERE item_id = (SELECT item_id FROM requests WHERE request_id = $requestId)");
    }
    header('Location: assigned_deliveries.php?id=' . $requestId);
    exit;
}

if ($action === 'complete_delivery') {
    $deliveryId = (int)($_SESSION['delivery_id'] ?? $_SESSION['user_id'] ?? 0);
    $completionNote = trim($_POST['completion_note'] ?? '');
    if ($completionNote === '') {
        header('Location: assigned_deliveries.php?id=' . $requestId . '&error=completion_required');
        exit;
    }

    $completionNoteSafe = mysqli_real_escape_string($conn, $completionNote);
    $updated = mysqli_query($conn, "UPDATE requests SET status = 'Completed', completion_note = '$completionNoteSafe', completed_at = NOW() WHERE request_id = $requestId AND delivery_id = $deliveryId AND status = 'Picked Up'");
    if ($updated && mysqli_affected_rows($conn) > 0) {
        mysqli_query($conn, "UPDATE items SET status = 'Completed' WHERE item_id = (SELECT item_id FROM requests WHERE request_id = $requestId)");
        mysqli_query($conn, "UPDATE delivery SET availability = 'Available' WHERE delivery_id = $deliveryId");
    }
    header('Location: assigned_deliveries.php?id=' . $requestId);
    exit;
}

if ($action === 'picked_up') {
    if (!$canAccessAdmin) {
        header('Location: login.php');
        exit;
    }
    mysqli_query($conn, "UPDATE requests SET status = 'Picked Up' WHERE request_id = $requestId AND status = 'Accepted'");
    mysqli_query($conn, "UPDATE items SET status = 'Collected' WHERE item_id = (SELECT item_id FROM requests WHERE request_id = $requestId)");
    header('Location: request_management.php');
    exit;
}

if ($action === 'complete') {
    mysqli_query($conn, "UPDATE requests SET status = 'Completed' WHERE request_id = $requestId");
    mysqli_query($conn, "UPDATE items SET status = 'Completed' WHERE item_id = (SELECT item_id FROM requests WHERE request_id = $requestId)");
    $deliveryResult = mysqli_query($conn, "SELECT delivery_id FROM requests WHERE request_id = $requestId LIMIT 1");
    if ($deliveryResult && mysqli_num_rows($deliveryResult) > 0) {
        $deliveryRow = mysqli_fetch_assoc($deliveryResult);
        if (!empty($deliveryRow['delivery_id'])) {
            mysqli_query($conn, "UPDATE delivery SET availability = 'Available' WHERE delivery_id = " . (int) $deliveryRow['delivery_id']);
        }
    }
    header('Location: request_management.php');
    exit;
}

header('Location: request_management.php');
exit;
