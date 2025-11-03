<?php
    require('connect.php');

    // Validate and sanitize category ID
    $category_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$category_id) {
        header("Location: index.php");
        exit;
    }

    // Fetch category details
    $cat_query = "SELECT * FROM categories WHERE id = :id";
    $cat_stmt = $db->prepare($cat_query);
    $cat_stmt->bindValue(':id', $category_id, PDO::PARAM_INT);
    $cat_stmt->execute();
    $category = $cat_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        header("Location: index.php");
        exit;
    }

    // Pagination setup
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
    $page = max(1, $page);
    $per_page = 12;
    $offset = ($page - 1) * $per_page;

    // Get total count for this category
    $count_query = "SELECT COUNT(*) FROM sneakers WHERE category_id = :category_id";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
    $count_stmt->execute();
    $total_sneakers = $count_stmt->fetchColumn();
    $total_pages = ceil($total_sneakers / $per_page);

    // Fetch sneakers in this category
    $query = "SELECT s.*, c.name as category_name 
            FROM sneakers s 
            LEFT JOIN categories c ON s.category_id = c.id 
            WHERE s.category_id = :category_id
            ORDER BY s.created_at DESC 
            LIMIT :limit OFFSET :offset";
    $statement = $db->prepare($query);
    $statement->bindValue(':category_id', $category_id, PDO::PARAM_INT);
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
    <meta name="description" content="Browse <?= htmlspecialchars($category['name']) ?> sneakers at SneakVault">
    <title><?= htmlspecialchars($category['name']) ?> Sneakers - SneakVault</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .category-header {
            background: linear-gradient(135deg, var(--primary-color), var(--text-light));
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            border-radius: var(--radius-md);
            margin: 2rem 0;
        }
        
        .category-header h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .category-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .breadcrumb {
            margin: 1.5rem 0;
            color: var(--text-light);
        }
        
        .breadcrumb a {
            color: var(--accent-color);
        }
        
        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <main class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php">Home</a> / 
            <a href="sneakers.php">All Sneakers</a> / 
            <strong><?= htmlspecialchars($category['name']) ?></strong>
        </nav>
        
        <!-- Category Header -->
        <header class="category-header">
            <h1><?= htmlspecialchars($category['name']) ?> Sneakers</h1>
            <?php if($category['description']): ?>
                <p><?= htmlspecialchars($category['description']) ?></p>
            <?php endif; ?>
        </header>
        
        <!-- Results Info -->
        <div class="results-info">
            <div>
                <h2 style="margin: 0;">
                    <?= $total_sneakers ?> 
                    <?= $total_sneakers === 1 ? 'Sneaker' : 'Sneakers' ?> 
                    Found
                </h2>
            </div>
            <div>
                <a href="sneakers.php" class="btn btn-secondary">View All Sneakers</a>
            </div>
        </div>
        
        <!-- Sneakers Grid -->
        <?php if(count($sneakers) > 0): ?>
            <section class="grid grid-3">
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
                                
                                <span class="badge"><?= htmlspecialchars($sneaker['category_name']) ?></span>
                                
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
            </section>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
                <nav class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?id=<?= $category_id ?>&page=<?= $page - 1 ?>" class="btn btn-secondary">← Previous</a>
                    <?php endif; ?>
                    
                    <span class="page-info">Page <?= $page ?> of <?= $total_pages ?></span>
                    
                    <?php if($page < $total_pages): ?>
                        <a href="?id=<?= $category_id ?>&page=<?= $page + 1 ?>" class="btn btn-secondary">Next →</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-results">
                <p>No sneakers found in this category yet.</p>
                <p><a href="sneakers.php" class="btn btn-primary">Browse All Sneakers</a></p>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include('includes/footer.php'); ?>
</body>
</html>