<?php
/**
 * SneakVault CMS - Site Header
 * 
 * This file contains the site header with navigation menu.
 * It is included on all public pages.
 * 
 * Requirements Met:
 * - 2.7: Navigate pages via menu
 * - 2.8: Navigate by categories
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch categories for navigation dropdown
$cat_query = "SELECT * FROM categories ORDER BY name ASC";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="site-header">
    <div class="container">
        <div class="header-content">
            <div class="site-logo">
                <a href="<?= $_SERVER['REQUEST_URI'] === '/sneakvault/admin/dashboard.php' || strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../index.php' : 'index.php' ?>">
                    SneakVault
                </a>
            </div>
            
            <nav class="main-nav">
                <ul>
                    <li>
                        <a href="<?= strpos($current_page, 'admin') !== false ? '../index.php' : 'index.php' ?>" 
                           class="<?= $current_page === 'index.php' ? 'active' : '' ?>">
                            Home
                        </a>
                    </li>
                    
                    <li>
                        <a href="<?= strpos($current_page, 'admin') !== false ? '../sneakers.php' : 'sneakers.php' ?>" 
                           class="<?= $current_page === 'sneakers.php' ? 'active' : '' ?>">
                            All Sneakers
                        </a>
                    </li>
                    
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">Categories ▼</a>
                        <ul class="dropdown-menu">
                            <?php foreach($categories as $category): ?>
                                <li>
                                    <a href="<?= strpos($current_page, 'admin') !== false ? '../' : '' ?>category.php?id=<?= $category['id'] ?>">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    
                    <li>
                        <a href="<?= strpos($current_page, 'admin') !== false ? '../search.php' : 'search.php' ?>" 
                           class="<?= $current_page === 'search.php' ? 'active' : '' ?>">
                            Search
                        </a>
                    </li>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($_SESSION['role'] === 'admin'): ?>
                            <li>
                                <a href="<?= strpos($current_page, 'admin') !== false ? 'dashboard.php' : 'admin/dashboard.php' ?>" 
                                   class="<?= strpos($current_page, 'admin') !== false ? 'active' : '' ?>">
                                    Admin
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li>
                            <a href="<?= strpos($current_page, 'admin') !== false ? '../logout.php' : 'logout.php' ?>">
                                Logout (<?= htmlspecialchars($_SESSION['username']) ?>)
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="<?= strpos($current_page, 'admin') !== false ? '../login.php' : 'login.php' ?>" 
                               class="<?= $current_page === 'login.php' ? 'active' : '' ?>">
                                Login
                            </a>
                        </li>
                        
                        <li>
                            <a href="<?= strpos($current_page, 'admin') !== false ? '../register.php' : 'register.php' ?>" 
                               class="<?= $current_page === 'register.php' ? 'active' : '' ?>">
                                Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
</header>