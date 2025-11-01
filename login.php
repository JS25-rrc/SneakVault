<?php
/**
 * SneakVault CMS - Login Page
 * 
 * Allows users to log in with username and password.
 * 
 * Requirements Met:
 * - 7.4: Login functionality with username/password (5%)
 * - 7.3: Support for hashed passwords (2%)
 */

require('connect.php');
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize username input
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        // Query user from database
        $query = "SELECT * FROM users WHERE username = :username";
        $statement = $db->prepare($query);
        $statement->bindValue(':username', $username);
        $statement->execute();
        $user = $statement->fetch(PDO::FETCH_ASSOC);
        
        // Verify password using password_verify()
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SneakVault</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .login-container {
            max-width: 450px;
            margin: 3rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            margin-bottom: 0.5rem;
        }
        
        .login-links {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <main class="container">
        <div class="login-container">
            <div class="login-header">
                <h1>Login to SneakVault</h1>
                <p>Enter your credentials to access your account</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="username">Username: <span class="required">*</span></label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           required 
                           autofocus
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password: <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="login-links">
                <p>Don't have an account? <a href="register.php"><strong>Register here</strong></a></p>
                <p style="margin-top: 0.5rem;"><a href="index.php">← Back to Home</a></p>
            </div>
        </div>
    </main>
    
    <?php include('includes/footer.php'); ?>
</body>
</html>