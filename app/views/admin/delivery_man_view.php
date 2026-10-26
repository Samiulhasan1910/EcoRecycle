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
                            <a href="index.php?url=admin/delivery-men-action&action=download&id=<?php echo $deliveryId; ?>" class="btn download">Download</a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </div>
                </div>
                <div class="actions">
                    <a class="btn back" href="index.php?url=admin/delivery-men">Back</a>
                    <?php if ($statusText === 'Pending'): ?>
                        <a class="btn approve" href="index.php?url=admin/delivery-men-action&action=approve&id=<?php echo $deliveryId; ?>">Approve</a>
                    <?php endif; ?>
                    <?php if ($statusText === 'Approved'): ?>
                        <a class="btn block" href="index.php?url=admin/delivery-men-action&action=block&id=<?php echo $deliveryId; ?>">Block</a>
                    <?php endif; ?>
                    <?php if ($statusText === 'Blocked'): ?>
                        <a class="btn unblock" href="index.php?url=admin/delivery-men-action&action=unblock&id=<?php echo $deliveryId; ?>">Unblock</a>
                    <?php endif; ?>
                    <?php if ($documentPath !== ''): ?>
                        <a class="btn download" href="<?php echo htmlspecialchars($documentPath); ?>" target="_blank">Open File</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </body>
    </html>
