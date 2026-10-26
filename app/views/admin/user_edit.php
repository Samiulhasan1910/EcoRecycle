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
                    <a href="index.php?url=admin/users" style="padding: 12px; background: #ddd; color: #333; border-radius: 8px; text-decoration: none; text-align: center; font-weight: bold;">Cancel</a>
                </div>
            </form>
        </div>
    </body>
    </html>
