<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Create Donation Post | EcoCycle</title>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>

<style>

*{
    box-sizing:border-box;
}

body{
    margin:0;
    font-family:Arial, sans-serif;
    background:#f5f7f6;
    color:#222;
}


/* =========================
   SIDEBAR
========================= */

.sidebar{
    position:fixed;
    left:0;
    top:0;

    width:230px;
    height:100vh;

    background:#064d3b;
    color:white;

    padding:25px 12px;

    z-index:1000;
}

.logo-area{
    text-align:center;
    margin-bottom:30px;
}

.logo-icon{
    font-size:42px;
    margin-bottom:8px;
}

.logo-area h2{
    margin:0;
    font-size:20px;
}

.logo-area p{
    margin:7px 0 0;
    font-size:13px;
    color:#d8eee5;
}


/* =========================
   MENU
========================= */

.menu{
    margin-top:10px;
}

.menu a{
    display:flex;
    align-items:center;

    gap:14px;

    color:white;
    text-decoration:none;

    padding:13px 13px;

    margin-bottom:5px;

    border-radius:8px;

    font-size:14px;

    transition:0.2s;
}

.menu a i{
    width:22px;
    text-align:center;
    font-size:17px;
}

.menu a:hover{
    background:#0b7458;
}

.menu a.active{
    background:#0b9b6c;
    font-weight:bold;
}


/* =========================
   NOTIFICATION
========================= */

.notification{
    margin-left:auto;

    background:#ef4444;

    width:22px;
    height:22px;

    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:11px;
    font-weight:bold;
}


/* =========================
   SIDEBAR BOTTOM
========================= */

.sidebar-bottom{
    position:absolute;

    bottom:25px;
    left:12px;
    right:12px;

    border:1px solid #168a68;

    border-radius:8px;

    padding:17px 10px;

    text-align:center;

    color:#d9f4e9;

    font-size:13px;

    line-height:1.5;
}

.sidebar-bottom i{
    font-size:27px;
    margin-bottom:8px;
}


/* =========================
   TOP HEADER
========================= */

.topbar{
    position:fixed;

    top:0;
    left:230px;
    right:0;

    height:70px;

    background:white;

    border-bottom:1px solid #e5e5e5;

    display:flex;
    align-items:center;
    justify-content:flex-end;

    padding:0 30px;

    z-index:900;
}

.top-right{
    display:flex;
    align-items:center;
    gap:20px;
}

.bell{
    position:relative;

    font-size:20px;
    color:#333;
}

.bell-badge{
    position:absolute;

    top:-8px;
    right:-8px;

    width:19px;
    height:19px;

    background:#ef4444;
    color:white;

    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:10px;
    font-weight:bold;
}

.profile-mini{
    display:flex;
    align-items:center;
    gap:10px;

    font-weight:bold;
}

.profile-circle{
    width:38px;
    height:38px;

    border-radius:50%;

    background:#08734f;
    color:white;

    display:flex;
    align-items:center;
    justify-content:center;

    font-weight:bold;

    font-size:13px;
}


/* =========================
   MAIN
========================= */

.main{
    margin-left:230px;

    padding:105px 45px 50px;

    width:calc(100% - 230px);
    max-width:none;
}


/* =========================
   BACK
========================= */

.back{
    color:#168a45;

    text-decoration:none;

    font-size:14px;

    font-weight:bold;

    display:inline-block;

    margin-bottom:20px;
}

.back:hover{
    text-decoration:underline;
}


/* =========================
   HEADER
========================= */

.page-header{
    margin-bottom:25px;
}

.page-header h1{
    margin:0 0 7px;

    font-size:28px;
}

.page-header p{
    margin:0;

    color:#777;

    font-size:14px;
}


/* =========================
   FORM CARD
========================= */

.form-card{
    background:white;

    border:1px solid #ddd;

    border-radius:12px;

    padding:28px 30px 22px;

    width:100%;
    max-width:none;
}

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 26px; }
.field-with-icon { display: grid; grid-template-columns: 38px 1fr; gap: 10px; align-items: start; }
.field-icon { width: 38px; height: 38px; margin-top: 27px; display: flex; align-items: center; justify-content: center; border-radius: 7px; background: #eaf7ef; color: #159b64; font-size: 17px; }
.field-with-icon .form-group { min-width: 0; }
.field-full { display: grid; grid-template-columns: 38px 1fr; gap: 10px; }
.field-full .field-icon { margin-top: 27px; }
.field-full .form-group { min-width: 0; }
.form-actions { display: flex; justify-content: flex-end; align-items: center; gap: 8px; padding-top: 18px; margin-top: 5px; border-top: 1px solid #e1e8e3; }
.upload-box { display: block; padding: 18px; border: 1px dashed #c7d2cd; border-radius: 7px; background: #fff; text-align: center; cursor: pointer; }
.upload-box label { display: block; margin: 0; cursor: pointer; }
.upload-box i { display: block; margin-bottom: 7px; color: #159b64; font-size: 24px; }
.upload-box span { display: block; color: #38524a; font-size: 13px; }
.upload-box small { display: block; margin-top: 5px; color: #68736d; font-size: 11px; }
.upload-box input[type="file"] { display: none; }

.my-posts { width:100%; max-width:none; margin-top:25px; }
.my-post { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 16px 18px; margin-bottom: 12px; background: #fff; border: 1px solid #e1e8e3; border-radius: 9px; }
.my-post h3 { margin: 0 0 6px; font-size: 16px; color: #064d3b; }
.my-post p { margin: 0; color: #68736d; font-size: 13px; }
.post-actions { display: flex; gap: 8px; flex-shrink: 0; }
.post-actions a, .post-actions button { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 12px; border: 0; border-radius: 6px; font-size: 12px; font-weight: bold; text-decoration: none; }
.post-actions a { background: #e8f7ef; color: #087b52; }
.post-actions button { background: #fff0f0; color: #c62828; cursor: pointer; }
.post-actions button:hover { background: #ffe0e0; }
.cancel-button { display: inline-flex; align-items: center; height: 45px; margin-left: 8px; padding: 0 20px; border-radius: 7px; background: #eef2f1; color: #38524a; text-decoration: none; font-size: 14px; font-weight: bold; }
@media(max-width:550px){ .my-post { align-items: flex-start; flex-direction: column; } .post-actions { width: 100%; } .post-actions a, .post-actions button { flex: 1; justify-content: center; } .cancel-button { margin: 10px 0 0; } }
@media(max-width:800px){ .form-row { grid-template-columns: 1fr; gap: 0; } .form-card { padding: 22px 18px; } }


/* =========================
   INFO
========================= */

.info{
    background:#eaf7ef;

    color:#176b3b;

    padding:14px;

    border-radius:7px;

    font-size:13px;

    margin-bottom:22px;

    line-height:1.5;
}


/* =========================
   MESSAGE
========================= */

.message{
    padding:12px;

    border-radius:7px;

    margin-bottom:20px;

    font-size:14px;

    font-weight:bold;
}

.message.success{
    background:#eaf7ef;

    color:#176b3b;

    border:1px solid #bde5cb;
}

.message.error{
    background:#fff0f0;

    color:#c62828;

    border:1px solid #ffd2d2;
}


/* =========================
   FORM
========================= */

.form-group{
    margin-bottom:20px;
}

label{
    display:block;

    margin-bottom:8px;

    font-size:14px;

    font-weight:bold;
}

input,
select,
textarea{
    width:100%;

    border:1px solid #ccc;

    border-radius:7px;

    padding:11px 12px;

    font-size:14px;

    font-family:Arial,sans-serif;

    background:white;
}

input{
    height:45px;
}

textarea{
    min-height:120px;

    resize:vertical;

    line-height:1.5;
}

input:focus,
select:focus,
textarea:focus{
    outline:none;

    border-color:#168a45;
}


/* =========================
   FILE
========================= */

input[type="file"]{
    padding:10px;

    cursor:pointer;
}

.file-note{
    color:#777;

    font-size:12px;

    margin-top:6px;
}


/* =========================
   BUTTON
========================= */

button{
    height:45px;

    padding:0 25px;

    border:none;

    border-radius:7px;

    background:#168a45;

    color:white;

    font-size:14px;

    font-weight:bold;

    cursor:pointer;
}

button:hover{
    background:#117239;
}


/* =========================
   MOBILE
========================= */

@media(max-width:800px){

    .sidebar{
        width:70px;
        padding:20px 8px;
    }

    .logo-area h2,
    .logo-area p,
    .menu a span,
    .sidebar-bottom{
        display:none;
    }

    .logo-icon{
        font-size:30px;
    }

    .menu a{
        justify-content:center;
        padding:14px 5px;
    }

    .menu a i{
        font-size:18px;
    }

    .notification{
        display:none;
    }

    .topbar{
        left:70px;
    }

    .main{
        margin-left:70px;
        width:calc(100% - 70px);

        padding:100px 20px 40px;
    }

}

@media(max-width:550px){

    .form-card{
        padding:20px;
    }

    .top-right{
        gap:10px;
    }

    .profile-mini > span{
        display:none;
    }

}

</style>
<link rel="stylesheet" href="assets/css/ngo_sidebar.css">

</head>


<body>


<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar ngo-sidebar">


    <div class="logo-area">

        <div class="logo-icon">

            <i class="fa-solid fa-hand-holding-heart"></i>

        </div>

        <h2>
            Helping Hands NGO
        </h2>

        <p>
            NGO Panel
        </p>

    </div>


    <nav class="menu">


        <!-- DASHBOARD -->

        <a href="index.php?url=ngo/dashboard">

            <i class="fa-solid fa-house"></i>

            <span>
                Dashboard
            </span>

        </a>


        <!-- DONATION REQUESTS -->

        <a href="index.php?url=ngo/donation-requests">

            <i class="fa-solid fa-gift"></i>

            <span>
                Donation Requests
            </span>

        </a>


        <!-- ACCEPTED DONATIONS -->

        <a href="index.php?url=ngo/accepted-donations">

            <i class="fa-solid fa-circle-check"></i>

            <span>
                Accepted Donations
            </span>

        </a>


        <!-- PICKUP STATUS -->

<a href="index.php?url=ngo/pickups">

            <i class="fa-solid fa-truck"></i>

            <span>
                Pickup Status
            </span>

        </a>


        <!-- BROWSE WANTED POSTS -->

        <a href="index.php?url=ngo/browse-wanted">

            <i class="fa-solid fa-magnifying-glass"></i>

            <span>
                Browse Wanted Posts
            </span>

        </a>


        <!-- CREATE POST ACTIVE -->

        <a
            href="index.php?url=ngo/create-post"
            class="active"
        >

            <i class="fa-solid fa-square-plus"></i>

            <span>
                Create Post
            </span>

        </a>


        <!-- NOTIFICATIONS -->

        <a href="index.php?url=ngo/notifications">

            <i class="fa-solid fa-bell"></i>

            <span>
                Notifications
            </span>

            <span class="notification">
                4
            </span>

        </a>


        <!-- NGO PROFILE -->

        <a href="index.php?url=ngo/profile">

            <i class="fa-solid fa-user"></i>

            <span>
                NGO Profile
            </span>

        </a>


        <!-- LOGOUT -->

        <a href="index.php?url=auth/logout">

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>
                Logout
            </span>

        </a>


    </nav>


    <div class="sidebar-bottom">

        <i class="fa-solid fa-hand-holding-heart"></i>

        <br>

        Together we can

        <br>

        make a difference.

    </div>


</aside>



<!-- =========================
     TOP BAR
========================= -->

<header class="topbar">

    <div class="top-right">


        <div class="bell">

            <i class="fa-solid fa-bell"></i>

            <span class="bell-badge">
                4
            </span>

        </div>


        <div class="profile-mini">

            <div class="profile-circle">
                HH
            </div>

            <span>
                Helping Hands NGO
            </span>

            <i class="fa-solid fa-chevron-down"></i>

        </div>

    </div>

</header>



<!-- =========================
     MAIN CONTENT
========================= -->

<main class="main">


    <a
        class="back"
        href="index.php?url=ngo/dashboard"
    >

        ← Back to Dashboard

    </a>


    <div class="page-header">

        <h1>
            <?php echo $editPost ? "Edit Donation Request Post" : "Create Donation Request Post"; ?>
        </h1>

        <p>
            Create a donation post to let people know what your NGO needs.
        </p>

    </div>


    <div class="form-card">


        <div class="info">

            <i class="fa-solid fa-circle-info"></i> <strong>Donation Request Post:</strong>

            Tell people what items your NGO needs.
            They can see this post and donate the requested items.

        </div>


        <!-- =========================
             MESSAGE
        ========================= -->

        <?php if ($message !== ""): ?>

            <div
                class="message <?php echo htmlspecialchars($message_type); ?>"
            >

                <?php
                echo htmlspecialchars($message);
                ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <div class="form-row">

            <div class="field-with-icon">
            <div class="field-icon"><i class="fa-solid fa-bag-shopping"></i></div>
            <div class="form-group">

                <label for="title">
                    What donation is needed?
                </label>

                <input
                    id="title"
                    name="title"
                    type="text"
                    placeholder="Example: School Bags"
                    value="<?php
                    echo htmlspecialchars($_POST["title"] ?? $editName);
                    ?>"
                    required
                >

            </div>
            </div>

            <div class="field-with-icon">
            <div class="field-icon"><i class="fa-solid fa-layer-group"></i></div>
            <div class="form-group">

                <label for="quantity">
                    Quantity
                </label>

                <input
                    id="quantity"
                    name="quantity"
                    type="text"
                    placeholder="Example: 400 pieces"
                    value="<?php
                    echo htmlspecialchars($_POST["quantity"] ?? $editQuantity);
                    ?>"
                    required
                >

            </div>
            </div>
            </div>

            <div class="form-row">
            <div class="field-with-icon">
            <div class="field-icon"><i class="fa-solid fa-tag"></i></div>
            <div class="form-group">

                <label for="category">
                    Category
                </label>

                <input
                    id="category"
                    name="category"
                    type="text"
                    placeholder="Example: Education"
                    value="<?php
                    echo htmlspecialchars($_POST["category"] ?? ($editPost["category"] ?? ""));
                    ?>"
                    required
                >

            </div>
            </div>

            <div class="field-with-icon">
            <div class="field-icon"><i class="fa-solid fa-location-dot"></i></div>
            <div class="form-group">

                <label for="location">
                    Location
                </label>

                <input
                    id="location"
                    name="location"
                    type="text"
                    placeholder="Example: Mirpur, Dhaka"
                    value="<?php
                    echo htmlspecialchars($_POST["location"] ?? ($editPost["location"] ?? ""));
                    ?>"
                    required
                >

            </div>
            </div>
            </div>

            <div class="field-full">
            <div class="field-icon"><i class="fa-solid fa-file-lines"></i></div>
            <div class="form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    placeholder="Describe what your NGO needs and why it is needed..."
                    <?php echo $editPost ? "" : "required"; ?>
                ><?php
                echo htmlspecialchars(
                    $_POST["description"] ?? ($editPost["description"] ?? "")
                );
                ?></textarea>

            </div>
            </div>

            <div class="field-full">
            <div class="field-icon"><i class="fa-solid fa-image"></i></div>
            <div class="form-group">

                <label for="image">
                    Image (Optional)
                </label>

                <div class="upload-box">
                    <label for="image">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Click to upload an image</span>
                    <small>JPG, JPEG, PNG, GIF or WEBP (Max 5MB)</small>
                    </label>
                <input
                    id="image"
                    name="image"
                    type="file"
                    accept="image/*"
                >
                </div>

            </div>
            </div>


            <!-- BUTTON -->

            <input type="hidden" name="post_action" value="<?php echo $editPost ? "edit" : "create"; ?>">
            <?php if ($editPost): ?><input type="hidden" name="item_id" value="<?php echo (int)$editPost["item_id"]; ?>"><?php endif; ?>

            <div class="form-actions">
            <button type="submit">

                <i class="fa-solid fa-plus"></i>

                &nbsp; <?php echo $editPost ? "Update Post" : "Create Post"; ?>

            </button>

            <?php if ($editPost): ?>
                <a class="cancel-button" href="index.php?url=ngo/create-post">Cancel</a>
            <?php else: ?>
                <button type="reset" style="background:#eef2f1; color:#38524a; margin-left:8px;">Cancel</button>
            <?php endif; ?>
            </div>


        </form>


    </div>

    <section class="my-posts">
        <div class="page-header"><h2>My Donation Posts</h2><p>Edit or delete posts created by your NGO.</p></div>
        <?php if ($myPostsResult && count($myPostsResult) > 0): ?>
            <?php foreach($myPostsResult as $post): ?>
                <article class="my-post">
                    <?php if (!empty($post["image"]) && is_file(UPLOAD_PATH . "/" . $post["image"])): ?>
                        <img src="uploads/<?php echo rawurlencode($post["image"]); ?>" alt="<?php echo htmlspecialchars($post["title"]); ?>" style="width:90px;height:70px;object-fit:cover;border-radius:8px;">
                    <?php endif; ?>
                    <div>
                        <h3><?php echo htmlspecialchars($post["title"]); ?></h3>
                        <p><?php echo htmlspecialchars($post["category"]); ?> · <?php echo htmlspecialchars($post["location"]); ?> · <?php echo htmlspecialchars($post["status"]); ?></p>
                    </div>
                    <div class="post-actions">
                        <a href="index.php?url=ngo/create-post&edit_id=<?php echo (int)$post["item_id"]; ?>"><i class="fa-solid fa-pen"></i> Edit</a>
                        <form method="POST" onsubmit="return confirm('Delete this donation post?');">
                            <input type="hidden" name="post_action" value="delete">
                            <input type="hidden" name="item_id" value="<?php echo (int)$post["item_id"]; ?>">
                            <button type="submit"><i class="fa-solid fa-trash"></i> Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="my-post"><p>No donation posts created yet.</p></div>
        <?php endif; ?>
    </section>


</main>


</body>

</html>
