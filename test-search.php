<?php
/**
 * Test Search Page - Demo with SQLite
 */

require_once '/tmp/demo_db.php';

// Simple Product class for demo
class DemoProduct {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function search($query, $categoryId = null, $limit = 20, $offset = 0) {
        if (!$this->db) return [];
        
        $searchTerm = "%{$query}%";
        $sql = "
            SELECT *, 
                   'Demo Vendor' as vendor_name
            FROM products 
            WHERE (name LIKE ? OR description LIKE ? OR short_description LIKE ?) 
            AND status = 'active'
            ORDER BY 
                CASE 
                    WHEN name LIKE ? THEN 1
                    WHEN short_description LIKE ? THEN 2
                    WHEN description LIKE ? THEN 3
                    ELSE 4
                END,
                featured DESC, id DESC
            LIMIT ? OFFSET ?
        ";
        
        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit, $offset];
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Search error: " . $e->getMessage());
            return [];
        }
    }
}

// Initialize demo database and search
$demoDb = db_demo();
if (!$demoDb) {
    die("Failed to create demo database");
}

$query = $_GET['q'] ?? 'laptop';
$product = new DemoProduct($demoDb);
$results = $product->search($query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Test - <?php echo htmlspecialchars($query); ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 20px; 
        }
        .search-form { 
            margin-bottom: 30px; 
            padding: 20px; 
            background: #f5f5f5; 
            border-radius: 8px; 
        }
        .search-form input { 
            padding: 10px; 
            font-size: 16px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            width: 300px; 
            margin-right: 10px; 
        }
        .search-form button { 
            padding: 10px 20px; 
            background: #007cba; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
        }
        .results { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
            gap: 20px; 
        }
        .product-card { 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            padding: 15px; 
            background: white; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .product-image { 
            width: 100%; 
            height: 200px; 
            object-fit: cover; 
            border-radius: 4px; 
            margin-bottom: 10px; 
        }
        .product-name { 
            font-weight: bold; 
            font-size: 1.1em; 
            margin-bottom: 10px; 
        }
        .product-description { 
            color: #666; 
            margin-bottom: 10px; 
            font-size: 0.9em; 
        }
        .product-price { 
            font-size: 1.2em; 
            font-weight: bold; 
            color: #d70026; 
        }
        .compare-price { 
            text-decoration: line-through; 
            color: #999; 
            margin-left: 10px; 
        }
        .no-results { 
            text-align: center; 
            padding: 50px; 
            color: #666; 
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 20px; 
        }
    </style>
</head>
<body>
    <h1>Search Functionality Test</h1>
    
    <div class="success">
        ✅ Database connection working! Demo SQLite database created with sample laptop data.
    </div>
    
    <form class="search-form" method="GET">
        <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search for products...">
        <button type="submit">Search</button>
    </form>
    
    <h2>Search Results for "<?php echo htmlspecialchars($query); ?>" (<?php echo count($results); ?> found)</h2>
    
    <?php if (empty($results)): ?>
        <div class="no-results">
            <p>No products found matching your search.</p>
            <p>Try searching for: laptop, macbook, dell, gaming, business</p>
        </div>
    <?php else: ?>
        <div class="results">
            <?php foreach ($results as $product): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="product-image">
                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                    <div class="product-description"><?php echo htmlspecialchars($product['short_description']); ?></div>
                    <div class="product-price">
                        $<?php echo number_format($product['price'], 2); ?>
                        <?php if ($product['compare_price']): ?>
                            <span class="compare-price">$<?php echo number_format($product['compare_price'], 2); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($product['featured']): ?>
                        <div style="color: #ff6b35; font-weight: bold; margin-top: 5px;">⭐ Featured</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <h3>Test Different Searches:</h3>
        <a href="?q=macbook" style="margin-right: 15px;">MacBook</a>
        <a href="?q=dell" style="margin-right: 15px;">Dell</a>
        <a href="?q=gaming" style="margin-right: 15px;">Gaming</a>
        <a href="?q=business" style="margin-right: 15px;">Business</a>
        <a href="?q=professional" style="margin-right: 15px;">Professional</a>
    </div>
</body>
</html>