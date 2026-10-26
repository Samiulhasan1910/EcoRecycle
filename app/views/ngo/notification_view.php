<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<title>Notification View</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

body {
    font-family: Arial, sans-serif;
    background: #f5f6f8;
    margin: 0;
}

.container {
    width: 700px;
    margin: 50px auto;
}

.card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 25px;
}

h2 {
    margin-top: 0;
}

.message {
    background: #f1f8f4;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 25px;
    font-weight: bold;
}

.info {
    margin-bottom: 12px;
}

.label {
    font-weight: bold;
}

.back-btn {
    display: inline-block;
    margin-top: 20px;
    text-decoration: none;
    background: #168a45;
    color: white;
    padding: 10px 18px;
    border-radius: 6px;
}

.action-row { display: flex; gap: 12px; margin-top: 22px; }
.action-row form { margin: 0; }
.action-btn { border: 0; color: white; padding: 10px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; }
.accept-btn { background: #168a45; }
.reject-btn { background: #dc3545; }
.feedback { padding: 12px 15px; border-radius: 7px; margin-bottom: 18px; }
.feedback.success { background: #e7f7ed; color: #126b37; }
.feedback.error { background: #fdebec; color: #a52834; }

</style>
<link rel="stylesheet" href="assets/css/ngo_sidebar.css">

</head>

<body>

<?php require APP_PATH . '/views/layouts/ngo_sidebar.php'; ?>

<div class="container ngo-content">

<div class="card">

<h2>Notification Details</h2>

<?php if (isset($_GET["result"])): ?>
<div class="feedback success"><?php echo $_GET["result"] === "requested" ? "Request sent to the donor successfully." : "Donation request " . ($_GET["result"] === "accept" ? "accepted" : "rejected") . " successfully."; ?></div>
<?php elseif (!empty($_GET["error"])): ?>
<div class="feedback error"><?php echo htmlspecialchars($_GET["error"]); ?></div>
<?php endif; ?>

<div class="message">

<?php echo htmlspecialchars($notification["message"]); ?>

</div>


<?php

/* =========================
   DONATION / REQUEST DETAILS
========================= */

if ($request) {

?>

<h3>Donation Request Details</h3>

<div class="info">
<span class="label">Item:</span>
<?php echo htmlspecialchars($request["title"]); ?>
</div>

<div class="info">
<span class="label">Category:</span>
<?php echo htmlspecialchars($request["category"]); ?>
</div>

<div class="info">
<span class="label">Price:</span>
<?php echo htmlspecialchars($request["price"]); ?>
</div>

<div class="info">
<span class="label">Location:</span>
<?php echo htmlspecialchars($request["location"]); ?>
</div>

<div class="info">
<span class="label">Request Type:</span>
<?php echo htmlspecialchars($request["request_type"]); ?>
</div>

<div class="info">
<span class="label">Request Status:</span>
<?php echo htmlspecialchars($request["status"]); ?>
</div>

<?php

}

elseif ($item) {
?>

<h3>Donation Post Details</h3>

<div class="info"><span class="label">Item:</span> <?php echo htmlspecialchars($item["title"]); ?></div>
<div class="info"><span class="label">Category:</span> <?php echo htmlspecialchars($item["category"]); ?></div>
<div class="info"><span class="label">Location:</span> <?php echo htmlspecialchars($item["location"]); ?></div>
<div class="info"><span class="label">Status:</span> <?php echo htmlspecialchars($item["status"]); ?></div>

<?php
}

elseif ($wanted) {
?>

<h3>Wanted Post Details</h3>
<div class="info"><span class="label">Title:</span> <?php echo htmlspecialchars($wanted["title"]); ?></div>
<div class="info"><span class="label">Category:</span> <?php echo htmlspecialchars($wanted["category"]); ?></div>
<div class="info"><span class="label">Location:</span> <?php echo htmlspecialchars($wanted["location"]); ?></div>
<div class="info"><span class="label">Status:</span> <?php echo htmlspecialchars($wanted["status"]); ?></div>
<div class="info"><span class="label">Description:</span> <?php echo htmlspecialchars($wanted["description"] ?? ""); ?></div>

<?php
}


/* =========================
   PICKUP DETAILS
========================= */

elseif ($pickup) {

?>

<h3>Pickup Details</h3>

<div class="info">
<span class="label">Delivery Person:</span>
<?php echo htmlspecialchars($pickup["delivery_name"]); ?>
</div>

<div class="info">
<span class="label">Phone:</span>
<?php echo htmlspecialchars($pickup["delivery_phone"]); ?>
</div>

<div class="info">
<span class="label">Delivery Location:</span>
<?php echo htmlspecialchars($pickup["delivery_location"]); ?>
</div>

<div class="info">
<span class="label">Pickup Date:</span>
<?php echo htmlspecialchars($pickup["date"]); ?>
</div>

<div class="info">
<span class="label">Pickup Time:</span>
<?php echo htmlspecialchars($pickup["time"]); ?>
</div>

<div class="info">
<span class="label">Pickup Status:</span>
<?php echo htmlspecialchars($pickup["status"]); ?>
</div>

<?php

}

else {

?>

<p>No related request or pickup details found.</p>

<?php

}

?>

<?php
$itemStatus = strtolower(trim($item["status"] ?? ""));
$requestStatus = strtolower(trim($request["status"] ?? ""));
$isDonation = strtolower(trim($item["type"] ?? $request["request_type"] ?? ""));
$canRespond = $item
    && in_array($isDonation, ["donate", "donation"], true)
    && (int)($item["ngo_id"] ?? 0) === $ngo_id
    && (
        in_array($itemStatus, ["available", "pending"], true)
        || ($itemStatus === "accepted" && $requestStatus === "pending")
    )
    && !in_array(strtolower(trim($notification["status"] ?? "")), ["accepted", "rejected"], true);
if ($canRespond):
?>
<div class="action-row">
    <form method="post" onsubmit="return confirm('Accept this donation and send it to available delivery men?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="accept">
        <button type="submit" class="action-btn accept-btn">Accept</button>
    </form>
    <form method="post" onsubmit="return confirm('Reject this donation request?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="reject">
        <button type="submit" class="action-btn reject-btn">Reject</button>
    </form>
</div>
<?php endif; ?>

<?php if ($chargeDelivery): ?>
<div class="message" style="margin-top:22px;">
    Delivery charge proposed by <?php echo htmlspecialchars($chargeDelivery["delivery_name"] ?? "Delivery man"); ?>:
    <?php echo number_format((float)$chargeDelivery["proposed_charge"], 2); ?> BDT
</div>
<div class="action-row">
    <form method="post" action="index.php?url=delivery/charge-response" onsubmit="return confirm('Accept this delivery charge?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="delivery_id" value="<?php echo (int)$chargeDelivery["delivery_id"]; ?>">
        <button type="submit" name="action" value="accept" class="action-btn accept-btn">Accept Charge</button>
    </form>
    <form method="post" action="index.php?url=delivery/charge-response" onsubmit="return confirm('Reject this delivery charge?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="delivery_id" value="<?php echo (int)$chargeDelivery["delivery_id"]; ?>">
        <button type="submit" name="action" value="reject" class="action-btn reject-btn">Reject Charge</button>
    </form>
</div>
<?php endif; ?>

<?php
$isPublicDonation = $item
    && strtolower(trim($item["type"] ?? "")) === "donate"
    && strtolower(trim($item["status"] ?? "")) === "available"
    && empty($item["ngo_id"])
    && !(strtolower(trim($request["request_type"] ?? "")) === "ngo donation"
        && strtolower(trim($request["status"] ?? "")) === "pending");
if ($isPublicDonation):
?>
<div class="action-row">
    <form method="post" onsubmit="return confirm('Send a donation request to this donor?');">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="request_donor">
        <button type="submit" class="action-btn accept-btn">Request Donor</button>
    </form>
</div>
<?php endif; ?>

<a href="index.php?url=ngo/notifications" class="back-btn">
Back to Notifications
</a>

</div>

</div>

</body>

</html>
