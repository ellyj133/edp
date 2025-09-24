<?php
/**
 * Financial Management - Admin Module
 * Transaction tracking, vendor payouts, and financial reporting
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

// Require proper permissions
requireAdminPermission(AdminPermissions::FINANCE_VIEW);

$page_title = 'Financial Management';
$action = $_GET['action'] ?? 'dashboard';

// Handle actions
if ($_POST && isset($_POST['action'])) {
    validateCsrfAndRateLimit();
    
    try {
        switch ($_POST['action']) {
            case 'process_payout':
                requireAdminPermission(AdminPermissions::FINANCE_PAYOUTS);
                
                $payoutId = intval($_POST['payout_id']);
                $status = sanitizeInput($_POST['status']);
                $notes = sanitizeInput($_POST['notes'] ?? '');
                
                // Update payout status
                $updated = Database::query(
                    "UPDATE vendor_payouts SET status = ?, processed_by = ?, processed_at = NOW(), notes = ? 
                     WHERE id = ?",
                    [$status, getCurrentUserId(), $notes, $payoutId]
                );
                
                if ($updated) {
                    // Get payout details for logging
                    $payout = Database::query("SELECT * FROM vendor_payouts WHERE id = ?", [$payoutId])->fetch();
                    
                    logAdminAction('payout_processed', 'payout', $payoutId, null, 
                        ['status' => $status, 'notes' => $notes], 
                        "Vendor payout {$status}: {$payout['amount']}"
                    );
                    
                    $_SESSION['success_message'] = "Payout status updated to {$status}.";
                } else {
                    $_SESSION['error_message'] = 'Failed to update payout status.';
                }
                break;
                
            case 'create_manual_transaction':
                requireAdminPermission(AdminPermissions::FINANCE_TRANSACTIONS);
                
                $transactionData = [
                    'reference_id' => 'MANUAL_' . strtoupper(uniqid()),
                    'type' => sanitizeInput($_POST['type']),
                    'user_id' => $_POST['user_id'] ? intval($_POST['user_id']) : null,
                    'amount' => floatval($_POST['amount']),
                    'fee' => floatval($_POST['fee'] ?? 0),
                    'net_amount' => floatval($_POST['amount']) - floatval($_POST['fee'] ?? 0),
                    'currency' => sanitizeInput($_POST['currency']),
                    'status' => 'completed',
                    'description' => sanitizeInput($_POST['description']),
                    'processed_at' => date('Y-m-d H:i:s')
                ];
                
                $transactionId = Database::query(
                    "INSERT INTO transactions (reference_id, type, user_id, amount, fee, net_amount, currency, status, description, processed_at, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                    array_values($transactionData)
                );
                
                if ($transactionId) {
                    logAdminAction('manual_transaction_created', 'transaction', $transactionId, null, 
                        $transactionData, 'Manual transaction created');
                    
                    $_SESSION['success_message'] = 'Manual transaction created successfully.';
                } else {
                    $_SESSION['error_message'] = 'Failed to create transaction.';
                }
                break;
                
            case 'generate_report':
                requireAdminPermission(AdminPermissions::FINANCE_REPORTS);
                
                $reportType = sanitizeInput($_POST['report_type']);
                $periodStart = sanitizeInput($_POST['period_start']);
                $periodEnd = sanitizeInput($_POST['period_end']);
                
                // Generate report data based on type
                $reportData = [];
                
                switch ($reportType) {
                    case 'revenue':
                        $reportData = Database::query("
                            SELECT DATE(created_at) as date, 
                                   SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END) as revenue,
                                   SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END) as refunds,
                                   COUNT(CASE WHEN type = 'payment' THEN 1 END) as transactions
                            FROM transactions 
                            WHERE created_at >= ? AND created_at <= ? AND status = 'completed'
                            GROUP BY DATE(created_at)
                            ORDER BY date DESC
                        ", [$periodStart, $periodEnd])->fetchAll();
                        break;
                        
                    case 'vendor_payouts':
                        $reportData = Database::query("
                            SELECT v.business_name, u.username,
                                   SUM(vp.amount) as total_payouts,
                                   SUM(vp.fee) as total_fees,
                                   SUM(vp.net_amount) as total_net,
                                   COUNT(*) as payout_count
                            FROM vendor_payouts vp
                            JOIN vendors v ON vp.vendor_id = v.id
                            JOIN users u ON v.user_id = u.id
                            WHERE vp.created_at >= ? AND vp.created_at <= ?
                            GROUP BY vp.vendor_id
                            ORDER BY total_payouts DESC
                        ", [$periodStart, $periodEnd])->fetchAll();
                        break;
                }
                
                // Save report
                $reportId = Database::query(
                    "INSERT INTO financial_reports (report_type, period_start, period_end, data, generated_by, generated_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())",
                    [$reportType, $periodStart, $periodEnd, json_encode($reportData), getCurrentUserId()]
                );
                
                if ($reportId) {
                    logAdminAction('financial_report_generated', 'report', $reportId, null, 
                        ['type' => $reportType, 'period' => "{$periodStart} to {$periodEnd}"], 
                        "Financial report generated: {$reportType}"
                    );
                    
                    $_SESSION['success_message'] = 'Report generated successfully.';
                } else {
                    $_SESSION['error_message'] = 'Failed to generate report.';
                }
                break;
        }
        
        header('Location: /admin/finance/?action=' . $action);
        exit;
    } catch (Exception $e) {
        error_log("Finance management error: " . $e->getMessage());
        $_SESSION['error_message'] = 'An error occurred while processing your request.';
        header('Location: /admin/finance/');
        exit;
    }
}

// Get financial statistics
try {
    $stats = [
        'total_revenue' => Database::query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'payment' AND status = 'completed'")->fetchColumn(),
        'monthly_revenue' => Database::query("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'payment' AND status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
        'pending_payouts' => Database::query("SELECT COALESCE(SUM(amount), 0) FROM vendor_payouts WHERE status = 'pending'")->fetchColumn(),
        'processed_payouts' => Database::query("SELECT COALESCE(SUM(amount), 0) FROM vendor_payouts WHERE status = 'completed'")->fetchColumn(),
        'total_transactions' => Database::query("SELECT COUNT(*) FROM transactions")->fetchColumn(),
        'failed_transactions' => Database::query("SELECT COUNT(*) FROM transactions WHERE status = 'failed'")->fetchColumn()
    ];
} catch (Exception $e) {
    $stats = [
        'total_revenue' => 0, 'monthly_revenue' => 0, 'pending_payouts' => 0, 
        'processed_payouts' => 0, 'total_transactions' => 0, 'failed_transactions' => 0
    ];
}

// Get data based on action
$transactions = [];
$payouts = [];
$reports = [];

if ($action === 'transactions') {
    try {
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 25;
        $offset = ($page - 1) * $limit;
        
        $filter = $_GET['filter'] ?? 'all';
        $whereClause = $filter !== 'all' ? "WHERE type = '" . sanitizeInput($filter) . "'" : "";
        
        $transactions = Database::query("
            SELECT t.*, u.username, u.email
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.id
            {$whereClause}
            ORDER BY t.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ")->fetchAll();
    } catch (Exception $e) {
        $transactions = [];
    }
} elseif ($action === 'payouts') {
    try {
        $payouts = Database::query("
            SELECT vp.*, v.business_name, u.username, u.email,
                   processor.username as processor_name
            FROM vendor_payouts vp
            JOIN vendors v ON vp.vendor_id = v.id
            JOIN users u ON v.user_id = u.id
            LEFT JOIN users processor ON vp.processed_by = processor.id
            ORDER BY vp.created_at DESC
            LIMIT 50
        ")->fetchAll();
    } catch (Exception $e) {
        $payouts = [];
    }
} elseif ($action === 'reports') {
    try {
        $reports = Database::query("
            SELECT fr.*, u.username as generated_by_name
            FROM financial_reports fr
            LEFT JOIN users u ON fr.generated_by = u.id
            ORDER BY fr.generated_at DESC
            LIMIT 20
        ")->fetchAll();
    } catch (Exception $e) {
        $reports = [];
    }
}

// Include admin header
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Financial Management Content -->
<div class="row">
    <div class="col-12">
        <div class="page-header">
            <h1><i class="fas fa-dollar-sign me-2"></i>Financial Management</h1>
            <p class="text-muted">Monitor transactions, process payouts, and generate financial reports</p>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="row mb-4">
    <div class="col-12">
        <nav class="nav nav-pills">
            <a class="nav-link <?php echo $action === 'dashboard' ? 'active' : ''; ?>" href="?action=dashboard">
                <i class="fas fa-chart-pie me-1"></i>Financial Dashboard
            </a>
            <?php if (hasAdminPermission(AdminPermissions::FINANCE_TRANSACTIONS)): ?>
            <a class="nav-link <?php echo $action === 'transactions' ? 'active' : ''; ?>" href="?action=transactions">
                <i class="fas fa-exchange-alt me-1"></i>Transactions
            </a>
            <?php endif; ?>
            <?php if (hasAdminPermission(AdminPermissions::FINANCE_PAYOUTS)): ?>
            <a class="nav-link <?php echo $action === 'payouts' ? 'active' : ''; ?>" href="?action=payouts">
                <i class="fas fa-money-check-alt me-1"></i>Vendor Payouts
            </a>
            <?php endif; ?>
            <?php if (hasAdminPermission(AdminPermissions::FINANCE_REPORTS)): ?>
            <a class="nav-link <?php echo $action === 'reports' ? 'active' : ''; ?>" href="?action=reports">
                <i class="fas fa-chart-line me-1"></i>Reports
            </a>
            <?php endif; ?>
        </nav>
    </div>
</div>

<?php if ($action === 'dashboard'): ?>
<!-- Financial Dashboard -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="stats-card success">
            <div class="stats-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
            <div class="stats-label">Total Revenue</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-value">$<?php echo number_format($stats['monthly_revenue'], 2); ?></div>
            <div class="stats-label">30-Day Revenue</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card warning">
            <div class="stats-value">$<?php echo number_format($stats['pending_payouts'], 2); ?></div>
            <div class="stats-label">Pending Payouts</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card success">
            <div class="stats-value">$<?php echo number_format($stats['processed_payouts'], 2); ?></div>
            <div class="stats-label">Processed Payouts</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-value"><?php echo number_format($stats['total_transactions']); ?></div>
            <div class="stats-label">Total Transactions</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card danger">
            <div class="stats-value"><?php echo number_format($stats['failed_transactions']); ?></div>
            <div class="stats-label">Failed Transactions</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- Revenue Chart -->
        <div class="dashboard-card">
            <h5><i class="fas fa-chart-area me-2"></i>Revenue Trends</h5>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- Quick Actions -->
        <div class="dashboard-card">
            <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
            <div class="d-grid gap-2">
                <?php if (hasAdminPermission(AdminPermissions::FINANCE_TRANSACTIONS)): ?>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manualTransactionModal">
                    <i class="fas fa-plus me-1"></i>Create Manual Transaction
                </button>
                <?php endif; ?>
                <?php if (hasAdminPermission(AdminPermissions::FINANCE_REPORTS)): ?>
                <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#generateReportModal">
                    <i class="fas fa-chart-line me-1"></i>Generate Report
                </button>
                <?php endif; ?>
                <a href="?action=transactions" class="btn btn-outline-info">
                    <i class="fas fa-list me-1"></i>View All Transactions
                </a>
                <a href="?action=payouts" class="btn btn-outline-warning">
                    <i class="fas fa-money-check-alt me-1"></i>Manage Payouts
                </a>
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div class="dashboard-card">
            <h5><i class="fas fa-clock me-2"></i>Recent Transactions</h5>
            <?php
            try {
                $recentTransactions = Database::query("
                    SELECT * FROM transactions 
                    ORDER BY created_at DESC 
                    LIMIT 5
                ")->fetchAll();
            } catch (Exception $e) {
                $recentTransactions = [];
            }
            ?>
            
            <div class="list-group list-group-flush">
                <?php foreach ($recentTransactions as $tx): ?>
                <div class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span class="badge bg-<?php echo $tx['type'] === 'payment' ? 'success' : ($tx['type'] === 'refund' ? 'danger' : 'info'); ?>">
                            <?php echo ucfirst($tx['type']); ?>
                        </span>
                        <span class="fw-bold">$<?php echo number_format($tx['amount'], 2); ?></span>
                    </div>
                    <small class="text-muted"><?php echo htmlspecialchars($tx['description'] ?? $tx['reference_id']); ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php elseif ($action === 'transactions' && hasAdminPermission(AdminPermissions::FINANCE_TRANSACTIONS)): ?>
<!-- Transactions List -->
<div class="dashboard-card mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <select class="form-select" onchange="filterTransactions(this.value)" style="width: auto;">
                <option value="all">All Types</option>
                <option value="payment" <?php echo ($_GET['filter'] ?? '') === 'payment' ? 'selected' : ''; ?>>Payments</option>
                <option value="refund" <?php echo ($_GET['filter'] ?? '') === 'refund' ? 'selected' : ''; ?>>Refunds</option>
                <option value="payout" <?php echo ($_GET['filter'] ?? '') === 'payout' ? 'selected' : ''; ?>>Payouts</option>
                <option value="fee" <?php echo ($_GET['filter'] ?? '') === 'fee' ? 'selected' : ''; ?>>Fees</option>
            </select>
        </div>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manualTransactionModal">
            <i class="fas fa-plus me-1"></i>Manual Transaction
        </button>
    </div>
</div>

<div class="dashboard-card">
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Type</th>
                    <th>User</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td>
                        <span class="font-monospace"><?php echo htmlspecialchars($transaction['reference_id']); ?></span>
                    </td>
                    <td>
                        <span class="badge bg-<?php echo $transaction['type'] === 'payment' ? 'success' : ($transaction['type'] === 'refund' ? 'danger' : 'info'); ?>">
                            <?php echo ucfirst($transaction['type']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($transaction['username']): ?>
                            <?php echo htmlspecialchars($transaction['username']); ?><br>
                            <small class="text-muted"><?php echo htmlspecialchars($transaction['email']); ?></small>
                        <?php else: ?>
                            <span class="text-muted">System</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong>$<?php echo number_format($transaction['amount'], 2); ?></strong>
                        <?php if ($transaction['fee'] > 0): ?>
                        <br><small class="text-muted">Fee: $<?php echo number_format($transaction['fee'], 2); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo $transaction['status']; ?>">
                            <?php echo ucfirst($transaction['status']); ?>
                        </span>
                    </td>
                    <td>
                        <?php echo date('M j, Y', strtotime($transaction['created_at'])); ?><br>
                        <small class="text-muted"><?php echo date('g:i A', strtotime($transaction['created_at'])); ?></small>
                    </td>
                    <td class="table-actions">
                        <button class="btn btn-sm btn-outline-info" onclick="viewTransaction(<?php echo htmlspecialchars(json_encode($transaction)); ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($action === 'payouts' && hasAdminPermission(AdminPermissions::FINANCE_PAYOUTS)): ?>
<!-- Vendor Payouts -->
<div class="dashboard-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Vendor Payouts</h5>
        <span class="text-muted">Showing latest 50 payouts</span>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Vendor</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Processed</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payouts as $payout): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($payout['business_name']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($payout['username']); ?></small>
                    </td>
                    <td>
                        <strong>$<?php echo number_format($payout['amount'], 2); ?></strong>
                        <?php if ($payout['fee'] > 0): ?>
                        <br><small class="text-muted">Fee: $<?php echo number_format($payout['fee'], 2); ?></small>
                        <br><small class="text-success">Net: $<?php echo number_format($payout['net_amount'], 2); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $payout['method'])); ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $payout['status']; ?>">
                            <?php echo ucfirst($payout['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($payout['created_at'])); ?></td>
                    <td>
                        <?php if ($payout['processed_at']): ?>
                            <?php echo date('M j, Y', strtotime($payout['processed_at'])); ?><br>
                            <small class="text-muted">by <?php echo htmlspecialchars($payout['processor_name'] ?? 'System'); ?></small>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="table-actions">
                        <?php if ($payout['status'] === 'pending'): ?>
                        <button class="btn btn-sm btn-outline-success" onclick="processPayout(<?php echo $payout['id']; ?>, 'completed')">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="processPayout(<?php echo $payout['id']; ?>, 'failed')">
                            <i class="fas fa-times"></i>
                        </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-info" onclick="viewPayout(<?php echo htmlspecialchars(json_encode($payout)); ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($action === 'reports' && hasAdminPermission(AdminPermissions::FINANCE_REPORTS)): ?>
<!-- Financial Reports -->
<div class="row">
    <div class="col-md-8">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>Generated Reports</h5>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#generateReportModal">
                    <i class="fas fa-plus me-1"></i>Generate Report
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Report Type</th>
                            <th>Period</th>
                            <th>Generated By</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?php echo ucfirst(str_replace('_', ' ', $report['report_type'])); ?></td>
                            <td>
                                <?php echo date('M j, Y', strtotime($report['period_start'])); ?> - 
                                <?php echo date('M j, Y', strtotime($report['period_end'])); ?>
                            </td>
                            <td><?php echo htmlspecialchars($report['generated_by_name'] ?? 'System'); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($report['generated_at'])); ?></td>
                            <td class="table-actions">
                                <a href="?action=view_report&id=<?php echo $report['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <button class="btn btn-sm btn-outline-success" onclick="downloadReport(<?php echo $report['id']; ?>)">
                                    <i class="fas fa-download"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="dashboard-card">
            <h5>Report Templates</h5>
            <div class="d-grid gap-2">
                <button class="btn btn-outline-primary" onclick="quickReport('revenue', 30)">
                    <i class="fas fa-chart-line me-1"></i>30-Day Revenue
                </button>
                <button class="btn btn-outline-success" onclick="quickReport('vendor_payouts', 30)">
                    <i class="fas fa-money-check-alt me-1"></i>Vendor Payouts
                </button>
                <button class="btn btn-outline-info" onclick="quickReport('revenue', 90)">
                    <i class="fas fa-calendar me-1"></i>Quarterly Report
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Manual Transaction Modal -->
<?php if (hasAdminPermission(AdminPermissions::FINANCE_TRANSACTIONS)): ?>
<div class="modal fade" id="manualTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="admin-form">
                <?php echo csrfTokenInput(); ?>
                <input type="hidden" name="action" value="create_manual_transaction">
                
                <div class="modal-header">
                    <h5 class="modal-title">Create Manual Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Transaction Type</label>
                        <select class="form-select" name="type" required>
                            <option value="payment">Payment</option>
                            <option value="refund">Refund</option>
                            <option value="adjustment">Adjustment</option>
                            <option value="fee">Fee</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">User ID (Optional)</label>
                        <input type="number" class="form-control" name="user_id" placeholder="Leave empty for system transaction">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" class="form-control" name="amount" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Currency</label>
                                <select class="form-select" name="currency">
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fee (Optional)</label>
                        <input type="number" class="form-control" name="fee" step="0.01" min="0" value="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required 
                                  placeholder="Describe the reason for this manual transaction"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Generate Report Modal -->
<?php if (hasAdminPermission(AdminPermissions::FINANCE_REPORTS)): ?>
<div class="modal fade" id="generateReportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="admin-form">
                <?php echo csrfTokenInput(); ?>
                <input type="hidden" name="action" value="generate_report">
                
                <div class="modal-header">
                    <h5 class="modal-title">Generate Financial Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Report Type</label>
                        <select class="form-select" name="report_type" required>
                            <option value="revenue">Revenue Report</option>
                            <option value="vendor_payouts">Vendor Payouts Report</option>
                            <option value="transaction_summary">Transaction Summary</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Period Start</label>
                                <input type="date" class="form-control" name="period_start" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Period End</label>
                                <input type="date" class="form-control" name="period_end" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Generate Report</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Additional scripts for finance management
$additional_scripts = '
<script>
function filterTransactions(type) {
    const url = new URL(window.location);
    url.searchParams.set("filter", type);
    window.location = url;
}

function processPayout(payoutId, status) {
    const notes = prompt(`Please enter notes for marking this payout as ${status}:`);
    if (notes !== null) {
        const form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = `
            ' . csrfTokenInput() . '
            <input type="hidden" name="action" value="process_payout">
            <input type="hidden" name="payout_id" value="${payoutId}">
            <input type="hidden" name="status" value="${status}">
            <input type="hidden" name="notes" value="${notes}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewTransaction(transaction) {
    alert("Transaction Details:\\n\\nReference: " + transaction.reference_id + "\\nType: " + transaction.type + "\\nAmount: $" + transaction.amount + "\\nStatus: " + transaction.status);
}

function viewPayout(payout) {
    alert("Payout Details:\\n\\nVendor: " + payout.business_name + "\\nAmount: $" + payout.amount + "\\nStatus: " + payout.status);
}

function quickReport(type, days) {
    const endDate = new Date();
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - days);
    
    const form = document.createElement("form");
    form.method = "POST";
    form.innerHTML = `
        ' . csrfTokenInput() . '
        <input type="hidden" name="action" value="generate_report">
        <input type="hidden" name="report_type" value="${type}">
        <input type="hidden" name="period_start" value="${startDate.toISOString().split(\"T\")[0]}">
        <input type="hidden" name="period_end" value="${endDate.toISOString().split(\"T\")[0]}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Revenue chart
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById("revenueChart");
    if (ctx) {
        new Chart(ctx, {
            type: "line",
            data: {
                labels: ["7 days ago", "6 days ago", "5 days ago", "4 days ago", "3 days ago", "2 days ago", "Yesterday", "Today"],
                datasets: [{
                    label: "Revenue",
                    data: [1200, 1900, 800, 2500, 1500, 2200, 1800, 2100],
                    borderColor: "rgb(75, 192, 192)",
                    backgroundColor: "rgba(75, 192, 192, 0.1)",
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return "$" + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>';

// Include admin footer
require_once __DIR__ . '/../../includes/footer.php';
?>