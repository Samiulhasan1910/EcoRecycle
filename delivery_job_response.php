<?php
require 'delivery_guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
    header('Location: delivery_dashboard.php'); exit;
}
$jobId = (int)($_POST['delivery_id'] ?? 0);
$action = $_POST['action'] ?? '';
if ($jobId <= 0 || !in_array($action, ['accept', 'reject'], true)) { header('Location: delivery_dashboard.php'); exit; }

mysqli_begin_transaction($conn);
try {
    $staffResult = mysqli_query($conn, "SELECT availability, verification_status, is_blocked FROM delivery_man WHERE delivery_id = $deliveryId FOR UPDATE");
    $staff = $staffResult ? mysqli_fetch_assoc($staffResult) : null;
    if (!$staff) throw new Exception('Delivery man account could not be found.');

    $jobResult = mysqli_query($conn, "SELECT status FROM deliveries WHERE delivery_id = $jobId FOR UPDATE");
    $job = $jobResult ? mysqli_fetch_assoc($jobResult) : null;
    if (!$job || !in_array($job['status'], ['Open', 'Assigned'], true)) throw new Exception('This job is no longer available.');
    if ($action === 'reject') {
        mysqli_query($conn, "INSERT INTO delivery_job_responses (delivery_id, delivery_man_id, response_status) VALUES ($jobId, $deliveryId, 'Rejected') ON DUPLICATE KEY UPDATE response_status = 'Rejected', responded_at = NOW()");
    } else {
        if ($staff['verification_status'] !== 'Approved') throw new Exception('Only approved delivery men can accept jobs.');
        if ((int)$staff['is_blocked'] !== 0) throw new Exception('Your account is blocked and cannot accept jobs.');
        if ($staff['availability'] !== 'Available') throw new Exception('Set your availability to Available before accepting a job.');

        $activeResult = mysqli_query($conn, "SELECT delivery_id FROM deliveries WHERE assigned_delivery_man_id = $deliveryId AND status NOT IN ('Completed', 'Open') LIMIT 1 FOR UPDATE");
        if ($activeResult && mysqli_num_rows($activeResult) > 0) throw new Exception('Complete your current delivery before accepting another job.');

        $nextStatus = $job['status'] === 'Assigned' ? 'Charge Accepted' : 'Charge Pending';
        mysqli_query($conn, "UPDATE deliveries SET assigned_delivery_man_id = $deliveryId, status = '$nextStatus', updated_at = NOW() WHERE delivery_id = $jobId AND status IN ('Open', 'Assigned') AND (assigned_delivery_man_id IS NULL OR assigned_delivery_man_id = $deliveryId)");
        if (mysqli_affected_rows($conn) !== 1) throw new Exception('Another delivery man already accepted this job.');
        mysqli_query($conn, "UPDATE requests r INNER JOIN deliveries d ON d.request_id = r.request_id SET r.status = 'Accepted', r.delivery_id = $deliveryId, r.assigned_at = NOW(), r.updated_at = NOW() WHERE d.delivery_id = $jobId");
        mysqli_query($conn, "UPDATE pickups p INNER JOIN deliveries d ON d.request_id = p.request_id SET p.delivery_id = $deliveryId, p.status = 'Accepted' WHERE d.delivery_id = $jobId");
        mysqli_query($conn, "INSERT INTO delivery_job_responses (delivery_id, delivery_man_id, response_status) VALUES ($jobId, $deliveryId, 'Accepted') ON DUPLICATE KEY UPDATE response_status = 'Accepted', responded_at = NOW()");
        if (!mysqli_query($conn, "UPDATE delivery_man SET availability = 'Busy' WHERE delivery_id = $deliveryId AND availability = 'Available'")) {
            throw new Exception('Could not update delivery availability.');
        }
        if (mysqli_affected_rows($conn) !== 1) throw new Exception('Your availability changed before this job could be assigned.');
    }
    mysqli_commit($conn);
    header('Location: delivery_dashboard.php?message=' . urlencode($action === 'accept' ? 'Delivery accepted successfully.' : 'Delivery job rejected.'));
} catch (Exception $e) {
    mysqli_rollback($conn);
    header('Location: delivery_dashboard.php?error=' . urlencode($e->getMessage()));
}
