<?php
require('../connect.php');
session_start();

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$success = '';
$error = '';

// Handle DELETE
if (isset($_GET['delete'])) {
    $delete_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    
    if ($delete_id) {
        $delete_query = "DELETE FROM comments WHERE id = :id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindValue(':id', $delete_id, PDO::PARAM_INT);
        
        if ($delete_stmt->execute()) {
            $success = "Comment deleted successfully!";
        } else {
            $error = "Failed to delete comment.";
        }
    }
}

// Handle DISEMVOWEL
if (isset($_GET['disemvowel'])) {
    $disemvowel_id = filter_input(INPUT_GET, 'disemvowel', FILTER_VALIDATE_INT);
    
    if ($disemvowel_id) {
        // Get current comment
        $get_query = "SELECT content FROM comments WHERE id = :id";
        $get_stmt = $db->prepare($get_query);
        $get_stmt->bindValue(':id', $disemvowel_id, PDO::PARAM_INT);
        $get_stmt->execute();
        $comment = $get_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($comment) {
            // Remove vowels (a, e, i, o, u - case insensitive)
            $disemvoweled = preg_replace('/[aeiouAEIOU]/', '', $comment['content']);
            
            // Update comment and mark as moderated
            $update_query = "UPDATE comments SET content = :content, is_moderated = 1 WHERE id = :id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindValue(':content', $disemvoweled);
            $update_stmt->bindValue(':id', $disemvowel_id, PDO::PARAM_INT);
            
            if ($update_stmt->execute()) {
                $success = "Comment disemvoweled successfully!";
            } else {
                $error = "Failed to disemvowel comment.";
            }
        }
    }
}

// Handle APPROVE (un-moderate)
if (isset($_GET['approve'])) {
    $approve_id = filter_input(INPUT_GET, 'approve', FILTER_VALIDATE_INT);
    
    if ($approve_id) {
        $approve_query = "UPDATE comments SET is_moderated = 0 WHERE id = :id";
        $approve_stmt = $db->prepare($approve_query);
        $approve_stmt->bindValue(':id', $approve_id, PDO::PARAM_INT);
        
        if ($approve_stmt->execute()) {
            $success = "Comment approved!";
        } else {
            $error = "Failed to approve comment.";
        }
    }
}

// Fetch all comments
$comments_query = "SELECT c.*, s.name as sneaker_name, u.username 
                   FROM comments c 
                   LEFT JOIN sneakers s ON c.sneaker_id = s.id 
                   LEFT JOIN users u ON c.user_id = u.id 
                   ORDER BY c.created_at DESC";
$comments = $db->query($comments_query)->fetchAll(PDO::FETCH_ASSOC);

$total_comments = count($comments);
$pending_comments = count(array_filter($comments, fn($c) => $c['is_moderated'] == 0));
$moderated_comments = count(array_filter($comments, fn($c) => $c['is_moderated'] == 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderate Comments - SneakVault Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .stat-box {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            text-align: center;
        }
        
        .stat-box h3 {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 0.25rem;
        }
        
        .comment-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--accent-color);
        }
        
        .comment-card.moderated {
            border-left-color: var(--warning);
            background-color: #fff9e6;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .comment-meta {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .comment-content {
            padding: 1rem;
            background: var(--bg-light);
            border-radius: var(--radius-sm);
            margin: 1rem 0;
            line-height: 1.6;
        }
        
        .comment-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tabs {
            display: flex;
            gap: 1rem;
            margin: 2rem 0;
            border-bottom: 2px solid var(--border-color);
        }
        
        .filter-tabs a {
            padding: 0.75rem 1.5rem;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .filter-tabs a.active {
            border-bottom-color: var(--accent-color);
            color: var(--accent-color);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include('../includes/header.php'); ?>
    
    <main class="container">
        <div style="margin: 2rem 0;">
            <h1>Moderate Comments</h1>
            <p><a href="dashboard.php">← Back to Dashboard</a></p>
        </div>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-row">
            <div class="stat-box">
                <h3><?= $total_comments ?></h3>
                <p>Total Comments</p>
            </div>
            <div class="stat-box">
                <h3><?= $pending_comments ?></h3>
                <p>Pending/Active</p>
            </div>
            <div class="stat-box">
                <h3><?= $moderated_comments ?></h3>
                <p>Moderated</p>
            </div>
        </div>
        
        <!-- Comments List -->
        <div class="comments-section">
            <h2>All Comments</h2>
            
            <?php if(count($comments) > 0): ?>
                <?php foreach($comments as $comment): ?>
                    <div class="comment-card <?= $comment['is_moderated'] ? 'moderated' : '' ?>">
                        <div class="comment-header">
                            <div>
                                <strong style="font-size: 1.1rem;">
                                    <?= htmlspecialchars($comment['author_name']) ?>
                                </strong>
                                <?php if($comment['is_moderated']): ?>
                                    <span class="badge" style="background-color: var(--warning); margin-left: 0.5rem;">Moderated</span>
                                <?php endif; ?>
                                <div class="comment-meta">
                                    On: <a href="../sneaker.php?id=<?= $comment['sneaker_id'] ?>" target="_blank">
                                        <?= htmlspecialchars($comment['sneaker_name']) ?>
                                    </a>
                                    <br>
                                    Posted: <?= date('M j, Y \a\t g:i A', strtotime($comment['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="comment-content">
                            <?= nl2br(htmlspecialchars($comment['content'])) ?>
                        </div>
                        
                        <div class="comment-actions">
                            <a href="../sneaker.php?id=<?= $comment['sneaker_id'] ?>" 
                               class="btn btn-secondary btn-sm" 
                               target="_blank">View Sneaker Page</a>
                            
                            <?php if(!$comment['is_moderated']): ?>
                                <a href="?disemvowel=<?= $comment['id'] ?>" 
                                   class="btn btn-sm"
                                   style="background-color: var(--warning);"
                                   onclick="return confirm('Remove all vowels from this comment?')">Disemvowel</a>
                            <?php else: ?>
                                <a href="?approve=<?= $comment['id'] ?>" 
                                   class="btn btn-success btn-sm"
                                   onclick="return confirm('Restore this comment?')">Restore/Approve</a>
                            <?php endif; ?>
                            
                            <a href="?delete=<?= $comment['id'] ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Permanently delete this comment?')">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-results">No comments yet.</p>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include('../includes/footer.php'); ?>
</body>
</html>