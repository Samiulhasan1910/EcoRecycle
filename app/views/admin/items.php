<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item / Post Management</title>
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
            display: grid; grid-template-columns: repeat(7, minmax(120px, 1fr)); gap: 16px; margin-bottom: 28px;
        }
        .stat-card {
            background: #fff; border-radius: 16px; padding: 18px 16px; min-height: 110px; border: 1px solid #ebf0ec;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .stat-card h3 { font-size: 11px; letter-spacing: 0.06em; color: #6d7c70; text-transform: uppercase; margin: 0 0 12px; }
        .stat-card .value { font-size: 26px; font-weight: bold; color: #168a45; }
        .stat-card .value.pending { color: #d99b00; }
        .stat-card .value.approved { color: #1d9a60; }
        .stat-card .value.available { color: #1b8c4a; }
        .stat-card .value.requested { color: #3b82f6; }
        .stat-card .value.collected { color: #8b5cf6; }
        .stat-card .value.completed { color: #0f766e; }
        .stat-card .value.rejected { color: #d93025; }

        .toolbar {
            display: flex; justify-content: space-between; align-items: center; gap: 16px; margin: 24px 0 18px; flex-wrap: wrap;
        }
        .search-wrap { display: flex; align-items: center; gap: 12px; flex: 1; min-width: 260px; }
        .search-wrap input, .search-wrap select { padding: 11px 14px; border-radius: 8px; border: 1px solid #dfe7e1; font-size: 14px; }
        .search-wrap input { flex: 1; }
        .primary-btn, .secondary-btn {
            padding: 10px 18px; border-radius: 8px; border: none; font-weight: bold; font-size: 14px;
        }
        .primary-btn { background: #168a45; color: white; }
        .secondary-btn { background: #e8f7ee; color: #168a45; }

        .panel {
            background: #fff; border-radius: 16px; border: 1px solid #ebf0ec; box-shadow: 0 2px 8px rgba(0,0,0,0.02); overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 16px; border-bottom: 1px solid #edf1ee; text-align: left; }
        th { background: #f6faf7; color: #2c3d2f; font-size: 13px; text-transform: uppercase; }
        td { font-size: 14px; }
        .status-pill {
            display: inline-block; padding: 5px 10px; border-radius: 999px; font-size: 12px; font-weight: bold;
        }
        .status-pending { background: #fff4d9; color: #a86c00; }
        .status-approved { background: #dff5e6; color: #1b8c4a; }
        .status-available { background: #dff5e6; color: #1b8c4a; }
        .status-requested { background: #dfe8ff; color: #2451c6; }
        .status-collected { background: #f0e7ff; color: #6d42c8; }
        .status-completed { background: #d7f7f3; color: #0f766e; }
        .status-rejected { background: #fde3e1; color: #c62828; }

        .action-group { display: flex; flex-wrap: wrap; gap: 8px; }
        .action-group a {
            display: inline-block; font-size: 12px; padding: 7px 10px; border-radius: 7px; font-weight: bold; color: white;
        }
        .view { background: #168a45; }
        .approve { background: #2aa85c; }
        .reject { background: #d93025; }
        .mark { background: #4f46e5; }
        .delete { background: #8d1b1b; }

        .empty-state {
            text-align: center; padding: 42px 20px; color: #687a6b; background: #fff; border-radius: 17px; border: 1px solid #ebf0ec;
        }
        .empty-state h3 { color: #2b3a2d; margin-bottom: 8px; }

        .pagination {
            display: flex; justify-content: center; gap: 10px; margin-top: 22px; flex-wrap: wrap;
        }

        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar { display: none; }
            .statsgrid { grid-template-columns: repeat(2, minmax(120px, 1fr)); }
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
            <a href="index.php?url=admin/dashboard">Admin Dashboard</a>
            <a href="index.php?url=auth/logout">Logout</a>
        </div>
    </div>

    <div class="layout">
        <aside class="sidebar">
            <a href="index.php?url=admin/dashboard">📊 Dashboard</a>
            <a href="index.php?url=admin/users">👥 Users</a>
            <a href="index.php?url=admin/ngos">🏢 NGOs</a>
            <a href="index.php?url=admin/delivery-men">🚚 Delivery Staff</a>
            <a href="index.php?url=admin/items" class="active">📦 Item / Post Management</a>
            <a href="index.php?url=admin/requests">📋 Requests</a>
        </aside>

        <main class="content">
            <h1 class="page-title">📦 Item / Post Management</h1>
            <p class="page-subtitle">Manage all listings, moderation, status updates, and removal of invalid posts.</p>

            <div class="statsgrid">
                <div class="stat-card"><h3>Total</h3><div class="value"><?php echo $totalItems; ?></div></div>
                <div class="stat-card"><h3>Pending</h3><div class="value pending"><?php echo $pendingItems; ?></div></div>
                <div class="stat-card"><h3>Approved</h3><div class="value approved"><?php echo $approvedItems; ?></div></div>
                <div class="stat-card"><h3>Available</h3><div class="value available"><?php echo $availableItems; ?></div></div>
                <div class="stat-card"><h3>Requested</h3><div class="value requested"><?php echo $requestedItems; ?></div></div>
                <div class="stat-card"><h3>Collected</h3><div class="value collected"><?php echo $collectedItems; ?></div></div>
                <div class="stat-card"><h3>Completed</h3><div class="value completed"><?php echo $completedItems; ?></div></div>
            </div>

            <div class="toolbar">
                <form method="GET" class="search-wrap" style="flex: 1;"><input type="hidden" name="url" value="admin/items">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by item name, user, category or location...">
                    <button type="submit" class="primary-btn">Search</button>
                    <?php if ($search !== '' || $categoryFilter !== '' || $statusFilter !== ''): ?>
                        <a href="index.php?url=admin/items" class="secondary-btn" style="display:inline-block; padding: 10px 18px; border-radius: 8px;">Clear</a>
                    <?php endif; ?>
                </form>

                <form method="GET" class="search-wrap" style="max-width: 240px;"><input type="hidden" name="url" value="admin/items">
                    <select name="category" onchange="this.form.submit()">
                        <option value="All" <?php echo ($categoryFilter === '' || $categoryFilter === 'All') ? 'selected' : ''; ?>>All Categories</option>
                        <option value="Electronics" <?php echo $categoryFilter === 'Electronics' ? 'selected' : ''; ?>>Electronics</option>
                        <option value="Books" <?php echo $categoryFilter === 'Books' ? 'selected' : ''; ?>>Books</option>
                        <option value="Clothes" <?php echo $categoryFilter === 'Clothes' ? 'selected' : ''; ?>>Clothes</option>
                        <option value="Furniture" <?php echo $categoryFilter === 'Furniture' ? 'selected' : ''; ?>>Furniture</option>
                        <option value="Plastic" <?php echo $categoryFilter === 'Plastic' ? 'selected' : ''; ?>>Plastic</option>
                        <option value="Paper" <?php echo $categoryFilter === 'Paper' ? 'selected' : ''; ?>>Paper</option>
                        <option value="Others" <?php echo $categoryFilter === 'Others' ? 'selected' : ''; ?>>Others</option>
                    </select>
                </form>

                <form method="GET" class="search-wrap" style="max-width: 220px;"><input type="hidden" name="url" value="admin/items">
                    <select name="status" onchange="this.form.submit()">
                        <option value="All" <?php echo ($statusFilter === '' || $statusFilter === 'All') ? 'selected' : ''; ?>>All Status</option>
                        <option value="Pending" <?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Approved" <?php echo $statusFilter === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="Available" <?php echo $statusFilter === 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Requested" <?php echo $statusFilter === 'Requested' ? 'selected' : ''; ?>>Requested</option>
                        <option value="Collected" <?php echo $statusFilter === 'Collected' ? 'selected' : ''; ?>>Collected</option>
                        <option value="Completed" <?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Rejected" <?php echo $statusFilter === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </form>
            </div>

            <?php if (!empty($items)): ?>
                <div class="panel">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Item</th>
                                <th>Category</th>
                                <th>User</th>
                                <th>Location</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <?php
                                    $statusText = status_or_pending($item['status']);
                                    $statusClass = strtolower(str_replace(' ', '-', $statusText));
                                    $imageFile = trim((string) ($item['image'] ?? ''));
                                    $imageSrc = $imageFile !== '' ? 'uploads/' . $imageFile : '';
                                ?>
                                <tr>
                                    <td>#<?php echo (int) $item['item_id']; ?></td>
                                    <td>
                                        <?php if ($imageFile !== '' && file_exists($imageSrc)): ?>
                                            <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="Item image" style="width: 58px; height: 58px; object-fit: cover; border-radius: 10px; border: 1px solid #dfe7e1;">
                                        <?php else: ?>
                                            <span style="display:inline-block; width:58px; height:58px; border-radius:10px; background:#f1f5f2; border:1px dashed #c5d1c7; color:#73857a; font-size:11px; display:flex; align-items:center; justify-content:center; text-align:center;">No Image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                                    <td><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td><?php echo htmlspecialchars($item['user_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($item['location']); ?></td>
                                    <td><?php echo htmlspecialchars($item['price']); ?></td>
                                    <td>
                                        <span class="status-pill status-<?php echo $statusClass; ?>"><?php echo htmlspecialchars($statusText); ?></span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <a href="index.php?url=admin/items-action&action=view&id=<?php echo (int) $item['item_id']; ?>" class="view">View</a>
                                            <?php if ($statusText !== 'Approved' && $statusText !== 'Available' && $statusText !== 'Requested' && $statusText !== 'Collected' && $statusText !== 'Completed'): ?>
                                                <a href="index.php?url=admin/items-action&action=approve&id=<?php echo (int) $item['item_id']; ?>" class="approve" onclick="return confirm('Approve this post?');">Approve</a>
                                            <?php endif; ?>
                                            <?php if ($statusText !== 'Rejected'): ?>
                                                <a href="index.php?url=admin/items-action&action=reject&id=<?php echo (int) $item['item_id']; ?>" class="reject" onclick="return confirm('Reject this post?');">Reject</a>
                                            <?php endif; ?>
                                            <?php if ($statusText !== 'Available'): ?>
                                                <a href="index.php?url=admin/items-action&action=set_status&id=<?php echo (int) $item['item_id']; ?>&value=Available" class="mark">Available</a>
                                            <?php endif; ?>
                                            <?php if ($statusText !== 'Requested'): ?>
                                                <a href="index.php?url=admin/items-action&action=set_status&id=<?php echo (int) $item['item_id']; ?>&value=Requested" class="mark">Requested</a>
                                            <?php endif; ?>
                                            <?php if ($statusText !== 'Collected'): ?>
                                                <a href="index.php?url=admin/items-action&action=set_status&id=<?php echo (int) $item['item_id']; ?>&value=Collected" class="mark">Collected</a>
                                            <?php endif; ?>
                                            <?php if ($statusText !== 'Completed'): ?>
                                                <a href="index.php?url=admin/items-action&action=set_status&id=<?php echo (int) $item['item_id']; ?>&value=Completed" class="mark">Completed</a>
                                            <?php endif; ?>
                                            <a href="index.php?url=admin/items-action&action=delete&id=<?php echo (int) $item['item_id']; ?>" class="delete" onclick="return confirm('Delete this post?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Posts Found</h3>
                    <p>No item posts match your current search or filter settings.</p>
                </div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="index.php?url=admin/items&page=1<?php if ($search !== '') echo '&search=' . urlencode($search); if ($categoryFilter !== '') echo '&category=' . urlencode($categoryFilter); if ($statusFilter !== '') echo '&status=' . urlencode($statusFilter); ?>" class="secondary-btn">First</a>
                        <a href="index.php?url=admin/items&page=<?php echo $page - 1; if ($search !== '') echo '&search=' . urlencode($search); if ($categoryFilter !== '') echo '&category=' . urlencode($categoryFilter); if ($statusFilter !== '') echo '&status=' . urlencode($statusFilter); ?>" class="secondary-btn">Prev</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i === $page): ?>
                            <span class="secondary-btn" style="background:#168a45;color:white;border:none;"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="index.php?url=admin/items&page=<?php echo $i; if ($search !== '') echo '&search=' . urlencode($search); if ($categoryFilter !== '') echo '&category=' . urlencode($categoryFilter); if ($statusFilter !== '') echo '&status=' . urlencode($statusFilter); ?>" class="secondary-btn"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="index.php?url=admin/items&page=<?php echo $page + 1; if ($search !== '') echo '&search=' . urlencode($search); if ($categoryFilter !== '') echo '&category=' . urlencode($categoryFilter); if ($statusFilter !== '') echo '&status=' . urlencode($statusFilter); ?>" class="secondary-btn">Next</a>
                        <a href="index.php?url=admin/items&page=<?php echo $totalPages; if ($search !== '') echo '&search=' . urlencode($search); if ($categoryFilter !== '') echo '&category=' . urlencode($categoryFilter); if ($statusFilter !== '') echo '&status=' . urlencode($statusFilter); ?>" class="secondary-btn">Last</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
