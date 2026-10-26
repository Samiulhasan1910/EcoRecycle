<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Offers for Wanted Post</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .main{ margin-left:230px; padding:105px 45px 50px; max-width:1000px; }
        .card{ background:white; border:1px solid #e5e5e5; border-radius:12px; padding:24px; }
        .offer{ border:1px solid #eee; padding:12px; border-radius:8px; margin-bottom:12px }
        .offer-actions{ margin-top:10px }
        .btn{ padding:8px 12px; border-radius:8px; text-decoration:none; color:white; border:none; cursor:pointer }
        .accept{ background:#168a45 }
        .reject{ background:#d93025 }
    </style>
</head>
<body>
<?php require APP_PATH . '/views/layouts/header_sidebar.php'; ?>
<main class="main">
    <div class="card">
        <h2>Offers for: <?php echo htmlspecialchars($wanted['title']); ?></h2>

        <?php if(count($res) == 0): ?>
            <div class="owner-note">No offers yet.</div>
        <?php else: ?>

            <?php foreach($res as $o): ?>
                <div class="offer">
                    <div><strong>Item:</strong> <?php echo htmlspecialchars($o['item_title']); ?></div>
                    <div><strong>Offerer:</strong> <?php echo htmlspecialchars($o['offerer_name']); ?></div>
                    <div><strong>Contact:</strong> <?php echo htmlspecialchars($o['offerer_phone']); ?></div>
                    <div><strong>Price:</strong> <?php echo $o['is_free'] ? '<span style="color:green;font-weight:bold">FREE</span>' : '৳'.htmlspecialchars($o['price']); ?></div>
                    <div><strong>Payment Method:</strong> <?php echo $o['payment_method'] ? htmlspecialchars($o['payment_method']) : '-'; ?></div>

                    <?php if($o['payment_visible']): ?>
                        <div><strong>Payment Number:</strong> <?php echo htmlspecialchars($o['payment_number']); ?></div>
                    <?php else: ?>
                        <div><strong>Payment Number:</strong> <em>Hidden until you accept</em></div>
                    <?php endif; ?>

                    <div class="offer-actions">
                        <?php if($o['status'] == 'Pending'): ?>
                            <a href="index.php?url=offers/action&offer_id=<?php echo $o['offer_id']; ?>&action=accept" class="btn accept" onclick="return confirm('Accept this offer?');">Accept</a>
                            <a href="index.php?url=offers/action&offer_id=<?php echo $o['offer_id']; ?>&action=reject" class="btn reject" onclick="return confirm('Reject this offer?');">Reject</a>
                        <?php else: ?>
                            <div>Status: <?php echo htmlspecialchars($o['status']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</main>
</body>
</html>
