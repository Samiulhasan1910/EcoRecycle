<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header('Location: login.php');
    exit();
}

include('db.php');

$user_id = $_SESSION['user_id'];

if(!isset($_GET['wanted_id']) || !is_numeric($_GET['wanted_id'])){
    header('Location: my_requests.php');
    exit();
}

$wanted_id = (int)$_GET['wanted_id'];

// verify owner
$ws = mysqli_query($conn, "SELECT * FROM wanted_posts WHERE wanted_id='$wanted_id'");
if(!$ws || mysqli_num_rows($ws) == 0) die('Wanted post not found.');
$wanted = mysqli_fetch_assoc($ws);
if($wanted['user_id'] != $user_id) die('Not authorized.');

// fetch offers
$sql = "SELECT wo.*, u.name AS offerer_name, u.phone AS offerer_phone, i.title AS item_title
        FROM wanted_offers wo
        LEFT JOIN users u ON wo.offerer_user_id = u.user_id
        LEFT JOIN items i ON wo.item_id = i.item_id
        WHERE wo.wanted_id='$wanted_id'
        ORDER BY wo.created_at DESC";
$res = mysqli_query($conn, $sql);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Offers for Wanted Post</title>
    <link rel="stylesheet" href="style.css">
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
<?php include('header_sidebar.php'); ?>
<main class="main">
    <div class="card">
        <h2>Offers for: <?php echo htmlspecialchars($wanted['title']); ?></h2>

        <?php if(mysqli_num_rows($res) == 0): ?>
            <div class="owner-note">No offers yet.</div>
        <?php else: ?>

            <?php while($o = mysqli_fetch_assoc($res)): ?>
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
                            <a href="offer_action.php?offer_id=<?php echo $o['offer_id']; ?>&action=accept" class="btn accept" onclick="return confirm('Accept this offer?');">Accept</a>
                            <a href="offer_action.php?offer_id=<?php echo $o['offer_id']; ?>&action=reject" class="btn reject" onclick="return confirm('Reject this offer?');">Reject</a>
                        <?php else: ?>
                            <div>Status: <?php echo htmlspecialchars($o['status']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>

        <?php endif; ?>

    </div>
</main>
</body>
</html>
