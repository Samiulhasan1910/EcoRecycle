<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($action === 'view' && $itemId > 0) {
    $query = "SELECT i.*, u.name AS user_name FROM items i LEFT JOIN users u ON u.user_id = i.user_id WHERE i.item_id = $itemId LIMIT 1";
    $result = mysqli_query($conn, $query);
    if (!$result || mysqli_num_rows($result) === 0) {
        header('Location: item_management.php');
        exit;
    }

    $item = mysqli_fetch_assoc($result);
    $statusText = trim((string) ($item['status'] ?? '')) === '' ? 'Pending' : trim((string) $item['status']);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Item Details</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f8f5; margin: 0; padding: 30px; }
            .card { max-width: 760px; margin: 0 auto; background: #fff; border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; }
            .header { background: #168a45; color: white; padding: 24px; }
            .content { padding: 24px; }
            .row { display: flex; justify-content: space-between; gap: 12px; padding: 12px 0; border-bottom: 1px solid #edf1ee; }
            .label { font-weight: bold; color: #6d7b6f; }
            .status { display: inline-block; padding: 6px 12px; border-radius: 999px; font-weight: bold; }
            .status-pending { background: #fff4d9; color: #a86c00; }
            .status-approved { background: #dff5e6; color: #1b8c4a; }
            .status-available { background: #dff5e6; color: #1b8c4a; }
            .status-requested { background: #dfe8ff; color: #2451c6; }
            .status-collected { background: #f0e7ff; color: #6d42c8; }
            .status-completed { background: #d7f7f3; color: #0f766e; }
            .status-rejected { background: #fde3e1; color: #c62828; }
            .actions { margin-top: 24px; display: flex; gap: 10px; flex-wrap: wrap; }
            .btn { display: inline-block; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: bold; }
            .back { background: #edf1ee; color: #203123; }
            .approve { background: #2aa85c; color: white; }
            .reject { background: #d93025; color: white; }
            .mark { background: #4f46e5; color: white; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="header">
                <h1><?php echo htmlspecialchars($item['title']); ?></h1>
                <span class="status status-<?php echo strtolower(str_replace(' ', '-', $statusText)); ?>"><?php echo htmlspecialchars($statusText); ?></span>
            </div>
            <div class="content">
                <?php
                    $imageFile = trim((string) ($item['image'] ?? ''));
                    $imagePath = $imageFile !== '' ? 'uploads/' . $imageFile : '';
                ?>
                <?php if ($imageFile !== '' && file_exists($imagePath)): ?>
                    <div style="margin: 0 0 20px; text-align: center;">
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Item image" style="max-width: 100%; max-height: 360px; border-radius: 12px; border: 1px solid #dfe7e1; background: #f7faf8;">
                    </div>
                <?php else: ?>
                    <div style="margin: 0 0 20px; padding: 18px; border: 1px dashed #d0d8d2; border-radius: 12px; text-align: center; color: #6d7b6f; background: #f7faf8;">
                        No image uploaded for this item.
                    </div>
                <?php endif; ?>
                <div class="row"><div class="label">Category</div><div><?php echo htmlspecialchars($item['category']); ?></div></div>
                <div class="row"><div class="label">Type</div><div><?php echo htmlspecialchars($item['type']); ?></div></div>
                <div class="row"><div class="label">Posted By</div><div><?php echo htmlspecialchars($item['user_name'] ?? 'Unknown'); ?></div></div>
                <div class="row"><div class="label">Location</div><div><?php echo htmlspecialchars($item['location']); ?></div></div>
                <div class="row"><div class="label">Price</div><div><?php echo htmlspecialchars($item['price']); ?></div></div>
                <div class="row"><div class="label">Status</div><div><?php echo htmlspecialchars($statusText); ?></div></div>
                <div class="actions">
                    <a class="btn back" href="item_management.php">Back</a>
                    <?php if ($statusText !== 'Approved' && $statusText !== 'Available' && $statusText !== 'Requested' && $statusText !== 'Collected' && $statusText !== 'Completed'): ?>
                        <a class="btn approve" href="item_management_action.php?action=approve&id=<?php echo $itemId; ?>">Approve</a>
                    <?php endif; ?>
                    <?php if ($statusText !== 'Rejected'): ?>
                        <a class="btn reject" href="item_management_action.php?action=reject&id=<?php echo $itemId; ?>">Reject</a>
                    <?php endif; ?>
                    <?php if ($statusText !== 'Available'): ?>
                        <a class="btn mark" href="item_management_action.php?action=set_status&id=<?php echo $itemId; ?>&value=Available">Set Available</a>
                    <?php endif; ?>
                    <?php if ($statusText !== 'Requested'): ?>
                        <a class="btn mark" href="item_management_action.php?action=set_status&id=<?php echo $itemId; ?>&value=Requested">Set Requested</a>
                    <?php endif; ?>
                    <?php if ($statusText !== 'Collected'): ?>
                        <a class="btn mark" href="item_management_action.php?action=set_status&id=<?php echo $itemId; ?>&value=Collected">Set Collected</a>
                    <?php endif; ?>
                    <?php if ($statusText !== 'Completed'): ?>
                        <a class="btn mark" href="item_management_action.php?action=set_status&id=<?php echo $itemId; ?>&value=Completed">Set Completed</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if ($action === 'approve' && $itemId > 0) {
    mysqli_query($conn, "UPDATE items SET status = 'Approved' WHERE item_id = $itemId");
    header('Location: item_management.php?success=approved');
    exit;
}

if ($action === 'reject' && $itemId > 0) {
    mysqli_query($conn, "UPDATE items SET status = 'Rejected' WHERE item_id = $itemId");
    header('Location: item_management.php?success=rejected');
    exit;
}

if ($action === 'set_status' && $itemId > 0) {
    $value = $_GET['value'] ?? 'Available';
    $allowed = ['Pending', 'Approved', 'Available', 'Requested', 'Collected', 'Completed', 'Rejected'];
    $statusValue = in_array($value, $allowed, true) ? $value : 'Available';
    mysqli_query($conn, "UPDATE items SET status = '" . mysqli_real_escape_string($conn, $statusValue) . "' WHERE item_id = $itemId");
    header('Location: item_management.php?success=status_updated');
    exit;
}

if ($action === 'delete' && $itemId > 0) {
    mysqli_query($conn, "DELETE FROM items WHERE item_id = $itemId");
    header('Location: item_management.php?success=deleted');
    exit;
}

header('Location: item_management.php');
exit;
