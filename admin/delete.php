<?php

require('../connect.php');
session_start();

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Validate ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: dashboard.php");
    exit;
}

// Get sneaker info for image deletion
$query = "SELECT image_path, name FROM sneakers WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$sneaker = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sneaker) {
    header("Location: dashboard.php");
    exit;
}

// Handle POST request for confirmed deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Delete associated image file if exists
    if ($sneaker['image_path'] && file_exists('../' . $sneaker['image_path'])) {
        unlink('../' . $sneaker['image_path']);
    }
    
    // Delete from database
    // Comments and API cache will be deleted automatically due to CASCADE
    $delete_query = "DELETE FROM sneakers WHERE id = :id";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bindValue(':id', $id, PDO::PARAM_INT);
    
    if ($delete_stmt->execute()) {
        header("Location: dashboard.php?deleted=1");
        exit;
    } else {
        $error = "Failed to delete sneaker.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Sneaker - SneakVault Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .delete-container {
            max-width: 600px;
            margin: 3rem auto;
            background: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            border-top: 4px solid var(--error);
        }
        
        .sneaker-preview {
            background: var(--bg-light);
            padding: 1.5rem;
            border-radius: var(--radius-sm);
            margin: 1.5rem 0;
        }
        
        .sneaker-preview img {
            max-width: 200px;
            border-radius: var(--radius-sm);
            margin-bottom: 1rem;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 1rem;
            border-radius: var(--radius-sm);
            margin: 1.5rem 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <?php include('../includes/header.php'); ?>
    
    <main class="container">
        <div class="delete-container">
            <h1 style="color: var(--error);">⚠️ Confirm Deletion</h1>
            <p>You are about to permanently delete the following sneaker:</p>
            
            <div class="sneaker-preview">
                <?php if($sneaker['image_path'] && file_exists('../' . $sneaker['image_path'])): ?>
                    <img src="../<?= htmlspecialchars($sneaker['image_path']) ?>" 
                         alt="<?= htmlspecialchars($sneaker['name']) ?>">
                <?php endif; ?>
                <h3><?= htmlspecialchars($sneaker['name']) ?></h3>
            </div>
            
            <div class="warning-box">
                <strong>⚠️ Warning:</strong>
                <ul style="margin: 0.5rem 0 0 1.5rem;">
                    <li>This action <strong>cannot be undone</strong></li>
                    <li>The sneaker will be permanently removed from the database</li>
                    <li>Associated image will be deleted from the server</li>
                    <li>All comments on this sneaker will be deleted</li>
                    <li>API cache data will be removed</li>
                </ul>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?id=<?= $id ?>">
                <p style="margin: 1.5rem 0;"><strong>Are you absolutely sure you want to delete this sneaker?</strong></p>
                
                <div class="action-buttons">
                    <button type="submit" name="confirm_delete" class="btn btn-danger">
                        Yes, Delete Permanently
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        No, Cancel
                    </a>
                </div>
            </form>
        </div>
    </main>
    
    <?php include('../includes/footer.php'); ?>
</body>
</html>