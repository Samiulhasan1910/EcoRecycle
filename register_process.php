<?php

mysqli_report(MYSQLI_REPORT_OFF);

include("db.php");

header("Content-Type: application/json");


// ==================================================
// 1. GET FORM DATA
// ==================================================

$name     = trim($_POST["name"] ?? "");
$email    = trim($_POST["email"] ?? "");
$phone    = trim($_POST["phone"] ?? "");
$location = trim($_POST["location"] ?? "");
$role     = trim($_POST["role"] ?? "");
$password = $_POST["password"] ?? "";


// ==================================================
// 2. BASIC VALIDATION
// ==================================================

if (
    $name == "" ||
    $email == "" ||
    $phone == "" ||
    $location == "" ||
    $role == "" ||
    $password == ""
) {
    echo json_encode([
        "success" => false,
        "message" => "Please fill in all fields."
    ]);
    exit();
}


// ==================================================
// 3. EMAIL VALIDATION
// ==================================================

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid email address."
    ]);

    exit();
}


// ==================================================
// 4. PASSWORD VALIDATION
// ==================================================

if (strlen($password) < 6) {

    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 6 characters."
    ]);

    exit();
}


// ==================================================
// 5. ROLE VALIDATION
// ==================================================

if (
    $role != "user" &&
    $role != "ngo" &&
    $role != "delivery_man"
) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid registration role."
    ]);

    exit();
}


// ==================================================
// 6. ESCAPE COMMON DATA
// ==================================================

$nameSafe = mysqli_real_escape_string(
    $conn,
    $name
);

$emailSafe = mysqli_real_escape_string(
    $conn,
    $email
);

$phoneSafe = mysqli_real_escape_string(
    $conn,
    $phone
);

$locationSafe = mysqli_real_escape_string(
    $conn,
    $location
);


// ==================================================
// 7. CHECK EMAIL IN USERS
// ==================================================

$checkUser = mysqli_query(
    $conn,
    "SELECT user_id
     FROM users
     WHERE email='$emailSafe'
     LIMIT 1"
);

if (!$checkUser) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Database error: " .
            mysqli_error($conn)
    ]);

    exit();
}


// ==================================================
// 8. CHECK EMAIL IN NGO
// ==================================================

$checkNgo = mysqli_query(
    $conn,
    "SELECT ngo_id
     FROM ngos
     WHERE email='$emailSafe'
     LIMIT 1"
);

if (!$checkNgo) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Database error: " .
            mysqli_error($conn)
    ]);

    exit();
}


// ==================================================
// 9. CHECK EMAIL IN DELIVERY MAN
// ==================================================

$checkDelivery = mysqli_query(
    $conn,
    "SELECT delivery_id
     FROM delivery_man
     WHERE email='$emailSafe'
     LIMIT 1"
);

if (!$checkDelivery) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Database error: " .
            mysqli_error($conn)
    ]);

    exit();
}


// ==================================================
// 10. DUPLICATE EMAIL
// ==================================================

if (
    mysqli_num_rows($checkUser) > 0 ||
    mysqli_num_rows($checkNgo) > 0 ||
    mysqli_num_rows($checkDelivery) > 0
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "This email is already registered."
    ]);

    exit();
}


// ==================================================
// 11. HASH PASSWORD
// ==================================================

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$passwordSafe = mysqli_real_escape_string(
    $conn,
    $hashedPassword
);


// ==================================================
// 12. NORMAL USER
// ==================================================

if ($role == "user") {

    /*
     * users table:
     *
     * user_id
     * name
     * email
     * password
     * phone
     * location
     * role
     *
     * NO verification_document
     */

    $sql = "
        INSERT INTO users
        (
            name,
            email,
            password,
            phone,
            location,
            role
        )
        VALUES
        (
            '$nameSafe',
            '$emailSafe',
            '$passwordSafe',
            '$phoneSafe',
            '$locationSafe',
            'user'
        )
    ";
}


// ==================================================
// 13. DELIVERY MAN
// ==================================================

elseif ($role == "delivery_man") {


    // --------------------------------------------------
    // CHECK DRIVING LICENSE
    // --------------------------------------------------

    if (
        !isset($_FILES["verification_document"]) ||
        $_FILES["verification_document"]["error"] != UPLOAD_ERR_OK
    ) {

        echo json_encode([
            "success" => false,
            "message" =>
                "Please upload your Driving License."
        ]);

        exit();
    }


    $fileName =
        $_FILES["verification_document"]["name"];

    $fileTmp =
        $_FILES["verification_document"]["tmp_name"];

    $fileSize =
        $_FILES["verification_document"]["size"];


    // --------------------------------------------------
    // FILE EXTENSION
    // --------------------------------------------------

    $fileExt = strtolower(
        pathinfo(
            $fileName,
            PATHINFO_EXTENSION
        )
    );


    // --------------------------------------------------
    // ALLOWED TYPES
    // --------------------------------------------------

    $allowedTypes = [
        "pdf",
        "jpg",
        "jpeg",
        "png"
    ];


    if (!in_array($fileExt, $allowedTypes)) {

        echo json_encode([
            "success" => false,
            "message" =>
                "Only PDF, JPG, JPEG and PNG files are allowed."
        ]);

        exit();
    }


    // --------------------------------------------------
    // MAXIMUM 5 MB
    // --------------------------------------------------

    if ($fileSize > 5 * 1024 * 1024) {

        echo json_encode([
            "success" => false,
            "message" =>
                "File size must be less than 5MB."
        ]);

        exit();
    }


    // --------------------------------------------------
    // CREATE DELIVERY FOLDER
    // --------------------------------------------------

    $uploadFolder =
        "uploads/delivery_documents/";


    if (!is_dir($uploadFolder)) {

        if (!mkdir(
            $uploadFolder,
            0777,
            true
        )) {

            echo json_encode([
                "success" => false,
                "message" =>
                    "Failed to create upload folder."
            ]);

            exit();
        }
    }


    // --------------------------------------------------
    // UNIQUE FILE NAME
    // --------------------------------------------------

    $newFileName =
        "delivery_" .
        time() .
        "_" .
        uniqid() .
        "." .
        $fileExt;


    $filePath =
        $uploadFolder .
        $newFileName;


    // --------------------------------------------------
    // UPLOAD LICENSE
    // --------------------------------------------------

    if (!move_uploaded_file(
        $fileTmp,
        $filePath
    )) {

        echo json_encode([
            "success" => false,
            "message" =>
                "Failed to upload Driving License."
        ]);

        exit();
    }


    // --------------------------------------------------
    // ESCAPE FILE PATH
    // --------------------------------------------------

    $filePathSafe =
        mysqli_real_escape_string(
            $conn,
            $filePath
        );


    // --------------------------------------------------
    // INSERT INTO delivery_man TABLE
    // --------------------------------------------------

    $sql = "
        INSERT INTO delivery_man
        (
            name,
            email,
            password,
            phone,
            location,
            verification_document,
            verification_status,
            status
        )
        VALUES
        (
            '$nameSafe',
            '$emailSafe',
            '$passwordSafe',
            '$phoneSafe',
            '$locationSafe',
            '$filePathSafe',
            'Pending',
            'Available'
        )
    ";
}


// ==================================================
// 14. NGO REGISTRATION
// ==================================================

elseif ($role == "ngo") {

    $organizationType =
        trim($_POST["organization_type"] ?? "");

    $establishedYear =
        trim($_POST["established_year"] ?? "");


    // --------------------------------------------------
    // NGO VALIDATION
    // --------------------------------------------------

    if (
        $organizationType == "" ||
        $establishedYear == ""
    ) {

        echo json_encode([
            "success" => false,
            "message" =>
                "Please provide all NGO information."
        ]);

        exit();
    }


    // --------------------------------------------------
    // CHECK NGO DOCUMENT
    // --------------------------------------------------

    if (
        !isset($_FILES["verification_document"]) ||
        $_FILES["verification_document"]["error"] != UPLOAD_ERR_OK
    ) {

        echo json_encode([
            "success" => false,
            "message" =>
                "Please upload NGO verification document."
        ]);

        exit();
    }


    $fileName =
        $_FILES["verification_document"]["name"];

    $fileTmp =
        $_FILES["verification_document"]["tmp_name"];

    $fileSize =
        $_FILES["verification_document"]["size"];


    // --------------------------------------------------
    // FILE EXTENSION
    // --------------------------------------------------

    $fileExt = strtolower(
        pathinfo(
            $fileName,
            PATHINFO_EXTENSION
        )
    );


    // --------------------------------------------------
    // ALLOWED TYPES
    // --------------------------------------------------

    $allowedTypes = [
        "pdf",
        "jpg",
        "jpeg",
        "png"
    ];


    if (!in_array($fileExt, $allowedTypes)) {

        echo json_encode([
            "success" => false,
            "message" =>
                "Only PDF, JPG, JPEG and PNG files are allowed."
        ]);

        exit();
    }


    // --------------------------------------------------
    // MAXIMUM 5 MB
    // --------------------------------------------------

    if ($fileSize > 5 * 1024 * 1024) {

        echo json_encode([
            "success" => false,
            "message" =>
                "File size must be less than 5MB."
        ]);

        exit();
    }


    // --------------------------------------------------
    // CREATE NGO FOLDER
    // --------------------------------------------------

    $uploadFolder =
        "uploads/ngo_documents/";


    if (!is_dir($uploadFolder)) {

        if (!mkdir(
            $uploadFolder,
            0777,
            true
        )) {

            echo json_encode([
                "success" => false,
                "message" =>
                    "Failed to create upload folder."
            ]);

            exit();
        }
    }


    // --------------------------------------------------
    // UNIQUE FILE NAME
    // --------------------------------------------------

    $newFileName =
        "ngo_" .
        time() .
        "_" .
        uniqid() .
        "." .
        $fileExt;


    $filePath =
        $uploadFolder .
        $newFileName;


    // --------------------------------------------------
    // UPLOAD DOCUMENT
    // --------------------------------------------------

    if (!move_uploaded_file(
        $fileTmp,
        $filePath
    )) {

        echo json_encode([
            "success" => false,
            "message" =>
                "Failed to upload NGO document."
        ]);

        exit();
    }


    // --------------------------------------------------
    // ESCAPE NGO DATA
    // --------------------------------------------------

    $organizationTypeSafe =
        mysqli_real_escape_string(
            $conn,
            $organizationType
        );

    $filePathSafe =
        mysqli_real_escape_string(
            $conn,
            $filePath
        );

    $establishedYearSafe =
        (int)$establishedYear;


    // --------------------------------------------------
    // NGO INSERT
    // --------------------------------------------------

    $sql = "
        INSERT INTO ngos
        (
            name,
            email,
            password,
            phone,
            location,
            organization_type,
            established_year,
            verification_document,
            verification_status
        )
        VALUES
        (
            '$nameSafe',
            '$emailSafe',
            '$passwordSafe',
            '$phoneSafe',
            '$locationSafe',
            '$organizationTypeSafe',
            $establishedYearSafe,
            '$filePathSafe',
            'Pending'
        )
    ";
}


// ==================================================
// 15. INSERT INTO DATABASE
// ==================================================

$result = mysqli_query(
    $conn,
    $sql
);


// ==================================================
// 16. RESPONSE
// ==================================================

if ($result) {

    echo json_encode([
        "success" => true,
        "message" =>
            "Registration successful."
    ]);

    exit();
}


// ==================================================
// 17. DATABASE ERROR
// ==================================================

echo json_encode([
    "success" => false,
    "message" =>
        "Registration failed: " .
        mysqli_error($conn)
]);

exit();
?>
