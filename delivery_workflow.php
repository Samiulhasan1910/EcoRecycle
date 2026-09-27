<?php

function createDeliveryJob(mysqli $conn, string $sourceTable, int $sourceRequestId, int $itemId, int $receiverId): int
{
    $sourceTable = in_array($sourceTable, ['requests', 'item_requests', 'transactions'], true)
        ? $sourceTable
        : 'item_requests';

    $existing = mysqli_query($conn, "SELECT delivery_id FROM deliveries WHERE source_table = '$sourceTable' AND source_request_id = $sourceRequestId LIMIT 1");
    if ($existing && ($row = mysqli_fetch_assoc($existing))) {
        return (int)$row['delivery_id'];
    }

    $staff = mysqli_query($conn, "SELECT delivery_id FROM delivery_man WHERE verification_status = 'Approved' AND availability = 'Available' AND is_blocked = 0 ORDER BY delivery_id ASC");
    $sql = "INSERT INTO deliveries (request_id, source_table, source_request_id, item_id, receiver_id, delivery_type, status, created_at, updated_at) VALUES ($sourceRequestId, '$sourceTable', $sourceRequestId, $itemId, $receiverId, 'delivery', 'Open', NOW(), NOW())";
    if (!mysqli_query($conn, $sql)) {
        throw new Exception('Could not create delivery job: ' . mysqli_error($conn));
    }
    $deliveryId = (int)mysqli_insert_id($conn);
    while ($staff && ($deliveryMan = mysqli_fetch_assoc($staff))) {
        $deliveryManId = (int)$deliveryMan['delivery_id'];
        mysqli_query($conn, "INSERT INTO notifications (user_id, delivery_id, notification_type, message, status) VALUES (NULL, $deliveryManId, 'delivery', 'New open delivery job #$deliveryId is available. Accept it to handle this delivery.', 'Unread')");
    }
    return $deliveryId;
}
