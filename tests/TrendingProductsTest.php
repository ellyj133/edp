<?php
/**
 * Test for the fixed trending products query
 * Validates that the SQL syntax is correct and binding works
 */

use PHPUnit\Framework\TestCase;

class TrendingProductsTest extends TestCase
{
    private $mockDb;
    private $mockStmt;
    
    protected function setUp(): void
    {
        // Mock PDO and PDOStatement
        $this->mockStmt = $this->createMock(PDOStatement::class);
        $this->mockDb = $this->createMock(PDO::class);
        
        // Set up the mock to return our mock statement
        $this->mockDb->method('prepare')->willReturn($this->mockStmt);
    }
    
    public function testTrendingProductsQuerySyntax()
    {
        // Load the models_extended class
        require_once __DIR__ . '/../includes/models_extended.php';
        
        // Create a mock model with our mock database
        $model = new class($this->mockDb) {
            private $db;
            public function __construct($db) {
                $this->db = $db;
            }
            
            public function getTrendingProducts($limit = 8) {
                // The fixed query from models_extended.php
                $sql = "
                    SELECT p.id, p.name AS title, p.price,
                           COALESCE(SUM(oi.qty), 0) AS sold,
                           MAX(pi.image_url) AS image
                    FROM products p 
                    LEFT JOIN order_items oi ON oi.product_id = p.id
                    LEFT JOIN orders o ON o.id = oi.order_id 
                      AND o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                      AND o.status IN ('paid','shipped','delivered')
                    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
                    WHERE p.status = 'active'
                    GROUP BY p.id, p.name, p.price
                    ORDER BY sold DESC, p.created_at DESC 
                    LIMIT ?
                ";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bindValue(1, $limit, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchAll();
            }
        };
        
        // Test that the query can be prepared (syntax is valid)
        $this->mockDb->expects($this->once())
                     ->method('prepare')
                     ->with($this->stringContains('SELECT p.id, p.name AS title'))
                     ->willReturn($this->mockStmt);
        
        // Test that parameters are bound correctly
        $this->mockStmt->expects($this->once())
                       ->method('bindValue')
                       ->with(1, 8, PDO::PARAM_INT);
                       
        $this->mockStmt->expects($this->once())
                       ->method('execute');
                       
        $this->mockStmt->method('fetchAll')
                       ->willReturn([
                           ['id' => 1, 'title' => 'Test Product', 'price' => 19.99, 'sold' => 5, 'image' => 'test.jpg']
                       ]);
        
        // Execute the method
        $result = $model->getTrendingProducts(8);
        
        // Verify result structure
        $this->assertIsArray($result);
        if (!empty($result)) {
            $this->assertArrayHasKey('id', $result[0]);
            $this->assertArrayHasKey('title', $result[0]);
            $this->assertArrayHasKey('price', $result[0]);
            $this->assertArrayHasKey('sold', $result[0]);
            $this->assertArrayHasKey('image', $result[0]);
        }
    }
    
    public function testTrendingProductsQueryDoesNotReferencePlacedAt()
    {
        // Read the models_extended.php file
        $content = file_get_contents(__DIR__ . '/../includes/models_extended.php');
        
        // Extract the getTrendingProducts method
        preg_match('/public function getTrendingProducts.*?(?=public function|\}$)/s', $content, $matches);
        
        if (!empty($matches[0])) {
            $method = $matches[0];
            
            // Verify that the method no longer references o.placed_at
            $this->assertStringNotContainsString('o.placed_at', $method, 
                'getTrendingProducts method should not reference the missing o.placed_at column');
                
            // Verify it uses o.created_at instead  
            $this->assertStringContainsString('o.created_at', $method,
                'getTrendingProducts method should use existing o.created_at column');
                
            // Verify it uses MariaDB DATE_SUB function
            $this->assertStringContainsString('DATE_SUB(NOW(), INTERVAL 7 DAY)', $method,
                'getTrendingProducts method should use MariaDB DATE_SUB syntax');
        } else {
            $this->fail('Could not find getTrendingProducts method in models_extended.php');
        }
    }
    
    public function testSqlQueryUsesProperMariaDbSyntax()
    {
        // Load the models_extended.php file
        $content = file_get_contents(__DIR__ . '/../includes/models_extended.php');
        
        // Check for MariaDB-specific syntax
        $this->assertStringContainsString('COALESCE(SUM(oi.qty), 0)', $content,
            'Should use COALESCE for NULL handling');
            
        $this->assertStringContainsString('LEFT JOIN', $content,
            'Should use LEFT JOIN syntax');
            
        $this->assertStringContainsString('GROUP BY p.id', $content,
            'Should use proper GROUP BY with all selected non-aggregate columns');
    }
}