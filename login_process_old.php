<?php

session_start();

include("db.php");

header("Content-Type: application/json");


// GET DATA

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$remember = $_POST["remember"] ?? "0";


// VALIDATION

if($email == "" || $password == "")
{
    echo json_encode([
        "success" => false,
        "message" => "Please enter email and password."
    ]);

    exit();
}


// ESCAPE EMAIL

$emailSafe = mysqli_real_escape_string(
    $conn,
    $email
);


// ==================================================
// 1. CHECK ADMINS TABLE FIRST
// ==================================================

$sql = "SELECT *
        FROM admins
        WHERE email='$emailSafe'
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if(!$result)
{
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);

    exit();
}

if(mysqli_num_rows($result) > 0)
{
    $admin = mysqli_fetch_assoc($result);

    $passwordValid = false;

    // HASHED PASSWORD
    if(password_verify($password, $admin["password"]))
    {
        $passwordValid = true;
    }
    // OLD PLAIN TEXT PASSWORD
    else if($password === $admin["password"])
    {
        $passwordValid = true;

        $newHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $newHashSafe = mysqli_real_escape_string(
            $conn,
            $newHash
        );

        $updateSql = "UPDATE admins
                      SET password='$newHashSafe'
                      WHERE admin_id=" .
                      (int)$admin["admin_id"];

        mysqli_query($conn, $updateSql);
    }

    if($passwordValid)
    {
        $_SESSION["admin_id"] = $admin["admin_id"];
        $_SESSION["name"] = $admin["name"];
        $_SESSION["email"] = $admin["email"];
        $_SESSION["role"] = "admin";

        // REMEMBER EMAIL
        if($remember == "1")
        {
            setcookie(
                "remember_email",
                $email,
                time() + (86400 * 30),
                "/"
            );
        }
        else
        {
            setcookie("remember_email", "", time() - 3600, "/");
        }

        echo json_encode([
            "success" => true,
            "message" => "Admin login successful!",
            "redirect" => "admin_dashboard.php"
        ]);

        exit();
    }
    else
    {
        echo json_encode([
            "success" => false,
            "message" => "Invalid email or password."
        ]);

        exit();
    }
}


// ==================================================
// 2. CHECK USERS TABLE
// ==================================================

$sql = "SELECT *
        FROM users
        WHERE email='$emailSafe'
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if(!$result)
{
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);

    exit();
}

if(mysqli_num_rows($result) > 0)
{
    $user = mysqli_fetch_assoc($result);

    $passwordValid = false;


    // HASHED PASSWORD

    if(password_verify($password, $user["password"]))
    {
        $passwordValid = true;
    }


    // OLD PLAIN TEXT PASSWORD

    else if($password === $user["password"])
    {
        $passwordValid = true;

        $newHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $newHashSafe = mysqli_real_escape_string(
            $conn,
            $newHash
        );

        $updateSql = "UPDATE users
                      SET password='$newHashSafe'
                      WHERE user_id=" .
                      (int)$user["user_id"];

        mysqli_query($conn, $updateSql);
    }


    // INVALID PASSWORD

    if(!$passwordValid)
    {
        // If user password is invalid, check NGO
        // Continue to NGO check below
    }
    else
    {
        // USER SESSION

        $_SESSION["user_id"] =
            $user["user_id"];

        $_SESSION["name"] =
            $user["name"];

        $_SESSION["email"] =
            $user["email"];

        $_SESSION["role"] =
            "user";

        $_SESSION["user_type"] =
            "user";


    // REMEMBER EMAIL

    if($remember == "1")
    {
        setcookie(
            "remember_email",
            $email,
            time() + (86400 * 30),
            "/"
        );
    }
    else
    {
        setcookie(
            "remember_email",
            "",
            time() - 3600,
            "/"
        );
    }


    echo json_encode([
        "success" => true,
        "message" => "Login successful.",
        "type" => "user"
    ]);

    exit();
}


// ==================================================
// 2. CHECK NGOS TABLE
// ==================================================

$ngoSql = "SELECT *
           FROM ngos
           WHERE email='$emailSafe'
           LIMIT 1";


$ngoResult = mysqli_query(
    $conn,
    $ngoSql
);


if(!$ngoResult)
{
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);

    exit();
}


// ==================================================
// NGO FOUND
// ==================================================

if(mysqli_num_rows($ngoResult) > 0)
{
    $ngo = mysqli_fetch_assoc($ngoResult);

    $passwordValid = false;


    // HASHED PASSWORD

    if(password_verify(
        $password,
        $ngo["password"]
    ))
    {
        $passwordValid = true;
    }


    // OLD PLAIN TEXT PASSWORD

    else if($password === $ngo["password"])
    {
        $passwordValid = true;

        $newHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $newHashSafe = mysqli_real_escape_string(
            $conn,
            $newHash
        );

        $updateSql = "UPDATE ngos
                      SET password='$newHashSafe'
                      WHERE ngo_id=" .
                      (int)$ngo["ngo_id"];

        mysqli_query($conn, $updateSql);
    }


    // INVALID PASSWORD

    if(!$passwordValid)
    {
        echo json_encode([
            "success" => false,
            "message" => "Email or password is incorrect."
        ]);

        exit();
    }


    // ==================================================
    // NGO VERIFICATION CHECK
    // ==================================================

    if($ngo["verification_status"] != "Approved")
    {

        if($ngo["verification_status"] == "Pending")
        {
            $message =
                "Your NGO account is waiting for admin approval.";
        }
        else if($ngo["verification_status"] == "Rejected")
        {
            $message =
                "Your NGO verification was rejected by admin.";
        }
        else
        {
            $message =
                "Your NGO account is not approved yet.";
        }


        echo json_encode([
            "success" => false,
            "message" => $message
        ]);

        exit();
    }


    // NGO SESSION

    $_SESSION["ngo_id"] =
        $ngo["ngo_id"];

    $_SESSION["name"] =
        $ngo["name"];

    $_SESSION["email"] =
        $ngo["email"];

    $_SESSION["role"] =
        "ngo";

    $_SESSION["user_type"] =
        "ngo";


    // REMEMBER EMAIL

    if($remember == "1")
    {
        setcookie(
            "remember_email",
            $email,
            time() + (86400 * 30),
            "/"
        );
    }
    else
    {
        setcookie(
            "remember_email",
            "",
            time() - 3600,
            "/"
        );
    }


    echo json_encode([
        "success" => true,
        "message" => "NGO login successful.",
        "type" => "ngo"
    ]);

    exit();
}


// ==================================================
// 3. CHECK DELIVERY TABLE
// ==================================================

$deliverySql = "SELECT *
                FROM delivery
                WHERE email='$emailSafe'
                LIMIT 1";


$deliveryResult = mysqli_query(
    $conn,
    $deliverySql
);


if(!$deliveryResult)
{
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);

    exit();
}


// ==================================================
// DELIVERY FOUND
// ==================================================

if(mysqli_num_rows($deliveryResult) > 0)
{
    $delivery =
        mysqli_fetch_assoc($deliveryResult);

    $passwordValid = false;


    // HASHED PASSWORD

    if(password_verify(
        $password,
        $delivery["password"]
    ))
    {
        $passwordValid = true;
    }


    // OLD PLAIN TEXT PASSWORD

    else if($password === $delivery["password"])
    {
        $passwordValid = true;

        $newHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $newHashSafe = mysqli_real_escape_string(
            $conn,
            $newHash
        );

        $updateSql = "UPDATE delivery
                      SET password='$newHashSafe'
                      WHERE delivery_id=" .
                      (int)$delivery["delivery_id"];

        mysqli_query($conn, $updateSql);
    }


    // INVALID PASSWORD

    if(!$passwordValid)
    {
        echo json_encode([
            "success" => false,
            "message" => "Email or password is incorrect."
        ]);

        exit();
    }


    // ==================================================
    // DELIVERY VERIFICATION CHECK
    // ==================================================

    if($delivery["verification_status"] != "Approved")
    {

        if($delivery["verification_status"] == "Pending")
        {
            $message =
                "Your Delivery account is waiting for admin approval.";
        }
        else if($delivery["verification_status"] == "Rejected")
        {
            $message =
                "Your Delivery verification was rejected by admin.";
        }
        else
        {
            $message =
                "Your Delivery account is not approved yet.";
        }


        echo json_encode([
            "success" => false,
            "message" => $message
        ]);

        exit();
    }


    // DELIVERY SESSION

    $_SESSION["delivery_id"] =
        $delivery["delivery_id"];

    $_SESSION["name"] =
        $delivery["name"];

    $_SESSION["email"] =
        $delivery["email"];

    $_SESSION["role"] =
        "delivery";

    $_SESSION["user_type"] =
        "delivery";


    // REMEMBER EMAIL

    if($remember == "1")
    {
        setcookie(
            "remember_email",
            $email,
            time() + (86400 * 30),
            "/"
        );
    }
    else
    {
        setcookie(
            "remember_email",
            "",
            time() - 3600,
            "/"
        );
    }


    echo json_encode([
        "success" => true,
        "message" => "Delivery login successful.",
        "type" => "delivery"
    ]);

    exit();
}


// ==================================================
// 4. CHECK ADMIN TABLE
// ==================================================

$adminSql = "SELECT *
             FROM admin
             WHERE email='$emailSafe'
             LIMIT 1";


$adminResult = mysqli_query(
    $conn,
    $adminSql
);


if(!$adminResult)
{
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);

    exit();
}


// ==================================================
// ADMIN FOUND
// ==================================================

if(mysqli_num_rows($adminResult) > 0)
{
    $admin =
        mysqli_fetch_assoc($adminResult);

    $passwordValid = false;


    // HASHED PASSWORD

    if(password_verify(
        $password,
        $admin["password"]
    ))
    {
        $passwordValid = true;
    }


    // OLD PLAIN TEXT PASSWORD

    else if($password === $admin["password"])
    {
        $passwordValid = true;

        $newHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $newHashSafe = mysqli_real_escape_string(
            $conn,
            $newHash
        );

        $updateSql = "UPDATE admin
                      SET password='$newHashSafe'
                      WHERE admin_id=" .
                      (int)$admin["admin_id"];

        mysqli_query($conn, $updateSql);
    }


    // INVALID PASSWORD

    if(!$passwordValid)
    {
        echo json_encode([
            "success" => false,
            "message" => "Email or password is incorrect."
        ]);

        exit();
    }


    // ADMIN SESSION

    $_SESSION["admin_id"] =
        $admin["admin_id"];

    $_SESSION["name"] =
        $admin["name"];

    $_SESSION["email"] =
        $admin["email"];

    $_SESSION["role"] =
        "admin";

    $_SESSION["user_type"] =
        "admin";


    // REMEMBER EMAIL

    if($remember == "1")
    {
        setcookie(
            "remember_email",
            $email,
            time() + (86400 * 30),
            "/"
        );
    }
    else
    {
        setcookie(
            "remember_email",
            "",
            time() - 3600,
            "/"
        );
    }


    echo json_encode([
        "success" => true,
        "message" => "Admin login successful.",
        "type" => "admin"
    ]);

    exit();
}


// ==================================================
// EMAIL NOT FOUND
// ==================================================

echo json_encode([
    "success" => false,
    "message" => "Email or password is incorrect."
]);

exit();

?>
