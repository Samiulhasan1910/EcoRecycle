<?php

session_start();
include 'db.php';

// Security check
if ($_SESSION["role"] !== "admin") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$action = isset($_GET["action"]) ? $_GET["action"] : "";
$ngoId = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if (!$ngoId) {
    header("Location: ngo_management.php");
    exit;
}

// ====================
// VIEW NGO DETAILS
// ====================

if ($action == "view") {

    $query = "SELECT ngo_id, name, email, phone, location, organization_type, 
                     established_year, verification_status, verification_document 
                     FROM ngos WHERE ngo_id = " . $ngoId;

    $result = mysqli_query($conn, $query);

    if (!$result || mysqli_num_rows($result) == 0) {
        header("Location: ngo_management.php");
        exit;
    }

    $ngo = mysqli_fetch_assoc($result);
    $statusText = trim((string)($ngo["verification_status"] ?? ""));
    if ($statusText === "") {
        $statusText = "Pending";
    }

?>

    <!DOCTYPE html>
    <html>

    <head>
        <title><?php echo htmlspecialchars($ngo["name"]); ?> - NGO Details</title>
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
                padding: 20px;
            }

            .container {
                max-width: 600px;
                margin: 0 auto;
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            }

            .header {
                background: #168a45;
                color: white;
                padding: 25px;
                text-align: center;
            }

            .header h1 {
                font-size: 28px;
                margin-bottom: 5px;
            }

            .content {
                padding: 30px;
            }

            .detail-row {
                margin-bottom: 20px;
            }

            .detail-row label {
                display: block;
                color: #777;
                font-size: 12px;
                font-weight: bold;
                text-transform: uppercase;
                margin-bottom: 5px;
            }

            .detail-row value {
                display: block;
                color: #333;
                font-size: 16px;
                padding: 10px;
                background: #f8f8f8;
                border-radius: 6px;
            }

            .status {
                display: inline-block;
                padding: 8px 15px;
                border-radius: 20px;
                font-size: 13px;
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
                gap: 10px;
                margin-top: 30px;
            }

            .btn {
                flex: 1;
                padding: 12px;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-weight: bold;
                text-decoration: none;
                display: inline-block;
                text-align: center;
                transition: all 0.3s;
            }

            .btn-back {
                background: #f0f0f0;
                color: #333;
            }

            .btn-back:hover {
                background: #ddd;
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

            .divider {
                border: 1px solid #eee;
                margin: 20px 0;
            }

        </style>
    </head>

    <body>

        <div class="container">

            <div class="header">
                <h1><?php echo htmlspecialchars($ngo["name"]); ?></h1>
                <span class="status <?php echo strtolower($statusText); ?>">
                    <?php echo $statusText; ?>
                </span>
            </div>

            <div class="content">

                <div class="detail-row">
                    <label>Email Address</label>
                    <value><?php echo htmlspecialchars($ngo["email"]); ?></value>
                </div>

                <div class="detail-row">
                    <label>Phone Number</label>
                    <value><?php echo htmlspecialchars($ngo["phone"]); ?></value>
                </div>

                <div class="detail-row">
                    <label>Location</label>
                    <value><?php echo htmlspecialchars($ngo["location"]); ?></value>
                </div>

                <div class="divider"></div>

                <div class="detail-row">
                    <label>Organization Type</label>
                    <value><?php echo htmlspecialchars($ngo["organization_type"]); ?></value>
                </div>

                <div class="detail-row">
                    <label>Established Year</label>
                    <value><?php echo htmlspecialchars($ngo["established_year"]); ?></value>
                </div>

                <div class="detail-row">
                    <label>Verification Status</label>
                    <value><?php echo htmlspecialchars($statusText); ?></value>
                </div>

                <div class="divider"></div>

                <div class="actions">

                    <a href="ngo_management.php" class="btn btn-back">← Back</a>

                    <?php if ($statusText == "Pending"): ?>
                        <a href="ngo_management_action.php?action=approve&id=<?php echo $ngo["ngo_id"]; ?>" class="btn btn-approve" onclick="return confirm('Approve this NGO?');">✓ Approve</a>
                        <a href="ngo_management_action.php?action=reject&id=<?php echo $ngo["ngo_id"]; ?>" class="btn btn-reject" onclick="return confirm('Reject this NGO?');">✗ Reject</a>
                    <?php endif; ?>

                    <?php if (!empty($ngo["verification_document"])): ?>
                        <a href="ngo_management_action.php?action=download&id=<?php echo $ngo["ngo_id"]; ?>" class="btn btn-download">📄 Document</a>
                    <?php endif; ?>

                </div>

            </div>

        </div>

    </body>

    </html>

<?php
    exit;
}

// ====================
// APPROVE NGO
// ====================

if ($action == "approve") {

    $query = "UPDATE ngos SET verification_status = 'Approved' WHERE ngo_id = " . $ngoId;

    if (mysqli_query($conn, $query)) {
        // Log the action
        error_log("Admin approved NGO ID: " . $ngoId);
        header("Location: ngo_management.php?approved=1");
        exit;
    } else {
        header("Location: ngo_management.php?error=1");
        exit;
    }

}

// ====================
// REJECT NGO
// ====================

if ($action == "reject") {

    $query = "UPDATE ngos SET verification_status = 'Rejected' WHERE ngo_id = " . $ngoId;

    if (mysqli_query($conn, $query)) {
        // Log the action
        error_log("Admin rejected NGO ID: " . $ngoId);
        header("Location: ngo_management.php?rejected=1");
        exit;
    } else {
        header("Location: ngo_management.php?error=1");
        exit;
    }

}

// ====================
// DOWNLOAD VERIFICATION DOCUMENT
// ====================

if ($action == "download") {

    $query = "SELECT verification_document FROM ngos WHERE ngo_id = " . $ngoId;

    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {

        $row = mysqli_fetch_assoc($result);
        $filePath = "uploads/ngo_documents/" . basename($row["verification_document"]);

        if (file_exists($filePath)) {

            // Force download
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=" . basename($filePath));
            header("Content-Length: " . filesize($filePath));
            readfile($filePath);
            exit;

        } else {

            header("Location: ngo_management.php?error=file_not_found");
            exit;

        }

    } else {

        header("Location: ngo_management.php?error=not_found");
        exit;

    }

}

// If no valid action
header("Location: ngo_management.php");
exit;

?>
