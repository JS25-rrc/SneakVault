<?php
    require('../connect.php');
    session_start();

    // Check if user is admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../login.php");
        exit;
    }

    // Get and validate sort parameter
    $sort_by = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'created_at';
    $allowed_sorts = ['name', 'created_at', 'updated_at'];
    $sort_column = in_array($sort_by, $allowed_sorts) ? $sort_by : 'created_at';

    // Fetch all sneakers with sorting
    $query = "SELECT s.*, c.name as category_name 
            FROM sneakers s 
            LEFT JOIN categories c ON s.category_id = c.id 
            ORDER BY s.$sort_column DESC";
    $statement = $db->prepare($query);
    $statement->execute();
    $sneakers = $statement->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics for dashboard
    $total_sneakers = count($sneakers);
    $total_categories = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_comments = $db->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    $pending_comments = $db->query("SELECT COUNT(*) FROM comments WHERE is_moderated = 0")->fetchColumn();

    // Check for success messages
    $success_message = '';
    if (isset($_GET['created'])) {
        $success_message = "Sneaker created successfully!";
    } elseif (isset($_GET['updated'])) {
        $success_message = "Sneaker updated successfully!";
    } elseif (isset($_GET['deleted'])) {
        $success_message = "Sneaker deleted successfully!";
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SneakVault</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color), var(--text-light));
            color: white;
            padding: 2rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
        }
        
        .admin-header h1 {
            color: white;
            margin-bottom: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-md);
            border-left: 4px solid var(--accent-color);
            box-shadow: var(--shadow-sm);
        }
        
        .stat-card h3 {
            font-size: 2rem;
            color: var(--accent-color);
            margin-bottom: 0.25rem;
        }
        
        .stat-card p {
            color: var(--text-light);
            margin: 0;
        }
        
        .admin-nav {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .sort-options {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .sort-options a {
            padding: 0.5rem 1rem;
            background-color: var(--bg-light);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
        }
        
        .sort-options a.active {
            background-color: var(--accent-color);
            color: white;
        }
        
        .admin-table {
            background: white;
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        
        .admin-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            text-align: left;
        }
        
        .admin-table td {
            vertical-align: middle;
        }
        
        .thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-sm);
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .actions .btn {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <?php include('../includes/header.php'); ?>
    
    <main class="container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>! Manage your sneaker inventory below.</p>
        </div>
        
        <?php if($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= $total_sneakers ?></h3>
                <p>Total Sneakers</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_categories ?></h3>
                <p>Categories</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_users ?></h3>
                <p>Registered Users</p>
            </div>
            <div class="stat-card">
                <h3><?= $total_comments ?></h3>
                <p>Total Comments</p>
            </div>
            <div class="stat-card" style="border-left-color: var(--warning);">
                <h3 style="color: var(--warning);"><?= $pending_comments ?></h3>
                <p>Pending Comments</p>
            </div>
        </div>
        
        <!-- Admin Navigation -->
        <nav class="admin-nav">
            <a href="create.php" class="btn btn-primary">+ Add New Sneaker</a>
            <a href="manage_categories.php" class="btn btn-secondary">Manage Categories</a>
            <a href="moderate_comments.php" class="btn btn-secondary">Moderate Comments</a>
            <a href="manage_users.php" class="btn btn-secondary">Manage Users</a>
        </nav>
        
        <!-- Sneakers List -->
        <section class="sneakers-management">
            <div class="section-header">
                <h2>All Sneakers (<?= $total_sneakers ?>)</h2>
                <div class="sort-options">
                    <span>Sort by:</span>
                    <a href="?sort=name" class="<?= $sort_column === 'name' ? 'active' : '' ?>">Title</a>
                    <a href="?sort=created_at" class="<?= $sort_column === 'created_at' ? 'active' : '' ?>">Created</a>
                    <a href="?sort=updated_at" class="<?= $sort_column === 'updated_at' ? 'active' : '' ?>">Updated</a>
                </div>
            </div>
            
            <?php if(count($sneakers) > 0): ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Created</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($sneakers as $sneaker): ?>
                                <tr>
                                    <td><?= $sneaker['id'] ?></td>
                                    <td>
                                        <?php if($sneaker['image_path'] && file_exists('../' . $sneaker['image_path'])): ?>
                                            <img src="../<?= htmlspecialchars($sneaker['image_path']) ?>" 
                                                 alt="<?= htmlspecialchars($sneaker['name']) ?>" 
                                                 class="thumbnail">
                                        <?php else: ?>
                                            <img src="../images/placeholder.jpg" alt="No image" class="thumbnail">
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($sneaker['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($sneaker['brand']) ?></td>
                                    <td>
                                        <?php if($sneaker['category_name']): ?>
                                            <span class="badge"><?= htmlspecialchars($sneaker['category_name']) ?></span>
                                        <?php else: ?>
                                            <span style="color: var(--text-light);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($sneaker['retail_price']): ?>
                                            $<?= number_format($sneaker['retail_price'], 2) ?>
                                        <?php else: ?>
                                            <span style="color: var(--text-light);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($sneaker['created_at'])) ?></td>
                                    <td><?= date('M j, Y', strtotime($sneaker['updated_at'])) ?></td>
                                    <td class="actions">
                                        <a href="../sneaker.php?id=<?= $sneaker['id'] ?>" class="btn btn-secondary" target="_blank">View</a>
                                        <a href="edit.php?id=<?= $sneaker['id'] ?>" class="btn btn-primary">Edit</a>
                                        <a href="delete.php?id=<?= $sneaker['id'] ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($sneaker['name']) ?>? This action cannot be undone.')">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-results">No sneakers found. Click "Add New Sneaker" to get started!</p>
            <?php endif; ?>
        </section>
    </main>
    
    <?php include('../includes/footer.php'); ?>
</body>
</html>