<?php
require 'delivery_guard.php';
if($_SERVER['REQUEST_METHOD']!=='POST' || !hash_equals($_SESSION['csrf_token']??'',$_POST['csrf_token']??'')) { header('Location: delivery_jobs.php'); exit; }
$jobId=(int)($_POST['delivery_id']??0); $action=$_POST['action']??'';
$steps=['arrived'=>['Charge Accepted','Arrived at Pickup'], 'picked_up'=>['Arrived at Pickup','Picked Up'], 'complete'=>['Picked Up','Completed']];
if($jobId<=0 || !isset($steps[$action])) { header('Location: delivery_jobs.php'); exit; }
[$from,$to]=$steps[$action];
$extra=$action==='arrived' ? ', arrived_at=NOW()' : ($action==='picked_up' ? ', picked_up_at=NOW()' : ', delivered_at=NOW()');
try {
    $ok=mysqli_query($conn,"UPDATE deliveries SET status='$to', updated_at=NOW()$extra WHERE delivery_id=$jobId AND assigned_delivery_man_id=$deliveryId AND status='$from'");
    if($ok && mysqli_affected_rows($conn)===1) {
        mysqli_query($conn,"UPDATE pickups p INNER JOIN deliveries d ON d.request_id=p.request_id SET p.status='$to' WHERE d.delivery_id=$jobId");
        mysqli_query($conn,"UPDATE requests r INNER JOIN deliveries d ON d.request_id=r.request_id SET r.status='$to', r.updated_at=NOW() WHERE d.delivery_id=$jobId");
        if($action==='complete') mysqli_query($conn,"UPDATE delivery_man SET availability='Available' WHERE delivery_id=$deliveryId");
        header('Location: delivery_assigned.php?message=status_updated');
    } else {
        header('Location: delivery_jobs.php?error=job_not_updated');
    }
} catch(Throwable $e) {
    header('Location: delivery_jobs.php?error='.urlencode($e->getMessage()));
}
exit;
