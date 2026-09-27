<?php
require 'delivery_guard.php';
$allowed = ['Available', 'Busy', 'Offline'];
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) { http_response_code(403); exit('Invalid request token.'); }
    $availability = $_POST['availability'] ?? 'Offline';
    if (in_array($availability, $allowed, true)) {
        $activeResult = mysqli_query($conn, "SELECT delivery_id FROM deliveries WHERE assigned_delivery_man_id = $deliveryId AND status NOT IN ('Completed', 'Open') LIMIT 1");
        $hasActiveDelivery = $activeResult && mysqli_num_rows($activeResult) > 0;

        if ($availability === 'Available' && $hasActiveDelivery) {
            $message = 'You have an active delivery, so availability must remain Busy.';
            $availability = 'Busy';
        } else {
            $message = 'Availability updated successfully.';
        }

        mysqli_query($conn, "UPDATE delivery_man SET availability = '" . mysqli_real_escape_string($conn, $availability) . "' WHERE delivery_id = $deliveryId");
    }
}
$staff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT availability FROM delivery_man WHERE delivery_id = $deliveryId LIMIT 1"));
$current = $staff['availability'] ?? 'Offline';
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Availability</title><link rel="stylesheet" href="style_delivery.css"></head><body><?php include 'header_sidebar_delivery.php'; ?><main class="main"><section class="delivery-dashboard-heading"><p class="eyebrow">Work status</p><h1>Availability</h1><p>Set your status so administrators can assign work accurately.</p></section><section class="delivery-panel"><?php if ($message): ?><p class="success-message"><?php echo $message; ?></p><?php endif; ?><form method="post" class="delivery-form-grid"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>"><label class="full-width">Current availability<select name="availability"><option value="Available" <?php echo $current === 'Available' ? 'selected' : ''; ?>>Available — ready for an assignment</option><option value="Busy" <?php echo $current === 'Busy' ? 'selected' : ''; ?>>Busy — currently handling work</option><option value="Offline" <?php echo $current === 'Offline' ? 'selected' : ''; ?>>Offline — do not assign work</option></select></label><div class="full-width"><button class="request-accept" type="submit">Save Availability</button></div></form></section></main></body></html>
