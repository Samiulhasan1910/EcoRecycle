
<?php

session_start();

if (!isset($_SESSION["ngo_id"])) {
    header("Location: login.php");
    exit();
}

include("db.php");

$ngo_id = (int)$_SESSION["ngo_id"];


/* =========================
   GET NGO PICKUP REQUESTS
   ========================= */

$sql = "SELECT
            p.pickup_id,
            p.request_id,
            p.delivery_id,
            p.date,
            p.time,
            p.status,

            r.ngo_id,
            r.item_id,

            i.title,
            i.category,
            i.location,

            d.name AS delivery_name,
            d.phone AS delivery_phone,
            d.location AS delivery_location

        FROM pickups p

        INNER JOIN requests r
            ON p.request_id = r.request_id

        INNER JOIN items i
            ON r.item_id = i.item_id

        INNER JOIN delivery d
            ON p.delivery_id = d.delivery_id

        WHERE r.ngo_id = $ngo_id

        ORDER BY p.pickup_id DESC";


$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html>

<head>

    <title>Pickup status</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 30px;
        }

        .container {
            width: 95%;
            margin: auto;
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: #333;
            color: white;
            padding: 12px;
        }

        td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }

        tr:hover {
            background: #f1f1f1;
        }

        .pending {
            color: #856404;
            font-weight: bold;
        }

        .accepted {
            color: #155724;
            font-weight: bold;
        }

        .collected {
            color: #004085;
            font-weight: bold;
        }

        .delivered {
            color: #28a745;
            font-weight: bold;
        }

        .cancelled {
            color: #dc3545;
            font-weight: bold;
        }

        .no-data {
            padding: 20px;
            text-align: center;
            color: #666;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background: #333;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .back-btn:hover {
            background: #555;
        }

    </style>
    <link rel="stylesheet" href="ngo_sidebar.css">

</head>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">


<body>

<?php include 'ngo_sidebar.php'; ?>

<div class="container ngo-content">


    <a href="ngo_dashboard.php" class="back-btn">
        Back to Dashboard
    </a>


    <h2>Pickup Requests</h2>


    <table>

        <tr>

            <th>Item</th>

            <th>Category</th>

            <th>Pickup Location</th>

            <th>Delivery Person</th>

            <th>Phone</th>

            <th>Delivery Location</th>

            <th>Date</th>

            <th>Time</th>

            <th>Status</th>

        </tr>


        <?php

        if ($result && mysqli_num_rows($result) > 0) {

            while ($row = mysqli_fetch_assoc($result)) {

                $status = strtolower(trim($row["status"]));

        ?>

        <tr>

            <td>
                <?php echo htmlspecialchars($row["title"]); ?>
            </td>


            <td>
                <?php echo htmlspecialchars($row["category"]); ?>
            </td>


            <td>
                <?php echo htmlspecialchars($row["location"]); ?>
            </td>


            <td>
                <?php echo htmlspecialchars($row["delivery_name"]); ?>
            </td>


            <td>
                <?php echo htmlspecialchars($row["delivery_phone"]); ?>
            </td>


            <td>
                <?php echo htmlspecialchars($row["delivery_location"]); ?>
            </td>


            <td>
                <?php echo htmlspecialchars($row["date"]); ?>
            </td>


            <td>
                <?php echo htmlspecialchars($row["time"]); ?>
            </td>


            <td>

                <?php

                if ($status == "pending") {

                    echo '<span class="pending">Pending</span>';

                } elseif ($status == "accepted") {

                    echo '<span class="accepted">Accepted</span>';

                } elseif ($status == "collected") {

                    echo '<span class="collected">Collected</span>';

                } elseif ($status == "delivered") {

                    echo '<span class="delivered">Delivered</span>';

                } elseif ($status == "cancelled") {

                    echo '<span class="cancelled">Cancelled</span>';

                } else {

                    echo htmlspecialchars($row["status"]);

                }

                ?>

            </td>

        </tr>


        <?php

            }

        } else {

        ?>

        <tr>

            <td colspan="9" class="no-data">

                No pickup requests found.

            </td>

        </tr>

        <?php

        }

        ?>

    </table>


</div>


</body>

</html>

