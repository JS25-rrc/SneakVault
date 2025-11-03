<?php
require('../connect.php');
session_start();

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$errors = [];
$success = '';

// Handle CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Validation
    if (empty($username) || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if (!in_array($role, ['admin', 'user'])) {
        $errors[] = "Invalid role selected.";
    }
    
    // Check if username/email exists
    if (empty($errors)) {
        $check_query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindValue(':username', $username);
        $check_stmt->bindValue(':email', $email);
        $check_stmt->execute();
        if ($check_stmt->fetch()) {
            $errors[] = "Username or email already exists.";
        }
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindValue(':username', $username);
        $insert_stmt->bindValue(':email', $email);
        $insert_stmt->bindValue(':password', $hashed_password);
        $insert_stmt->bindValue(':role', $role);
        
        if ($insert_stmt->execute()) {
            $success = "User created successfully!";
        } else {
            $errors[] = "Failed to create user.";
        }
    }
}

// Handle UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    
    if (empty($errors)) {
        // Build update query
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET username = :username, email = :email, password = :password, role = :role WHERE id = :id";
        } else {
            $update_query = "UPDATE users SET username = :username, email = :email, role = :role WHERE id = :id";
        }
        
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindValue(':username', $username);
        $update_stmt->bindValue(':email', $email);
        $update_stmt->bindValue(':role', $role);
        $update_stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        if (!empty($password)) {
            $update_stmt->bindValue(':password', $hashed_password);
        }
        
        if ($update_stmt->execute()) {
            $success = "User updated successfully!";
        } else {
            $errors[] = "Failed to update user.";
        }
    }
}

// Handle DELETE
if (isset($_GET['delete'])) {
    $delete_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    
    // Prevent deleting yourself
    if ($delete_id == $_SESSION['user_id']) {
        $errors[] = "You cannot delete your own account while logged in.";
    } elseif ($delete_id) {
        $delete_query = "DELETE FROM users WHERE id = :id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindValue(':id', $delete_id, PDO::PARAM_INT);
        
        if ($delete_stmt->execute()) {
            $success = "User deleted successfully!";
        } else {
            $errors[] = "Failed to delete user.";
        }
    }
}

// Fetch all users
$users_query = "SELECT u.*, COUNT(DISTINCT c.id) as comment_count 
                FROM users u 
                LEFT JOIN comments c ON u.id = c.user_id 
                GROUP BY u.id 
                ORDER BY u.created_at DESC";
$users = $db->query($users_query)->fetchAll(PDO::FETCH_ASSOC);

// Get user for editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $edit_id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($edit_id) {
        $edit_query = "SELECT * FROM users WHERE id = :id";
        $edit_stmt = $db->prepare($edit_query);
        $edit_stmt->bindValue(':id', $edit_id, PDO::PARAM_INT);
        $edit_stmt->execute();
        $edit_user = $edit_stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$total_users = count($users);
$admin_count = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$user_count = count(array_filter($users, fn($u) => $u['role'] === 'user'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - SneakVault Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .users-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .user-form {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .users-list {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-box {
            background: var(--bg-light);
            padding: 1rem;
            border-radius: var(--radius-sm);
            text-align: center;
        }
        
        .stat-box h3 {
            font-size: 2rem;
            color: var(--accent-color);
            margin: 0;
        }
        
        @media (max-width: 968px) {
            .users-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include('../includes/header.php'); ?>
    
    <main class="container">
        <div style="margin: 2rem 0;">
            <h1>Manage Users</h1>
            <p><a href="dashboard.php">← Back to Dashboard</a></p>
        </div>
        
        <?php if(!empty($errors)): ?>
            <div class="alert alert-error">
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
        
        <div class="users-container">
            <!-- Form Section -->
            <div class="user-form">
                <h2><?= $edit_user ? 'Edit User' : 'Add New User' ?></h2>
                
                <form method="post" action="">
                    <?php if($edit_user): ?>
                        <input type="hidden" name="id" value="<?= $edit_user['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="username">Username: <span class="required">*</span></label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               required
                               minlength="3"
                               value="<?= $edit_user ? htmlspecialchars($edit_user['username']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email: <span class="required">*</span></label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               required
                               value="<?= $edit_user ? htmlspecialchars($edit_user['email']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password: <?= $edit_user ? '' : '<span class="required">*</span>' ?></label>
                        <input type="password" 
                               id="password" 
                               name="password"
                               minlength="6"
                               <?= $edit_user ? '' : 'required' ?>>
                        <?php if($edit_user): ?>
                            <small>Leave empty to keep current password</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role: <span class="required">*</span></label>
                        <select id="role" name="role" required>
                            <option value="user" <?= ($edit_user && $edit_user['role'] === 'user') ? 'selected' : '' ?>>User</option>
                            <option value="admin" <?= ($edit_user && $edit_user['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    
                    <?php if($edit_user): ?>
                        <button type="submit" name="update_user" class="btn btn-primary btn-block">Update User</button>
                        <a href="manage_users.php" class="btn btn-secondary btn-block mt-1">Cancel Edit</a>
                    <?php else: ?>
                        <button type="submit" name="create_user" class="btn btn-primary btn-block">Create User</button>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Users List Section -->
            <div class="users-list">
                <h2>All Users (<?= $total_users ?>)</h2>
                
                <div class="stats-row">
                    <div class="stat-box">
                        <h3><?= $total_users ?></h3>
                        <p>Total Users</p>
                    </div>
                    <div class="stat-box">
                        <h3><?= $admin_count ?></h3>
                        <p>Admins</p>
                    </div>
                    <div class="stat-box">
                        <h3><?= $user_count ?></h3>
                        <p>Regular Users</p>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Comments</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($user['username']) ?></strong>
                                        <?php if($user['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge badge-secondary">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <?php if($user['role'] === 'admin'): ?>
                                            <span class="badge badge-danger">Admin</span>
                                        <?php else: ?>
                                            <span class="badge">User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $user['comment_count'] ?></td>
                                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="?edit=<?= $user['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="?delete=<?= $user['id'] ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Delete user <?= htmlspecialchars($user['username']) ?>?')">Delete</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <?php include('../includes/footer.php'); ?>
</body>
</html>