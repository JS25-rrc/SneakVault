<?php
/**
 * SneakVault CMS - Individual Sneaker Page
 * 
 * Displays detailed information about a single sneaker with comments.
 * 
 * Requirements Met:
 * - 2.7: Navigate pages (5%)
 * - 2.9: Comment on pages with one-to-many relationship (5%)
 * - 2.10: CAPTCHA verification for comments (5%)
 * - 4.2: Sanitize numeric IDs (1%)
 * - 4.3: Sanitize string inputs (1%)
 * - 6.4: Display images (2%)
 */

require('connect.php');
session_start();

// Validate and sanitize ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: index.php");
    exit;
}

// Fetch sneaker details
$query = "SELECT s.*, c.name as category_name, c.id as category_id
          FROM sneakers s 
          LEFT JOIN categories c ON s.category_id = c.id 
          WHERE s.id = :id";
$statement = $db->prepare($query);
$statement->bindValue(':id', $id, PDO::PARAM_INT);
$statement->execute();
$sneaker = $statement->fetch(PDO::FETCH_ASSOC);

if (!$sneaker) {
    header("Location: index.php");
    exit;
}

// Fetch comments (only non-moderated)
$comments_query = "SELECT c.*, u.username 
                   FROM comments c 
                   LEFT JOIN users u ON c.user_id = u.id 
                   WHERE c.sneaker_id = :id AND c.is_moderated = 0
                   ORDER BY c.created_at DESC";
$comments_stmt = $db->prepare($comments_query);
$comments_stmt->bindValue(':id', $id, PDO::PARAM_INT);
$comments_stmt->execute();
$comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle comment submission
$comment_error = '';
$comment_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    // Verify CAPTCHA
    if (!isset($_SESSION['captcha']) || !isset($_POST['captcha']) || 
        strtoupper($_POST['captcha']) !== $_SESSION['captcha']) {
        $comment_error = "Invalid CAPTCHA code. Please try again.";
    } else {
        // Sanitize inputs
        $author_name = isset($_SESSION['username']) 
            ? $_SESSION['username'] 
            : filter_input(INPUT_POST, 'author_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $user_id = $_SESSION['user_id'] ?? null;
        
        // Validation
        if (empty($author_name)) {
            $comment_error = "Name is required.";
        } elseif (empty($content)) {
            $comment_error = "Comment content is required.";
        } elseif (strlen($content) < 10) {
            $comment_error = "Comment must be at least 10 characters long.";
        } else {
            // Insert comment
            $insert_query = "INSERT INTO comments (sneaker_id, user_id, author_name, content) 
                            VALUES (:sneaker_id, :user_id, :author_name, :content)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindValue(':sneaker_id', $id, PDO::PARAM_INT);
            $insert_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $insert_stmt->bindValue(':author_name', $author_name);
            $insert_stmt->bindValue(':content', $content);
            
            if ($insert_stmt->execute()) {
                $comment_success = "Comment submitted successfully!";
                unset($_SESSION['captcha']);
                
                // Refresh page to show new comment
                header("Location: sneaker.php?id=$id&commented=1");
                exit;
            } else {
                $comment_error = "Failed to submit comment. Please try again.";
            }
        }
    }
}

// Check for success message after redirect
if (isset($_GET['commented']) && $_GET['commented'] == '1') {
    $comment_success = "Your comment has been posted successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($sneaker['name']) ?> - SneakVault</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .sneaker-detail {
            margin: 2rem 0;
        }
        
        .sneaker-header {
            text-align: center;
            padding: 2rem 0;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 2rem;
        }
        
        .sneaker-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .sneaker-image img {
            width: 100%;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
        }
        
        .sneaker-info-box {
            background-color: var(--bg-light);
            padding: 2rem;
            border-radius: var(--radius-md);
        }
        
        .info-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--text-light);
        }
        
        .info-value {
            color: var(--text-color);
        }
        
        .sneaker-description {
            padding: 2rem;
            background-color: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
        }
        
        .comments-section {
            margin-top: 3rem;
            padding: 2rem;
            background-color: var(--bg-light);
            border-radius: var(--radius-md);
        }
        
        .comment-form {
            background-color: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
        }
        
        .captcha-group {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 1rem;
            align-items: center;
        }
        
        .captcha-group img {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
        }
        
        .comment {
            background-color: white;
            padding: 1.5rem;
            border-radius: var(--radius-md);
            margin-bottom: 1rem;
            border-left: 3px solid var(--accent-color);
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            color: var(--text-light);
        }
        
        .comment-author {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .comment-date {
            font-size: 0.9rem;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .sneaker-content {
                grid-template-columns: 1fr;
            }
            
            .info-row {
                grid-template-columns: 1fr;
                gap: 0.25rem;
            }
            
            .captcha-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <main class="container">
        <article class="sneaker-detail">
            <!-- Sneaker Header -->
            <div class="sneaker-header">
                <h1><?= htmlspecialchars($sneaker['name']) ?></h1>
                <p style="font-size: 1.2rem; color: var(--text-light);">
                    <strong><?= htmlspecialchars($sneaker['brand']) ?></strong>
                    <?php if($sneaker['colorway']): ?>
                        | <?= htmlspecialchars($sneaker['colorway']) ?>
                    <?php endif; ?>
                </p>
            </div>
            
            <!-- Sneaker Content -->
            <div class="sneaker-content">
                <div class="sneaker-image">
                    <?php if($sneaker['image_path'] && file_exists($sneaker['image_path'])): ?>
                        <img src="<?= htmlspecialchars($sneaker['image_path']) ?>" 
                             alt="<?= htmlspecialchars($sneaker['name']) ?>">
                    <?php else: ?>
                        <img src="images/placeholder.jpg" alt="No image available">
                    <?php endif; ?>
                </div>
                
                <div class="sneaker-details">
                    <div class="sneaker-info-box">
                        <h2 style="margin-bottom: 1.5rem;">Details</h2>
                        
                        <div class="info-row">
                            <div class="info-label">Brand:</div>
                            <div class="info-value"><?= htmlspecialchars($sneaker['brand']) ?></div>
                        </div>
                        
                        <?php if($sneaker['colorway']): ?>
                            <div class="info-row">
                                <div class="info-label">Colorway:</div>
                                <div class="info-value"><?= htmlspecialchars($sneaker['colorway']) ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($sneaker['release_date']): ?>
                            <div class="info-row">
                                <div class="info-label">Release Date:</div>
                                <div class="info-value"><?= date('F j, Y', strtotime($sneaker['release_date'])) ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($sneaker['retail_price']): ?>
                            <div class="info-row">
                                <div class="info-label">Retail Price:</div>
                                <div class="info-value" style="font-size: 1.3rem; font-weight: 700; color: var(--secondary-color);">
                                    $<?= number_format($sneaker['retail_price'], 2) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($sneaker['sku']): ?>
                            <div class="info-row">
                                <div class="info-label">SKU:</div>
                                <div class="info-value"><code><?= htmlspecialchars($sneaker['sku']) ?></code></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="info-row">
                            <div class="info-label">Category:</div>
                            <div class="info-value">
                                <a href="category.php?id=<?= $sneaker['category_id'] ?>">
                                    <?= htmlspecialchars($sneaker['category_name']) ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Description -->
            <div class="sneaker-description">
                <h2>Description</h2>
                <p><?= nl2br(htmlspecialchars($sneaker['description'])) ?></p>
            </div>
            
            <!-- Comments Section -->
            <div class="comments-section">
                <h2>Comments (<?= count($comments) ?>)</h2>
                
                <?php if($comment_error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($comment_error) ?></div>
                <?php endif; ?>
                
                <?php if($comment_success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($comment_success) ?></div>
                <?php endif; ?>
                
                <!-- Comment Form -->
                <form method="post" action="" class="comment-form">
                    <h3>Leave a Comment</h3>
                    
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <div class="form-group">
                            <label for="author_name">Name: <span class="required">*</span></label>
                            <input type="text" 
                                   id="author_name" 
                                   name="author_name" 
                                   required
                                   value="<?= isset($_POST['author_name']) ? htmlspecialchars($_POST['author_name']) : '' ?>">
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="content">Comment: <span class="required">*</span></label>
                        <textarea id="content" 
                                  name="content" 
                                  rows="4" 
                                  required
                                  minlength="10"
                                  placeholder="Share your thoughts about this sneaker..."><?= isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '' ?></textarea>
                        <small>Minimum 10 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="captcha">Security Check: <span class="required">*</span></label>
                        <div class="captcha-group">
                            <img src="captcha.php?<?= time() ?>" alt="CAPTCHA" style="max-width: 200px;">
                            <input type="text" 
                                   id="captcha" 
                                   name="captcha" 
                                   required
                                   placeholder="Enter the code shown"
                                   autocomplete="off">
                        </div>
                        <small>Enter the code from the image above</small>
                    </div>
                    
                    <button type="submit" name="submit_comment" class="btn btn-primary">Submit Comment</button>
                </form>
                
                <!-- Comments List -->
                <?php if(count($comments) > 0): ?>
                    <div class="comments-list">
                        <h3 style="margin-bottom: 1rem;">All Comments</h3>
                        <?php foreach($comments as $comment): ?>
                            <article class="comment">
                                <div class="comment-header">
                                    <span class="comment-author">
                                        <?= htmlspecialchars($comment['author_name']) ?>
                                    </span>
                                    <span class="comment-date">
                                        <?= date('M j, Y \a\t g:i A', strtotime($comment['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="comment-content">
                                    <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-light); padding: 2rem;">
                        No comments yet. Be the first to share your thoughts!
                    </p>
                <?php endif; ?>
            </div>
        </article>
    </main>
    
    <?php include('includes/footer.php'); ?>
</body>
</html>