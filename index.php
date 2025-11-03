<?php
require('connect.php');

// Pagination setup
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
$page = max(1, $page); // Ensure page is at least 1
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM sneakers";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_sneakers = $count_stmt->fetchColumn();
$total_pages = ceil($total_sneakers / $per_page);

// Fetch sneakers with pagination
$query = "SELECT s.*, c.name as category_name 
          FROM sneakers s 
          LEFT JOIN categories c ON s.category_id = c.id 
          ORDER BY s.created_at DESC 
          LIMIT :limit OFFSET :offset";
$statement = $db->prepare($query);
$statement->bindValue(':limit', $per_page, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();
$sneakers = $statement->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SneakVault - Your source for sneaker releases, designs, and resale pricing trends">
    <title>SneakVault - Your Sneaker Resource</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <main>
        <div class="container">
            <!-- Hero Section -->
            <section class="hero">
                <h1>Welcome to SneakVault</h1>
                <p>Your ultimate resource for sneaker releases, designs, and resale pricing trends from Winnipeg, Manitoba</p>
            </section>
            
            <!-- Sneakers Grid -->
            <section class="sneakers-section">
                <h2 class="text-center mb-2">Latest Sneakers</h2>
                
                <?php if(count($sneakers) > 0): ?>
                    <div class="grid grid-3">
                        <?php foreach($sneakers as $sneaker): ?>
                            <article class="card">
                                <a href="sneaker.php?id=<?= $sneaker['id'] ?>">
                                    <?php if($sneaker['image_path'] && file_exists($sneaker['image_path'])): ?>
                                        <img src="<?= htmlspecialchars($sneaker['image_path']) ?>" 
                                             alt="<?= htmlspecialchars($sneaker['name']) ?>" 
                                             class="card-image">
                                    <?php else: ?>
                                        <img src="images/placeholder.jpg" 
                                             alt="No image available" 
                                             class="card-image">
                                    <?php endif; ?>
                                    
                                    <div class="card-body">
                                        <h3 class="card-title"><?= htmlspecialchars($sneaker['name']) ?></h3>
                                        
                                        <p class="card-text">
                                            <strong><?= htmlspecialchars($sneaker['brand']) ?></strong>
                                            <?php if($sneaker['colorway']): ?>
                                                <br>
                                                <em><?= htmlspecialchars($sneaker['colorway']) ?></em>
                                            <?php endif; ?>
                                        </p>
                                        
                                        <?php if($sneaker['category_name']): ?>
                                            <span class="badge"><?= htmlspecialchars($sneaker['category_name']) ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if($sneaker['retail_price']): ?>
                                            <p class="card-text mt-1">
                                                <strong style="color: var(--secondary-color); font-size: 1.2rem;">
                                                    $<?= number_format($sneaker['retail_price'], 2) ?>
                                                </strong>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                        <nav class="pagination">
                            <?php if($page > 1): ?>
                                <a href="?page=<?= $page - 1 ?>" class="btn btn-secondary">← Previous</a>
                            <?php endif; ?>
                            
                            <span class="page-info">Page <?= $page ?> of <?= $total_pages ?></span>
                            
                            <?php if($page < $total_pages): ?>
                                <a href="?page=<?= $page + 1 ?>" class="btn btn-secondary">Next →</a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <p class="no-results">No sneakers found. Check back soon!</p>
                <?php endif; ?>
            </section>
        </div>
    </main>
    
    <?php include('includes/footer.php'); ?>
</body>
</html>