<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = (int)$_SESSION["user_id"];


// =====================================================
// GET REQUEST ID
// =====================================================

if(
    !isset($_GET["request_id"]) ||
    !is_numeric($_GET["request_id"])
)
{
    die("Invalid request ID.");
}

$request_id = (int)$_GET["request_id"];


// =====================================================
// GET ACTION
// =====================================================

if(!isset($_GET["action"]))
{
    die("Invalid action.");
}

$action = strtolower(trim($_GET["action"]));

if($action !== "accept" && $action !== "reject")
{
    die("Invalid action.");
}


// =====================================================
// GET REQUEST + ITEM
// IMPORTANT:
// ONLY ITEM OWNER CAN ACCEPT / REJECT
// =====================================================

$sql = "
    SELECT
        r.request_id,
        r.user_id AS requester_id,
        r.item_id,
        r.request_type,
        r.status AS request_status,

        i.user_id AS owner_id,
        i.title AS item_title,
        i.type AS item_type,
        i.status AS item_status

    FROM requests r

    INNER JOIN items i
        ON i.item_id = r.item_id

    WHERE r.request_id = $request_id
      AND r.request_type = 'Donate'
      AND i.user_id = $user_id

    LIMIT 1
";

$result = mysqli_query($conn, $sql);

if(!$result)
{
    die("Request fetch error: " . mysqli_error($conn));
}


if(mysqli_num_rows($result) == 0)
{
    die("Request not found or you are not the owner of this item.");
}


$request = mysqli_fetch_assoc($result);


// =====================================================
// VARIABLES
// =====================================================

$requester_id  = (int)$request["requester_id"];
$item_id       = (int)$request["item_id"];
$owner_id      = (int)$request["owner_id"];

$item_type     = trim($request["item_type"]);
$item_status   = trim($request["item_status"]);

$request_status = trim($request["request_status"]);
$item_title     = $request["item_title"];


// =====================================================
// SECURITY CHECK
// =====================================================

if($owner_id !== $user_id)
{
    die("You are not allowed to perform this action.");
}


if(strtolower($item_type) !== "donate")
{
    die("This is not a Donate item.");
}


if(strtolower($request["request_type"]) !== "donate")
{
    die("Invalid request type.");
}


// =====================================================
// REQUEST MUST BE PENDING
// =====================================================

if(strtolower($request_status) !== "pending")
{
    header("Location: notifications.php");
    exit();
}


// =====================================================
// ACCEPT
// =====================================================

if($action === "accept")
{

    // =================================================
    // START TRANSACTION
    //
    // Lock the item before changing either table.  That keeps two
    // simultaneous owner clicks from accepting two Donate requests.
    // =================================================

    mysqli_begin_transaction($conn);


    try
    {

        $lockItemSql = "
            SELECT item_id, status
            FROM items
            WHERE item_id = $item_id
              AND user_id = $user_id
              AND type = 'Donate'
            FOR UPDATE
        ";

        $lockItemResult = mysqli_query($conn, $lockItemSql);

        if(!$lockItemResult || mysqli_num_rows($lockItemResult) !== 1)
        {
            throw new Exception("Donation item was not found or you are not its owner.");
        }

        $lockedItem = mysqli_fetch_assoc($lockItemResult);

        if(strtolower(trim($lockedItem['status'])) !== 'available')
        {
            throw new Exception("This donated item is no longer available.");
        }


        $checkAcceptedSql = "
            SELECT request_id
            FROM requests
            WHERE item_id = $item_id
              AND request_type = 'Donate'
              AND status = 'Accepted'
              AND request_id != $request_id
            LIMIT 1
            FOR UPDATE
        ";

        $checkAcceptedResult = mysqli_query($conn, $checkAcceptedSql);

        if(!$checkAcceptedResult)
        {
            throw new Exception(
                "Accepted request check failed: " . mysqli_error($conn)
            );
        }

        if(mysqli_num_rows($checkAcceptedResult) > 0)
        {
            throw new Exception(
                "This donated item has already been accepted by another requester."
            );
        }

        // =============================================
        // 1. ACCEPT CURRENT REQUEST
        // =============================================

        $updateRequestSql = "
            UPDATE requests

            SET status = 'Accepted'

            WHERE request_id = $request_id
              AND status = 'Pending'
        ";

        if(!mysqli_query($conn, $updateRequestSql))
        {
            throw new Exception(
                "Request update failed: "
                . mysqli_error($conn)
            );
        }

        if(mysqli_affected_rows($conn) !== 1)
        {
            throw new Exception("This request is no longer pending.");
        }


        // =============================================
        // 2. UPDATE ITEM STATUS
        //
        // The status condition makes the state transition explicit:
        // Available -> Donated. The row is already locked above.
        // =============================================

        $updateItemSql = "
            UPDATE items

            SET status = 'Donated'

            WHERE item_id = $item_id
              AND user_id = $user_id
              AND type = 'Donate'
              AND status = 'Available'
        ";

        if(!mysqli_query($conn, $updateItemSql))
        {
            throw new Exception(
                "Item status update failed: "
                . mysqli_error($conn)
            );
        }


        // =============================================
        // CHECK ITEM UPDATE
        // =============================================

        $itemAffectedRows = mysqli_affected_rows($conn);

        if($itemAffectedRows == 0)
        {
            /*
             * Check actual item data to give useful error
             */

            $debugSql = "
                SELECT
                    item_id,
                    user_id,
                    type,
                    status

                FROM items

                WHERE item_id = $item_id

                LIMIT 1
            ";

            $debugResult = mysqli_query(
                $conn,
                $debugSql
            );

            if($debugResult && mysqli_num_rows($debugResult) > 0)
            {
                $debugItem = mysqli_fetch_assoc($debugResult);

                throw new Exception(
                    "Item was found, but status was not changed. "
                    . "item_id=" . $debugItem["item_id"]
                    . ", owner=" . $debugItem["user_id"]
                    . ", type=" . $debugItem["type"]
                    . ", status=" . $debugItem["status"]
                );
            }

            throw new Exception(
                "Item #$item_id was not found."
            );
        }

        require_once __DIR__ . '/delivery_workflow.php';
        createDeliveryJob(
            $conn,
            'requests',
            $request_id,
            $item_id,
            $requester_id
        );


        // =============================================
        // 3. REJECT OTHER PENDING REQUESTS
        // =============================================

        $rejectOthersSql = "
            UPDATE requests

            SET status = 'Rejected'

            WHERE item_id = $item_id
              AND request_type = 'Donate'
              AND status = 'Pending'
              AND request_id != $request_id
        ";

        if(!mysqli_query($conn, $rejectOthersSql))
        {
            throw new Exception(
                "Other requests update failed: "
                . mysqli_error($conn)
            );
        }


        // =============================================
        // 4. UPDATE OWNER'S NOTIFICATION
        // =============================================

        $ownerNotificationMessage =
            "You accepted the donation request for your item: "
            . $item_title . ".";

        $ownerNotificationMessage =
            mysqli_real_escape_string(
                $conn,
                $ownerNotificationMessage
            );


        $updateNotificationSql = "
            UPDATE notifications

            SET
                message = '$ownerNotificationMessage',
                status = 'Read'

            WHERE user_id = $user_id
              AND request_id = $request_id
              AND notification_type = 'request'
        ";

        if(!mysqli_query($conn, $updateNotificationSql))
        {
            throw new Exception(
                "Owner notification update failed: "
                . mysqli_error($conn)
            );
        }


        // =============================================
        // 5. SEND NOTIFICATION TO REQUESTER
        // =============================================

        $requesterMessage =
            "Your request for the donated item '"
            . $item_title
            . "' has been accepted.";

        $requesterMessage =
            mysqli_real_escape_string(
                $conn,
                $requesterMessage
            );


        $insertRequesterNotificationSql = "
            INSERT INTO notifications
            (
                user_id,
                request_id,
                notification_type,
                message,
                status
            )

            VALUES
            (
                $requester_id,
                $request_id,
                'request',
                '$requesterMessage',
                'Unread'
            )
        ";

        if(!mysqli_query(
            $conn,
            $insertRequesterNotificationSql
        ))
        {
            throw new Exception(
                "Requester notification failed: "
                . mysqli_error($conn)
            );
        }


        // =============================================
        // 6. COMMIT
        // =============================================

        if(!mysqli_commit($conn))
        {
            throw new Exception(
                "Transaction commit failed."
            );
        }


        // =============================================
        // SUCCESS
        // =============================================

        header(
            "Location: notifications.php?success="
            . urlencode(
                "Donation request accepted successfully. Item status changed to Donated."
            )
        );

        exit();

    }
    catch(Exception $e)
    {

        // =============================================
        // ROLLBACK
        // =============================================

        mysqli_rollback($conn);

        die(
            "Accept failed: "
            . htmlspecialchars($e->getMessage())
        );
    }
}


// =====================================================
// REJECT
// =====================================================

if($action === "reject")
{

    mysqli_begin_transaction($conn);


    try
    {

        // =============================================
        // 1. REJECT REQUEST
        // =============================================

        $rejectRequestSql = "
            UPDATE requests

            SET status = 'Rejected'

            WHERE request_id = $request_id
              AND status = 'Pending'
        ";

        if(!mysqli_query($conn, $rejectRequestSql))
        {
            throw new Exception(
                "Request reject failed: "
                . mysqli_error($conn)
            );
        }


        // =============================================
        // 2. UPDATE OWNER NOTIFICATION
        // =============================================

        $ownerNotificationMessage =
            "You rejected the donation request for your item: "
            . $item_title . ".";

        $ownerNotificationMessage =
            mysqli_real_escape_string(
                $conn,
                $ownerNotificationMessage
            );


        $updateNotificationSql = "
            UPDATE notifications

            SET
                message = '$ownerNotificationMessage',
                status = 'Read'

            WHERE user_id = $user_id
              AND request_id = $request_id
              AND notification_type = 'request'
        ";

        if(!mysqli_query($conn, $updateNotificationSql))
        {
            throw new Exception(
                "Owner notification update failed: "
                . mysqli_error($conn)
            );
        }


        // =============================================
        // 3. NOTIFY REQUESTER
        // =============================================

        $requesterMessage =
            "Your request for the donated item '"
            . $item_title
            . "' has been rejected.";

        $requesterMessage =
            mysqli_real_escape_string(
                $conn,
                $requesterMessage
            );


        $insertRequesterNotificationSql = "
            INSERT INTO notifications
            (
                user_id,
                request_id,
                notification_type,
                message,
                status
            )

            VALUES
            (
                $requester_id,
                $request_id,
                'request',
                '$requesterMessage',
                'Unread'
            )
        ";

        if(!mysqli_query(
            $conn,
            $insertRequesterNotificationSql
        ))
        {
            throw new Exception(
                "Requester notification failed: "
                . mysqli_error($conn)
            );
        }


        // =============================================
        // COMMIT
        // =============================================

        if(!mysqli_commit($conn))
        {
            throw new Exception(
                "Transaction commit failed."
            );
        }


        // =============================================
        // SUCCESS
        // =============================================

        header(
            "Location: notifications.php?success="
            . urlencode(
                "Donation request rejected successfully."
            )
        );

        exit();

    }
    catch(Exception $e)
    {

        mysqli_rollback($conn);

        die(
            "Reject failed: "
            . htmlspecialchars($e->getMessage())
        );
    }
}

?>
