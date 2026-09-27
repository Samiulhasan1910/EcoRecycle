<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'delivery_man') { header('Location: login.php'); exit; }
include 'db.php';
$deliveryId = (int)($_SESSION['delivery_id'] ?? $_SESSION['user_id']);
$result = mysqli_query($conn, "SELECT notification_id, message, status, item_id, pickup_id FROM notifications WHERE delivery_id = $deliveryId ORDER BY notification_id DESC LIMIT 50");
$notifications = [];
if ($result) while ($row = mysqli_fetch_assoc($result)) $notifications[] = $row;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notificationId = (int)($_POST['notification_id'] ?? 0);
    if ($notificationId > 0) mysqli_query($conn, "UPDATE notifications SET status = 'Read' WHERE notification_id = $notificationId AND delivery_id = $deliveryId");
    header('Location: delivery_notifications.php'); exit;
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Notifications</title><link rel="stylesheet" href="style_delivery.css"><style>.note{display:flex;gap:14px;align-items:center;padding:16px;margin:12px 0;background:#fff;border:1px solid #dce8e0;border-radius:10px;color:inherit;text-decoration:none}.note:hover{border-color:#159b64}.note strong{flex:1}.note form{margin:0}</style></head><body><?php include 'header_sidebar_delivery.php'; ?><main class="main"><section class="delivery-dashboard-heading"><p class="eyebrow">Updates</p><h1>Notifications</h1></section><section class="delivery-panel"><?php if ($notifications): ?><?php foreach ($notifications as $note): ?><?php $link = !empty($note['item_id']) ? 'view_item.php?id=' . (int)$note['item_id'] : ''; ?><?php if ($link): ?><a class="note" href="<?php echo $link; ?>"><?php else: ?><div class="note"><?php endif; ?><strong><?php echo htmlspecialchars($note['message']); ?></strong><span><?php echo htmlspecialchars($note['status']); ?></span><?php if ($note['status'] === 'Unread'): ?><form method="post"><input type="hidden" name="notification_id" value="<?php echo (int)$note['notification_id']; ?>"><button type="submit">Mark read</button></form><?php endif; ?><?php echo $link ? '</a>' : '</div>'; ?><?php endforeach; ?><?php else: ?><p>You have no notifications.</p><?php endif; ?></section></main></body></html>
