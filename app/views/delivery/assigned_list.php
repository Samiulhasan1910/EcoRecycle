<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $requestDetails ? 'Request Details' : 'Assigned Requests'; ?> - EcoCycle</title>
    <link rel="stylesheet" href="assets/css/style_delivery.css">
</head>
<body>
<?php require APP_PATH . '/views/layouts/header_sidebar_delivery.php'; ?>
<main class="main assigned-page">
    <?php if ($requestDetails): ?>
        <?php $status = trim((string)$requestDetails['status']) ?: 'Pending'; ?>
        <?php $pageError = $_GET['error'] ?? ''; ?>
        <section class="delivery-dashboard-heading">
            <p class="eyebrow">Assigned request</p>
            <h1>Request #<?php echo (int)$requestDetails['request_id']; ?></h1>
            <p>Review the pickup and NGO information before accepting this delivery.</p>
        </section>

        <section class="request-detail-panel">
            <div class="detail-panel-heading">
                <div>
                    <p class="eyebrow">Request information</p>
                    <h2><?php echo htmlspecialchars($requestDetails['item_name'] ?? 'Item unavailable'); ?></h2>
                </div>
                <span class="request-status <?php echo status_class($status); ?>"><?php echo htmlspecialchars($status); ?></span>
            </div>
            <section class="pickup-information">
                <div class="pickup-information-heading">
                    <div>
                        <p class="eyebrow">Pickup information</p>
                        <h2>Collection point</h2>
                    </div>
                    <?php if ($pickupLocation !== ''): ?>
                        <a class="map-button" href="<?php echo htmlspecialchars($mapUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">View on Map</a>
                    <?php endif; ?>
                </div>
                <div class="pickup-information-grid">
                    <div class="pickup-field"><span>Owner</span><strong><?php echo htmlspecialchars($requestDetails['owner_name'] ?? 'N/A'); ?></strong></div>
                    <div class="pickup-field"><span>Phone</span><strong><?php echo htmlspecialchars($requestDetails['owner_phone'] ?? 'N/A'); ?></strong></div>
                    <div class="pickup-field pickup-location-field"><span>Pickup location</span><strong><?php echo htmlspecialchars($pickupLocation !== '' ? $pickupLocation : 'N/A'); ?></strong></div>
                    <div class="pickup-field"><span>Item</span><strong><?php echo htmlspecialchars($requestDetails['item_name'] ?? 'N/A'); ?></strong></div>
                </div>
            </section>
            <div class="request-detail-grid">
                <div class="detail-field"><span>Request ID</span><strong>#<?php echo (int)$requestDetails['request_id']; ?></strong></div>
                <div class="detail-field"><span>Item name</span><strong><?php echo htmlspecialchars($requestDetails['item_name'] ?? 'N/A'); ?></strong></div>
                <div class="detail-field"><span>Item category</span><strong><?php echo htmlspecialchars($requestDetails['item_category'] ?? 'N/A'); ?></strong></div>
                <div class="detail-field"><span>Item condition</span><strong><?php echo htmlspecialchars($requestDetails['item_condition'] ?? 'N/A'); ?></strong></div>
                <div class="detail-field"><span>Quantity</span><strong>N/A</strong></div>
                <div class="detail-field"><span>Pickup location</span><strong><?php echo htmlspecialchars($requestDetails['pickup_location'] ?? 'N/A'); ?></strong></div>
                <div class="detail-field"><span>NGO name</span><strong><?php echo htmlspecialchars($requestDetails['ngo_name'] ?? 'N/A'); ?></strong></div>
                <div class="detail-field"><span>NGO address</span><strong><?php echo htmlspecialchars($requestDetails['ngo_address'] ?? 'N/A'); ?></strong></div>
                <div class="detail-field"><span>NGO phone</span><strong><?php echo htmlspecialchars($requestDetails['ngo_phone'] ?? 'N/A'); ?></strong></div>
                <div class="detail-field"><span>Request date</span><strong><?php echo htmlspecialchars($requestDetails['created_at'] ?? 'N/A'); ?></strong></div>
                <div class="detail-field"><span>Assigned date</span><strong><?php echo htmlspecialchars($requestDetails['assigned_at'] ?? 'N/A'); ?></strong></div>
                <div class="detail-field"><span>Arrived at pickup</span><strong><?php echo htmlspecialchars($requestDetails['arrived_at'] ?? 'N/A'); ?></strong></div>
                <div class="detail-field"><span>Item received</span><strong><?php echo htmlspecialchars($requestDetails['item_received'] ?? 'N/A'); ?></strong></div>
                <div class="detail-field"><span>Pickup note</span><strong><?php echo htmlspecialchars($requestDetails['pickup_note'] ?? 'N/A'); ?></strong></div>
                <div class="detail-field"><span>Picked up date</span><strong><?php echo htmlspecialchars($requestDetails['picked_up_at'] ?? 'N/A'); ?></strong></div>
                <div class="detail-field"><span>Current status</span><strong><?php echo htmlspecialchars($status); ?></strong></div>
            </div>
            <div class="detail-actions">
                <a class="detail-secondary" href="index.php?url=delivery/assigned-list">Back to assigned requests</a>
                <?php if ($status === 'Assigned'): ?>
                    <form class="inline-action-form" method="post" action="index.php?url=admin/requests-action&action=accept&id=<?php echo (int)$requestDetails['request_id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>"><button class="request-accept" type="submit">Accept Request</button></form>
                    <button class="request-reject" type="button" onclick="document.getElementById('reject-request-form').hidden = false; this.hidden = true;">Reject Request</button>
                <?php elseif ($status === 'Accepted'): ?>
                    <span class="accepted-confirmation">Accepted &#10003;</span>
                    <form class="inline-action-form" method="post" action="index.php?url=admin/requests-action&action=arrived_at_pickup&id=<?php echo (int)$requestDetails['request_id']; ?>"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>"><button class="request-accept" type="submit">Mark as Arrived</button></form>
                <?php elseif ($status === 'Arrived at Pickup'): ?>
                    <span class="accepted-confirmation">Arrived &#10003;</span>
                    <form class="pickup-confirmation-form" method="post" action="index.php?url=admin/requests-action&action=picked_up_delivery&id=<?php echo (int)$requestDetails['request_id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <fieldset>
                            <legend>Pickup confirmation</legend>
                            <label><input type="radio" name="item_received" value="Yes" required> Item received: Yes</label>
                            <label><input type="radio" name="item_received" value="No"> Item received: No</label>
                        </fieldset>
                        <label for="pickup_note">Pickup note <span>(optional)</span></label>
                        <textarea id="pickup_note" name="pickup_note" rows="3" placeholder="Collected successfully."></textarea>
                        <button class="request-accept" type="submit">Confirm Pickup</button>
                    </form>
                <?php elseif ($status === 'Picked Up'): ?>
                    <form class="completion-form" method="post" action="index.php?url=admin/requests-action&action=complete_delivery&id=<?php echo (int)$requestDetails['request_id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <label for="completion_note">Completion information</label>
                        <textarea id="completion_note" name="completion_note" rows="3" required placeholder="Describe the completed delivery"></textarea>
                        <button class="request-accept" type="submit">Submit Completed</button>
                    </form>
                <?php elseif ($status === 'Completed'): ?>
                    <span class="accepted-confirmation">Completed &#10003;</span>
                <?php elseif ($status === 'Pickup Failed'): ?>
                    <span class="request-status status-pickup-failed">Pickup Failed</span>
                <?php endif; ?>
            </div>
            <?php if ($pageError === 'item_received_required'): ?>
                <p class="workflow-error">Please select whether the item was received.</p>
            <?php endif; ?>
            <?php if ($status === 'Assigned'): ?>
                <form class="reject-form" id="reject-request-form" hidden method="post" action="index.php?url=admin/requests-action&action=reject_delivery&id=<?php echo (int)$requestDetails['request_id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <label for="reject_reason">Reason for rejection</label>
                    <textarea id="reject_reason" name="reason" rows="3" required placeholder="Unable to reach pickup location"></textarea>
                    <button class="request-reject" type="submit">Submit Rejection</button>
                </form>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="delivery-dashboard-heading">
            <p class="eyebrow">Delivery workload</p>
            <h1>Assigned Requests</h1>
            <p>Requests assigned to you by the administrator.</p>
        </section>

        <section class="assigned-request-list">
            <?php if (!empty($requests)): ?>
                <?php foreach ($requests as $request): ?>
                    <?php $status = trim((string)$request['status']) ?: 'Pending'; ?>
                    <article class="assigned-request-card">
                        <div class="assigned-request-main">
                            <p class="eyebrow">Request #<?php echo (int)$request['request_id']; ?></p>
                            <h2><?php echo htmlspecialchars($request['item_name'] ?? 'Item unavailable'); ?></h2>
                            <div class="assigned-request-meta">
                                <span>Pickup: <?php echo htmlspecialchars($request['pickup_location'] ?? 'N/A'); ?></span>
                                <span>NGO: <?php echo htmlspecialchars($request['ngo_name'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="assigned-request-actions">
                            <span class="request-status <?php echo status_class($status); ?>"><?php echo htmlspecialchars($status); ?></span>
                            <?php if ($status === 'Accepted'): ?>
                                <span class="accepted-confirmation">Accepted &#10003;</span>
                            <?php endif; ?>
                            <a class="detail-secondary" href="index.php?url=delivery/assigned-list&id=<?php echo (int)$request['request_id']; ?>">View Details</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="assigned-empty">No requests have been assigned to you yet.</div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
