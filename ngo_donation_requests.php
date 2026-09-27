<?php
session_start();

if (!isset($_SESSION["ngo_id"])) {
    header("Location: login.php");
    exit();
}

include("db.php");

$ngo_id = (int)$_SESSION["ngo_id"];

if (isset($_GET['accept_id'])) {
    $itemId = (int)($_GET['accept_id'] ?? 0);

    if ($itemId > 0) {
        mysqli_begin_transaction($conn);

        $accept_sql = "UPDATE items
                       SET status = 'Accepted', ngo_id = $ngo_id
                       WHERE item_id = $itemId
                       AND ngo_id = $ngo_id
                       AND status = 'Pending'";

        mysqli_query($conn, $accept_sql);

        if (mysqli_affected_rows($conn) === 1) {
            $itemResult = mysqli_query(
                $conn,
                "SELECT user_id FROM items WHERE item_id = $itemId LIMIT 1"
            );
            $item = $itemResult ? mysqli_fetch_assoc($itemResult) : null;

            if ($item && !empty($item['user_id'])) {
                $existingRequest = mysqli_query(
                    $conn,
                    "SELECT request_id FROM requests WHERE item_id = $itemId LIMIT 1"
                );

                $existingRequestRow = $existingRequest ? mysqli_fetch_assoc($existingRequest) : null;
                if ($existingRequestRow) {
                    $requestId = (int)$existingRequestRow['request_id'];
                    mysqli_query($conn, "UPDATE requests SET ngo_id = $ngo_id, request_type = 'Donation', status = 'Accepted', assigned_at = NOW(), updated_at = NOW() WHERE request_id = $requestId");
                } else {
                    mysqli_query(
                        $conn,
                        "INSERT INTO requests
                        (user_id, item_id, ngo_id, request_type, status, created_at, updated_at)
                        VALUES (" . (int)$item['user_id'] . ", $itemId, $ngo_id, 'Donation', 'Accepted', NOW(), NOW())"
                    );

                    $requestId = (int)mysqli_insert_id($conn);
                }

                if ($requestId > 0) {
                    mysqli_query($conn, "UPDATE items SET status = 'Accepted' WHERE item_id = $itemId");
                    require_once __DIR__ . '/delivery_workflow.php';
                    createDeliveryJob($conn, 'requests', $requestId, $itemId, (int)$item['user_id']);
                    mysqli_query($conn, "INSERT INTO notifications (user_id, item_id, request_id, notification_type, message, status) VALUES (" . (int)$item['user_id'] . ", $itemId, $requestId, 'request', 'Your donation has been accepted. The delivery request is open for available delivery men.', 'Unread')");
                }
            }
        }

        mysqli_commit($conn);
    }

    header("Location: ngo_accepted_donations.php");
    exit();
}

$sql = "SELECT *
        FROM items
        WHERE type = 'Donate'
        AND ngo_id = $ngo_id
        AND status = 'Pending'
        ORDER BY item_id DESC";

$result = mysqli_query($conn, $sql);
?>

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
    <link rel="stylesheet" href="ngo_sidebar.css">
</head>
<body>
    <?php include 'ngo_sidebar.php'; ?>
    <div class="container ngo-content">
        <a href="ngo_dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="header">
            <h1>Donation Requests</h1>
        </div>

        <div class="table-wrap">
            <?php if (mysqli_num_rows($result) > 0): ?>
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
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
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
                                        <a href="ngo_donation_requests.php?accept_id=<?php echo (int)($row["item_id"] ?? 0); ?>" class="action-btn" onclick="return confirm('Accept this donation request?');">Accept</a>
                                    <?php else: ?>
                                        <span style="color:#6b7280; font-size:13px;">Done</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
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
