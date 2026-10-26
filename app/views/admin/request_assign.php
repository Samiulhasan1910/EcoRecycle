    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Assign Delivery Staff</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f8f5; margin: 0; padding: 40px; }
            .card { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 30px; }
            h2 { color: #168a45; margin-top: 0; }
            label { display: block; margin-bottom: 8px; font-weight: bold; color: #213326; }
            select { width: 100%; padding: 12px; border: 1px solid #dfe7e1; border-radius: 8px; margin-bottom: 18px; font-size: 15px; }
            .btn { display: inline-block; padding: 10px 18px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; font-weight: bold; }
            .primary { background: #168a45; color: white; }
            .secondary { background: #edf1ee; color: #203123; }
            .empty { color: #697a6f; padding: 18px 0; }
            .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        </style>
    </head>
    <body>
        <div class="card">
            <h2>Select Delivery Staff</h2>
            <?php if (!empty($deliveryList)): ?>
                <form method="POST" action="index.php?url=admin/requests-action&action=assign&id=<?php echo $requestId; ?>">
                    <label for="delivery_id">Available delivery staff</label>
                    <select name="delivery_id" id="delivery_id" required>
                        <option value="">-- Select delivery staff --</option>
                        <?php foreach ($deliveryList as $delivery): ?>
                            <option value="<?php echo (int) $delivery['delivery_id']; ?>">
                                <?php echo htmlspecialchars($delivery['name']); ?> (<?php echo htmlspecialchars($delivery['phone'] ?? ''); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="actions">
                        <button type="submit" class="btn primary">Assign</button>
                        <a href="index.php?url=admin/requests" class="btn secondary">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <p class="empty">No available delivery staff right now. Please mark someone Available first from the delivery staff list.</p>
                <div class="actions">
                    <a href="index.php?url=admin/requests" class="btn secondary">Back</a>
                </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
