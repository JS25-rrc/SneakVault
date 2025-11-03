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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $slug = filter_input(INPUT_POST, 'slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    if (empty($name)) {
        $errors[] = "Category name is required.";
    }
    if (empty($slug)) {
        $slug = strtolower(str_replace(' ', '-', $name));
    }
    
    // Check if slug already exists
    if (empty($errors)) {
        $check_query = "SELECT id FROM categories WHERE slug = :slug";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindValue(':slug', $slug);
        $check_stmt->execute();
        if ($check_stmt->fetch()) {
            $errors[] = "A category with this slug already exists.";
        }
    }
    
    if (empty($errors)) {
        $insert_query = "INSERT INTO categories (name, slug, description) VALUES (:name, :slug, :description)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindValue(':name', $name);
        $insert_stmt->bindValue(':slug', $slug);
        $insert_stmt->bindValue(':description', $description);
        
        if ($insert_stmt->execute()) {
            $success = "Category created successfully!";
        } else {
            $errors[] = "Failed to create category.";
        }
    }
}

// Handle UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $slug = filter_input(INPUT_POST, 'slug', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    if (empty($name)) {
        $errors[] = "Category name is required.";
    }
    
    if (empty($errors)) {
        $update_query = "UPDATE categories SET name = :name, slug = :slug, description = :description WHERE id = :id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindValue(':name', $name);
        $update_stmt->bindValue(':slug', $slug);
        $update_stmt->bindValue(':description', $description);
        $update_stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        if ($update_stmt->execute()) {
            $success = "Category updated successfully!";
        } else {
            $errors[] = "Failed to update category.";
        }
    }
}

// Handle DELETE
if (isset($_GET['delete'])) {
    $delete_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    
    if ($delete_id) {
        // Check if category has sneakers
        $check_query = "SELECT COUNT(*) FROM sneakers WHERE category_id = :id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindValue(':id', $delete_id, PDO::PARAM_INT);
        $check_stmt->execute();
        $sneaker_count = $check_stmt->fetchColumn();
        
        if ($sneaker_count > 0) {
            $errors[] = "Cannot delete category. It has {$sneaker_count} sneaker(s) assigned to it. Reassign or delete those sneakers first.";
        } else {
            $delete_query = "DELETE FROM categories WHERE id = :id";
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->bindValue(':id', $delete_id, PDO::PARAM_INT);
            
            if ($delete_stmt->execute()) {
                $success = "Category deleted successfully!";
            } else {
                $errors[] = "Failed to delete category.";
            }
        }
    }
}

// Fetch all categories
$categories_query = "SELECT c.*, COUNT(s.id) as sneaker_count 
                     FROM categories c 
                     LEFT JOIN sneakers s ON c.id = s.category_id 
                     GROUP BY c.id 
                     ORDER BY c.name ASC";
$categories = $db->query($categories_query)->fetchAll(PDO::FETCH_ASSOC);

// Get category for editing if edit parameter exists
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($edit_id) {
        $edit_query = "SELECT * FROM categories WHERE id = :id";
        $edit_stmt = $db->prepare($edit_query);
        $edit_stmt->bindValue(':id', $edit_id, PDO::PARAM_INT);
        $edit_stmt->execute();
        $edit_category = $edit_stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - SneakVault Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .categories-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .category-form {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .categories-list {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }
        
        .category-item {
            padding: 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            margin-bottom: 1rem;
        }
        
        .category-item h3 {
            margin-bottom: 0.5rem;
        }
        
        .category-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        @media (max-width: 968px) {
            .categories-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include('../includes/header.php'); ?>
    
    <main class="container">
        <div style="margin: 2rem 0;">
            <h1>Manage Categories</h1>
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
        
        <div class="categories-container">
            <!-- Form Section -->
            <div class="category-form">
                <h2><?= $edit_category ? 'Edit Category' : 'Add New Category' ?></h2>
                
                <form method="post" action="">
                    <?php if($edit_category): ?>
                        <input type="hidden" name="id" value="<?= $edit_category['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="name">Category Name: <span class="required">*</span></label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               required
                               value="<?= $edit_category ? htmlspecialchars($edit_category['name']) : '' ?>"
                               placeholder="e.g., Basketball">
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">Slug:</label>
                        <input type="text" 
                               id="slug" 
                               name="slug"
                               value="<?= $edit_category ? htmlspecialchars($edit_category['slug']) : '' ?>"
                               placeholder="e.g., basketball (auto-generated if empty)">
                        <small>URL-friendly version of the name</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" 
                                  name="description" 
                                  rows="4"
                                  placeholder="Brief description of this category"><?= $edit_category ? htmlspecialchars($edit_category['description']) : '' ?></textarea>
                    </div>
                    
                    <?php if($edit_category): ?>
                        <button type="submit" name="update_category" class="btn btn-primary btn-block">Update Category</button>
                        <a href="manage_categories.php" class="btn btn-secondary btn-block mt-1">Cancel Edit</a>
                    <?php else: ?>
                        <button type="submit" name="create_category" class="btn btn-primary btn-block">Create Category</button>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Categories List Section -->
            <div class="categories-list">
                <h2>All Categories (<?= count($categories) ?>)</h2>
                
                <?php if(count($categories) > 0): ?>
                    <?php foreach($categories as $category): ?>
                        <div class="category-item">
                            <h3><?= htmlspecialchars($category['name']) ?></h3>
                            <p><strong>Slug:</strong> <?= htmlspecialchars($category['slug']) ?></p>
                            <?php if($category['description']): ?>
                                <p><?= htmlspecialchars($category['description']) ?></p>
                            <?php endif; ?>
                            <p style="color: var(--text-light);">
                                <strong><?= $category['sneaker_count'] ?></strong> 
                                <?= $category['sneaker_count'] === 1 ? 'sneaker' : 'sneakers' ?>
                            </p>
                            
                            <div class="category-actions">
                                <a href="../category.php?id=<?= $category['id'] ?>" 
                                   class="btn btn-secondary btn-sm" 
                                   target="_blank">View Page</a>
                                <a href="?edit=<?= $category['id'] ?>" 
                                   class="btn btn-primary btn-sm">Edit</a>
                                <?php if($category['sneaker_count'] == 0): ?>
                                    <a href="?delete=<?= $category['id'] ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                                <?php else: ?>
                                    <button class="btn btn-sm" 
                                            style="background-color: var(--text-light); cursor: not-allowed;"
                                            disabled
                                            title="Cannot delete - has sneakers assigned">Delete</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-results">No categories created yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include('../includes/footer.php'); ?>
</body>
</html>