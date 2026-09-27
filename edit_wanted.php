<?php

session_start();

if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}

include("db.php");

$user_id = (int)$_SESSION["user_id"];
$name = $_SESSION["name"] ?? "User";

$message = "";
$messageType = "";


/* =========================
   CHECK WANTED ID
========================= */

if(
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
)
{
    header("Location: wanted_items.php");
    exit();
}

$wanted_id = (int)$_GET["id"];

if($wanted_id <= 0)
{
    header("Location: wanted_items.php");
    exit();
}


/* =========================
   GET WANTED POST
   ONLY OWNER CAN EDIT
========================= */

$sql = "SELECT *
        FROM wanted_posts
        WHERE wanted_id='$wanted_id'
        AND user_id='$user_id'
        LIMIT 1";

$result = mysqli_query($conn,$sql);

if(!$result || mysqli_num_rows($result) == 0)
{
    header("Location: wanted_items.php");
    exit();
}

$wanted = mysqli_fetch_assoc($result);


/* =========================
   PAYMENT COMPLETED
   PAID ITEM CANNOT BE EDITED
========================= */

$currentStatus = strtolower(
    trim(
        $wanted["status"] ?? ""
    )
);

if(in_array($currentStatus, ["accepted", "paid"], true))
{
    header("Location: wanted_items.php");
    exit();
}


/* =========================
   UPDATE WANTED POST
========================= */

if($_SERVER["REQUEST_METHOD"] == "POST")
{

    /* =========================
       CHECK AGAIN BEFORE UPDATE
       AGAINST PAID STATUS
    ========================= */

    $checkSql = "SELECT status
                 FROM wanted_posts
                 WHERE wanted_id='$wanted_id'
                 AND user_id='$user_id'
                 LIMIT 1";

    $checkResult = mysqli_query(
        $conn,
        $checkSql
    );

    if($checkResult && mysqli_num_rows($checkResult) > 0)
    {
        $latestWanted =
            mysqli_fetch_assoc(
                $checkResult
            );

        $latestStatus = strtolower(
            trim(
                $latestWanted["status"] ?? ""
            )
        );

        if(in_array($latestStatus, ["accepted", "paid"], true))
        {
            header(
                "Location: wanted_items.php"
            );
            exit();
        }
    }


    /* =========================
       GET FORM DATA
    ========================= */

    $title = trim(
        $_POST["title"] ?? ""
    );

    $category = trim(
        $_POST["category"] ?? ""
    );

    $location = trim(
        $_POST["location"] ?? ""
    );

    $description = trim(
        $_POST["description"] ?? ""
    );

    $status = trim(
        $_POST["status"] ?? ""
    );


    /* =========================
       VALIDATION
    ========================= */

    if(
        $title == "" ||
        $category == "" ||
        $location == "" ||
        $status == ""
    )
    {
        $message =
            "Please fill all required fields.";

        $messageType =
            "error";
    }

    else
    {

        /* =========================
           ONLY ALLOWED STATUS
        ========================= */

        $allowedStatuses = array(
            "Active",
            "Closed"
        );

        if(
            !in_array(
                $status,
                $allowedStatuses
            )
        )
        {
            $message =
                "Invalid status selected.";

            $messageType =
                "error";
        }


        /* =========================
           ESCAPE DATA
        ========================= */

        else
        {

            $titleSafe =
                mysqli_real_escape_string(
                    $conn,
                    $title
                );

            $categorySafe =
                mysqli_real_escape_string(
                    $conn,
                    $category
                );

            $locationSafe =
                mysqli_real_escape_string(
                    $conn,
                    $location
                );

            $descriptionSafe =
                mysqli_real_escape_string(
                    $conn,
                    $description
                );

            $statusSafe =
                mysqli_real_escape_string(
                    $conn,
                    $status
                );


            /* =========================
               NEW IMAGE
            ========================= */

            if(
                isset($_FILES["image"]) &&
                $_FILES["image"]["error"]
                == UPLOAD_ERR_OK
            )
            {

                $extension =
                    strtolower(
                        pathinfo(
                            $_FILES["image"]["name"],
                            PATHINFO_EXTENSION
                        )
                    );


                $allowed = array(
                    "jpg",
                    "jpeg",
                    "png",
                    "gif",
                    "webp"
                );


                /* =========================
                   IMAGE EXTENSION
                ========================= */

                if(
                    !in_array(
                        $extension,
                        $allowed
                    )
                )
                {
                    $message =
                        "Only JPG, JPEG, PNG, GIF or WEBP images are allowed.";

                    $messageType =
                        "error";
                }


                /* =========================
                   IMAGE SIZE
                ========================= */

                elseif(
                    $_FILES["image"]["size"]
                    >
                    5 * 1024 * 1024
                )
                {
                    $message =
                        "Image size must be less than 5 MB.";

                    $messageType =
                        "error";
                }


                else
                {

                    /* =========================
                       CREATE UPLOAD DIRECTORY
                    ========================= */

                    if(!is_dir("uploads"))
                    {
                        mkdir(
                            "uploads",
                            0777,
                            true
                        );
                    }


                    /* =========================
                       NEW IMAGE NAME
                    ========================= */

                    $imageName =
                        time() .
                        "_" .
                        uniqid() .
                        "." .
                        $extension;


                    $uploadPath =
                        "uploads/" .
                        $imageName;


                    /* =========================
                       MOVE IMAGE
                    ========================= */

                    if(
                        move_uploaded_file(
                            $_FILES["image"]["tmp_name"],
                            $uploadPath
                        )
                    )
                    {

                        $imageSafe =
                            mysqli_real_escape_string(
                                $conn,
                                $imageName
                            );


                        /* =========================
                           UPDATE WITH IMAGE
                        ========================= */

                        $sqlUpdate =
                        "UPDATE wanted_posts
                         SET
                            title='$titleSafe',
                            category='$categorySafe',
                            location='$locationSafe',
                            description='$descriptionSafe',
                            status='$statusSafe',
                            image='$imageSafe'
                         WHERE wanted_id='$wanted_id'
                         AND user_id='$user_id'
                         AND LOWER(TRIM(status)) NOT IN ('accepted', 'paid')
                         LIMIT 1";


                        if(
                            mysqli_query(
                                $conn,
                                $sqlUpdate
                            )
                        )
                        {

                            /* =========================
                               DELETE OLD IMAGE
                            ========================= */

                            if(
                                !empty(
                                    $wanted["image"]
                                )
                                &&
                                file_exists(
                                    "uploads/" .
                                    $wanted["image"]
                                )
                            )
                            {
                                unlink(
                                    "uploads/" .
                                    $wanted["image"]
                                );
                            }


                            /* =========================
                               GO TO VIEW PAGE
                            ========================= */

                            header(
                                "Location: view_wanted.php?id=" .
                                $wanted_id
                            );

                            exit();

                        }

                        else
                        {

                            /* DELETE NEW IMAGE */

                            if(
                                file_exists(
                                    $uploadPath
                                )
                            )
                            {
                                unlink(
                                    $uploadPath
                                );
                            }


                            $message =
                                "Failed to update wanted post: "
                                .
                                mysqli_error($conn);

                            $messageType =
                                "error";
                        }

                    }

                    else
                    {

                        $message =
                            "Image upload failed.";

                        $messageType =
                            "error";
                    }

                }

            }

            else
            {

                /* =========================
                   UPDATE WITHOUT IMAGE
                ========================= */

                $sqlUpdate =
                "UPDATE wanted_posts
                 SET
                    title='$titleSafe',
                    category='$categorySafe',
                    location='$locationSafe',
                    description='$descriptionSafe',
                    status='$statusSafe'
                 WHERE wanted_id='$wanted_id'
                 AND user_id='$user_id'
                 AND LOWER(TRIM(status)) NOT IN ('accepted', 'paid')
                 LIMIT 1";


                if(
                    mysqli_query(
                        $conn,
                        $sqlUpdate
                    )
                )
                {

                    header(
                        "Location: view_wanted.php?id=" .
                        $wanted_id
                    );

                    exit();

                }

                else
                {

                    $message =
                        "Failed to update wanted post: "
                        .
                        mysqli_error($conn);

                    $messageType =
                        "error";
                }

            }

        }

    }

}

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<title>
    Edit Wanted Post | EcoCycle
</title>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<link
    rel="stylesheet"
    href="style.css"
>


<style>

/* =========================
   EDIT WANTED PAGE
========================= */

.main{
    margin-left:230px;

    padding:
        105px
        45px
        50px;

    max-width:1000px;
}


/* =========================
   BACK
========================= */

.back{
    display:inline-block;

    color:#168a45;

    text-decoration:none;

    font-size:14px;

    font-weight:bold;

    margin-bottom:20px;
}

.back:hover{
    color:#117239;

    text-decoration:underline;
}


/* =========================
   HEADER
========================= */

.page-header{
    margin-bottom:25px;
}

.page-header h1{
    font-size:28px;

    margin-bottom:7px;
}

.page-header p{
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


/* =========================
   MESSAGE
========================= */

.message{
    padding:
        12px
        15px;

    border-radius:7px;

    margin-bottom:20px;

    font-size:13px;

    font-weight:bold;
}

.success{
    background:#eaf7ef;

    color:#168a45;
}

.error{
    background:#fdecec;

    color:#d93025;
}


/* =========================
   FORM
========================= */

.form-group{
    margin-bottom:18px;
}

.form-group label{
    display:block;

    font-size:13px;

    font-weight:bold;

    margin-bottom:7px;

    color:#333;
}


input,
select,
textarea{
    width:100%;

    min-height:45px;

    border:1px solid #ccc;

    border-radius:7px;

    padding:
        10px
        12px;

    font-size:14px;

    background:white;

    font-family:
        Arial,
        sans-serif;
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
   CURRENT IMAGE
========================= */

.current-image{
    margin-bottom:10px;
}

.current-image img{
    width:220px;

    height:150px;

    object-fit:cover;

    border-radius:8px;

    border:1px solid #ddd;

    display:block;
}

.image-note{
    font-size:12px;

    color:#888;

    margin-top:7px;
}


/* =========================
   BUTTONS
========================= */

.buttons{
    display:flex;

    gap:10px;

    margin-top:25px;
}


.buttons button,
.buttons a{
    width:auto;

    flex:none;

    padding:
        10px
        20px;

    border-radius:7px;

    text-align:center;

    text-decoration:none;

    font-size:13px;

    font-weight:bold;

    cursor:pointer;

    border:none;
}


.update{
    background:#168a45;

    color:white;
}

.cancel{
    background:#eee;

    color:#333;
}

.update:hover{
    background:#117239;
}

.cancel:hover{
    background:#ddd;
}


/* =========================
   RESPONSIVE
========================= */

@media(max-width:800px)
{

    .main{
        margin-left:70px;

        padding:
            100px
            20px
            50px;
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
        flex-direction:row;

        flex-wrap:wrap;
    }

    .buttons button,
    .buttons a{
        padding:
            10px
            16px;
    }

    .current-image img{
        width:100%;

        max-width:300px;
    }

}

</style>

</head>


<body>


<?php include("header_sidebar.php"); ?>


<main class="main">


    <!-- =========================
         BACK
    ========================= -->

    <a
        href="wanted_items.php"
        class="back"
    >

        ← Back

    </a>


    <!-- =========================
         PAGE HEADER
    ========================= -->

    <div class="page-header">

        <h1>
            Edit Wanted Post
        </h1>

        <p>
            Update the details of your wanted item.
        </p>

    </div>


    <!-- =========================
         FORM CARD
    ========================= -->

    <div class="form-card">


        <!-- =========================
             MESSAGE
        ========================= -->

        <?php

        if($message != "")
        {

        ?>

            <div
                class="message
                <?php
                echo htmlspecialchars(
                    $messageType
                );
                ?>"
            >

                <?php

                echo htmlspecialchars(
                    $message
                );

                ?>

            </div>

        <?php

        }

        ?>


        <!-- =========================
             FORM
        ========================= -->

        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <!-- =========================
                 TITLE
            ========================= -->

            <div class="form-group">

                <label>
                    Item Title
                </label>

                <input
                    type="text"
                    name="title"

                    value="<?php
                    echo htmlspecialchars(
                        $wanted["title"]
                    );
                    ?>"

                    required
                >

            </div>


            <!-- =========================
                 CATEGORY
            ========================= -->

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


                    <option
                        value="Electronics"

                        <?php

                        if(
                            $wanted["category"]
                            ==
                            "Electronics"
                        )
                        {
                            echo "selected";
                        }

                        ?>
                    >

                        Electronics

                    </option>


                    <option
                        value="Books"

                        <?php

                        if(
                            $wanted["category"]
                            ==
                            "Books"
                        )
                        {
                            echo "selected";
                        }

                        ?>
                    >

                        Books

                    </option>


                    <option
                        value="Clothing"

                        <?php

                        if(
                            $wanted["category"]
                            ==
                            "Clothing"
                        )
                        {
                            echo "selected";
                        }

                        ?>
                    >

                        Clothing

                    </option>


                    <option
                        value="Furniture"

                        <?php

                        if(
                            $wanted["category"]
                            ==
                            "Furniture"
                        )
                        {
                            echo "selected";
                        }

                        ?>
                    >

                        Furniture

                    </option>


                    <option
                        value="Others"

                        <?php

                        if(
                            $wanted["category"]
                            ==
                            "Others"
                        )
                        {
                            echo "selected";
                        }

                        ?>
                    >

                        Others

                    </option>

                </select>

            </div>


            <!-- =========================
                 LOCATION
            ========================= -->

            <div class="form-group">

                <label>
                    Location
                </label>

                <input
                    type="text"
                    name="location"

                    value="<?php

                    echo htmlspecialchars(
                        $wanted["location"]
                    );

                    ?>"

                    required
                >

            </div>


            <!-- =========================
                 DESCRIPTION
            ========================= -->

            <div class="form-group">

                <label>
                    Description / Requirements
                </label>

                <textarea
                    name="description"
                    placeholder="Describe what you are looking for..."
                ><?php

                echo htmlspecialchars(
                    $wanted["description"] ?? ""
                );

                ?></textarea>

            </div>


            <!-- =========================
                 STATUS
            ========================= -->

            <div class="form-group">

                <label>
                    Status
                </label>

                <select
                    name="status"
                    required
                >

                    <option
                        value="Active"

                        <?php

                        if(
                            $wanted["status"]
                            ==
                            "Active"
                        )
                        {
                            echo "selected";
                        }

                        ?>
                    >

                        Active

                    </option>


                    <option
                        value="Closed"

                        <?php

                        if(
                            $wanted["status"]
                            ==
                            "Closed"
                        )
                        {
                            echo "selected";
                        }

                        ?>
                    >

                        Closed

                    </option>

                </select>

            </div>


            <!-- =========================
                 CURRENT IMAGE
            ========================= -->

            <div class="form-group">

                <label>
                    Current Image
                </label>


                <?php

                if(
                    !empty(
                        $wanted["image"]
                    )
                    &&
                    file_exists(
                        "uploads/" .
                        $wanted["image"]
                    )
                )

                {

                ?>

                    <div class="current-image">

                        <img
                            src="uploads/<?php

                            echo htmlspecialchars(
                                $wanted["image"]
                            );

                            ?>"

                            alt="Current Wanted Image"
                        >

                        <div class="image-note">

                            Upload a new image only if you want to replace the current image.

                        </div>

                    </div>

                <?php

                }

                else

                {

                ?>

                    <div class="image-note">

                        No current image.

                    </div>

                <?php

                }

                ?>


                <input
                    type="file"

                    name="image"

                    accept=".jpg,.jpeg,.png,.gif,.webp"
                >

            </div>


            <!-- =========================
                 BUTTONS
            ========================= -->

            <div class="buttons">


                <button
                    type="submit"
                    class="update"
                >

                    Update

                </button>


                <a
                    href="wanted_items.php"
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
