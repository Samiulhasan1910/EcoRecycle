<?php

session_start();
include("db.php");

// SECURITY
if(!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// DELETE USER
if($action == 'delete' && $userId > 0) {
    $sql = "DELETE FROM users WHERE user_id = $userId";
    
    if(mysqli_query($conn, $sql)) {
        header("Location: user_management.php?message=User deleted successfully");
    } else {
        header("Location: user_management.php?error=Failed to delete user");
    }
    exit();
}

// EDIT USER - Show form
if($action == 'edit' && $userId > 0) {
    $userResult = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $userId");
    
    if(mysqli_num_rows($userResult) == 0) {
        header("Location: user_management.php");
        exit();
    }
    
    $user = mysqli_fetch_assoc($userResult);
    
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $location = trim($_POST['location'] ?? '');
        
        if($name && $email && $phone && $location) {
            $name = mysqli_real_escape_string($conn, $name);
            $email = mysqli_real_escape_string($conn, $email);
            $phone = mysqli_real_escape_string($conn, $phone);
            $location = mysqli_real_escape_string($conn, $location);
            
            $sql = "UPDATE users SET name='$name', email='$email', phone='$phone', location='$location' WHERE user_id=$userId";
            
            if(mysqli_query($conn, $sql)) {
                header("Location: user_management.php?message=User updated successfully");
                exit();
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit User - Admin</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background: #f4f8f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.06);
            max-width: 500px;
            width: 100%;
        }
        
        h2 {
            color: #168a45;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        input:focus {
            outline: none;
            border-color: #168a45;
        }
        
        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
        }
        
        .btn-save {
            background: #168a45;
            color: white;
        }
        
        .btn-save:hover {
            background: #117239;
        }
        
        .btn-cancel {
            background: #ddd;
            color: #333;
        }
        
        .btn-cancel:hover {
            background: #ccc;
        }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Edit User</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($user['location']); ?>" required>
                </div>
                
                <div class="buttons">
                    <button type="submit" class="btn-save">Save Changes</button>
                    <a href="user_management.php" style="padding: 12px; background: #ddd; color: #333; border-radius: 8px; text-decoration: none; text-align: center; font-weight: bold;">Cancel</a>
                </div>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// If no valid action, redirect
header("Location: user_management.php");
exit();

?>
