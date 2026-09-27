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
// HELPER FUNCTION - CHECK PASSWORD
// ==================================================

function checkPassword($plainPassword, $storedPassword) {
    if(password_verify($plainPassword, $storedPassword)) {
        return true;
    }
    else if($plainPassword === $storedPassword) {
        return true;
    }
    return false;
}


// ==================================================
// HELPER FUNCTION - HASH PASSWORD
// ==================================================

function updatePasswordHash($conn, $tableName, $idField, $idValue, $plainPassword) {
    $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $newHashSafe = mysqli_real_escape_string($conn, $newHash);
    
    $updateSql = "UPDATE $tableName SET password='$newHashSafe' WHERE $idField=" . (int)$idValue;
    mysqli_query($conn, $updateSql);
}


// ==================================================
// 1. CHECK ADMINS TABLE FIRST
// ==================================================

$sql = "SELECT * FROM admins WHERE email='$emailSafe' LIMIT 1";
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

    if(checkPassword($password, $admin["password"]))
    {
        // Update to hashed password if plain text
        if($password === $admin["password"] && !password_verify($password, $admin["password"])) {
            updatePasswordHash($conn, "admins", "admin_id", $admin["admin_id"], $password);
        }

        $_SESSION["admin_id"] = $admin["admin_id"];
        $_SESSION["name"] = $admin["name"];
        $_SESSION["email"] = $admin["email"];
        $_SESSION["role"] = "admin";

        // REMEMBER EMAIL
        if($remember == "1")
        {
            setcookie("remember_email", $email, time() + (86400 * 30), "/");
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

$sql = "SELECT * FROM users WHERE email='$emailSafe' LIMIT 1";
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

    if(checkPassword($password, $user["password"]))
    {
        // Update to hashed password if plain text
        if($password === $user["password"] && !password_verify($password, $user["password"])) {
            updatePasswordHash($conn, "users", "user_id", $user["user_id"], $password);
        }

        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["name"] = $user["name"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["role"] = "user";
        $_SESSION["user_type"] = "user";

        // REMEMBER EMAIL
        if($remember == "1")
        {
            setcookie("remember_email", $email, time() + (86400 * 30), "/");
        }
        else
        {
            setcookie("remember_email", "", time() - 3600, "/");
        }

        echo json_encode([
            "success" => true,
            "message" => "Login successful.",
            "redirect" => "dashboard.php"
        ]);
        exit();
    }
}


// ==================================================
// 3. CHECK NGOS TABLE
// ==================================================

$ngoSql = "SELECT * FROM ngos WHERE email='$emailSafe' LIMIT 1";
$ngoResult = mysqli_query($conn, $ngoSql);

if(!$ngoResult)
{
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
    exit();
}

if(mysqli_num_rows($ngoResult) > 0)
{
    $ngo = mysqli_fetch_assoc($ngoResult);

    if(checkPassword($password, $ngo["password"]))
    {
        // Check verification status
        if($ngo["verification_status"] != "Approved")
        {
            if($ngo["verification_status"] == "Pending")
            {
                echo json_encode([
                    "success" => false,
                    "message" => "Your account is pending verification. Please wait for admin approval."
                ]);
            }
            else if($ngo["verification_status"] == "Rejected")
            {
                echo json_encode([
                    "success" => false,
                    "message" => "Your account has been rejected. Please contact support."
                ]);
            }
            else
            {
                echo json_encode([
                    "success" => false,
                    "message" => "Your account is not verified. Please complete verification."
                ]);
            }
            exit();
        }

        // Update to hashed password if plain text
        if($password === $ngo["password"] && !password_verify($password, $ngo["password"])) {
            updatePasswordHash($conn, "ngos", "ngo_id", $ngo["ngo_id"], $password);
        }

        $_SESSION["ngo_id"] = $ngo["ngo_id"];
        $_SESSION["name"] = $ngo["name"];
        $_SESSION["email"] = $ngo["email"];
        $_SESSION["role"] = "ngo";

        // REMEMBER EMAIL
        if($remember == "1")
        {
            setcookie("remember_email", $email, time() + (86400 * 30), "/");
        }
        else
        {
            setcookie("remember_email", "", time() - 3600, "/");
        }

        echo json_encode([
            "success" => true,
            "message" => "NGO login successful!",
            "redirect" => "ngo_dashboard.php"
        ]);
        exit();
    }
}


// ==================================================
// NO ACCOUNT FOUND
// ==================================================

echo json_encode([
    "success" => false,
    "message" => "Invalid email or password."
]);

?>
