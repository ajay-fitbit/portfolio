<?php
include('../includes/config.php');

// Drop the table if it exists with wrong structure and recreate
$drop_table = true;
$table_check = $conn->query("SHOW TABLES LIKE 'admin_users'");
if ($table_check->num_rows > 0) {
    // Table exists, verify structure
    $expected_columns = [
        'id' => ['Type' => 'int', 'Null' => 'NO', 'Key' => 'PRI', 'Extra' => 'auto_increment'],
        'username' => ['Type' => 'varchar(50)', 'Null' => 'NO', 'Key' => 'UNI'],
        'password' => ['Type' => 'varchar(255)', 'Null' => 'NO'],
        'email' => ['Type' => 'varchar(255)', 'Null' => 'NO', 'Key' => 'UNI'],
        'created_at' => ['Type' => 'timestamp']
    ];
    
    $structure_check = $conn->query("DESCRIBE admin_users");
    if ($structure_check) {
        $existing_columns = [];
        while ($row = $structure_check->fetch_assoc()) {
            $existing_columns[$row['Field']] = [
                'Type' => strtolower($row['Type']),
                'Null' => $row['Null'],
                'Key' => $row['Key'],
                'Extra' => $row['Extra']
            ];
        }
        
        $structure_valid = true;
        foreach ($expected_columns as $column => $props) {
            if (!isset($existing_columns[$column])) {
                $structure_valid = false;
                break;
            }
            foreach ($props as $prop => $value) {
                if (isset($existing_columns[$column][$prop]) && 
                    strtolower($existing_columns[$column][$prop]) != strtolower($value)) {
                    $structure_valid = false;
                    break 2;
                }
            }
        }
        
        if ($structure_valid) {
            $drop_table = false;
        }
    }
}

if ($drop_table) {
    // Drop existing table if it exists
    $conn->query("DROP TABLE IF EXISTS admin_users");
    
    // Create table with correct structure
    $sql = "CREATE TABLE admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if ($conn->query($sql) === FALSE) {
        die("Error creating admin_users table: " . $conn->error);
    }
    
    // Verify table was created
    $table_check = $conn->query("SHOW TABLES LIKE 'admin_users'");
    if ($table_check->num_rows === 0) {
        die("Failed to create admin_users table.");
    }
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate input
    if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['email'])) {
        $message = '<div class="error">All fields are required!</div>';
    } else {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Password validation
        if (strlen($password) < 8) {
            $message = '<div class="error">Password must be at least 8 characters long!</div>';
        } elseif ($password !== $confirm_password) {
            $message = '<div class="error">Passwords do not match!</div>';
        } else {
            // First verify if the table exists and has the right structure
            $verify_sql = "SHOW COLUMNS FROM admin_users LIKE 'email'";
            $result = $conn->query($verify_sql);
            if ($result->num_rows === 0) {
                // Add email column if it doesn't exist
                $alter_sql = "ALTER TABLE admin_users ADD email VARCHAR(255) NOT NULL UNIQUE";
                if (!$conn->query($alter_sql)) {
                    $message = '<div class="error">Error updating table structure: ' . $conn->error . '</div>';
                    die($message);
                }
            }

            // Check if username or email already exists
            $check_stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
            if ($check_stmt === false) {
                $message = '<div class="error">Prepare failed: ' . $conn->error . '</div>';
            } else {
                $check_stmt->bind_param("ss", $username, $email);
                if ($check_stmt->execute()) {
                    $result = $check_stmt->get_result();
                    if ($result->num_rows > 0) {
                        $message = '<div class="error">Username or email already exists!</div>';
                    } else {
                        // Hash password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        // Insert new admin user
                        $insert_stmt = $conn->prepare("INSERT INTO admin_users (username, password, email) VALUES (?, ?, ?)");
                        if ($insert_stmt === false) {
                            $message = '<div class="error">Prepare failed: ' . $conn->error . '</div>';
                        } else {
                            $insert_stmt->bind_param("sss", $username, $hashed_password, $email);
                            if ($insert_stmt->execute()) {
                                $message = '<div class="success">Admin user created successfully! Please delete this setup file for security.</div>';
                            } else {
                                $message = '<div class="error">Error creating admin user: ' . $conn->error . '</div>';
                            }
                            $insert_stmt->close();
                        }
                    }
                    $result->close();
                } else {
                    $message = '<div class="error">Error checking existing user: ' . $check_stmt->error . '</div>';
                }
                $check_stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin User</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .setup-container {
            max-width: 500px;
            margin: 40px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn-submit {
            background-color: #4a90e2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        .btn-submit:hover {
            background-color: #357abd;
        }
        .error {
            color: #dc3545;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #dc3545;
            border-radius: 4px;
            background-color: #f8d7da;
        }
        .success {
            color: #28a745;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #28a745;
            border-radius: 4px;
            background-color: #d4edda;
        }
        .warning {
            color: #856404;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ffeeba;
            border-radius: 4px;
            background-color: #fff3cd;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1>Setup Admin User</h1>
        
        <div class="warning">
            Warning: This file should be deleted after creating the admin user for security purposes.
        </div>

        <?php echo $message; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password (minimum 8 characters)</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
            </div>

            <button type="submit" class="btn-submit">Create Admin User</button>
        </form>
    </div>
</body>
</html>
