<?php
/**
 * SneakVault CMS - Search Page
 * 
 * Search functionality with keyword and category filtering.
 * 
 * Requirements Met:
 * - 3.1: Search by keyword (5%)
 * - 3.2: Search with category filter (5%)
 * - 3.3: Paginated search results (5%)
 */

require('connect.php');

// Get search parameters
$keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$category_id = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);

// Pagination
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
$page = max(1, $page);
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Fetch categories for dropdown
$cat_query = "SELECT * FROM categories ORDER BY name ASC";
$categories = $db->query($cat_query)->fetchAll(PDO::FETCH_ASSOC);

$sneakers = [];
$total_results = 0;
$total_pages = 0;

if ($keyword || $category_id) {
    // Build query
    $query = "SELECT s.*, c.name as category_name FROM sneakers s 
              LEFT JOIN categories c ON s.category_id = c.id WHERE 1=1";
    $count_query = "SELECT COUNT(*) FROM sneakers s WHERE 1=1";
    $params = [];
    
    if ($keyword) {
        $search_condition = " AND (s.name LIKE :keyword OR s.brand LIKE :keyword OR s.description LIKE :keyword OR s.colorway LIKE :keyword OR s.sku LIKE :keyword)";
        $query .= $search_condition;
        $count_query .= $search_condition;
        $params[':keyword'] = '%' . $keyword . '%';
    }
    
    if ($category_id) {
        $category_condition = " AND s.category_id = :category_id";
        $query .= $category_condition;
        $count_query .= $category_condition;
        $params[':category_id'] = $category_id;
    }
    
    // Get total count
    $count_stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        if ($key === ':category_id') {
            $count_stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $count_stmt->bindValue($key, $value);
        }
    }
    $count_stmt->execute();
    $total_results = $count_stmt->fetchColumn();
    $total_pages = ceil($total_results / $per_page);
    
    // Get results with pagination
    $query .= " ORDER BY s.created_at DESC LIMIT :limit OFFSET :offset";
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Sneakers - SneakVault</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .search-container {
            background-color: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            margin: 2rem 0;
        }
        
        .search-form {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        
        .search-results-header {
            margin: 2rem 0 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }
        
        .search-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 1rem;
            color: var(--text-light);
        }
        
        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <main class="container">
        <h1 style="margin-top: 2rem;">Search Sneakers</h1>
        
        <div class="search-container">
            <form method="get" action="" class="search-form">
                <div class="form-group" style="margin: 0;">
                    <label for="keyword">Search by keyword:</label>
                    <input type="text" 
                           id="keyword" 
                           name="keyword" 
                           placeholder="Enter sneaker name, brand, colorway, or description..."
                           value="<?= htmlspecialchars($keyword ?? '') ?>"
                           autofocus>
                </div>
                
                <div class="form-group" style="margin: 0;">
                    <label for="category_id">Filter by category:</label>
                    <select id="category_id" name="category_id">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                <?= ($category_id == $category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
        
        <?php if ($keyword || $category_id): ?>
            <div class="search-results-header">
                <h2>Search Results</h2>
                <p>
                    Found <strong><?= $total_results ?></strong> sneaker<?= $total_results !== 1 ? 's' : '' ?>
                    <?php if ($keyword): ?>
                        matching "<strong><?= htmlspecialchars($keyword) ?></strong>"
                    <?php endif; ?>
                    <?php if ($category_id): ?>
                        <?php
                        $selected_cat = array_filter($categories, fn($c) => $c['id'] == $category_id);
                        $selected_cat = reset($selected_cat);
                        ?>
                        in <strong><?= htmlspecialchars($selected_cat['name']) ?></strong>
                    <?php endif; ?>
                </p>
            </div>
            
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
                                    <img src="images/placeholder.jpg" alt="No image available" class="card-image">
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
                
                <?php if($total_pages > 1): ?>
                    <nav class="pagination">
                        <?php if($page > 1): ?>
                            <a href="?keyword=<?= urlencode($keyword ?? '') ?>&category_id=<?= $category_id ?? '' ?>&page=<?= $page - 1 ?>" class="btn btn-secondary">← Previous</a>
                        <?php endif; ?>
                        
                        <span class="page-info">Page <?= $page ?> of <?= $total_pages ?></span>
                        
                        <?php if($page < $total_pages): ?>
                            <a href="?keyword=<?= urlencode($keyword ?? '') ?>&category_id=<?= $category_id ?? '' ?>&page=<?= $page + 1 ?>" class="btn btn-secondary">Next →</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <p class="no-results">No sneakers found matching your search criteria. Try different keywords or categories.</p>
            <?php endif; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; color: var(--text-light);">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <p style="font-size: 1.2rem;">Enter a keyword or select a category to search for sneakers.</p>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include('includes/footer.php'); ?>
</body>
</html>