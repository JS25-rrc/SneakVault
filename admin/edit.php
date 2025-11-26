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

// Fetch categories
$cat_query = "SELECT * FROM categories ORDER BY name ASC";
$categories = $db->query($cat_query)->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing sneaker
$query = "SELECT * FROM sneakers WHERE id = :id";
$statement = $db->prepare($query);
$statement->bindValue(':id', $id, PDO::PARAM_INT);
$statement->execute();
$sneaker = $statement->fetch(PDO::FETCH_ASSOC);

if (!$sneaker) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $brand = filter_input(INPUT_POST, 'brand', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $colorway = filter_input(INPUT_POST, 'colorway', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $release_date = filter_input(INPUT_POST, 'release_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $retail_price = filter_input(INPUT_POST, 'retail_price', FILTER_VALIDATE_FLOAT);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $sku = filter_input(INPUT_POST, 'sku', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Validation
    if (empty($name)) {
        $errors[] = "Sneaker name is required.";
    }
    if (empty($brand)) {
        $errors[] = "Brand is required.";
    }
    if (empty($description)) {
        $errors[] = "Description is required.";
    }
    if (!$category_id) {
        $errors[] = "Please select a valid category.";
    }
    
    // Handle image deletion
    $image_path = $sneaker['image_path'];
    if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if ($sneaker['image_path'] && file_exists('../' . $sneaker['image_path'])) {
            unlink('../' . $sneaker['image_path']);
        }
        $image_path = null;
    }
    
    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_info = getimagesize($_FILES['image']['tmp_name']);
        
        if ($file_info && in_array($file_info['mime'], $allowed_types)) {
            $upload_dir = '../uploads/images/';
            
            // Delete old image
            if ($sneaker['image_path'] && file_exists('../' . $sneaker['image_path'])) {
                unlink('../' . $sneaker['image_path']);
            }
            
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('sneaker_') . '.' . $extension;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = 'uploads/images/' . $filename;
                resize_image($target_path, 800, 800);
            } else {
                $errors[] = "Failed to upload image.";
            }
        } else {
            $errors[] = "Invalid image file.";
        }
    }
    
    // Update if no errors
    if (empty($errors)) {
        $update_query = "UPDATE sneakers SET 
                        name = :name, 
                        brand = :brand, 
                        colorway = :colorway, 
                        release_date = :release_date, 
                        retail_price = :retail_price, 
                        description = :description, 
                        image_path = :image_path, 
                        category_id = :category_id, 
                        sku = :sku 
                        WHERE id = :id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindValue(':name', $name);
        $update_stmt->bindValue(':brand', $brand);
        $update_stmt->bindValue(':colorway', $colorway);
        $update_stmt->bindValue(':release_date', $release_date);
        $update_stmt->bindValue(':retail_price', $retail_price);
        $update_stmt->bindValue(':description', $description);
        $update_stmt->bindValue(':image_path', $image_path);
        $update_stmt->bindValue(':category_id', $category_id, PDO::PARAM_INT);
        $update_stmt->bindValue(':sku', $sku);
        $update_stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        if ($update_stmt->execute()) {
            header("Location: dashboard.php?updated=1");
            exit;
        } else {
            $errors[] = "Failed to update sneaker.";
        }
    }
}

function resize_image($file, $max_width, $max_height) {
    list($width, $height, $type) = getimagesize($file);
    
    if ($width <= $max_width && $height <= $max_height) {
        return;
    }
    
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    $src = null;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($file);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($file);
            break;
        case IMAGETYPE_GIF:
            $src = imagecreatefromgif($file);
            break;
        case IMAGETYPE_WEBP:
            $src = imagecreatefromwebp($file);
            break;
        default:
            return;
    }
    
    $dst = imagecreatetruecolor($new_width, $new_height);
    
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($dst, $file, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($dst, $file, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($dst, $file);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($dst, $file, 90);
            break;
    }
    
    imagedestroy($src);
    imagedestroy($dst);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sneaker - SneakVault Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://cdn.tiny.cloud/1/bwute7jbo1grqpzuyk18j59wptoo9ctgfd38gcw3apkb252h/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#description',
            height: 300,
            menubar: false,
            plugins: 'lists link',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link',
            setup: function(editor) {
                editor.on('init', function() {
                    // Remove required attribute from hidden textarea
                    document.getElementById('description').removeAttribute('required');
                });
            }
        });
        
        // Add form validation before submit
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                const content = tinymce.get('description').getContent();
                if (!content || content.trim() === '') {
                    e.preventDefault();
                    alert('Description is required.');
                    tinymce.get('description').focus();
                    return false;
                }
            });
        });
    </script>
    <style>
        .admin-form {
            background: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            max-width: 900px;
            margin: 0 auto;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        .current-image {
            max-width: 300px;
            border-radius: var(--radius-sm);
            margin: 1rem 0;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include('../includes/header.php'); ?>
    
    <main class="container" style="margin-top: 2rem; margin-bottom: 3rem;">
        <div style="margin-bottom: 2rem;">
            <h1>Edit Sneaker: <?= htmlspecialchars($sneaker['name']) ?></h1>
            <p><a href="dashboard.php">← Back to Dashboard</a></p>
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
        
        <form method="post" action="" enctype="multipart/form-data" class="admin-form">
            <div class="form-group">
                <label for="name">Sneaker Name: <span class="required">*</span></label>
                <input type="text" id="name" name="name" required 
                       value="<?= htmlspecialchars($sneaker['name']) ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="brand">Brand: <span class="required">*</span></label>
                    <input type="text" id="brand" name="brand" required 
                           value="<?= htmlspecialchars($sneaker['brand']) ?>">
                </div>
                
                <div class="form-group">
                    <label for="colorway">Colorway:</label>
                    <input type="text" id="colorway" name="colorway" 
                           value="<?= htmlspecialchars($sneaker['colorway']) ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="release_date">Release Date:</label>
                    <input type="date" id="release_date" name="release_date" 
                           value="<?= $sneaker['release_date'] ?>">
                </div>
                
                <div class="form-group">
                    <label for="retail_price">Retail Price ($):</label>
                    <input type="number" id="retail_price" name="retail_price" 
                           step="0.01" min="0" 
                           value="<?= $sneaker['retail_price'] ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="category_id">Category: <span class="required">*</span></label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                <?= ($sneaker['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="sku">SKU:</label>
                    <input type="text" id="sku" name="sku" 
                           value="<?= htmlspecialchars($sneaker['sku']) ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description: <span class="required">*</span></label>
                <textarea id="description" name="description" rows="6" required><?= htmlspecialchars($sneaker['description']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Current Image:</label>
                <?php if($sneaker['image_path'] && file_exists('../' . $sneaker['image_path'])): ?>
                    <img src="../<?= htmlspecialchars($sneaker['image_path']) ?>" 
                         alt="<?= htmlspecialchars($sneaker['name']) ?>" 
                         class="current-image">
                    <div style="margin-top: 0.5rem;">
                        <label style="font-weight: normal;">
                            <input type="checkbox" name="delete_image" value="1">
                            Delete current image
                        </label>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-light);">No image uploaded</p>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="image">Upload New Image:</label>
                <input type="file" id="image" name="image" accept="image/*">
                <small>Leave empty to keep current image. Accepted formats: JPEG, PNG, GIF, WebP.</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Sneaker</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                <a href="delete.php?id=<?= $id ?>" 
                   class="btn btn-danger" 
                   onclick="return confirm('Are you sure you want to delete this sneaker? This action cannot be undone.')"
                   style="margin-left: auto;">Delete Sneaker</a>
            </div>
        </form>
    </main>
    
    <?php include('../includes/footer.php'); ?>
</body>
</html>