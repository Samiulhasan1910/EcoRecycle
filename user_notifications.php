<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header('Location: login.php');
    exit;
}
include 'db.php';
$userId = (int)$_SESSION['user_id'];
$result = mysqli_query($conn, "SELECT notification_id, message, status, item_id, request_id FROM notifications WHERE user_id = $userId ORDER BY notification_id DESC LIMIT 50");
$notifications = [];
if ($result) while ($row = mysqli_fetch_assoc($result)) $notifications[] = $row;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $notificationId = (int)($_POST['notification_id'] ?? 0);
    if ($action === 'clear' && $notificationId > 0) {
        mysqli_query($conn, "DELETE FROM notifications WHERE notification_id = $notificationId AND user_id = $userId");
    } elseif ($notificationId > 0) {
        mysqli_query($conn, "UPDATE notifications SET status = 'Read' WHERE notification_id = $notificationId AND user_id = $userId");
    }
    header('Location: user_notifications.php');
    exit;
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Notifications</title><link rel="stylesheet" href="style.css"><style>.notification-list{max-width:900px;margin:110px auto 40px;padding:0 24px}.clear-btn{border:1px solid #dc3545;background:#fff;color:#dc3545;border-radius:7px;padding:9px 14px;font-weight:600;cursor:pointer}.clear-btn:hover{background:#dc3545;color:#fff}.notification{display:flex;gap:15px;align-items:center;background:#fff;border:1px solid #e5e5e5;border-radius:10px;padding:16px;margin:12px 0;color:inherit;text-decoration:none}.notification:hover{border-color:#168a45}.notification strong{flex:1}.notification-message{color:inherit;text-decoration:none}.notification-message:hover{color:#168a45}.notification small{color:#777}.notification form{margin:0}</style></head><body><?php include 'header_sidebar.php'; ?><main class="notification-list"><h1>Notifications</h1><?php if ($notifications): ?><?php foreach ($notifications as $note): ?><?php $link = !empty($note['item_id']) ? 'view_item.php?id=' . (int)$note['item_id'] : ''; ?><div class="notification"><strong><?php if ($link): ?><a class="notification-message" href="<?php echo $link; ?>"><?php endif; ?><?php echo htmlspecialchars($note['message']); ?><?php if ($link): ?></a><?php endif; ?></strong><small><?php echo htmlspecialchars($note['status']); ?></small><?php if ($note['status'] === 'Unread'): ?><form method="post"><input type="hidden" name="notification_id" value="<?php echo (int)$note['notification_id']; ?>"><button type="submit">Mark read</button></form><?php endif; ?><form method="post" onsubmit="return confirm('Clear this notification?');"><input type="hidden" name="action" value="clear"><input type="hidden" name="notification_id" value="<?php echo (int)$note['notification_id']; ?>"><button class="clear-btn" type="submit">Clear</button></form></div><?php endforeach; ?><?php else: ?><p>No notifications yet.</p><?php endif; ?></main></body></html>
