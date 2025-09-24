<?php
/**
 * Dispute Resolution Management Module
 * E-Commerce Platform - Admin Panel
 * 
 * Features:
 * - Case management linked to orders
 * - Evidence uploads and management
 * - Built-in messaging system
 * - SLA timers and tracking
 * - Dispute decisions (refund/replacement/reject)
 * - Complete audit trail
 */

// Global admin page requirements
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/rbac.php';
require_once __DIR__ . '/../../includes/mailer.php';
require_once __DIR__ . '/../../includes/audit_log.php';

// Load additional dependencies
require_once __DIR__ . '/../../includes/init.php';

// Initialize PDO global variable for this module
$pdo = db();

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
    // Normal authentication check - redirect to login if not authenticated as admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

$page_title = 'Dispute Resolution';
$action = $_GET['action'] ?? 'index';
$dispute_id = $_GET['id'] ?? null;

// Handle actions
if ($_POST && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'update_status':
                $id = intval($_POST['dispute_id']);
                $status = sanitizeInput($_POST['status']);
                $resolution_notes = sanitizeInput($_POST['resolution_notes'] ?? '');
                
                $stmt = $pdo->prepare("
                    UPDATE disputes 
                    SET status = ?, resolution_notes = ?, resolved_by = ?, resolved_at = NOW(), updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$status, $resolution_notes, $_SESSION['user_id'], $id]);
                
                // Log decision
                $stmt = $pdo->prepare("
                    INSERT INTO dispute_decisions (dispute_id, decision_type, decision_notes, decided_by, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$id, $status, $resolution_notes, $_SESSION['user_id']]);
                
                $success = "Dispute status updated successfully!";
                break;
                
            case 'add_message':
                $id = intval($_POST['dispute_id']);
                $message = sanitizeInput($_POST['message']);
                $is_internal = isset($_POST['is_internal']) ? 1 : 0;
                
                $stmt = $pdo->prepare("
                    INSERT INTO dispute_messages (dispute_id, sender_id, message, is_internal, created_at) 
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$id, $_SESSION['user_id'], $message, $is_internal]);
                
                $success = "Message added successfully!";
                break;
                
            case 'upload_evidence':
                $id = intval($_POST['dispute_id']);
                $description = sanitizeInput($_POST['description'] ?? '');
                
                // Handle file upload (stub implementation)
                if (isset($_FILES['evidence_file']) && $_FILES['evidence_file']['error'] === 0) {
                    $filename = basename($_FILES['evidence_file']['name']);
                    $filepath = '/uploads/disputes/' . $id . '/' . time() . '_' . $filename;
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO dispute_evidence (dispute_id, filename, filepath, description, uploaded_by, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$id, $filename, $filepath, $description, $_SESSION['user_id']]);
                    
                    $success = "Evidence uploaded successfully!";
                } else {
                    $error = "Error uploading file";
                }
                break;
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get disputes based on action
$disputes = [];
$dispute = null;

try {
    if ($action === 'view' && $dispute_id) {
        // Get specific dispute
        $stmt = $pdo->prepare("
            SELECT d.*, o.order_number, u.username as customer_name, u.email as customer_email,
                   v.business_name as vendor_name, res.username as resolved_by_name
            FROM disputes d
            LEFT JOIN orders o ON d.order_id = o.id
            LEFT JOIN users u ON d.customer_id = u.id
            LEFT JOIN vendors v ON d.vendor_id = v.id
            LEFT JOIN users res ON d.resolved_by = res.id
            WHERE d.id = ?
        ");
        $stmt->execute([$dispute_id]);
        $dispute = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$dispute) {
            header('Location: /admin/disputes/');
            exit;
        }
        
        // Get messages
        $stmt = $pdo->prepare("
            SELECT dm.*, u.username as sender_name
            FROM dispute_messages dm
            LEFT JOIN users u ON dm.sender_id = u.id
            WHERE dm.dispute_id = ?
            ORDER BY dm.created_at ASC
        ");
        $stmt->execute([$dispute_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get evidence
        $stmt = $pdo->prepare("
            SELECT de.*, u.username as uploaded_by_name
            FROM dispute_evidence de
            LEFT JOIN users u ON de.uploaded_by = u.id
            WHERE de.dispute_id = ?
            ORDER BY de.created_at DESC
        ");
        $stmt->execute([$dispute_id]);
        $evidence = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get decisions
        $stmt = $pdo->prepare("
            SELECT dd.*, u.username as decided_by_name
            FROM dispute_decisions dd
            LEFT JOIN users u ON dd.decided_by = u.id
            WHERE dd.dispute_id = ?
            ORDER BY dd.created_at DESC
        ");
        $stmt->execute([$dispute_id]);
        $decisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Get all disputes
        $filter = $_GET['filter'] ?? 'all';
        $where_clause = '';
        $params = [];
        
        switch ($filter) {
            case 'pending':
                $where_clause = "WHERE d.status IN ('open', 'investigating')";
                break;
            case 'overdue':
                $where_clause = "WHERE d.status IN ('open', 'investigating') AND d.sla_deadline < NOW()";
                break;
            case 'resolved':
                $where_clause = "WHERE d.status IN ('resolved', 'closed')";
                break;
        }
        
        $stmt = $pdo->prepare("
            SELECT d.*, o.order_number, u.username as customer_name, v.business_name as vendor_name,
                   CASE 
                       WHEN d.sla_deadline < NOW() AND d.status IN ('open', 'investigating') THEN 'overdue'
                       WHEN d.sla_deadline < DATE_ADD(NOW(), INTERVAL 24 HOUR) AND d.status IN ('open', 'investigating') THEN 'due_soon'
                       ELSE 'on_time'
                   END as sla_status
            FROM disputes d
            LEFT JOIN orders o ON d.order_id = o.id
            LEFT JOIN users u ON d.customer_id = u.id
            LEFT JOIN vendors v ON d.vendor_id = v.id
            {$where_clause}
            ORDER BY d.created_at DESC
        ");
        $stmt->execute($params);
        $disputes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'overdue' => 0,
    'resolved' => 0
];

try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status IN ('open', 'investigating') THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status IN ('open', 'investigating') AND sla_deadline < NOW() THEN 1 ELSE 0 END) as overdue,
            SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as resolved
        FROM disputes
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Use default stats
}

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
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--admin-accent);
        }
        
        .stats-card:hover {
            transform: translateY(-2px);
        }
        
        .stats-card.success { border-left-color: var(--admin-success); }
        .stats-card.warning { border-left-color: var(--admin-warning); }
        .stats-card.danger { border-left-color: var(--admin-danger); }
        
        .stats-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .dispute-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .dispute-card:hover {
            transform: translateY(-2px);
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        
        .sla-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .sla-on-time { background-color: var(--admin-success); }
        .sla-due-soon { background-color: var(--admin-warning); }
        .sla-overdue { background-color: var(--admin-danger); }
        
        .message-bubble {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .message-bubble.internal {
            background: #fff3cd;
            border-left: 4px solid var(--admin-warning);
        }
        
        .message-meta {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .evidence-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.5rem;
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
                        <i class="fas fa-gavel me-2"></i>
                        <?php echo $action === 'view' ? 'Dispute #' . $dispute_id : 'Dispute Resolution'; ?>
                    </h1>
                    <small class="text-white-50">Manage customer and vendor disputes</small>
                </div>
                <div class="col-md-6 text-end">
                    <a href="/admin/" class="btn btn-light btn-sm me-2">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Admin
                    </a>
                    <span class="text-white-50">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'Administrator'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-4">
        <!-- Admin Bypass Notice -->
        <?php if (defined('ADMIN_BYPASS') && ADMIN_BYPASS && isset($_SESSION['admin_bypass'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Admin Bypass Mode Active!</strong> Authentication is disabled for development.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($action === 'view' && $dispute): ?>
        <!-- Dispute Details View -->
        <div class="row">
            <div class="col-md-8">
                <!-- Dispute Information -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Dispute Details</h5>
                        <span class="badge bg-<?php echo $dispute['status'] === 'resolved' ? 'success' : ($dispute['status'] === 'open' ? 'danger' : 'warning'); ?>">
                            <?php echo ucfirst($dispute['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Order Information</h6>
                                <p><strong>Order Number:</strong> <?php echo htmlspecialchars($dispute['order_number']); ?></p>
                                <p><strong>Customer:</strong> <?php echo htmlspecialchars($dispute['customer_name']); ?></p>
                                <p><strong>Vendor:</strong> <?php echo htmlspecialchars($dispute['vendor_name']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Dispute Information</h6>
                                <p><strong>Type:</strong> <?php echo ucfirst($dispute['dispute_type']); ?></p>
                                <p><strong>Priority:</strong> 
                                    <span class="badge bg-<?php echo $dispute['priority'] === 'high' ? 'danger' : ($dispute['priority'] === 'medium' ? 'warning' : 'secondary'); ?>">
                                        <?php echo ucfirst($dispute['priority']); ?>
                                    </span>
                                </p>
                                <p><strong>SLA Deadline:</strong> 
                                    <span class="sla-indicator sla-<?php echo strtotime($dispute['sla_deadline']) < time() ? 'overdue' : 'on-time'; ?>"></span>
                                    <?php echo date('M d, Y H:i', strtotime($dispute['sla_deadline'])); ?>
                                </p>
                            </div>
                        </div>
                        <hr>
                        <h6>Description</h6>
                        <p><?php echo nl2br(htmlspecialchars($dispute['description'])); ?></p>
                        
                        <?php if ($dispute['resolution_notes']): ?>
                        <hr>
                        <h6>Resolution Notes</h6>
                        <p><?php echo nl2br(htmlspecialchars($dispute['resolution_notes'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Messages -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Messages</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($messages)): ?>
                        <p class="text-muted">No messages yet.</p>
                        <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                        <div class="message-bubble <?php echo $message['is_internal'] ? 'internal' : ''; ?>">
                            <div class="message-meta">
                                <strong><?php echo htmlspecialchars($message['sender_name']); ?></strong>
                                <span class="text-muted">• <?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></span>
                                <?php if ($message['is_internal']): ?>
                                <span class="badge bg-warning ms-2">Internal</span>
                                <?php endif; ?>
                            </div>
                            <div><?php echo nl2br(htmlspecialchars($message['message'])); ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Add Message Form -->
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="add_message">
                            <input type="hidden" name="dispute_id" value="<?php echo $dispute['id']; ?>">
                            <div class="mb-3">
                                <label for="message" class="form-label">Add Message</label>
                                <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="is_internal" name="is_internal">
                                <label class="form-check-label" for="is_internal">
                                    Internal message (not visible to customer/vendor)
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Message</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Actions</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="dispute_id" value="<?php echo $dispute['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="open" <?php echo $dispute['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                    <option value="investigating" <?php echo $dispute['status'] === 'investigating' ? 'selected' : ''; ?>>Investigating</option>
                                    <option value="resolved" <?php echo $dispute['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="closed" <?php echo $dispute['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="resolution_notes" class="form-label">Resolution Notes</label>
                                <textarea class="form-control" id="resolution_notes" name="resolution_notes" rows="3"><?php echo htmlspecialchars($dispute['resolution_notes']); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        </form>
                    </div>
                </div>
                
                <!-- Evidence -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Evidence</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($evidence)): ?>
                        <p class="text-muted">No evidence uploaded yet.</p>
                        <?php else: ?>
                        <?php foreach ($evidence as $item): ?>
                        <div class="evidence-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['filename']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($item['description']); ?></small>
                                    <br><small class="text-muted">
                                        Uploaded by <?php echo htmlspecialchars($item['uploaded_by_name']); ?>
                                        on <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                    </small>
                                </div>
                                <a href="<?php echo htmlspecialchars($item['filepath']); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <!-- Upload Evidence Form -->
                        <form method="POST" enctype="multipart/form-data" class="mt-3">
                            <input type="hidden" name="action" value="upload_evidence">
                            <input type="hidden" name="dispute_id" value="<?php echo $dispute['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="evidence_file" class="form-label">Upload Evidence</label>
                                <input type="file" class="form-control" id="evidence_file" name="evidence_file" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <input type="text" class="form-control" id="description" name="description" placeholder="Brief description of evidence">
                            </div>
                            <button type="submit" class="btn btn-outline-primary">Upload</button>
                        </form>
                    </div>
                </div>
                
                <!-- Decision History -->
                <?php if (!empty($decisions)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Decision History</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($decisions as $decision): ?>
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo ucfirst($decision['decision_type']); ?></strong>
                                    <br><small class="text-muted">
                                        by <?php echo htmlspecialchars($decision['decided_by_name']); ?>
                                        on <?php echo date('M d, Y H:i', strtotime($decision['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                            <?php if ($decision['decision_notes']): ?>
                            <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($decision['decision_notes'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Disputes List View -->
        
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-value text-primary">
                        <?php echo number_format($stats['total']); ?>
                    </div>
                    <div class="text-muted">Total Disputes</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card warning">
                    <div class="stats-value text-warning">
                        <?php echo number_format($stats['pending']); ?>
                    </div>
                    <div class="text-muted">Pending Resolution</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card danger">
                    <div class="stats-value text-danger">
                        <?php echo number_format($stats['overdue']); ?>
                    </div>
                    <div class="text-muted">Overdue SLA</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card success">
                    <div class="stats-value text-success">
                        <?php echo number_format($stats['resolved']); ?>
                    </div>
                    <div class="text-muted">Resolved</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="?" class="btn btn-outline-primary <?php echo ($_GET['filter'] ?? 'all') === 'all' ? 'active' : ''; ?>">
                                    All Disputes
                                </a>
                                <a href="?filter=pending" class="btn btn-outline-warning <?php echo ($_GET['filter'] ?? '') === 'pending' ? 'active' : ''; ?>">
                                    Pending
                                </a>
                                <a href="?filter=overdue" class="btn btn-outline-danger <?php echo ($_GET['filter'] ?? '') === 'overdue' ? 'active' : ''; ?>">
                                    Overdue
                                </a>
                                <a href="?filter=resolved" class="btn btn-outline-success <?php echo ($_GET['filter'] ?? '') === 'resolved' ? 'active' : ''; ?>">
                                    Resolved
                                </a>
                            </div>
                            <div>
                                <button class="btn btn-primary" onclick="exportDisputes()">
                                    <i class="fas fa-download me-1"></i>
                                    Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Disputes List -->
        <?php if (empty($disputes)): ?>
        <div class="text-center py-5">
            <i class="fas fa-gavel fa-4x text-muted mb-3"></i>
            <h4 class="text-muted">No Disputes Found</h4>
            <p class="text-muted">
                <?php 
                switch ($_GET['filter'] ?? 'all') {
                    case 'pending': echo "No pending disputes at this time."; break;
                    case 'overdue': echo "No overdue disputes - great job!"; break;
                    case 'resolved': echo "No resolved disputes in the system."; break;
                    default: echo "No disputes have been filed yet."; break;
                }
                ?>
            </p>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($disputes as $dispute): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="dispute-card card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Dispute #<?php echo $dispute['id']; ?></h6>
                        <span class="sla-indicator sla-<?php echo $dispute['sla_status'] ?? 'on-time'; ?>"></span>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <span class="badge bg-<?php echo $dispute['status'] === 'resolved' ? 'success' : ($dispute['status'] === 'open' ? 'danger' : 'warning'); ?> status-badge">
                                <?php echo ucfirst($dispute['status']); ?>
                            </span>
                            <span class="badge bg-secondary status-badge">
                                <?php echo ucfirst($dispute['dispute_type']); ?>
                            </span>
                        </div>
                        <p><strong>Order:</strong> <?php echo htmlspecialchars($dispute['order_number']); ?></p>
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($dispute['customer_name']); ?></p>
                        <p><strong>Vendor:</strong> <?php echo htmlspecialchars($dispute['vendor_name']); ?></p>
                        <p class="text-muted">
                            <?php echo substr(htmlspecialchars($dispute['description']), 0, 100) . (strlen($dispute['description']) > 100 ? '...' : ''); ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <?php echo date('M d, Y', strtotime($dispute['created_at'])); ?>
                            </small>
                            <a href="?action=view&id=<?php echo $dispute['id']; ?>" class="btn btn-primary btn-sm">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function exportDisputes() {
            window.location.href = '?action=export&filter=<?php echo $_GET['filter'] ?? 'all'; ?>';
        }
    </script>
</body>
</html>