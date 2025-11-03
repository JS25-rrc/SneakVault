<?php
require('connect.php');

// Get filter parameters
$filter_category = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$filter_brand = filter_input(INPUT_GET, 'brand', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Pagination setup
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
$page = max(1, $page);
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query based on filters
$where_conditions = [];
$params = [];

if ($filter_category) {
    $where_conditions[] = "s.category_id = :category_id";
    $params[':category_id'] = $filter_category;
}

if ($filter_brand) {
    $where_conditions[] = "s.brand = :brand";
    $params[':brand'] = $filter_brand;
}

$where_clause = '';
if (count($where_conditions) > 0) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count
$count_query = "SELECT COUNT(*) FROM sneakers s $where_clause";
$count_stmt = $db->prepare($count_query);
foreach ($params as $key => $value) {
    if ($key === ':category_id') {
        $count_stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $count_stmt->bindValue($key, $value);
    }
}
$count_stmt->execute();
$total_sneakers = $count_stmt->fetchColumn();
$total_pages = ceil($total_sneakers / $per_page);

// Fetch sneakers
$query = "SELECT s.*, c.name as category_name 
          FROM sneakers s 
          LEFT JOIN categories c ON s.category_id = c.id 
          $where_clause
          ORDER BY s.created_at DESC 
          LIMIT :limit OFFSET :offset";
$statement = $db->prepare($query);

foreach ($params as $key => $value) {
    if ($key === ':category_id') {
        $statement->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $statement->bindValue($key, $value);
    }
}
$statement->bindValue(':limit', $per_page, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();
$sneakers = $statement->fetchAll(PDO::FETCH_ASSOC);

// Fetch all categories for filter dropdown
$cat_query = "SELECT * FROM categories ORDER BY name ASC";
$categories = $db->query($cat_query)->fetchAll(PDO::FETCH_ASSOC);

// Fetch unique brands for filter
$brand_query = "SELECT DISTINCT brand FROM sneakers ORDER BY brand ASC";
$brands = $db->query($brand_query)->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse all sneakers at SneakVault">
    <title>All Sneakers - SneakVault</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .page-header {
            text-align: center;
            padding: 2rem 0;
            border-bottom: 2px solid var(--border-color);
            margin-bottom: 2rem;
        }
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }
        
        .filters-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        .results-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: var(--bg-light);
            border-radius: var(--radius-sm);
        }
        
        .clear-filters {
            margin-left: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .filters-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <main class="container">
        <!-- Page Header -->
        <header class="page-header">
            <h1>All Sneakers</h1>
            <p>Browse our complete collection of sneakers</p>
        </header>
        
        <!-- Filters -->
        <div class="filters">
            <form method="get" action="" class="filters-form">
                <div class="form-group" style="margin: 0;">
                    <label for="category">Filter by Category:</label>
                    <select id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                <?= ($filter_category == $category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin: 0;">
                    <label for="brand">Filter by Brand:</label>
                    <select id="brand" name="brand">
                        <option value="">All Brands</option>
                        <?php foreach($brands as $brand): ?>
                            <option value="<?= htmlspecialchars($brand) ?>" 
                                <?= ($filter_brand === $brand) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($brand) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <?php if($filter_category || $filter_brand): ?>
                        <a href="sneakers.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Results Info -->
        <div class="results-info">
            <div>
                <strong><?= $total_sneakers ?></strong> 
                <?= $total_sneakers === 1 ? 'sneaker' : 'sneakers' ?> found
                <?php if($filter_category || $filter_brand): ?>
                    <span style="color: var(--text-light);">
                        (filtered)
                    </span>
                <?php endif; ?>
            </div>
            <div>
                <?php if($page > 1): ?>
                    <span style="color: var(--text-light);">
                        Showing <?= $offset + 1 ?>-<?= min($offset + $per_page, $total_sneakers) ?> of <?= $total_sneakers ?>
                    </span>
                <?php endif; ?>
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
            </section>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
                <nav class="pagination">
                    <?php if($page > 1): ?>
                        <?php
                        $prev_params = http_build_query(array_merge($_GET, ['page' => $page - 1]));
                        ?>
                        <a href="?<?= $prev_params ?>" class="btn btn-secondary">← Previous</a>
                    <?php endif; ?>
                    
                    <span class="page-info">Page <?= $page ?> of <?= $total_pages ?></span>
                    
                    <?php if($page < $total_pages): ?>
                        <?php
                        $next_params = http_build_query(array_merge($_GET, ['page' => $page + 1]));
                        ?>
                        <a href="?<?= $next_params ?>" class="btn btn-secondary">Next →</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-results">
                <p>No sneakers found matching your criteria.</p>
                <p><a href="sneakers.php" class="btn btn-primary">View All Sneakers</a></p>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include('includes/footer.php'); ?>
</body>
</html>