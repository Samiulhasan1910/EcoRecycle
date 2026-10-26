<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>Post Wanted Item | EcoCycle</title>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>


<!-- COMMON NAVBAR + SIDEBAR CSS -->

<link rel="stylesheet" href="assets/css/style.css">


<!-- =========================
     POST WANTED PAGE CSS ONLY
     ========================= -->

<style>

.main{
    margin-left:230px;

    padding:105px 45px 50px;

    max-width:1100px;
}


/* =========================
   HEADER
   ========================= */

.header{
    margin-bottom:25px;
}

.header h1{
    font-size:28px;

    margin-bottom:7px;
}

.header p{
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

    padding:30px;

    max-width:800px;
}

.form-group{
    margin-bottom:20px;
}

label{
    display:block;

    font-size:14px;

    font-weight:bold;

    margin-bottom:8px;
}

input,
select,
textarea{
    width:100%;

    min-height:45px;

    border:1px solid #ccc;

    border-radius:7px;

    padding:10px 12px;

    font-size:14px;

    background:white;

    font-family:Arial,sans-serif;
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
   FILE INPUT
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
   BUTTONS
   ========================= */

.buttons{
    display:flex;

    gap:10px;
}

button,
.cancel{
    height:45px;

    padding:0 25px;

    border-radius:7px;

    font-size:14px;

    font-weight:bold;
}

button{
    border:none;

    background:#168a45;

    color:white;

    cursor:pointer;
}

button:hover{
    background:#117239;
}

.cancel{
    border:1px solid #ccc;

    background:white;

    color:#444;

    text-decoration:none;

    display:flex;

    align-items:center;

    justify-content:center;
}


/* =========================
   MOBILE
   ========================= */

@media(max-width:800px)
{
    .main{
        margin-left:70px;

        padding:100px 20px;
    }

    .form-card{
        max-width:100%;
    }
}

@media(max-width:550px)
{
    .form-card{
        padding:20px;
    }

    .buttons{
        flex-direction:column;
    }
}

</style>

</head>


<body>


<!-- =========================
     COMMON HEADER + SIDEBAR
     ========================= -->

<?php require APP_PATH . '/views/layouts/header_sidebar.php'; ?>


<!-- =========================
     MAIN CONTENT
     ========================= -->

<main class="main">


    <!-- BACK -->

    <a
        href="index.php?url=dashboard"
        class="back"
    >
        ← Back
    </a>


    <!-- HEADER -->

    <div class="header">

        <h1>
            Post a Wanted Item
        </h1>

        <p>
            Tell other EcoCycle users what item you need.
        </p>

    </div>


    <!-- FORM CARD -->

    <div class="form-card">


        <!-- INFO -->

        <div class="info">

            <strong>Wanted Post:</strong>

            Post the item you are looking for.
            Other EcoCycle users can see your request.

        </div>


        <!-- MESSAGE -->

        <?php

        if($message != "")
        {

        ?>

            <div class="message <?php echo $messageType; ?>">

                <?php

                echo htmlspecialchars(
                    $message
                );

                ?>

            </div>

        <?php

        }

        ?>


        <!-- FORM -->

        <form
            method="post"
            enctype="multipart/form-data"
        >


            <!-- TITLE -->

            <div class="form-group">

                <label>
                    Item Title
                </label>

                <input
                    type="text"
                    name="title"
                    placeholder="Example: Bicycle"
                    required
                >

            </div>


            <!-- CATEGORY -->

            <div class="form-group">

                <label>
                    Category
                </label>

                <select
                    name="category"
                    required
                >

                    <option value="">
                        Select Category
                    </option>

                    <option value="Electronics">
                        Electronics
                    </option>

                    <option value="Furniture">
                        Furniture
                    </option>

                    <option value="Clothing">
                        Clothing
                    </option>

                    <option value="Books">
                        Books
                    </option>

                    <option value="Home Appliances">
                        Home Appliances
                    </option>

                    <option value="Kitchen">
                        Kitchen
                    </option>

                    <option value="Sports">
                        Sports
                    </option>

                    <option value="Toys">
                        Toys
                    </option>

                    <option value="Others">
                        Others
                    </option>

                </select>

            </div>


            <!-- LOCATION -->

            <div class="form-group">

                <label>
                    Preferred Location
                </label>

                <input
                    type="text"
                    name="location"
                    placeholder="Example: Mirpur, Dhaka"
                    required
                >

            </div>


            <!-- DESCRIPTION -->

            <div class="form-group">

                <label>
                    Description / Requirements
                </label>

                <textarea
                    name="description"
                    placeholder="Describe your requirements, preferred condition, size, color, brand, or any other details..."
                    rows="5"
                ></textarea>

                <div class="file-note">

                    Mention any specific requirements you have for the item.

                </div>

            </div>


            <!-- IMAGE -->

            <div class="form-group">

                <label>
                    Image (Optional)
                </label>

                <input
                    type="file"
                    name="image"
                    accept="image/*"
                >

                <div class="file-note">

                    You can upload an image if you want.
                    This is optional.

                </div>

            </div>


            <!-- BUTTONS -->

            <div class="buttons">

                <button type="submit">
                    Post
                </button>


                <a
                    href="index.php?url=dashboard"
                    class="cancel"
                >
                    Cancel
                </a>

            </div>


        </form>

    </div>


</main>


</body>

</html>
