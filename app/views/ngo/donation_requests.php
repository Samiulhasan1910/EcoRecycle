<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Requests | NGO</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7f6;
            color: #1f2937;
        }
        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .header h1 {
            margin: 0;
            font-size: 30px;
        }
        .badge {
            background: #dff3e5;
            color: #146c43;
            padding: 8px 14px;
            border-radius: 999px;
            font-weight: bold;
            font-size: 14px;
        }
        .table-wrap {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            min-height: 360px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 19px 22px;
            text-align: left;
            border-bottom: 1px solid #eef2f7;
        }
        .action-btn {
            background: #0f766e;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
            cursor: pointer;
        }
        th {
            background: #f9fafb;
            color: #374151;
            font-size: 14px;
        }
        td {
            font-size: 15px;
        }
        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }
        .pending {
            background: #fff3cd;
            color: #9a6700;
        }
        .accepted {
            background: #d1fae5;
            color: #065f46;
        }
        .completed {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .available {
            background: #e0f2fe;
            color: #075985;
        }
        .empty {
            padding: 30px;
            text-align: center;
            color: #6b7280;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #0f766e;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
    <link rel="stylesheet" href="assets/css/ngo_sidebar.css">
</head>
<body>
    <?php require APP_PATH . '/views/layouts/ngo_sidebar.php'; ?>
    <div class="container ngo-content">
        <a href="index.php?url=ngo/dashboard" class="back-link">← Back to Dashboard</a>

        <div class="header">
            <h1>Donation Requests</h1>
        </div>

        <div class="table-wrap">
            <?php if (count($result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($result as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row["title"] ?? ""); ?></td>
                                <td><?php echo htmlspecialchars($row["category"] ?? ""); ?></td>
                                <td><?php echo htmlspecialchars($row["price"] ?? ""); ?></td>
                                <td><?php echo htmlspecialchars($row["location"] ?? ""); ?></td>
                                <td>
                                    <?php
                                        $status = strtolower($row["status"] ?? "");
                                        $class = "available";
                                        if ($status === "pending") $class = "pending";
                                        elseif ($status === "accepted") $class = "accepted";
                                        elseif ($status === "completed") $class = "completed";
                                    ?>
                                    <span class="status <?php echo $class; ?>">
                                        <?php echo htmlspecialchars($row["status"] ?? "Available"); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($status !== "accepted" && $status !== "completed"): ?>
                                        <a href="index.php?url=ngo/donation-requests&accept_id=<?php echo (int)($row["item_id"] ?? 0); ?>" class="action-btn" onclick="return confirm('Accept this donation request?');">Accept</a>
                                    <?php else: ?>
                                        <span style="color:#6b7280; font-size:13px;">Done</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty">
                    No donation requests found for this NGO.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
