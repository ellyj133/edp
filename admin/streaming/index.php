<?php
/**
 * Live Streaming Management Admin Module
 * E-Commerce Platform Admin Panel
 */

require_once __DIR__ . '/../../includes/init.php';

// Database availability check with graceful fallback
$database_available = false;
$pdo = null;
try {
    $pdo = db();
    $pdo->query('SELECT 1');
    $database_available = true;
} catch (Exception $e) {
    $database_available = false;
    error_log("Database connection failed: " . $e->getMessage());
}

// Admin Bypass Mode - Skip all authentication when enabled
if (defined('ADMIN_BYPASS') && ADMIN_BYPASS) {
    // Set up admin session automatically in bypass mode
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_role'] = 'admin';
        $_SESSION['user_email'] = 'admin@example.com';
        $_SESSION['username'] = 'Administrator';
        $_SESSION['admin_bypass'] = true;
    }
} else {
    // Normal authentication check
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

$page_title = 'Live Streaming Management';

// Demo streaming data
$streams = [
    ['id' => 1, 'title' => 'Spring Fashion Collection Live', 'vendor_id' => 1, 'vendor_name' => 'Fashion Hub', 'status' => 'live', 'viewer_count' => 145, 'max_viewers' => 203, 'revenue' => 2847.50, 'scheduled_at' => '2024-03-15 14:00:00', 'started_at' => '2024-03-15 14:02:00'],
    ['id' => 2, 'title' => 'Tech Gadgets Showcase', 'vendor_id' => 2, 'vendor_name' => 'TechWorld', 'status' => 'scheduled', 'viewer_count' => 0, 'max_viewers' => 0, 'revenue' => 0, 'scheduled_at' => '2024-03-15 18:00:00', 'started_at' => null],
    ['id' => 3, 'title' => 'Home & Kitchen Essentials', 'vendor_id' => 3, 'vendor_name' => 'Home Store', 'status' => 'ended', 'viewer_count' => 0, 'max_viewers' => 89, 'revenue' => 1245.75, 'scheduled_at' => '2024-03-14 16:00:00', 'started_at' => '2024-03-14 16:05:00'],
    ['id' => 4, 'title' => 'Beauty Products Demo', 'vendor_id' => 4, 'vendor_name' => 'Beauty Zone', 'status' => 'cancelled', 'viewer_count' => 0, 'max_viewers' => 0, 'revenue' => 0, 'scheduled_at' => '2024-03-13 12:00:00', 'started_at' => null],
];

$stats = [
    'total_streams' => count($streams),
    'live_streams' => count(array_filter($streams, fn($s) => $s['status'] === 'live')),
    'scheduled_streams' => count(array_filter($streams, fn($s) => $s['status'] === 'scheduled')),
    'total_viewers' => array_sum(array_column($streams, 'viewer_count')),
    'total_revenue' => array_sum(array_column($streams, 'revenue')),
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Admin Panel</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --admin-primary: #2c3e50;
            --admin-secondary: #34495e;
            --admin-accent: #3498db;
            --admin-success: #27ae60;
            --admin-warning: #f39c12;
            --admin-danger: #e74c3c;
            --admin-light: #ecf0f1;
        }
        
        body {
            background-color: var(--admin-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
        .status-live { background-color: #dc3545; }
        .status-scheduled { background-color: #17a2b8; }
        .status-ended { background-color: #6c757d; }
        .status-cancelled { background-color: #ffc107; color: #000; }
        
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            text-align: center;
            border-left: 4px solid var(--admin-accent);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .stats-card.success { border-left-color: var(--admin-success); }
        .stats-card.warning { border-left-color: var(--admin-warning); }
        .stats-card.danger { border-left-color: var(--admin-danger); }
        .stats-card.info { border-left-color: #17a2b8; }
        
        .stats-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .live-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #dc3545;
            border-radius: 50%;
            animation: pulse 2s infinite;
            margin-right: 5px;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .stream-thumbnail {
            width: 60px;
            height: 40px;
            background: #f8f9fa;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-video me-2"></i>
                        <?php echo htmlspecialchars($page_title); ?>
                    </h1>
                    <small class="text-white-50">Manage live streams, chat, and streaming analytics</small>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/admin/" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid py-4">
        <!-- Admin Bypass Notice -->
        <?php if (defined('ADMIN_BYPASS') && ADMIN_BYPASS && isset($_SESSION['admin_bypass'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Admin Bypass Mode Active!</strong> Authentication is disabled for development.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Streaming Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <i class="fas fa-video fa-2x text-primary mb-2"></i>
                    <div class="stats-value text-primary"><?php echo number_format($stats['total_streams']); ?></div>
                    <div class="stats-label">Total Streams</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card danger">
                    <i class="fas fa-broadcast-tower fa-2x text-danger mb-2"></i>
                    <div class="stats-value text-danger"><?php echo number_format($stats['live_streams']); ?></div>
                    <div class="stats-label">Live Now</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card info">
                    <i class="fas fa-users fa-2x text-info mb-2"></i>
                    <div class="stats-value text-info"><?php echo number_format($stats['total_viewers']); ?></div>
                    <div class="stats-label">Current Viewers</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card success">
                    <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                    <div class="stats-value text-success">$<?php echo number_format($stats['total_revenue'], 0); ?></div>
                    <div class="stats-label">Stream Revenue</div>
                </div>
            </div>
        </div>

        <!-- Live Streaming Control Panel -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Streaming Control Panel</h5>
                        <div>
                            <button class="btn btn-success btn-sm me-2">
                                <i class="fas fa-plus me-1"></i> Schedule Stream
                            </button>
                            <button class="btn btn-primary btn-sm me-2">
                                <i class="fas fa-cog me-1"></i> Stream Settings
                            </button>
                            <button class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-download me-1"></i> Export Data
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filter Controls -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" placeholder="Search streams...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select">
                                    <option value="">All Status</option>
                                    <option value="live">Live</option>
                                    <option value="scheduled">Scheduled</option>
                                    <option value="ended">Ended</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select">
                                    <option value="">All Vendors</option>
                                    <option value="1">Fashion Hub</option>
                                    <option value="2">TechWorld</option>
                                    <option value="3">Home Store</option>
                                    <option value="4">Beauty Zone</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-primary me-2">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                                <button class="btn btn-outline-secondary">
                                    <i class="fas fa-sync me-1"></i> Refresh
                                </button>
                            </div>
                        </div>

                        <!-- Streams Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Preview</th>
                                        <th>Stream Details</th>
                                        <th>Vendor</th>
                                        <th>Status</th>
                                        <th>Viewers</th>
                                        <th>Revenue</th>
                                        <th>Scheduled Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($streams as $stream): ?>
                                    <tr>
                                        <td>
                                            <div class="stream-thumbnail">
                                                <?php if ($stream['status'] === 'live'): ?>
                                                <i class="fas fa-play text-danger"></i>
                                                <?php else: ?>
                                                <i class="fas fa-video text-muted"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($stream['title']); ?></strong>
                                                <?php if ($stream['status'] === 'live'): ?>
                                                <br><small class="text-danger">
                                                    <span class="live-indicator"></span>LIVE
                                                </small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($stream['vendor_name']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge status-badge status-<?php echo htmlspecialchars($stream['status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($stream['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo number_format($stream['viewer_count']); ?></strong>
                                                <?php if ($stream['max_viewers'] > 0): ?>
                                                <br><small class="text-muted">Peak: <?php echo number_format($stream['max_viewers']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong>$<?php echo number_format($stream['revenue'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y H:i', strtotime($stream['scheduled_at'])); ?>
                                            <?php if ($stream['started_at']): ?>
                                            <br><small class="text-success">Started: <?php echo date('H:i', strtotime($stream['started_at'])); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <?php if ($stream['status'] === 'live'): ?>
                                                <button type="button" class="btn btn-outline-danger" title="End Stream">
                                                    <i class="fas fa-stop"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-warning" title="Pause Stream">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                                <?php elseif ($stream['status'] === 'scheduled'): ?>
                                                <button type="button" class="btn btn-outline-success" title="Start Stream">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" title="Edit Schedule">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-info" title="Chat Moderation">
                                                    <i class="fas fa-comments"></i>
                                                </button>
                                                <?php if ($stream['status'] !== 'live'): ?>
                                                <button type="button" class="btn btn-outline-danger" title="Delete Stream">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Stream Analytics -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Stream Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="border rounded p-3 mb-3">
                                    <div class="h4 text-primary mb-1"><?php echo $stats['scheduled_streams']; ?></div>
                                    <small class="text-muted">Scheduled</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-3 mb-3">
                                    <div class="h4 text-success mb-1"><?php echo count(array_filter($streams, fn($s) => $s['status'] === 'ended')); ?></div>
                                    <small class="text-muted">Completed</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="border rounded p-3 mb-3">
                                    <div class="h4 text-warning mb-1"><?php echo count(array_filter($streams, fn($s) => $s['status'] === 'cancelled')); ?></div>
                                    <small class="text-muted">Cancelled</small>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <h6>Average Revenue Per Stream</h6>
                            <div class="h3 text-success">$<?php echo number_format($stats['total_revenue'] / max(1, $stats['total_streams']), 2); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Top Performing Streams</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $sorted_streams = $streams;
                        usort($sorted_streams, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
                        $top_streams = array_slice($sorted_streams, 0, 3);
                        ?>
                        <?php foreach ($top_streams as $index => $stream): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong><?php echo htmlspecialchars($stream['title']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($stream['vendor_name']); ?> • <?php echo number_format($stream['max_viewers']); ?> viewers</small>
                            </div>
                            <div class="text-end">
                                <strong class="text-success">$<?php echo number_format($stream['revenue'], 2); ?></strong>
                            </div>
                        </div>
                        <?php if ($index < count($top_streams) - 1): ?>
                        <hr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <button class="btn btn-outline-primary w-100 mb-2">
                                    <i class="fas fa-key me-2"></i>
                                    Generate Stream Key
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-success w-100 mb-2">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    View Analytics
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-warning w-100 mb-2">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    Moderation Tools
                                </button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-outline-info w-100 mb-2">
                                    <i class="fas fa-cogs me-2"></i>
                                    RTMP Settings
                                </button>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Stream Configuration:</strong> RTMP endpoint is configured and ready. 
                                    Vendors can use their assigned stream keys to broadcast live content.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-refresh for live streams
        setInterval(function() {
            // Here you would normally update live viewer counts and stream status
            console.log('Refreshing live stream data...');
        }, 10000); // Refresh every 10 seconds
        
        // Mock real-time updates for demo
        document.addEventListener('DOMContentLoaded', function() {
            const liveViewers = document.querySelectorAll('.status-live').closest('tr').querySelectorAll('td:nth-child(5) strong');
            
            setInterval(function() {
                liveViewers.forEach(viewerElement => {
                    const currentCount = parseInt(viewerElement.textContent.replace(/,/g, ''));
                    const change = Math.floor(Math.random() * 10) - 5; // Random change -5 to +5
                    const newCount = Math.max(0, currentCount + change);
                    viewerElement.textContent = newCount.toLocaleString();
                });
            }, 5000); // Update every 5 seconds
        });
    </script>
</body>
</html>