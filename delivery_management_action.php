<?php
session_start();
include 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

/* Delivery columns are declared in project (5).sql. */

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$deliveryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if ($name === '' || $email === '' || $password === '' || $phone === '' || $location === '') {
        header('Location: delivery_management.php?error=missing_fields');
        exit;
    }

    if (!isset($_FILES['verification_document']) || $_FILES['verification_document']['error'] !== UPLOAD_ERR_OK) {
        header('Location: delivery_management.php?error=license_required');
        exit;
    }

    $check = mysqli_query($conn, "SELECT delivery_id FROM delivery WHERE email = '" . mysqli_real_escape_string($conn, $email) . "' LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        header('Location: delivery_management.php?error=email_exists');
        exit;
    }

    $fileName = $_FILES['verification_document']['name'];
    $fileTmp = $_FILES['verification_document']['tmp_name'];
    $fileSize = (int) $_FILES['verification_document']['size'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png'];

    if (!in_array($fileExt, $allowedTypes, true)) {
        header('Location: delivery_management.php?error=invalid_license_type');
        exit;
    }

    if ($fileSize > 5 * 1024 * 1024) {
        header('Location: delivery_management.php?error=license_too_large');
        exit;
    }

    $uploadFolder = 'uploads/delivery_documents/';
    if (!is_dir($uploadFolder)) {
        mkdir($uploadFolder, 0777, true);
    }

    $newFileName = 'delivery_' . time() . '_' . uniqid() . '.' . $fileExt;
    $filePath = $uploadFolder . $newFileName;

    if (!move_uploaded_file($fileTmp, $filePath)) {
        header('Location: delivery_management.php?error=license_upload_failed');
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $documentPath = mysqli_real_escape_string($conn, $filePath);
    $nameSafe = mysqli_real_escape_string($conn, $name);
    $emailSafe = mysqli_real_escape_string($conn, $email);
    $phoneSafe = mysqli_real_escape_string($conn, $phone);
    $locationSafe = mysqli_real_escape_string($conn, $location);
    $passwordSafe = mysqli_real_escape_string($conn, $hashedPassword);

    $sql = "INSERT INTO delivery (name, email, password, phone, location, verification_document, verification_status, availability, is_blocked) VALUES (
        '$nameSafe',
        '$emailSafe',
        '$passwordSafe',
        '$phoneSafe',
        '$locationSafe',
        '$documentPath',
        'Approved',
        'Offline',
        0)
    ";

    if (mysqli_query($conn, $sql)) {
        header('Location: delivery_management.php?success=staff_added');
    } else {
        header('Location: delivery_management.php?error=insert_failed');
    }
    exit;
}

if ($action === 'view' && $deliveryId > 0) {
    $result = mysqli_query($conn, "SELECT * FROM delivery WHERE delivery_id = $deliveryId LIMIT 1");
    if (!$result || mysqli_num_rows($result) === 0) {
        header('Location: delivery_management.php');
        exit;
    }

    $staff = mysqli_fetch_assoc($result);
    $statusText = trim((string)($staff['verification_status'] ?? ''));
    if ($statusText === '') $statusText = 'Pending';
    $documentPath = trim((string)($staff['verification_document'] ?? ''));
    $documentFile = $documentPath === '' ? '' : basename($documentPath);

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Delivery Staff Details</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f8f5; margin: 0; padding: 30px; }
            .card { max-width: 700px; margin: 0 auto; background: #fff; border-radius: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; }
            .header { background: #168a45; color: white; padding: 24px; }
            .content { padding: 24px; }
            .row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #edf1ee; }
            .label { color: #6d7b6f; font-weight: bold; }
            .status { display: inline-block; padding: 6px 12px; border-radius: 999px; font-weight: bold; }
            .status-approved { background: #dff5e6; color: #1b8c4a; }
            .status-pending { background: #fff4d9; color: #a86c00; }
            .status-blocked { background: #fde3e1; color: #c62828; }
            .actions { margin-top: 24px; display: flex; gap: 10px; flex-wrap: wrap; }
            .btn { display: inline-block; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: bold; }
            .back { background: #edf1ee; color: #203123; }
            .approve { background: #2aa85c; color: white; }
            .block { background: #d93025; color: white; }
            .unblock { background: #ff8a1c; color: white; }
            .download { background: #2a5bd7; color: white; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="header">
                <h1><?php echo htmlspecialchars($staff['name']); ?></h1>
                <span class="status status-<?php echo strtolower(str_replace(' ', '-', $statusText)); ?>"><?php echo htmlspecialchars($statusText); ?></span>
            </div>
            <div class="content">
                <div class="row"><div class="label">Email</div><div><?php echo htmlspecialchars($staff['email']); ?></div></div>
                <div class="row"><div class="label">Phone</div><div><?php echo htmlspecialchars($staff['phone']); ?></div></div>
                <div class="row"><div class="label">Location</div><div><?php echo htmlspecialchars($staff['location']); ?></div></div>
                <div class="row"><div class="label">Availability</div><div><?php echo htmlspecialchars((string)($staff['availability'] ?? 'Offline')); ?></div></div>
                <div class="row">
                    <div class="label">Driving Licence</div>
                    <div>
                        <?php if ($documentPath !== ''): ?>
                            <a href="delivery_management_action.php?action=download&id=<?php echo $deliveryId; ?>" class="btn download">Download</a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </div>
                </div>
                <div class="actions">
                    <a class="btn back" href="delivery_management.php">Back</a>
                    <?php if ($statusText === 'Pending'): ?>
                        <a class="btn approve" href="delivery_management_action.php?action=approve&id=<?php echo $deliveryId; ?>">Approve</a>
                    <?php endif; ?>
                    <?php if ($statusText === 'Approved'): ?>
                        <a class="btn block" href="delivery_management_action.php?action=block&id=<?php echo $deliveryId; ?>">Block</a>
                    <?php endif; ?>
                    <?php if ($statusText === 'Blocked'): ?>
                        <a class="btn unblock" href="delivery_management_action.php?action=unblock&id=<?php echo $deliveryId; ?>">Unblock</a>
                    <?php endif; ?>
                    <?php if ($documentPath !== ''): ?>
                        <a class="btn download" href="<?php echo htmlspecialchars($documentPath); ?>" target="_blank">Open File</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if ($action === 'download' && $deliveryId > 0) {
    $query = "SELECT verification_document FROM delivery WHERE delivery_id = $deliveryId LIMIT 1";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $filePath = trim((string)($row['verification_document'] ?? ''));
        if ($filePath !== '' && file_exists($filePath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($filePath));
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        }
    }
    header('Location: delivery_management.php?error=document_not_found');
    exit;
}

if ($action === 'approve' && $deliveryId > 0) {
    $sql = "UPDATE delivery SET verification_status = 'Approved', is_blocked = 0 WHERE delivery_id = $deliveryId";
    mysqli_query($conn, $sql);
    header('Location: delivery_management.php?success=approved');
    exit;
}

if ($action === 'block' && $deliveryId > 0) {
    $sql = "UPDATE delivery SET verification_status = 'Blocked', is_blocked = 1 WHERE delivery_id = $deliveryId";
    mysqli_query($conn, $sql);
    header('Location: delivery_management.php?success=blocked');
    exit;
}

if ($action === 'unblock' && $deliveryId > 0) {
    $sql = "UPDATE delivery SET verification_status = 'Approved', is_blocked = 0 WHERE delivery_id = $deliveryId";
    mysqli_query($conn, $sql);
    header('Location: delivery_management.php?success=unblocked');
    exit;
}

if ($action === 'delete' && $deliveryId > 0) {
    mysqli_query($conn, "DELETE FROM delivery WHERE delivery_id = $deliveryId");
    header('Location: delivery_management.php?success=deleted');
    exit;
}

if ($action === 'set_availability' && $deliveryId > 0) {
    $value = $_GET['value'] ?? 'Offline';
    $safe = in_array($value, ['Available', 'Busy', 'Offline']) ? $value : 'Offline';
    mysqli_query($conn, "UPDATE delivery SET availability = '" . mysqli_real_escape_string($conn, $safe) . "' WHERE delivery_id = $deliveryId");
    header('Location: delivery_management.php?success=availability_updated');
    exit;
}

header('Location: delivery_management.php');
exit;
