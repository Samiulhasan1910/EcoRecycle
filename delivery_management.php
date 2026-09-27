<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

function ensureDeliveryColumns($conn)
{
    $checks = [
        ['availability', "ALTER TABLE delivery ADD COLUMN availability VARCHAR(20) NOT NULL DEFAULT 'Offline'"],
        ['is_blocked', "ALTER TABLE delivery ADD COLUMN is_blocked TINYINT(1) NOT NULL DEFAULT 0"],
    ];

    foreach ($checks as [$col, $sql]) {
        $result = mysqli_query($conn, "SHOW COLUMNS FROM delivery LIKE '" . mysqli_real_escape_string($conn, $col) . "'");
        if (!$result || mysqli_num_rows($result) === 0) {
            mysqli_query($conn, $sql);
        }
    }
}

ensureDeliveryColumns($conn);

function normalizeDeliveryStatus($value)
{
    $status = trim((string) $value);
    if ($status === '') {
        return 'Pending';
    }
    return $status;
}

// Search / filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';

$searchSql = '';
if ($search !== '') {
    $s = mysqli_real_escape_string($conn, $search);
    $searchSql = " WHERE (name LIKE '%$s%' OR email LIKE '%$s%' OR phone LIKE '%$s%' OR location LIKE '%$s%') ";
}

$statusSql = '';
if ($statusFilter !== '' && $statusFilter !== 'All') {
    $status = mysqli_real_escape_string($conn, $statusFilter);
    $statusSql = $searchSql === '' ? " WHERE verification_status = '$status'" : " AND verification_status = '$status'";
}

if ($statusFilter === 'Blocked') {
    $statusSql = $searchSql === '' ? " WHERE (verification_status = 'Blocked' OR is_blocked = 1)" : " AND (verification_status = 'Blocked' OR is_blocked = 1)";
}

// page pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) AS total FROM delivery" . $searchSql . $statusSql;
$countResult = mysqli_query($conn, $countSql);
$totalRecords = 0;
if ($countResult) {
    $row = mysqli_fetch_assoc($countResult);
    $totalRecords = (int)($row['total'] ?? 0);
}
$totalPages = max(1, (int) ceil($totalRecords / $perPage));

$listSql = "SELECT delivery_id, name, email, phone, location, verification_status, verification_document, availability, is_blocked FROM delivery" . $searchSql . $statusSql . " ORDER BY delivery_id DESC LIMIT $offset, $perPage";
$listResult = mysqli_query($conn, $listSql);
$staffList = [];
if ($listResult) {
    while ($row = mysqli_fetch_assoc($listResult)) {
        $row['status_text'] = normalizeDeliveryStatus($row['verification_status']);
        $staffList[] = $row;
    }
}

$totalStaff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM delivery"))['total'];
$pendingStaff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM delivery WHERE verification_status = 'Pending' OR verification_status = ''"))['total'];
$approvedStaff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM delivery WHERE verification_status = 'Approved'"))['total'];
$blockedStaff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM delivery WHERE verification_status = 'Blocked' OR is_blocked = 1"))['total'];
$availableStaff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM delivery WHERE availability = 'Available'"))['total'];
$busyStaff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM delivery WHERE availability = 'Busy'"))['total'];
$offlineStaff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM delivery WHERE availability = 'Offline'"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Staff Management</title>
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
        .sidebar {
            background: #fff; padding: 25px 0; box-shadow: 2px 0 10px rgba(0,0,0,0.04);
        }
        .sidebar a {
            display: block; padding: 14px 22px; color: #2d2d2d; border-left: 4px solid transparent; margin: 6px 0;
        }
        .sidebar a:hover { background: #f3f7f4; border-left-color: #168a45; }
        .sidebar a.active { background: #e9f7ee; border-left-color: #168a45; color: #168a45; font-weight: bold; }

        .content { padding: 30px 36px; }
        .page-title { font-size: 32px; color: #168a45; margin: 0 0 8px; }
        .page-subtitle { color: #6b7b6f; margin-bottom: 25px; }

        .statsgrid {
            display: grid; grid-template-columns: repeat(6, minmax(140px, 1fr)); gap: 16px; margin-bottom: 28px;
        }

        .stat-card {
            background: #fff; border-radius: 16px; padding: 18px 16px; min-height: 110px; border: 1px solid #ebf0ec;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .stat-card h3 { font-size: 12px; letter-spacing: 0.06em; color: #6d7c70; text-transform: uppercase; margin: 0 0 12px; }
        .stat-card .value { font-size: 26px; font-weight: bold; color: #168a45; }
        .stat-card .value.pending { color: #eaa300; }
        .stat-card .value.blocked { color: #d93025; }
        .stat-card .value.available { color: #1b8c4a; }
        .stat-card .value.busy { color: #ff8a1c; }
        .stat-card .value.offline { color: #70777b; }

        .toolbar {
            display: flex; justify-content: space-between; align-items: center; gap: 16px; margin: 24px 0 18px;
            flex-wrap: wrap;
        }
        .search-wrap { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 260px; }
        .search-wrap input, .search-wrap select { padding: 11px 14px; border-radius: 8px; border: 1px solid #dfe7e1; font-size: 14px; }
        .search-wrap input { flex: 1; }
        .primary-btn, .secondary-btn, .danger-btn, .small-btn {
            padding: 10px 18px; border-radius: 8px; border: none; cursor: pointer; font-weight: bold; font-size: 14px;
        }
        .primary-btn { background: #168a45; color: white; }
        .secondary-btn { background: #e8f7ee; color: #168a45; }
        .danger-btn { background: #d93025; color: white; }
        .small-btn { padding: 7px 10px; font-size: 12px; }

        .panel {
            background: #fff; border-radius: 16px; border: 1px solid #ebf0ec; box-shadow: 0 2px 8px rgba(0,0,0,0.02);
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 16px; border-bottom: 1px solid #edf1ee; text-align: left; }
        th { background: #f6faf7; color: #2c3d2f; font-size: 13px; text-transform: uppercase; }
        td { font-size: 14px; }
        .status-pill {
            display: inline-block; padding: 5px 10px; border-radius: 999px; font-size: 12px; font-weight: bold;
        }
        .status-approved { background: #dff5e6; color: #1b8c4a; }
        .status-pending { background: #fff4d9; color: #a86c00; }
        .status-blocked { background: #fde3e1; color: #c62828; }
        .status-rejected { background: #f5d7d7; color: #9c1e1e; }

        .availability-pill {
            display: inline-block; padding: 5px 10px; border-radius: 999px; font-size: 12px; font-weight: bold;
        }
        .availability-available { background: #dff5e6; color: #1b8c4a; }
        .availability-busy { background: #fff1d9; color: #af6900; }
        .availability-offline { background: #eceff1; color: #58656d; }

        .action-group { display: flex; flex-wrap: wrap; gap: 8px; }
        .action-group a {
            display: inline-block; font-size: 12px; padding: 7px 10px; border-radius: 7px; font-weight: bold; color: white;
        }
        .view { background: #168a45; }
        .approve { background: #2aa85c; }
        .block { background: #d93025; }
        .unblock { background: #ff8a1c; }
        .delete { background: #8d1b1b; }
        .set {
            background: #f1f4f8;
            color: #111827 !important;
            border: 1px solid #d5dbe4;
            transition: all 0.2s ease;
        }
        .set.active-available {
            background: #3ec76f;
            color: #0a3b22 !important;
            border-color: #2da85c;
            box-shadow: 0 0 0 2px rgba(62, 199, 111, 0.18);
        }
        .set.active-busy {
            background: #f4b942;
            color: #5b3500 !important;
            border-color: #d9981e;
            box-shadow: 0 0 0 2px rgba(244, 185, 66, 0.18);
        }
        .set.active-offline {
            background: #aeb8c2;
            color: #1e293b !important;
            border-color: #7f8a99;
            box-shadow: 0 0 0 2px rgba(174, 184, 194, 0.18);
        }

        .empty-state {
            text-align: center; padding: 42px 20px; color: #687a6b; background: #fff; border-radius: 17px;
            border: 1px solid #ebf0ec;
        }
        .empty-state h3 { color: #2b3a2d; margin-bottom: 8px; }

        .form-card {
            background: #fff; border: 1px solid #ebf0ec; border-radius: 16px; padding: 22px; margin-top: 28px;
        }
        .form-title { font-size: 22px; color: #168a45; margin: 0 0 18px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; }
        .field { display: flex; flex-direction: column; gap: 8px; }
        .field label { font-size: 13px; font-weight: bold; color: #3c4d3f; }
        .field input { padding: 12px 14px; border: 1px solid #dfe7e1; border-radius: 8px; }
        .form-actions { margin-top: 20px; }
        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { display: none; }
            .statsgrid { grid-template-columns: repeat(2, minmax(140px, 1fr)); }
            .toolbar { flex-direction: column; align-items: stretch; }
            .search-wrap { width: 100%; }
            th, td { padding: 10px; }
        }
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
            <a href="delivery_management.php" class="active">🚚 Delivery Staff</a>
            <a href="item_management.php">📦 Items</a>
            <a href="request_management.php">📋 Requests</a>
        </aside>

        <main class="content">
            <h1 class="page-title">🚚 Delivery Staff Management</h1>
            <p class="page-subtitle">Manage delivery staff accounts, approval, blocking, and availability.</p>

            <div class="statsgrid">
                <div class="stat-card"><h3>Total Staff</h3><div class="value"><?php echo $totalStaff; ?></div></div>
                <div class="stat-card"><h3>Pending</h3><div class="value pending"><?php echo $pendingStaff; ?></div></div>
                <div class="stat-card"><h3>Approved</h3><div class="value"><?php echo $approvedStaff; ?></div></div>
                <div class="stat-card"><h3>Blocked</h3><div class="value blocked"><?php echo $blockedStaff; ?></div></div>
                <div class="stat-card"><h3>Available</h3><div class="value available"><?php echo $availableStaff; ?></div></div>
                <div class="stat-card"><h3>Busy</h3><div class="value busy"><?php echo $busyStaff; ?></div></div>
            </div>

            <div class="toolbar">
                <form method="GET" class="search-wrap" style="flex: 1;">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, phone, or location...">
                    <button type="submit" class="primary-btn">Search</button>
                    <?php if ($search !== '' || $statusFilter !== ''): ?>
                        <a href="delivery_management.php" class="secondary-btn" style="display:inline-block; padding: 10px 18px; border-radius: 8px;">Clear</a>
                    <?php endif; ?>
                </form>

                <form method="GET" class="search-wrap" style="max-width: 220px;">
                    <select name="status" onchange="this.form.submit()">
                        <option value="All" <?php echo ($statusFilter === '' || $statusFilter === 'All') ? 'selected' : ''; ?>>All Status</option>
                        <option value="Pending" <?php echo ($statusFilter === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo ($statusFilter === 'Approved') ? 'selected' : ''; ?>>Approved</option>
                        <option value="Blocked" <?php echo ($statusFilter === 'Blocked') ? 'selected' : ''; ?>>Blocked</option>
                    </select>
                </form>
            </div>

            <?php if (!empty($staffList)): ?>
                <div class="panel">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Availability</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staffList as $staff): ?>
                                <?php
                                    $statusText = normalizeDeliveryStatus($staff['verification_status']);
                                    $availability = trim((string)($staff['availability'] ?? 'Offline'));
                                    $availabilityValue = $availability === '' ? 'Offline' : $availability;
                                    $availabilityDisplay = strtoupper($availabilityValue);
                                    $isBlocked = (int)($staff['is_blocked'] ?? 0) === 1 || $statusText === 'Blocked';
                                ?>
                                <tr>
                                    <td>#<?php echo (int)$staff['delivery_id']; ?></td>
                                    <td><?php echo htmlspecialchars($staff['name']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($staff['location']); ?></td>
                                    <td>
                                        <span class="status-pill <?php echo strtolower(str_replace(' ', '-', $statusText)); ?>">
                                            <?php echo htmlspecialchars($statusText); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="availability-pill availability-<?php echo strtolower($availabilityDisplay); ?>">
                                            <?php echo htmlspecialchars($availabilityDisplay); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="delivery_management_action.php?action=view&id=<?php echo (int)$staff['delivery_id']; ?>" class="view">View</a>
                                            <?php if ($statusText === 'Pending'): ?>
                                                <a href="delivery_management_action.php?action=approve&id=<?php echo (int)$staff['delivery_id']; ?>" class="approve" onclick="return confirm('Approve this delivery staff?');">Approve</a>
                                            <?php endif; ?>
                                            <?php if ($statusText === 'Approved' && !$isBlocked): ?>
                                                <a href="delivery_management_action.php?action=block&id=<?php echo (int)$staff['delivery_id']; ?>" class="block" onclick="return confirm('Block this delivery staff?');">Block</a>
                                            <?php endif; ?>
                                            <?php if ($isBlocked): ?>
                                                <a href="delivery_management_action.php?action=unblock&id=<?php echo (int)$staff['delivery_id']; ?>" class="unblock" onclick="return confirm('Unblock this delivery staff?');">Unblock</a>
                                            <?php endif; ?>
                                            <a href="delivery_management_action.php?action=set_availability&id=<?php echo (int)$staff['delivery_id']; ?>&value=Available" class="set <?php echo $availabilityValue === 'Available' ? 'active-available' : ''; ?>">Available</a>
                                            <a href="delivery_management_action.php?action=set_availability&id=<?php echo (int)$staff['delivery_id']; ?>&value=Busy" class="set <?php echo $availabilityValue === 'Busy' ? 'active-busy' : ''; ?>">Busy</a>
                                            <a href="delivery_management_action.php?action=set_availability&id=<?php echo (int)$staff['delivery_id']; ?>&value=Offline" class="set <?php echo $availabilityValue === 'Offline' ? 'active-offline' : ''; ?>">Offline</a>
                                            <a href="delivery_management_action.php?action=delete&id=<?php echo (int)$staff['delivery_id']; ?>" class="delete" onclick="return confirm('Delete this delivery staff?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Delivery Staff Found</h3>
                    <p>There are no delivery staff matching your search criteria.</p>
                </div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <div style="display:flex; justify-content:center; gap:10px; margin-top: 22px; flex-wrap: wrap;">
                    <?php if ($page > 1): ?>
                        <a href="delivery_management.php?page=1<?php if ($search !== '') echo '&search=' . urlencode($search); if ($statusFilter !== '') echo '&status=' . urlencode($statusFilter); ?>" class="secondary-btn">First</a>
                        <a href="delivery_management.php?page=<?php echo $page - 1; if ($search !== '') echo '&search=' . urlencode($search); if ($statusFilter !== '') echo '&status=' . urlencode($statusFilter); ?>" class="secondary-btn">Prev</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="secondary-btn" style="background:#168a45;color:white;border:none;"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="delivery_management.php?page=<?php echo $i; if ($search !== '') echo '&search=' . urlencode($search); if ($statusFilter !== '') echo '&status=' . urlencode($statusFilter); ?>" class="secondary-btn"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="delivery_management.php?page=<?php echo $page + 1; if ($search !== '') echo '&search=' . urlencode($search); if ($statusFilter !== '') echo '&status=' . urlencode($statusFilter); ?>" class="secondary-btn">Next</a>
                        <a href="delivery_management.php?page=<?php echo $totalPages; if ($search !== '') echo '&search=' . urlencode($search); if ($statusFilter !== '') echo '&status=' . urlencode($statusFilter); ?>" class="secondary-btn">Last</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <h2 class="form-title">➕ Add Delivery Staff</h2>
                <form method="POST" action="delivery_management_action.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="form-grid">
                        <div class="field">
                            <label>Name</label>
                            <input type="text" name="name" placeholder="Enter full name" required>
                        </div>
                        <div class="field">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="Enter email" required>
                        </div>
                        <div class="field">
                            <label>Password</label>
                            <input type="password" name="password" placeholder="Create password" required>
                        </div>
                        <div class="field">
                            <label>Phone</label>
                            <input type="text" name="phone" placeholder="Enter phone number" required>
                        </div>
                        <div class="field">
                            <label>Location</label>
                            <input type="text" name="location" placeholder="Enter location" required>
                        </div>
                        <div class="field">
                            <label>Driving Licence Image</label>
                            <input type="file" name="verification_document" accept=".pdf,.jpg,.jpeg,.png" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="primary-btn">Add Staff</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
