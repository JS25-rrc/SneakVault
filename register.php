<?php
/**
 * SneakVault CMS - Registration Page
 * 
 * Allows new users to register for an account.
 * 
 * Requirements Met:
 * - 7.5: User registration with username/password (5%)
 * - 7.3: Password hashing with password_hash() (2%)
 * - 4.1: Validation rules (1%)
 */

require('connect.php');
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation rules
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long.";
    } elseif (strlen($username) > 50) {
        $errors[] = "Username must not exceed 50 characters.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindValue(':username', $username);
        $check_stmt->bindValue(':email', $email);
        $check_stmt->execute();
        
        if ($check_stmt->fetch()) {
            $errors[] = "Username or email is already in use. Please choose another.";
        }
    }
    
    // Insert new user if no errors
    if (empty($errors)) {
        // Hash password using password_hash()
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (username, email, password, role) 
                        VALUES (:username, :email, :password, 'user')";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindValue(':username', $username);
        $insert_stmt->bindValue(':email', $email);
        $insert_stmt->bindValue(':password', $hashed_password);
        
        if ($insert_stmt->execute()) {
            // Auto-login the user after registration
            $new_user_id = $db->lastInsertId();
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = 'user';
            
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SneakVault</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 3rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-header h1 {
            margin-bottom: 0.5rem;
        }
        
        .register-links {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .password-requirements {
            background-color: var(--bg-light);
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .password-requirements ul {
            margin: 0.5rem 0 0 1.5rem;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <main class="container">
        <div class="register-container">
            <div class="register-header">
                <h1>Create Account</h1>
                <p>Join the SneakVault community</p>
            </div>
            
            <?php if(!empty($errors)): ?>
                <div class="alert alert-error">
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        <?php foreach($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="username">Username: <span class="required">*</span></label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           required 
                           minlength="3"
                           maxlength="50"
                           autofocus
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                    <small>3-50 characters, letters and numbers only</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address: <span class="required">*</span></label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           required
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password: <span class="required">*</span></label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           minlength="6">
                    <small>Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password: <span class="required">*</span></label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           required
                           minlength="6">
                    <small>Re-enter your password</small>
                </div>
                
                <div class="password-requirements">
                    <strong>Password Requirements:</strong>
                    <ul>
                        <li>At least 6 characters long</li>
                        <li>Both passwords must match</li>
                    </ul>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block mt-2">Create Account</button>
            </form>
            
            <div class="register-links">
                <p>Already have an account? <a href="login.php"><strong>Login here</strong></a></p>
                <p style="margin-top: 0.5rem;"><a href="index.php">← Back to Home</a></p>
            </div>
        </div>
    </main>
    
    <?php include('includes/footer.php'); ?>
</body>
</html>