<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>
My Requests | EcoCycle
</title>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<link
    rel="stylesheet"
    href="assets/css/style.css"
>

<style>

.main{
    margin-left:230px;
    padding:105px 45px 50px;
    max-width:1100px;
}

.page-title{
    font-size:28px;
    font-weight:bold;
    margin-bottom:8px;
    color:#222;
}

.page-subtitle{
    font-size:14px;
    color:#777;
    margin-bottom:30px;
}

.request-card{
    background:#ffffff;
    border:1px solid #ddd;
    border-radius:12px;
    margin-bottom:20px;
    overflow:hidden;
    box-shadow:0 4px 15px rgba(0,0,0,0.04);
}

.request-content{
    padding:22px;
}

.request-title{
    font-size:21px;
    font-weight:bold;
    color:#222;
    margin-bottom:15px;
}

.request-detail{
    font-size:14px;
    color:#666;
    margin-bottom:9px;
}

.request-detail strong{
    color:#222;
}

.status{
    display:inline-block;
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
}

.status-pending{
    background:#fff5d6;
    color:#a87500;
}

.status-accepted{
    background:#e8f7ed;
    color:#168a45;
}

.status-rejected{
    background:#fdeaea;
    color:#d93025;
}

.status-other{
    background:#eeeeee;
    color:#555;
}

.request-actions{
    margin-top:18px;
    padding-top:16px;
    border-top:1px solid #eee;
}

.action-btn{
    display:inline-block;
    background:#168a45;
    color:white;
    padding:9px 18px;
    border-radius:7px;
    text-decoration:none;
    font-size:13px;
    font-weight:bold;
    border:none;
    cursor:pointer;
}

.action-btn:hover{
    background:#117239;
}

.empty-box{
    background:white;
    border:1px solid #ddd;
    border-radius:12px;
    padding:40px;
    text-align:center;
    color:#777;
}

@media(max-width:750px)
{
    .main{
        margin-left:70px;
        padding:100px 20px 40px;
    }
}

</style>

</head>


<body>

<?php require APP_PATH . '/views/layouts/header_sidebar.php'; ?>


<main class="main">


<div class="page-title">
    My Requests
</div>


<div class="page-subtitle">
    Here you can see all the items you have requested.
</div>


<?php

if(count($result) == 0)
{

?>

<div class="empty-box">

    You have not requested any item yet.

</div>

<?php

}
else
{

    foreach($result as $row)
    {

        $requestStatus = strtolower(
            trim($row["status"] ?? "")
        );

?>

<div class="request-card">


<div class="request-content">


<div class="request-title">

<?php

echo htmlspecialchars(
    $row["title"] ?? "Item"
);

?>

</div>


<div class="request-detail">

<strong>Category:</strong>

<?php

echo htmlspecialchars(
    $row["category"] ?? ""
);

?>

</div>


<div class="request-detail">

<strong>Type:</strong>

<?php

echo htmlspecialchars(
    $row["item_type"] ?? ""
);

?>

</div>


<div class="request-detail">

<strong>Location:</strong>

<?php

echo htmlspecialchars(
    $row["location"] ?? ""
);

?>

</div>


<div class="request-detail">

<strong>Request Type:</strong>

<?php

echo htmlspecialchars(
    $row["request_type"] ?? ""
);

?>

</div>


<div class="request-detail">

<strong>Request Status:</strong>

<?php

if($requestStatus === "pending")
{

?>

<span class="status status-pending">
    Pending
</span>

<?php

}
elseif($requestStatus === "accepted")
{

?>

<span class="status status-accepted">
    Accepted
</span>

<?php

}
elseif($requestStatus === "rejected")
{

?>

<span class="status status-rejected">
    Rejected
</span>

<?php

}
else
{

?>

<span class="status status-other">

<?php

echo htmlspecialchars(
    $row["status"] ?? ""
);

?>

</span>

<?php

}

?>

</div>


<!-- =================================================
     ACTIONS
================================================= -->

<div class="request-actions">


<?php

/*
=====================================================
IMPORTANT

VIEW BUTTON MUST SEND:

back=my_requests

So index.php?url=items/view knows that the user came
from My Requests.
=====================================================
*/

if(
    !empty($row["item_id"]) &&
    (int)$row["item_id"] > 0
)
{

?>

<a
    href="index.php?url=items/view&id=<?php echo (int)$row["item_id"]; ?>&back=my_requests"
    class="action-btn"
>
    View
</a>

<?php

}
else
{

?>

<span style="color:#999;font-size:13px;">
    Item no longer available
</span>

<?php

}

?>

</div>


</div>

</div>


<?php

    }

}

?>


</main>

</body>

</html>