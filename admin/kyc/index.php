<?php
/**
 * KYC & Verification Management - Admin Module
 * Document verification and compliance system
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
requireAdminPermission(AdminPermissions::KYC_VIEW);

$page_title = 'KYC & Verification';
$action = $_GET['action'] ?? 'list';
$document_id = $_GET['id'] ?? null;
$user_id = $_GET['user_id'] ?? null;

// Handle actions
if ($_POST && isset($_POST['action'])) {
    validateCsrfAndRateLimit();
    
    try {
        switch ($_POST['action']) {
            case 'approve_document':
                requireAdminPermission(AdminPermissions::KYC_APPROVE);
                
                $documentId = intval($_POST['document_id']);
                $notes = sanitizeInput($_POST['notes'] ?? '');
                
                // Update document status
                Database::query(
                    "UPDATE kyc_documents SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), review_notes = ? 
                     WHERE id = ?",
                    [getCurrentUserId(), $notes, $documentId]
                );
                
                // Get document info
                $document = Database::query("SELECT * FROM kyc_documents WHERE id = ?", [$documentId])->fetch();
                
                if ($document) {
                    // Update user KYC verification status
                    Database::query(
                        "INSERT INTO kyc_verifications (user_id, status, verified_by, verified_at, notes) 
                         VALUES (?, 'approved', ?, NOW(), ?) 
                         ON DUPLICATE KEY UPDATE 
                         status = 'approved', verified_by = ?, verified_at = NOW(), notes = ?",
                        [$document['user_id'], getCurrentUserId(), $notes, getCurrentUserId(), $notes]
                    );
                    
                    // Log admin action
                    logAdminAction('kyc_document_approved', 'kyc_document', $documentId, null, 
                        ['status' => 'approved', 'notes' => $notes], 'KYC document approved');
                    
                    // Send notification
                    sendKycStatusNotification($document['user_id'], 'approved', $notes);
                    
                    $_SESSION['success_message'] = 'Document approved successfully.';
                }
                break;
                
            case 'reject_document':
                requireAdminPermission(AdminPermissions::KYC_REJECT);
                
                $documentId = intval($_POST['document_id']);
                $notes = sanitizeInput($_POST['notes'] ?? '');
                
                if (empty($notes)) {
                    $_SESSION['error_message'] = 'Rejection reason is required.';
                    break;
                }
                
                // Update document status
                Database::query(
                    "UPDATE kyc_documents SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_notes = ? 
                     WHERE id = ?",
                    [getCurrentUserId(), $notes, $documentId]
                );
                
                // Get document info
                $document = Database::query("SELECT * FROM kyc_documents WHERE id = ?", [$documentId])->fetch();
                
                if ($document) {
                    // Update user KYC verification status
                    Database::query(
                        "INSERT INTO kyc_verifications (user_id, status, verified_by, verified_at, notes) 
                         VALUES (?, 'rejected', ?, NOW(), ?) 
                         ON DUPLICATE KEY UPDATE 
                         status = 'rejected', verified_by = ?, verified_at = NOW(), notes = ?",
                        [$document['user_id'], getCurrentUserId(), $notes, getCurrentUserId(), $notes]
                    );
                    
                    // Log admin action
                    logAdminAction('kyc_document_rejected', 'kyc_document', $documentId, null, 
                        ['status' => 'rejected', 'notes' => $notes], 'KYC document rejected');
                    
                    // Send notification
                    sendKycStatusNotification($document['user_id'], 'rejected', $notes);
                    
                    $_SESSION['success_message'] = 'Document rejected successfully.';
                }
                break;
                
            case 'bulk_action':
                $bulkAction = $_POST['bulk_action'] ?? '';
                $selectedItems = $_POST['selected_items'] ?? [];
                
                if (empty($selectedItems)) {
                    $_SESSION['error_message'] = 'No items selected.';
                    break;
                }
                
                $count = 0;
                foreach ($selectedItems as $documentId) {
                    $documentId = intval($documentId);
                    
                    switch ($bulkAction) {
                        case 'approve':
                            requireAdminPermission(AdminPermissions::KYC_APPROVE);
                            Database::query(
                                "UPDATE kyc_documents SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() 
                                 WHERE id = ? AND status = 'pending'",
                                [getCurrentUserId(), $documentId]
                            );
                            $count++;
                            break;
                            
                        case 'mark_pending':
                            requireAdminPermission(AdminPermissions::KYC_APPROVE);
                            Database::query(
                                "UPDATE kyc_documents SET status = 'pending', reviewed_by = NULL, reviewed_at = NULL 
                                 WHERE id = ?",
                                [$documentId]
                            );
                            $count++;
                            break;
                    }
                }
                
                logAdminAction('kyc_bulk_action', 'kyc_document', null, null, 
                    ['action' => $bulkAction, 'count' => $count], "Bulk action: {$bulkAction} on {$count} documents");
                
                $_SESSION['success_message'] = "Bulk action completed on {$count} items.";
                break;
        }
        
        header('Location: /admin/kyc/');
        exit;
    } catch (Exception $e) {
        error_log("KYC management error: " . $e->getMessage());
        $_SESSION['error_message'] = 'An error occurred while processing your request.';
        header('Location: /admin/kyc/');
        exit;
    }
}

// Get KYC documents with filtering
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

$whereConditions = [];
$params = [];

// Apply filters
if ($filter !== 'all') {
    $whereConditions[] = "kd.status = ?";
    $params[] = $filter;
}

if (!empty($search)) {
    $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ? OR kd.document_type LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

try {
    $documents = Database::query("
        SELECT kd.*, u.username, u.email, u.first_name, u.last_name,
               reviewer.username as reviewer_name
        FROM kyc_documents kd
        JOIN users u ON kd.user_id = u.id
        LEFT JOIN users reviewer ON kd.reviewed_by = reviewer.id
        $whereClause
        ORDER BY kd.uploaded_at DESC
        LIMIT $limit OFFSET $offset
    ", $params)->fetchAll();
    
    $totalDocuments = Database::query("
        SELECT COUNT(*)
        FROM kyc_documents kd
        JOIN users u ON kd.user_id = u.id
        $whereClause
    ", $params)->fetchColumn();
    
    $totalPages = ceil($totalDocuments / $limit);
} catch (Exception $e) {
    $documents = [];
    $totalDocuments = 0;
    $totalPages = 0;
    error_log("Error fetching KYC documents: " . $e->getMessage());
}

// Get KYC statistics
try {
    $stats = [
        'total' => Database::query("SELECT COUNT(*) FROM kyc_documents")->fetchColumn(),
        'pending' => Database::query("SELECT COUNT(*) FROM kyc_documents WHERE status = 'pending'")->fetchColumn(),
        'approved' => Database::query("SELECT COUNT(*) FROM kyc_documents WHERE status = 'approved'")->fetchColumn(),
        'rejected' => Database::query("SELECT COUNT(*) FROM kyc_documents WHERE status = 'rejected'")->fetchColumn(),
        'expired' => Database::query("SELECT COUNT(*) FROM kyc_documents WHERE status = 'expired'")->fetchColumn(),
        'verified_users' => Database::query("SELECT COUNT(*) FROM kyc_verifications WHERE status = 'approved'")->fetchColumn()
    ];
} catch (Exception $e) {
    $stats = [
        'total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0, 'expired' => 0, 'verified_users' => 0
    ];
}

// Get current document for review
$currentDocument = null;
if ($action === 'review' && $document_id) {
    try {
        $currentDocument = Database::query("
            SELECT kd.*, u.username, u.email, u.first_name, u.last_name
            FROM kyc_documents kd
            JOIN users u ON kd.user_id = u.id
            WHERE kd.id = ?
        ", [$document_id])->fetch();
    } catch (Exception $e) {
        error_log("Error fetching document details: " . $e->getMessage());
    }
}

// Include admin header
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- KYC Management Content -->
<div class="row">
    <div class="col-12">
        <div class="page-header">
            <h1><i class="fas fa-id-card me-2"></i>KYC & Verification Management</h1>
            <p class="text-muted">Review and verify user documents and identities</p>
        </div>
    </div>
</div>

<?php if ($action === 'list'): ?>
<!-- KYC Statistics -->
<div class="row mb-4">
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-value"><?php echo number_format($stats['total']); ?></div>
            <div class="stats-label">Total Documents</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card warning">
            <div class="stats-value"><?php echo number_format($stats['pending']); ?></div>
            <div class="stats-label">Pending Review</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card success">
            <div class="stats-value"><?php echo number_format($stats['approved']); ?></div>
            <div class="stats-label">Approved</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card danger">
            <div class="stats-value"><?php echo number_format($stats['rejected']); ?></div>
            <div class="stats-label">Rejected</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card">
            <div class="stats-value"><?php echo number_format($stats['expired']); ?></div>
            <div class="stats-label">Expired</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="stats-card success">
            <div class="stats-value"><?php echo number_format($stats['verified_users']); ?></div>
            <div class="stats-label">Verified Users</div>
        </div>
    </div>
</div>

<!-- Filters and Search -->
<div class="dashboard-card mb-4">
    <div class="row align-items-center">
        <div class="col-md-3">
            <select class="form-select" onchange="updateFilter(this.value)">
                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                <option value="approved" <?php echo $filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                <option value="expired" <?php echo $filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
            </select>
        </div>
        <div class="col-md-6">
            <form method="GET" class="d-flex">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                <input type="text" class="form-control" name="search" 
                       placeholder="Search by user, email, or document type..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-outline-primary ms-2">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        <div class="col-md-3 text-end">
            <button class="btn btn-outline-success" onclick="exportKycData()">
                <i class="fas fa-download me-1"></i>Export
            </button>
        </div>
    </div>
</div>

<!-- Documents Table -->
<div class="dashboard-card">
    <form method="POST" class="bulk-action-form">
        <?php echo csrfTokenInput(); ?>
        <input type="hidden" name="action" value="bulk_action">
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center">
                <select class="form-select me-2" name="bulk_action" style="width: auto;">
                    <option value="">Bulk Actions</option>
                    <?php if (hasAdminPermission(AdminPermissions::KYC_APPROVE)): ?>
                    <option value="approve">Approve Selected</option>
                    <option value="mark_pending">Mark as Pending</option>
                    <?php endif; ?>
                </select>
                <button type="submit" class="btn btn-outline-primary">Apply</button>
            </div>
            <div>
                Showing <?php echo number_format($totalDocuments); ?> documents
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><input type="checkbox" class="select-all"></th>
                        <th>User</th>
                        <th>Document Type</th>
                        <th>Status</th>
                        <th>Uploaded</th>
                        <th>Reviewed By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $document): ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="item-select" name="selected_items[]" value="<?php echo $document['id']; ?>">
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($document['username']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($document['email']); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $document['document_type'])); ?></span><br>
                            <small class="text-muted"><?php echo htmlspecialchars($document['original_filename']); ?></small>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $document['status']; ?>">
                                <?php echo ucfirst($document['status']); ?>
                            </span>
                            <?php if ($document['review_notes']): ?>
                            <br><small class="text-muted" title="<?php echo htmlspecialchars($document['review_notes']); ?>">
                                <?php echo strlen($document['review_notes']) > 50 ? substr($document['review_notes'], 0, 50) . '...' : $document['review_notes']; ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo date('M j, Y', strtotime($document['uploaded_at'])); ?><br>
                            <small class="text-muted"><?php echo date('g:i A', strtotime($document['uploaded_at'])); ?></small>
                        </td>
                        <td>
                            <?php if ($document['reviewer_name']): ?>
                                <?php echo htmlspecialchars($document['reviewer_name']); ?><br>
                                <small class="text-muted"><?php echo date('M j, Y', strtotime($document['reviewed_at'])); ?></small>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="table-actions">
                            <a href="?action=review&id=<?php echo $document['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> Review
                            </a>
                            <?php if ($document['status'] === 'pending'): ?>
                                <?php if (hasAdminPermission(AdminPermissions::KYC_APPROVE)): ?>
                                <button type="button" class="btn btn-sm btn-outline-success" 
                                        onclick="quickApprove(<?php echo $document['id']; ?>)">
                                    <i class="fas fa-check"></i>
                                </button>
                                <?php endif; ?>
                                <?php if (hasAdminPermission(AdminPermissions::KYC_REJECT)): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="quickReject(<?php echo $document['id']; ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php elseif ($action === 'review' && $currentDocument): ?>
<!-- Document Review -->
<div class="row">
    <div class="col-md-8">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5>Review Document</h5>
                <a href="/admin/kyc/" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to List
                </a>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6>User Information</h6>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($currentDocument['first_name'] . ' ' . $currentDocument['last_name']); ?></p>
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($currentDocument['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($currentDocument['email']); ?></p>
                </div>
                <div class="col-md-6">
                    <h6>Document Details</h6>
                    <p><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $currentDocument['document_type'])); ?></p>
                    <p><strong>Filename:</strong> <?php echo htmlspecialchars($currentDocument['original_filename']); ?></p>
                    <p><strong>Size:</strong> <?php echo number_format($currentDocument['file_size'] / 1024, 2); ?> KB</p>
                    <p><strong>Uploaded:</strong> <?php echo date('M j, Y g:i A', strtotime($currentDocument['uploaded_at'])); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="status-badge status-<?php echo $currentDocument['status']; ?>">
                            <?php echo ucfirst($currentDocument['status']); ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <!-- Document Preview -->
            <div class="mb-4">
                <h6>Document Preview</h6>
                <div class="border rounded p-3 text-center bg-light">
                    <?php if (in_array($currentDocument['mime_type'], ['image/jpeg', 'image/png', 'image/gif'])): ?>
                        <img src="<?php echo htmlspecialchars($currentDocument['file_path']); ?>" 
                             alt="Document" class="img-fluid" style="max-height: 500px;">
                    <?php elseif ($currentDocument['mime_type'] === 'application/pdf'): ?>
                        <iframe src="<?php echo htmlspecialchars($currentDocument['file_path']); ?>" 
                                width="100%" height="500px" style="border: none;"></iframe>
                    <?php else: ?>
                        <p><i class="fas fa-file fa-3x text-muted"></i></p>
                        <p>Preview not available for this file type</p>
                        <a href="<?php echo htmlspecialchars($currentDocument['file_path']); ?>" 
                           target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-download me-1"></i>Download File
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="dashboard-card">
            <h6>Review Actions</h6>
            
            <?php if ($currentDocument['status'] === 'pending'): ?>
            <!-- Approve Form -->
            <?php if (hasAdminPermission(AdminPermissions::KYC_APPROVE)): ?>
            <form method="POST" class="mb-3">
                <?php echo csrfTokenInput(); ?>
                <input type="hidden" name="action" value="approve_document">
                <input type="hidden" name="document_id" value="<?php echo $currentDocument['id']; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Approval Notes (Optional)</label>
                    <textarea class="form-control" name="notes" rows="3" 
                              placeholder="Add any notes about the approval..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-success w-100">
                    <i class="fas fa-check me-1"></i>Approve Document
                </button>
            </form>
            <?php endif; ?>
            
            <!-- Reject Form -->
            <?php if (hasAdminPermission(AdminPermissions::KYC_REJECT)): ?>
            <form method="POST">
                <?php echo csrfTokenInput(); ?>
                <input type="hidden" name="action" value="reject_document">
                <input type="hidden" name="document_id" value="<?php echo $currentDocument['id']; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Rejection Reason *</label>
                    <textarea class="form-control" name="notes" rows="3" required
                              placeholder="Please explain why this document is being rejected..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-danger w-100">
                    <i class="fas fa-times me-1"></i>Reject Document
                </button>
            </form>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="alert alert-info">
                This document has already been reviewed.
                <?php if ($currentDocument['review_notes']): ?>
                <hr>
                <strong>Review Notes:</strong><br>
                <?php echo htmlspecialchars($currentDocument['review_notes']); ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- User's Other Documents -->
        <div class="dashboard-card">
            <h6>User's Other Documents</h6>
            <?php
            try {
                $otherDocs = Database::query(
                    "SELECT * FROM kyc_documents WHERE user_id = ? AND id != ? ORDER BY uploaded_at DESC",
                    [$currentDocument['user_id'], $currentDocument['id']]
                )->fetchAll();
            } catch (Exception $e) {
                $otherDocs = [];
            }
            ?>
            
            <?php if ($otherDocs): ?>
            <div class="list-group list-group-flush">
                <?php foreach ($otherDocs as $doc): ?>
                <a href="?action=review&id=<?php echo $doc['id']; ?>" class="list-group-item list-group-item-action">
                    <div class="d-flex justify-content-between">
                        <span><?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?></span>
                        <span class="status-badge status-<?php echo $doc['status']; ?>"><?php echo ucfirst($doc['status']); ?></span>
                    </div>
                    <small class="text-muted"><?php echo date('M j, Y', strtotime($doc['uploaded_at'])); ?></small>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted">No other documents found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Additional scripts for KYC management
$additional_scripts = '
<script>
function updateFilter(value) {
    const url = new URL(window.location);
    url.searchParams.set("filter", value);
    url.searchParams.delete("page");
    window.location = url;
}

function quickApprove(documentId) {
    if (confirm("Are you sure you want to approve this document?")) {
        const form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = `
            ' . csrfTokenInput() . '
            <input type="hidden" name="action" value="approve_document">
            <input type="hidden" name="document_id" value="${documentId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function quickReject(documentId) {
    const reason = prompt("Please provide a reason for rejection:");
    if (reason && reason.trim()) {
        const form = document.createElement("form");
        form.method = "POST";
        form.innerHTML = `
            ' . csrfTokenInput() . '
            <input type="hidden" name="action" value="reject_document">
            <input type="hidden" name="document_id" value="${documentId}">
            <input type="hidden" name="notes" value="${reason.trim()}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function exportKycData() {
    window.open("/admin/kyc/export.php", "_blank");
}
</script>';

// Include admin footer
require_once __DIR__ . '/../../includes/footer.php';
?>