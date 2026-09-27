<?php

session_start();
include("db.php");

// SECURITY CHECK
if(!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

// GET STATS
$totalResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$totalUsers = mysqli_fetch_assoc($totalResult)['count'];

// SEARCH
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchSafe = mysqli_real_escape_string($conn, $search);

// PAGINATION
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// QUERY
if($search != '') {
    $sql = "SELECT * FROM users WHERE name LIKE '%$searchSafe%' OR email LIKE '%$searchSafe%' OR phone LIKE '%$searchSafe%' ORDER BY user_id DESC LIMIT $offset, $limit";
    $countSql = "SELECT COUNT(*) as total FROM users WHERE name LIKE '%$searchSafe%' OR email LIKE '%$searchSafe%' OR phone LIKE '%$searchSafe%'";
} else {
    $sql = "SELECT * FROM users ORDER BY user_id DESC LIMIT $offset, $limit";
    $countSql = "SELECT COUNT(*) as total FROM users";
}

$result = mysqli_query($conn, $sql);
$countResult = mysqli_query($conn, $countSql);
$totalCount = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalCount / $limit);

?>
<!DOCTYPE html>
<html>
<head>
    <title>User Management - Admin</title>
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

.navbar {
    height: 70px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 7%;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.logo {
    font-size: 25px;
    font-weight: bold;
    color: #168a45;
}

.logo span {
    color: #222;
}

.navbar a {
    color: #168a45;
    text-decoration: none;
    font-weight: bold;
}

.layout {
    display: flex;
    min-height: calc(100vh - 70px);
}

.sidebar {
    width: 240px;
    background: white;
    padding: 25px 15px;
    box-shadow: 2px 0 10px rgba(0,0,0,0.05);
}

.sidebar-title {
    font-size: 13px;
    color: #888;
    margin-bottom: 15px;
    padding-left: 12px;
}

.menu-item {
    display: block;
    text-decoration: none;
    color: #444;
    padding: 13px 12px;
    border-radius: 8px;
    margin-bottom: 6px;
    font-size: 14px;
}

.menu-item:hover {
    background: #eaf7ef;
    color: #168a45;
}

.menu-item.active {
    background: #168a45;
    color: white;
}

.main {
    flex: 1;
    padding: 35px;
}

.page-title {
    font-size: 28px;
    margin-bottom: 8px;
    color: #168a45;
}

.page-subtitle {
    color: #777;
    margin-bottom: 30px;
}

.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 18px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 22px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.06);
    border-left: 4px solid #168a45;
}

.stat-label {
    color: #777;
    font-size: 13px;
    margin-bottom: 8px;
}

.stat-number {
    font-size: 28px;
    color: #168a45;
    font-weight: bold;
}

.search-box {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.06);
    margin-bottom: 25px;
    display: flex;
    gap: 10px;
}

.search-box input {
    flex: 1;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
}

.search-box input:focus {
    outline: none;
    border-color: #168a45;
}

.search-box button {
    padding: 12px 25px;
    background: #168a45;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
}

.search-box button:hover {
    background: #117239;
}

.table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 3px 12px rgba(0,0,0,0.06);
    overflow: hidden;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #168a45;
    color: white;
    padding: 15px;
    text-align: left;
    font-size: 14px;
    font-weight: bold;
}

td {
    padding: 15px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
}

tr:hover {
    background: #f9f9f9;
}

.actions {
    display: flex;
    gap: 8px;
}

.btn {
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
}

.btn-edit {
    background: #4CAF50;
    color: white;
}

.btn-edit:hover {
    background: #45a049;
}

.btn-delete {
    background: #f44336;
    color: white;
}

.btn-delete:hover {
    background: #da190b;
}

.pagination {
    margin-top: 25px;
    display: flex;
    justify-content: center;
    gap: 8px;
}

.pagination a, .pagination span {
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: #168a45;
}

.pagination a:hover {
    background: #eaf7ef;
}

.pagination .active {
    background: #168a45;
    color: white;
    border-color: #168a45;
}

@media(max-width: 1000px) {
    .layout {
        flex-direction: column;
    }
    .sidebar {
        width: 100%;
        display: flex;
        gap: 20px;
        overflow-x: auto;
    }
}

    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">Eco<span>Cycle</span></div>
    <a href="logout.php">Logout</a>
</nav>

<div class="layout">
    <div class="sidebar">
        <a href="admin_dashboard.php" class="menu-item">📊 Dashboard</a>
        <a href="user_management.php" class="menu-item active">👥 Users</a>
        <a href="ngo_management.php" class="menu-item">🏢 NGOs</a>
        <a href="item_management.php" class="menu-item">📦 Items</a>
        <a href="request_management.php" class="menu-item">📋 Requests</a>
    </div>

    <div class="main">
        <h1 class="page-title">👥 User Management</h1>
        <p class="page-subtitle">Manage all users in the system</p>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-label">Total Users</div>
                <div class="stat-number"><?php echo $totalUsers; ?></div>
            </div>
        </div>

        <form class="search-box" method="GET">
            <input type="text" name="search" placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">🔍 Search</button>
            <?php if($search): ?>
                <a href="user_management.php" style="padding: 12px 15px; background: #ddd; color: #333; border-radius: 8px; text-decoration: none;">Clear</a>
            <?php endif; ?>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if(mysqli_num_rows($result) > 0) {
                        while($user = mysqli_fetch_assoc($result)) {
                            echo "
                            <tr>
                                <td>#" . $user['user_id'] . "</td>
                                <td>" . htmlspecialchars($user['name']) . "</td>
                                <td>" . htmlspecialchars($user['email']) . "</td>
                                <td>" . htmlspecialchars($user['phone']) . "</td>
                                <td>" . htmlspecialchars($user['location']) . "</td>
                                <td>
                                    <div class='actions'>
                                        <a href='user_management_action.php?action=edit&id=" . $user['user_id'] . "' class='btn btn-edit'>Edit</a>
                                        <a href='user_management_action.php?action=delete&id=" . $user['user_id'] . "' class='btn btn-delete' onclick='return confirm(\"Delete this user?\");'>Delete</a>
                                    </div>
                                </td>
                            </tr>
                            ";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align: center; color: #999;'>No users found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php
            if($page > 1) {
                echo "<a href='user_management.php?page=1" . ($search ? "&search=" . urlencode($search) : "") . "'>« First</a>";
                echo "<a href='user_management.php?page=" . ($page - 1) . ($search ? "&search=" . urlencode($search) : "") . "'>‹ Prev</a>";
            }
            
            for($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) {
                if($i == $page) {
                    echo "<span class='active'>$i</span>";
                } else {
                    echo "<a href='user_management.php?page=$i" . ($search ? "&search=" . urlencode($search) : "") . "'>$i</a>";
                }
            }
            
            if($page < $totalPages) {
                echo "<a href='user_management.php?page=" . ($page + 1) . ($search ? "&search=" . urlencode($search) : "") . "'>Next ›</a>";
                echo "<a href='user_management.php?page=$totalPages" . ($search ? "&search=" . urlencode($search) : "") . "'>Last »</a>";
            }
            ?>
        </div>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
