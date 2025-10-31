<?php
/**
 * SneakVault CMS - Site Footer
 * 
 * This file contains the site footer with links and information.
 * It is included on all pages.
 */

// Get current year for copyright
$current_year = date('Y');

// Fetch a few categories for footer links
$footer_cat_query = "SELECT * FROM categories ORDER BY name ASC LIMIT 5";
$footer_cat_stmt = $db->prepare($footer_cat_query);
$footer_cat_stmt->execute();
$footer_categories = $footer_cat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>About SneakVault</h3>
                <p>Your trusted source for sneaker releases, designs, and resale pricing trends. Based in Winnipeg, Manitoba, we bring you the latest information on the sneakers you love.</p>
            </div>
            
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="<?= strpos($_SERVER['PHP_SELF'], 'admin') !== false ? '../index.php' : 'index.php' ?>">Home</a></li>
                    <li><a href="<?= strpos($_SERVER['PHP_SELF'], 'admin') !== false ? '../sneakers.php' : 'sneakers.php' ?>">All Sneakers</a></li>
                    <li><a href="<?= strpos($_SERVER['PHP_SELF'], 'admin') !== false ? '../search.php' : 'search.php' ?>">Search</a></li>
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin'): ?>
                        <li><a href="<?= strpos($_SERVER['PHP_SELF'], 'admin') !== false ? 'dashboard.php' : 'admin/dashboard.php' ?>">Admin Dashboard</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Popular Categories</h3>
                <ul>
                    <?php foreach($footer_categories as $cat): ?>
                        <li>
                            <a href="<?= strpos($_SERVER['PHP_SELF'], 'admin') !== false ? '../' : '' ?>category.php?id=<?= $cat['id'] ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="footer-section">
                <h3>Connect With Us</h3>
                <p>Winnipeg, Manitoba<br>Canada</p>
                <p style="margin-top: 1rem;">
                    <strong>Built for WEBD-2008</strong><br>
                    Web Development 2
                </p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?= $current_year ?> SneakVault. All rights reserved. | Built with PHP & MySQL</p>
        </div>
    </div>
</footer>