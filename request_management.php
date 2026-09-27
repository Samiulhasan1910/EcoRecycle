<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$query = "
        SELECT r.request_id, r.request_type, r.status, r.created_at, r.delivery_id,
            r.delivery_rejection_reason, r.completion_note, r.completed_at,
           u.name AS user_name,
           i.title AS item_title,
           n.name AS ngo_name,
           d.name AS delivery_name
    FROM requests r
    LEFT JOIN users u ON u.user_id = r.user_id
    LEFT JOIN items i ON i.item_id = r.item_id
    LEFT JOIN ngos n ON n.ngo_id = r.ngo_id
    LEFT JOIN delivery d ON d.delivery_id = r.delivery_id
    ORDER BY r.request_id DESC
";

$result = mysqli_query($conn, $query);
$requests = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
}

function requestStatusClass($status) {
    $normalized = strtolower(trim((string) $status));
    $normalized = str_replace(' ', '-', $normalized);
    return 'status-' . $normalized;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Monitoring</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, sans-serif; }
        body { margin: 0; background: #f4f8f5; color: #1d2d1f; }
        a { text-decoration: none; }
        .topbar {
            height: 70px; background: #fff; display: flex; align-items: center; justify-content: space-between;
            padding: 0 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .brand { font-size: 24px; font-weight: bold; color: #168a45; }
        .brand span { color: #1f2e22; }
        .top-actions { display: flex; align-items: center; gap: 18px; }
        .top-actions a { color: #168a45; font-weight: bold; }
        .layout { display: grid; grid-template-columns: 230px 1fr; min-height: calc(100vh - 70px); }
        .sidebar { background: #fff; padding: 25px 0; box-shadow: 2px 0 10px rgba(0,0,0,0.04); }
        .sidebar a { display: block; padding: 14px 22px; color: #2d2d2d; border-left: 4px solid transparent; margin: 6px 0; }
        .sidebar a:hover { background: #f3f7f4; border-left-color: #168a45; }
        .sidebar a.active { background: #e9f7ee; border-left-color: #168a45; color: #168a45; font-weight: bold; }
        .content { padding: 30px 36px; }
        .page-title { font-size: 32px; color: #168a45; margin: 0 0 8px; }
        .page-subtitle { color: #6b7b6f; margin-bottom: 25px; }
        .panel { background: #fff; border-radius: 16px; border: 1px solid #ebf0ec; box-shadow: 0 2px 8px rgba(0,0,0,0.02); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 16px; border-bottom: 1px solid #edf1ee; text-align: left; }
        th { background: #f6faf7; color: #2c3d2f; font-size: 13px; text-transform: uppercase; }
        td { font-size: 14px; vertical-align: top; }
        .status-pill {
            display: inline-block; padding: 5px 10px; border-radius: 999px; font-size: 12px; font-weight: bold;
        }
        .status-pending { background: #fff4d9; color: #a86c00; }
        .status-approved { background: #dff5e6; color: #1b8c4a; }
        .status-assigned { background: #dfeeff; color: #0f5ebd; }
        .status-delivery-rejected { background: #fde3e1; color: #c62828; }
        .status-picked-up { background: #fff1d9; color: #b76000; }
        .status-completed { background: #d8f3df; color: #0d7d3c; }
        .status-rejected { background: #fde3e1; color: #c62828; }
        .status-cancelled { background: #eceff1; color: #5b6873; }
        .action-group { display: flex; gap: 8px; flex-wrap: wrap; }
        .action-group a {
            display: inline-block; padding: 7px 10px; border-radius: 7px; font-size: 12px; font-weight: bold; color: white;
        }
        .approve { background: #2aa85c; }
        .reject { background: #d93025; }
        .assign { background: #2f6eea; }
        .picked { background: #e79013; }
        .complete { background: #0d7d3c; }
        .cancel { background: #6b7280; }
        .empty { text-align: center; color: #697a6f; padding: 42px 20px; }
        @media (max-width: 900px) { .layout { grid-template-columns: 1fr; } .sidebar { display: none; } .content { padding: 20px 18px; } }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="brand">Eco<span>Cycle</span></div>
        <div class="top-actions">
            <a href="admin_dashboard.php">Admin Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="layout">
        <aside class="sidebar">
            <a href="admin_dashboard.php">📊 Dashboard</a>
            <a href="user_management.php">👥 Users</a>
            <a href="ngo_management.php">🏢 NGOs</a>
            <a href="delivery_management.php">🚚 Delivery Staff</a>
            <a href="item_management.php">📦 Item / Post Management</a>
            <a href="request_management.php" class="active">📋 Request Monitoring</a>
        </aside>

        <main class="content">
            <h1 class="page-title">📋 Request Monitoring</h1>
            <p class="page-subtitle">Track every purchase request through approval, assignment, pickup, and completion.</p>

            <div class="panel">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Item</th>
                            <th>NGO</th>
                            <th>Delivery</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($requests)): ?>
                            <?php foreach ($requests as $req): ?>
                                <?php
                                    $status = trim((string) ($req['status'] ?? '')) ?: 'Pending';
                                ?>
                                <tr>
                                    <td>#<?php echo (int) $req['request_id']; ?></td>
                                    <td><?php echo htmlspecialchars($req['user_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($req['item_title'] ?? 'Unknown Item'); ?></td>
                                    <td><?php echo htmlspecialchars($req['ngo_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($req['delivery_name'] ?? 'Not assigned'); ?></td>
                                    <td><?php echo htmlspecialchars($req['request_type'] ?? 'Buy'); ?></td>
                                    <td>
                                        <span class="status-pill <?php echo requestStatusClass($status); ?>"><?php echo htmlspecialchars($status); ?></span>
                                        <?php if (!empty($req['completion_note'])): ?>
                                            <div class="delivery-note">Completion: <?php echo htmlspecialchars($req['completion_note']); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($req['delivery_rejection_reason'])): ?>
                                            <div class="delivery-note">Rejected: <?php echo htmlspecialchars($req['delivery_rejection_reason']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($req['created_at'] ?? '—'); ?></td>
                                    <td>
                                        <div class="action-group">
                                            <?php if ($status === 'Pending'): ?>
                                                <a href="request_management_action.php?action=approve&id=<?php echo (int) $req['request_id']; ?>" class="approve">Approve</a>
                                                <a href="request_management_action.php?action=reject&id=<?php echo (int) $req['request_id']; ?>" class="reject">Reject</a>
                                            <?php endif; ?>

                                            <?php if ($status === 'Approved'): ?>
                                                <a href="request_management_action.php?action=assign_form&id=<?php echo (int) $req['request_id']; ?>" class="assign">Assign</a>
                                                <a href="request_management_action.php?action=reject&id=<?php echo (int) $req['request_id']; ?>" class="reject">Reject</a>
                                            <?php endif; ?>

                                            <?php if (in_array($status, ['Delivery Rejected', 'Pickup Failed'], true)): ?>
                                                <a href="request_management_action.php?action=assign_form&id=<?php echo (int) $req['request_id']; ?>" class="assign">Assign Another Delivery</a>
                                            <?php endif; ?>

                                            <?php if (in_array($status, ['Accepted', 'Arrived at Pickup', 'Picked Up'], true)): ?>
                                                <span class="delivery-update">Delivery staff updating</span>
                                            <?php endif; ?>

                                            <?php if (!in_array($status, ['Rejected', 'Cancelled', 'Completed'], true)): ?>
                                                <a href="request_management_action.php?action=cancel&id=<?php echo (int) $req['request_id']; ?>" class="cancel">Cancel</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="empty">No requests found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
