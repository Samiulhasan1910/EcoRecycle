
<?php

session_start();

include("db.php");

header("Content-Type: application/json");


// ==================================================
// GET DATA
// ==================================================

$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$remember = $_POST["remember"] ?? "0";


// ==================================================
// VALIDATION
// ==================================================

if ($email == "" || $password == "")
{
    echo json_encode([
        "success" => false,
        "message" => "Please enter email and password."
    ]);

    exit();
}


// ==================================================
// ESCAPE EMAIL
// ==================================================

$emailSafe = mysqli_real_escape_string(
    $conn,
    $email
);


// ==================================================
// HELPER FUNCTION
// CHECK PASSWORD
// ==================================================

function checkPassword($plainPassword, $storedPassword)
{
    /*
     * First check hashed password
     */
    if (password_verify($plainPassword, $storedPassword))
    {
        return true;
    }

    /*
     * Also support old plain-text passwords
     */
    if ($plainPassword === $storedPassword)
    {
        return true;
    }

    return false;
}


// ==================================================
// HELPER FUNCTION
// UPDATE OLD PASSWORD TO HASH
// ==================================================

function updatePasswordHash(
    $conn,
    $tableName,
    $idField,
    $idValue,
    $plainPassword
)
{
    $newHash = password_hash(
        $plainPassword,
        PASSWORD_DEFAULT
    );

    $newHashSafe = mysqli_real_escape_string(
        $conn,
        $newHash
    );

    $updateSql = "
        UPDATE $tableName
        SET password='$newHashSafe'
        WHERE $idField=" . (int)$idValue;

    mysqli_query($conn, $updateSql);
}


// ==================================================
// REMEMBER EMAIL FUNCTION
// ==================================================

function rememberEmail($email, $remember)
{
    if ($remember == "1")
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
}


// ==================================================
// 1. CHECK ADMIN
// ==================================================

$sql = "
    SELECT *
    FROM admins
    WHERE email='$emailSafe'
    LIMIT 1
";

$result = mysqli_query($conn, $sql);


if (!$result)
{
    echo json_encode([
        "success" => false,
        "message" =>
            "Database error: " .
            mysqli_error($conn)
    ]);

    exit();
}


if (mysqli_num_rows($result) > 0)
{
    $admin = mysqli_fetch_assoc($result);


    if (checkPassword(
        $password,
        $admin["password"]
    ))
    {

        /*
         * Convert old plain password to hash
         */
        if (
            $password === $admin["password"] &&
            !password_verify(
                $password,
                $admin["password"]
            )
        )
        {
            updatePasswordHash(
                $conn,
                "admins",
                "admin_id",
                $admin["admin_id"],
                $password
            );
        }


        /*
         * ADMIN SESSION
         */
        $_SESSION["admin_id"] =
            $admin["admin_id"];

        $_SESSION["name"] =
            $admin["name"];

        $_SESSION["email"] =
            $admin["email"];

        $_SESSION["role"] =
            "admin";


        rememberEmail(
            $email,
            $remember
        );


        echo json_encode([
            "success" => true,
            "message" => "Admin login successful!",
            "redirect" => "admin_dashboard.php"
        ]);

        exit();
    }


    echo json_encode([
        "success" => false,
        "message" => "Invalid email or password."
    ]);

    exit();
}


// ==================================================
// 2. CHECK NORMAL USERS
// ==================================================

$sql = "
    SELECT *
    FROM users
    WHERE email='$emailSafe'
    LIMIT 1
";

$result = mysqli_query($conn, $sql);


if (!$result)
{
    echo json_encode([
        "success" => false,
        "message" =>
            "Database error: " .
            mysqli_error($conn)
    ]);

    exit();
}


if (mysqli_num_rows($result) > 0)
{
    $user = mysqli_fetch_assoc($result);


    if (checkPassword(
        $password,
        $user["password"]
    ))
    {

        /*
         * Convert old plain password to hash
         */
        if (
            $password === $user["password"] &&
            !password_verify(
                $password,
                $user["password"]
            )
        )
        {
            updatePasswordHash(
                $conn,
                "users",
                "user_id",
                $user["user_id"],
                $password
            );
        }


        /*
         * NORMAL USER SESSION
         */
        $_SESSION["user_id"] =
            $user["user_id"];

        $_SESSION["name"] =
            $user["name"];

        $_SESSION["email"] =
            $user["email"];

        $_SESSION["role"] =
            "user";


        rememberEmail(
            $email,
            $remember
        );


        echo json_encode([
            "success" => true,
            "message" => "Login successful.",
            "redirect" => "dashboard.php"
        ]);

        exit();
    }


    echo json_encode([
        "success" => false,
        "message" => "Invalid email or password."
    ]);

    exit();
}


// ==================================================
// 3. CHECK DELIVERY MAN
// ==================================================

$deliverySql = "
    SELECT *
    FROM delivery_man
    WHERE email='$emailSafe'
    LIMIT 1
";

$deliveryResult = mysqli_query(
    $conn,
    $deliverySql
);


if (!$deliveryResult)
{
    echo json_encode([
        "success" => false,
        "message" =>
            "Delivery Man database error: " .
            mysqli_error($conn)
    ]);

    exit();
}


if (mysqli_num_rows($deliveryResult) > 0)
{
    $delivery = mysqli_fetch_assoc(
        $deliveryResult
    );


    /*
     * CHECK PASSWORD
     */
    if (checkPassword(
        $password,
        $delivery["password"]
    ))
    {

        /*
         * CHECK VERIFICATION STATUS
         */
        $verificationStatus =
            trim(
                (string)(
                    $delivery["verification_status"]
                    ?? "Pending"
                )
            );


        if ($verificationStatus == "")
        {
            $verificationStatus =
                "Pending";
        }


        /*
         * DELIVERY MAN MUST BE APPROVED
         */
        if (
            strtolower(
                $verificationStatus
            ) != "approved"
        )
        {

            if (
                strtolower(
                    $verificationStatus
                ) == "pending"
            )
            {

                echo json_encode([
                    "success" => false,
                    "message" =>
                        "Your Delivery Man account is pending verification. Please wait for admin approval."
                ]);

            }
            else if (
                strtolower(
                    $verificationStatus
                ) == "rejected"
            )
            {

                echo json_encode([
                    "success" => false,
                    "message" =>
                        "Your Delivery Man account has been rejected. Please contact support."
                ]);

            }
            else
            {

                echo json_encode([
                    "success" => false,
                    "message" =>
                        "Your Delivery Man account is not verified yet."
                ]);
            }

            exit();
        }


        /*
         * CONVERT OLD PLAIN PASSWORD TO HASH
         */
        if (
            $password === $delivery["password"] &&
            !password_verify(
                $password,
                $delivery["password"]
            )
        )
        {
            updatePasswordHash(
                $conn,
                "delivery_man",
                "delivery_id",
                $delivery["delivery_id"],
                $password
            );
        }


        /*
         * DELIVERY MAN SESSION
         */
        $_SESSION["user_id"] =
            $delivery["delivery_id"];

        $_SESSION["delivery_id"] =
            $delivery["delivery_id"];

        $_SESSION["delivery_name"] =
            $delivery["name"];

        $_SESSION["name"] =
            $delivery["name"];

        $_SESSION["email"] =
            $delivery["email"];

        $_SESSION["role"] =
            "delivery_man";


        /*
         * REMEMBER EMAIL
         */
        rememberEmail(
            $email,
            $remember
        );


        /*
         * SUCCESS
         */
        echo json_encode([
            "success" => true,
            "message" =>
                "Delivery Man login successful!",
            "redirect" =>
                "delivery_dashboard.php"
        ]);

        exit();
    }


    /*
     * WRONG PASSWORD
     */
    echo json_encode([
        "success" => false,
        "message" =>
            "Invalid email or password."
    ]);

    exit();
}


// ==================================================
// 4. CHECK NGO
// ==================================================

$ngoSql = "
    SELECT *
    FROM ngos
    WHERE email='$emailSafe'
    LIMIT 1
";

$ngoResult = mysqli_query(
    $conn,
    $ngoSql
);


if (!$ngoResult)
{
    echo json_encode([
        "success" => false,
        "message" =>
            "Database error: " .
            mysqli_error($conn)
    ]);

    exit();
}


if (mysqli_num_rows($ngoResult) > 0)
{
    $ngo = mysqli_fetch_assoc(
        $ngoResult
    );


    /*
     * NGO VERIFICATION STATUS
     */
    $ngoStatus =
        trim(
            (string)(
                $ngo["verification_status"]
                ?? ""
            )
        );


    if ($ngoStatus == "")
    {
        $ngoStatus = "Pending";
    }


    /*
     * CHECK PASSWORD
     */
    if (checkPassword(
        $password,
        $ngo["password"]
    ))
    {

        /*
         * CHECK NGO APPROVAL
         */
        if (
            strtolower($ngoStatus)
            != "approved"
        )
        {

            if (
                strtolower($ngoStatus)
                == "pending"
            )
            {

                echo json_encode([
                    "success" => false,
                    "message" =>
                        "Your NGO account is pending verification. Please wait for admin approval."
                ]);

            }
            else if (
                strtolower($ngoStatus)
                == "rejected"
            )
            {

                echo json_encode([
                    "success" => false,
                    "message" =>
                        "Your NGO account has been rejected. Please contact support."
                ]);

            }
            else
            {

                echo json_encode([
                    "success" => false,
                    "message" =>
                        "Your NGO account is not verified yet."
                ]);
            }

            exit();
        }


        /*
         * CONVERT OLD PLAIN PASSWORD TO HASH
         */
        if (
            $password === $ngo["password"] &&
            !password_verify(
                $password,
                $ngo["password"]
            )
        )
        {
            updatePasswordHash(
                $conn,
                "ngos",
                "ngo_id",
                $ngo["ngo_id"],
                $password
            );
        }


        /*
         * NGO SESSION
         */
        $_SESSION["ngo_id"] =
            $ngo["ngo_id"];

        $_SESSION["name"] =
            $ngo["name"];

        $_SESSION["email"] =
            $ngo["email"];

        $_SESSION["role"] =
            "ngo";


        rememberEmail(
            $email,
            $remember
        );


        echo json_encode([
            "success" => true,
            "message" =>
                "NGO login successful!",
            "redirect" =>
                "ngo_dashboard.php"
        ]);

        exit();
    }


    echo json_encode([
        "success" => false,
        "message" =>
            "Invalid email or password."
    ]);

    exit();
}


// ==================================================
// NO ACCOUNT FOUND
// ==================================================

echo json_encode([
    "success" => false,
    "message" =>
        "Invalid email or password."
]);

exit();

?>
