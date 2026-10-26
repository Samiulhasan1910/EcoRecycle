<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile | EcoCycle</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>

        .main{
            margin-left:230px;
            padding:105px 45px 50px;
            max-width:1000px;
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


        /* =========================
           PROFILE CARD
        ========================= */

        .profile-card{
            background:white;
            border:1px solid #e5e5e5;
            border-radius:12px;
            padding:30px;
        }


        .profile-top{
            display:flex;
            align-items:center;
            gap:20px;
            padding-bottom:25px;
            margin-bottom:25px;
            border-bottom:1px solid #eee;
        }


        .profile-icon{
            width:70px;
            height:70px;
            border-radius:50%;
            background:#eaf7ef;
            color:#168a45;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:28px;
            font-weight:bold;
        }


        .profile-name{
            font-size:22px;
            font-weight:bold;
            margin-bottom:5px;
        }


        .profile-role{
            font-size:13px;
            color:#777;
        }


        /* =========================
           FORM
        ========================= */

        .form-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:20px;
        }


        .form-group{
            display:flex;
            flex-direction:column;
        }


        .form-group.full{
            grid-column:1 / -1;
        }


        .form-group label{
            font-size:13px;
            font-weight:bold;
            margin-bottom:7px;
            color:#333;
        }


        .form-group input{
            width:100%;
            box-sizing:border-box;
            padding:11px 13px;
            border:1px solid #ddd;
            border-radius:7px;
            font-size:14px;
            outline:none;
        }


        .form-group input:focus{
            border-color:#168a45;
        }


        .form-group input:disabled{
            background:#f5f5f5;
            color:#555;
            cursor:not-allowed;
        }


        .role-box{
            padding:11px 13px;
            background:#f5f5f5;
            border:1px solid #ddd;
            border-radius:7px;
            font-size:14px;
            color:#555;
        }


        /* =========================
           BUTTONS
        ========================= */

        .form-actions{
            margin-top:25px;
            display:flex;
            justify-content:flex-end;
            gap:10px;
        }


        .edit-btn,
        .update-btn,
        .cancel-btn{
            text-decoration:none;
            border:none;
            padding:11px 24px;
            border-radius:7px;
            font-size:14px;
            font-weight:bold;
            cursor:pointer;
            display:inline-block;
        }


        .edit-btn{
            background:#168a45;
            color:white;
        }


        .update-btn{
            background:#168a45;
            color:white;
        }


        .cancel-btn{
            background:#eee;
            color:#333;
        }


        .edit-btn:hover,
        .update-btn:hover{
            opacity:0.9;
        }


        /* =========================
           MESSAGES
        ========================= */

        .success-message{
            background:#eaf7ef;
            color:#168a45;
            border:1px solid #ccebd8;
            padding:11px 14px;
            border-radius:7px;
            margin-bottom:20px;
            font-size:14px;
        }


        .error-message{
            background:#fdecec;
            color:#b71c1c;
            border:1px solid #f3caca;
            padding:11px 14px;
            border-radius:7px;
            margin-bottom:20px;
            font-size:14px;
        }


        @media(max-width:750px){

            .main{
                margin-left:70px;
                padding-left:20px;
                padding-right:20px;
            }

            .form-grid{
                grid-template-columns:1fr;
            }

            .form-group.full{
                grid-column:auto;
            }

        }

    </style>

</head>


<body>


<?php require APP_PATH . '/views/layouts/header_sidebar.php'; ?>


<main class="main">


    <div class="header">

        <h1>My Profile</h1>

        <p>
            View and manage your account information.
        </p>

    </div>


    <?php if(!empty($message)): ?>

        <div class="success-message">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <?php if(!empty($error)): ?>

        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php endif; ?>


    <div class="profile-card">


        <!-- PROFILE HEADER -->

        <div class="profile-top">

            <div class="profile-icon">

                <?php
                echo strtoupper(substr($name, 0, 1));
                ?>

            </div>


            <div>

                <div class="profile-name">

                    <?php
                    echo htmlspecialchars($name);
                    ?>

                </div>


                <div class="profile-role">

                    <?php
                    echo htmlspecialchars($role);
                    ?>

                </div>

            </div>

        </div>


        <!-- PROFILE FORM -->

        <form method="POST" action="">


            <div class="form-grid">


                <!-- NAME -->

                <div class="form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php echo htmlspecialchars($name); ?>"
                        <?php echo !$editMode ? "disabled" : ""; ?>
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email); ?>"
                        <?php echo !$editMode ? "disabled" : ""; ?>
                        required
                    >

                </div>


                <!-- PHONE -->

                <div class="form-group">

                    <label for="phone">
                        Phone
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="<?php echo htmlspecialchars($phone); ?>"
                        <?php echo !$editMode ? "disabled" : ""; ?>
                        placeholder="Enter phone number"
                    >

                </div>


                <!-- ROLE -->

                <div class="form-group">

                    <label>
                        Role
                    </label>

                    <div class="role-box">

                        <?php
                        echo htmlspecialchars($role);
                        ?>

                    </div>

                </div>


                <!-- LOCATION -->

                <div class="form-group full">

                    <label for="location">
                        Location
                    </label>

                    <input
                        type="text"
                        id="location"
                        name="location"
                        value="<?php echo htmlspecialchars($location); ?>"
                        <?php echo !$editMode ? "disabled" : ""; ?>
                        placeholder="Enter your location"
                    >

                </div>


            </div>


            <!-- BUTTONS -->

            <div class="form-actions">


                <?php if(!$editMode): ?>

                    <a
                        href="index.php?url=profile&edit=1"
                        class="edit-btn"
                    >
                        Edit Profile
                    </a>

                <?php else: ?>

                    <a
                        href="index.php?url=profile"
                        class="cancel-btn"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="update-btn"
                    >
                        Update Profile
                    </button>

                <?php endif; ?>


            </div>


        </form>


    </div>


</main>


</body>

</html>
