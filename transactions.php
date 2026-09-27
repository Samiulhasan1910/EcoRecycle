<?php

session_start();

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = $_SESSION["user_id"];

$sql = "SELECT t.*, i.title, i.type, u.name AS other_party
        FROM transactions t
        LEFT JOIN items i ON i.item_id = t.item_id
        LEFT JOIN users u ON u.user_id = CASE
            WHEN t.buyer_id = '$user_id' THEN t.seller_id
            ELSE t.buyer_id
        END
        WHERE t.buyer_id = '$user_id' OR t.seller_id = '$user_id'
        ORDER BY t.transaction_id DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Transactions | EcoCycle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        .main{
            margin-left:230px;
            padding:105px 45px 50px;
            max-width:1200px;
        }
        .header{
            margin-bottom:25px;
        }
        .header h1{
            font-size:28px;
            margin-bottom:8px;
        }
        .header p{
            color:#777;
            font-size:14px;
        }
        .grid{
            display:grid;
            grid-template-columns:repeat(3, minmax(0, 1fr));
            gap:20px;
        }
        .card{
            background:white;
            border:1px solid #e5e5e5;
            border-radius:12px;
            padding:20px;
        }
        .title{
            font-size:20px;
            font-weight:bold;
            margin-bottom:12px;
        }
        .meta{
            font-size:13px;
            color:#666;
            margin-bottom:8px;
        }
        .meta strong{
            color:#333;
        }
        .amount{
            font-size:18px;
            font-weight:bold;
            color:#168a45;
            margin:12px 0;
        }
        .status-pill{
            display:inline-block;
            padding:5px 10px;
            border-radius:20px;
            background:#eaf7ef;
            color:#168a45;
            font-size:11px;
            font-weight:bold;
        }
        .empty{
            background:white;
            border:1px solid #e5e5e5;
            border-radius:12px;
            padding:35px;
            text-align:center;
            color:#777;
        }
        @media(max-width:950px){
            .grid{ grid-template-columns:repeat(2, minmax(0, 1fr)); }
        }
        @media(max-width:750px){
            .main{ margin-left:70px; padding-left:20px; padding-right:20px; }
            .grid{ grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<?php include("header_sidebar.php"); ?>

<main class="main">
    <div class="header">
        <h1>Transactions</h1>
        <p>All completed payments and purchase records.</p>
    </div>

    <?php if(!$result || mysqli_num_rows($result) == 0): ?>
        <div class="empty">No transaction has been recorded yet.</div>
    <?php else: ?>
        <div class="grid">
            <?php while($txn = mysqli_fetch_assoc($result)): ?>
                <?php
                $isBuyer = ($txn["buyer_id"] == $user_id);
                $otherParty = $txn["other_party"] ?? "User";
                $status = $txn["status"] ?? "Completed";
                ?>

                <div class="card">
                    <div class="title"><?php echo htmlspecialchars($txn["title"] ?? "Item"); ?></div>

                    <div class="meta"><strong>Type:</strong> <?php echo htmlspecialchars($txn["type"] ?? ""); ?></div>
                    <div class="meta"><strong><?php echo $isBuyer ? 'Seller' : 'Buyer'; ?>:</strong> <?php echo htmlspecialchars($otherParty); ?></div>
                    <div class="meta"><strong>Method:</strong> <?php echo htmlspecialchars($txn["payment_method"] ?? ""); ?></div>
                    <div class="meta"><strong>Number:</strong> <?php echo htmlspecialchars($txn["receiver_number"] ?? ""); ?></div>
                    <div class="meta"><strong>PIN:</strong> ****</div>
                    <div class="meta"><strong>Date:</strong> <?php echo htmlspecialchars($txn["created_at"] ?? ""); ?></div>

                    <div class="amount"><?php echo number_format(floatval($txn["amount"] ?? 0), 2, '.', ''); ?> BDT</div>

                    <span class="status-pill"><?php echo htmlspecialchars($status); ?></span>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</main>

</body>
</html>
