<?php

session_start();
include 'db.php';

// Security check
if ($_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

// Helper function to count NGOs by status
function getCount($conn, $query)
{
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        return $row[0];
    }
    return 0;
}

function normalizeNgoStatus($status)
{
    $status = trim((string) $status);
    return $status !== "" ? $status : "Pending";
}

// Get totals
$totalNGOs = getCount(
    $conn,
    "SELECT COUNT(*) FROM ngos"
);

$pendingNGOs = getCount(
    $conn,
    "SELECT COUNT(*) FROM ngos WHERE verification_status = 'Pending' OR verification_status = ''"
);

$approvedNGOs = getCount(
    $conn,
    "SELECT COUNT(*) FROM ngos WHERE verification_status = 'Approved'"
);

$rejectedNGOs = getCount(
    $conn,
    "SELECT COUNT(*) FROM ngos WHERE verification_status = 'Rejected'"
);

// Search functionality
$search = "";
$searchQuery = "";
if (isset($_GET["search"]) && !empty($_GET["search"])) {
    $search = htmlspecialchars($_GET["search"]);
    $searchQuery = " WHERE (name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'
                     OR email LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'
                     OR phone LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
}

// Status filter
$statusFilter = "";
if (isset($_GET["status"]) && !empty($_GET["status"]) && in_array($_GET["status"], ["Pending", "Approved", "Rejected"])) {
    $status = mysqli_real_escape_string($conn, $_GET["status"]);
    if ($status === "Pending") {
        $statusCondition = " (verification_status = 'Pending' OR verification_status = '') ";
    } else {
        $statusCondition = " verification_status = '" . $status . "' ";
    }

    if ($searchQuery) {
        $statusFilter = " AND " . $statusCondition;
    } else {
        $statusFilter = " WHERE " . $statusCondition;
    }
}

// Pagination
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
if ($page < 1) $page = 1;
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Count total records
$countResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) FROM ngos" . $searchQuery . $statusFilter
);
$totalRecords = 0;
if ($countResult) {
    $row = mysqli_fetch_array($countResult);
    $totalRecords = $row[0];
}
$totalPages = ceil($totalRecords / $itemsPerPage);

// Fetch NGOs
$query = "SELECT ngo_id, name, email, phone, location, organization_type, 
                 established_year, verification_status, verification_document 
                 FROM ngos" . $searchQuery . $statusFilter . 
          " ORDER BY ngo_id DESC LIMIT " . $offset . ", " . $itemsPerPage;

$result = mysqli_query($conn, $query);
$ngos = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $ngos[] = $row;
    }
}

?>

<!DOCTYPE html>
<html>

<head>

    <title>NGO Management - Admin Dashboard</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f4f8f5;
        }

        /* NAVBAR */

        .navbar {
            height: 70px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 7%;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .logo {
            font-size: 25px;
            font-weight: bold;
            color: #168a45;
        }

        .logo span {
            color: #222;
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .navbar a {
            color: #168a45;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }

        /* MAIN CONTAINER */

        .container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: calc(100vh - 70px);
        }

        /* SIDEBAR */

        .sidebar {
            background: white;
            padding: 30px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }

        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }

        .sidebar a:hover {
            background: #f0f0f0;
            border-left-color: #168a45;
            color: #168a45;
        }

        .sidebar a.active {
            background: #e8f7f0;
            border-left-color: #168a45;
            color: #168a45;
            font-weight: bold;
        }

        /* CONTENT */

        .content {
            padding: 40px;
            background: #f4f8f5;
        }

        .header {
            margin-bottom: 30px;
        }

        .header h1 {
            color: #168a45;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .header p {
            color: #777;
            font-size: 14px;
        }

        /* STATS */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #168a45;
        }

        .stat-card h3 {
            color: #777;
            font-size: 13px;
            font-weight: normal;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .stat-card .number {
            color: #168a45;
            font-size: 28px;
            font-weight: bold;
        }

        /* CONTROLS */

        .controls {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            display: flex;
            gap: 10px;
        }

        .search-box input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .search-box button {
            padding: 10px 20px;
            background: #168a45;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: background 0.3s;
        }

        .search-box button:hover {
            background: #116d34;
        }

        .filter-box {
            display: flex;
            gap: 10px;
        }

        .filter-box select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }

        /* TABLE */

        .table-wrapper {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f8f8;
            padding: 15px;
            text-align: left;
            font-weight: bold;
            color: #333;
            font-size: 13px;
            border-bottom: 1px solid #eee;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        tr:hover {
            background: #f8f8f8;
        }

        .status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status.approved {
            background: #d4edda;
            color: #155724;
        }

        .status.rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view {
            background: #168a45;
            color: white;
        }

        .btn-view:hover {
            background: #116d34;
        }

        .btn-approve {
            background: #28a745;
            color: white;
        }

        .btn-approve:hover {
            background: #218838;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
        }

        .btn-reject:hover {
            background: #c82333;
        }

        .btn-download {
            background: #007bff;
            color: white;
        }

        .btn-download:hover {
            background: #0056b3;
        }

        /* PAGINATION */

        .pagination {
            margin-top: 25px;
            display: flex;
            justify-content: center;
            gap: 5px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #168a45;
            font-size: 13px;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: #168a45;
            color: white;
        }

        .pagination .active {
            background: #168a45;
            color: white;
            border-color: #168a45;
        }

        .pagination .disabled {
            color: #999;
            cursor: not-allowed;
        }

        /* NO DATA */

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .no-data h3 {
            color: #777;
            margin-bottom: 10px;
        }

        /* MESSAGES */

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid;
        }

        .alert.success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }

        .alert.error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .controls {
                flex-direction: column;
            }

            .search-box {
                min-width: 100%;
            }

            table {
                font-size: 12px;
            }

            td, th {
                padding: 10px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

    </style>

</head>

<body>

    <!-- NAVBAR -->

    <div class="navbar">

        <div class="logo">
            Eco<span>Cycle</span>
        </div>

        <div class="navbar-right">
            <span><?php echo ucfirst($_SESSION["role"]); ?> Dashboard</span>
            <a href="logout.php">Logout</a>
        </div>

    </div>

    <!-- MAIN CONTAINER -->

    <div class="container">

        <!-- SIDEBAR -->

        <div class="sidebar">
            <a href="admin_dashboard.php" class="menu-item">📊 Dashboard</a>
            <a href="user_management.php" class="menu-item">👥 Users</a>
            <a href="ngo_management.php" class="menu-item active">🏢 NGOs</a>
            <a href="item_management.php" class="menu-item">📦 Items</a>
            <a href="request_management.php" class="menu-item">📋 Requests</a>
        </div>

        <!-- CONTENT -->

        <div class="content">

            <!-- HEADER -->

            <div class="header">
                <h1>🏢 NGO Management</h1>
                <p>Manage NGO accounts and verification requests</p>
            </div>

            <!-- MESSAGES -->

            <?php if (isset($_GET["approved"])): ?>
                <div class="alert success">
                    ✓ NGO approved successfully! They can now login.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["rejected"])): ?>
                <div class="alert error">
                    ✗ NGO rejected successfully!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["error"])): ?>
                <div class="alert error">
                    ✗ An error occurred. Please try again.
                </div>
            <?php endif; ?>

            <!-- STATS -->

            <div class="stats">

                <div class="stat-card">
                    <h3>Total NGOs</h3>
                    <div class="number"><?php echo $totalNGOs; ?></div>
                </div>

                <div class="stat-card">
                    <h3>Pending Approval</h3>
                    <div class="number" style="color: #ff9800;"><?php echo $pendingNGOs; ?></div>
                </div>

                <div class="stat-card">
                    <h3>Approved</h3>
                    <div class="number" style="color: #4caf50;"><?php echo $approvedNGOs; ?></div>
                </div>

                <div class="stat-card">
                    <h3>Rejected</h3>
                    <div class="number" style="color: #f44336;"><?php echo $rejectedNGOs; ?></div>
                </div>

            </div>

            <!-- CONTROLS -->

            <div class="controls">

                <div class="search-box">
                    <form method="GET" style="display: flex; gap: 10px; flex: 1;">
                        <input
                            type="text"
                            name="search"
                            placeholder="Search by name, email, or phone..."
                            value="<?php echo $search; ?>"
                        >
                        <?php if (isset($_GET["status"])): ?>
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($_GET["status"]); ?>">
                        <?php endif; ?>
                        <button type="submit">Search</button>
                    </form>
                </div>

                <div class="filter-box">
                    <form method="GET" style="display: flex; gap: 10px;">
                        <select name="status" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="Pending" <?php if (isset($_GET["status"]) && $_GET["status"] == "Pending") echo "selected"; ?>>Pending</option>
                            <option value="Approved" <?php if (isset($_GET["status"]) && $_GET["status"] == "Approved") echo "selected"; ?>>Approved</option>
                            <option value="Rejected" <?php if (isset($_GET["status"]) && $_GET["status"] == "Rejected") echo "selected"; ?>>Rejected</option>
                        </select>
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo $search; ?>">
                        <?php endif; ?>
                    </form>
                </div>

            </div>

            <!-- TABLE -->

            <div class="table-wrapper">

                <?php if (count($ngos) > 0): ?>

                    <table>

                        <thead>

                            <tr>
                                <th>NGO Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Organization Type</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>

                        </thead>

                        <tbody>

                            <?php foreach ($ngos as $ngo): ?>
                                <?php $statusText = normalizeNgoStatus($ngo["verification_status"]); ?>

                                <tr>

                                    <td><?php echo htmlspecialchars($ngo["name"]); ?></td>

                                    <td><?php echo htmlspecialchars($ngo["email"]); ?></td>

                                    <td><?php echo htmlspecialchars($ngo["phone"]); ?></td>

                                    <td><?php echo htmlspecialchars($ngo["organization_type"]); ?></td>

                                    <td><?php echo htmlspecialchars($ngo["location"]); ?></td>

                                    <td>
                                        <span class="status <?php echo strtolower($statusText); ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>

                                    <td>

                                        <div class="actions">

                                            <a href="ngo_management_action.php?action=view&id=<?php echo $ngo["ngo_id"]; ?>" class="btn btn-view">View</a>

                                            <?php if ($statusText == "Pending"): ?>
                                                <a href="ngo_management_action.php?action=approve&id=<?php echo $ngo["ngo_id"]; ?>" class="btn btn-approve" onclick="return confirm('Approve this NGO?');">Approve</a>
                                                <a href="ngo_management_action.php?action=reject&id=<?php echo $ngo["ngo_id"]; ?>" class="btn btn-reject" onclick="return confirm('Reject this NGO?');">Reject</a>
                                            <?php endif; ?>

                                            <?php if (!empty($ngo["verification_document"])): ?>
                                                <a href="ngo_management_action.php?action=download&id=<?php echo $ngo["ngo_id"]; ?>" class="btn btn-download">Document</a>
                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <div class="no-data">
                        <h3>No NGOs Found</h3>
                        <p>There are no NGO accounts matching your search criteria.</p>
                    </div>

                <?php endif; ?>

            </div>

            <!-- PAGINATION -->

            <?php if ($totalPages > 1): ?>

                <div class="pagination">

                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php if (!empty($search)) echo "&search=" . urlencode($search); if (isset($_GET["status"])) echo "&status=" . urlencode($_GET["status"]); ?>">« First</a>
                        <a href="?page=<?php echo $page - 1; if (!empty($search)) echo "&search=" . urlencode($search); if (isset($_GET["status"])) echo "&status=" . urlencode($_GET["status"]); ?>">‹ Prev</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; if (!empty($search)) echo "&search=" . urlencode($search); if (isset($_GET["status"])) echo "&status=" . urlencode($_GET["status"]); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; if (!empty($search)) echo "&search=" . urlencode($search); if (isset($_GET["status"])) echo "&status=" . urlencode($_GET["status"]); ?>">Next ›</a>
                        <a href="?page=<?php echo $totalPages; if (!empty($search)) echo "&search=" . urlencode($search); if (isset($_GET["status"])) echo "&status=" . urlencode($_GET["status"]); ?>">Last »</a>
                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

</body>

</html>
