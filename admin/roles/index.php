<?php
/**
 * Role & Permission Management - Admin Module
 * RBAC Management System
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
requireAdminPermission(AdminPermissions::ROLES_VIEW);

$page_title = 'Roles & Permissions';
$action = $_GET['action'] ?? 'list';
$role_id = $_GET['id'] ?? null;

// Handle actions
if ($_POST && isset($_POST['action'])) {
    validateCsrfAndRateLimit();
    
    try {
        switch ($_POST['action']) {
            case 'update_role_permissions':
                requireAdminPermission(AdminPermissions::ROLE_PERMISSIONS_MANAGE);
                
                $roleId = intval($_POST['role_id']);
                $permissions = $_POST['permissions'] ?? [];
                
                // Get current permissions for logging
                $currentPermissions = Database::query(
                    "SELECT p.name FROM role_permissions rp 
                     JOIN permissions p ON rp.permission_id = p.id 
                     WHERE rp.role_id = ?",
                    [$roleId]
                )->fetchAll(PDO::FETCH_COLUMN);
                
                // Remove all current permissions
                Database::query("DELETE FROM role_permissions WHERE role_id = ?", [$roleId]);
                
                // Add new permissions
                foreach ($permissions as $permissionId) {
                    Database::query(
                        "INSERT INTO role_permissions (role_id, permission_id, granted_by, granted_at) 
                         VALUES (?, ?, ?, NOW())",
                        [$roleId, intval($permissionId), getCurrentUserId()]
                    );
                }
                
                // Get new permissions for logging
                $newPermissions = Database::query(
                    "SELECT p.name FROM role_permissions rp 
                     JOIN permissions p ON rp.permission_id = p.id 
                     WHERE rp.role_id = ?",
                    [$roleId]
                )->fetchAll(PDO::FETCH_COLUMN);
                
                // Log admin action
                logAdminAction('role_permissions_updated', 'role', $roleId, 
                    ['permissions' => $currentPermissions], 
                    ['permissions' => $newPermissions], 
                    'Role permissions updated'
                );
                
                $_SESSION['success_message'] = 'Role permissions updated successfully.';
                break;
                
            case 'create_role':
                requireAdminPermission(AdminPermissions::ROLES_EDIT);
                
                $roleData = [
                    'name' => sanitizeInput($_POST['name']),
                    'display_name' => sanitizeInput($_POST['display_name']),
                    'description' => sanitizeInput($_POST['description']),
                    'level' => intval($_POST['level'])
                ];
                
                $roleId = Database::query(
                    "INSERT INTO roles (name, display_name, description, level, created_at) 
                     VALUES (?, ?, ?, ?, NOW())",
                    array_values($roleData)
                );
                
                if ($roleId) {
                    logAdminAction('role_created', 'role', $roleId, null, $roleData, 'New role created');
                    $_SESSION['success_message'] = 'Role created successfully.';
                } else {
                    $_SESSION['error_message'] = 'Failed to create role.';
                }
                break;
                
            case 'update_role':
                requireAdminPermission(AdminPermissions::ROLES_EDIT);
                
                $roleId = intval($_POST['role_id']);
                
                // Get old data for logging
                $oldData = Database::query("SELECT * FROM roles WHERE id = ?", [$roleId])->fetch();
                
                $roleData = [
                    'display_name' => sanitizeInput($_POST['display_name']),
                    'description' => sanitizeInput($_POST['description']),
                    'level' => intval($_POST['level']),
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ];
                
                $updated = Database::query(
                    "UPDATE roles SET display_name = ?, description = ?, level = ?, is_active = ?, updated_at = NOW() 
                     WHERE id = ?",
                    array_merge(array_values($roleData), [$roleId])
                );
                
                if ($updated) {
                    logAdminAction('role_updated', 'role', $roleId, $oldData, $roleData, 'Role information updated');
                    $_SESSION['success_message'] = 'Role updated successfully.';
                } else {
                    $_SESSION['error_message'] = 'Failed to update role.';
                }
                break;
        }
        
        header('Location: /admin/roles/');
        exit;
    } catch (Exception $e) {
        error_log("Role management error: " . $e->getMessage());
        $_SESSION['error_message'] = 'An error occurred while processing your request.';
        header('Location: /admin/roles/');
        exit;
    }
}

// Get roles list
try {
    $roles = Database::query("SELECT * FROM roles ORDER BY level DESC, name")->fetchAll();
} catch (Exception $e) {
    $roles = [];
    error_log("Error fetching roles: " . $e->getMessage());
}

// Get permissions list grouped by module
try {
    $permissions = Database::query("SELECT * FROM permissions ORDER BY module, name")->fetchAll();
    $permissionsByModule = [];
    foreach ($permissions as $permission) {
        $permissionsByModule[$permission['module']][] = $permission;
    }
} catch (Exception $e) {
    $permissions = [];
    $permissionsByModule = [];
    error_log("Error fetching permissions: " . $e->getMessage());
}

// Get current role for editing
$currentRole = null;
$currentRolePermissions = [];
if ($action === 'edit' && $role_id) {
    try {
        $currentRole = Database::query("SELECT * FROM roles WHERE id = ?", [$role_id])->fetch();
        if ($currentRole) {
            $currentRolePermissions = Database::query(
                "SELECT permission_id FROM role_permissions WHERE role_id = ?",
                [$role_id]
            )->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (Exception $e) {
        error_log("Error fetching role details: " . $e->getMessage());
    }
}

// Include admin header
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Role Management Content -->
<div class="row">
    <div class="col-12">
        <div class="page-header">
            <h1><i class="fas fa-user-shield me-2"></i>Role & Permission Management</h1>
            <p class="text-muted">Manage user roles and permissions</p>
        </div>
    </div>
</div>

<?php if ($action === 'list'): ?>
<!-- Roles List -->
<div class="row">
    <div class="col-md-8">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>System Roles</h5>
                <?php if (hasAdminPermission(AdminPermissions::ROLES_EDIT)): ?>
                <button class="btn btn-admin-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                    <i class="fas fa-plus me-1"></i>Create Role
                </button>
                <?php endif; ?>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Role Name</th>
                            <th>Level</th>
                            <th>Users</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                        <?php
                        try {
                            $userCount = Database::query(
                                "SELECT COUNT(*) FROM user_role_assignments WHERE role_id = ? AND is_active = 1",
                                [$role['id']]
                            )->fetchColumn();
                        } catch (Exception $e) {
                            $userCount = 0;
                        }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($role['display_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($role['description'] ?? ''); ?></small>
                            </td>
                            <td><span class="badge bg-info"><?php echo $role['level']; ?></span></td>
                            <td><?php echo $userCount; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $role['is_active'] ? 'active' : 'suspended'; ?>">
                                    <?php echo $role['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="table-actions">
                                <?php if (hasAdminPermission(AdminPermissions::ROLE_PERMISSIONS_MANAGE)): ?>
                                <a href="?action=edit&id=<?php echo $role['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-key"></i> Permissions
                                </a>
                                <?php endif; ?>
                                <?php if (hasAdminPermission(AdminPermissions::ROLES_EDIT) && $role['name'] !== 'admin'): ?>
                                <button class="btn btn-sm btn-outline-secondary" onclick="editRole(<?php echo htmlspecialchars(json_encode($role)); ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php endif; ?>
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
            <h5>Role Statistics</h5>
            <div class="stats-card mb-3">
                <div class="stats-value"><?php echo count($roles); ?></div>
                <div class="stats-label">Total Roles</div>
            </div>
            <div class="stats-card mb-3">
                <div class="stats-value"><?php echo count($permissions); ?></div>
                <div class="stats-label">Total Permissions</div>
            </div>
            <div class="stats-card">
                <div class="stats-value"><?php echo count(array_filter($roles, fn($r) => $r['is_active'])); ?></div>
                <div class="stats-label">Active Roles</div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($action === 'edit' && $currentRole): ?>
<!-- Edit Role Permissions -->
<div class="row">
    <div class="col-12">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5>Edit Permissions for: <?php echo htmlspecialchars($currentRole['display_name']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($currentRole['description'] ?? ''); ?></p>
                </div>
                <a href="/admin/roles/" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Back to Roles
                </a>
            </div>
            
            <form method="POST" class="admin-form">
                <?php echo csrfTokenInput(); ?>
                <input type="hidden" name="action" value="update_role_permissions">
                <input type="hidden" name="role_id" value="<?php echo $currentRole['id']; ?>">
                
                <?php foreach ($permissionsByModule as $module => $modulePermissions): ?>
                <div class="mb-4">
                    <h6 class="text-primary text-uppercase fw-bold"><?php echo ucfirst($module); ?> Module</h6>
                    <div class="row">
                        <?php foreach ($modulePermissions as $permission): ?>
                        <div class="col-md-6 col-lg-4 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="permissions[]" 
                                       value="<?php echo $permission['id']; ?>"
                                       id="perm_<?php echo $permission['id']; ?>"
                                       <?php echo in_array($permission['id'], $currentRolePermissions) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="perm_<?php echo $permission['id']; ?>">
                                    <strong><?php echo htmlspecialchars($permission['display_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($permission['description'] ?? ''); ?></small>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-admin-primary">
                        <i class="fas fa-save me-1"></i>Update Permissions
                    </button>
                    <a href="/admin/roles/" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Create Role Modal -->
<?php if (hasAdminPermission(AdminPermissions::ROLES_EDIT)): ?>
<div class="modal fade" id="createRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="admin-form">
                <?php echo csrfTokenInput(); ?>
                <input type="hidden" name="action" value="create_role">
                
                <div class="modal-header">
                    <h5 class="modal-title">Create New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Role Name (Internal)</label>
                        <input type="text" class="form-control" name="name" required
                               pattern="[a-z_]+" title="Lowercase letters and underscores only">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Display Name</label>
                        <input type="text" class="form-control" name="display_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Level (1-10)</label>
                        <input type="number" class="form-control" name="level" min="1" max="10" value="1" required>
                        <small class="form-text text-muted">Higher levels inherit lower level permissions</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-admin-primary">Create Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Role Modal -->
<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="admin-form">
                <?php echo csrfTokenInput(); ?>
                <input type="hidden" name="action" value="update_role">
                <input type="hidden" name="role_id" id="edit_role_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Display Name</label>
                        <input type="text" class="form-control" name="display_name" id="edit_display_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Level (1-10)</label>
                        <input type="number" class="form-control" name="level" id="edit_level" min="1" max="10" required>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" value="1">
                            <label class="form-check-label" for="edit_is_active">Active</label>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-admin-primary">Update Role</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Additional scripts for role management
$additional_scripts = '
<script>
function editRole(role) {
    document.getElementById("edit_role_id").value = role.id;
    document.getElementById("edit_display_name").value = role.display_name;
    document.getElementById("edit_description").value = role.description || "";
    document.getElementById("edit_level").value = role.level;
    document.getElementById("edit_is_active").checked = role.is_active == 1;
    
    new bootstrap.Modal(document.getElementById("editRoleModal")).show();
}

// Select all permissions in module
document.addEventListener("DOMContentLoaded", function() {
    // Add select all checkboxes for each module
    const modules = document.querySelectorAll(".text-primary.text-uppercase");
    modules.forEach(function(moduleHeader) {
        const selectAllBtn = document.createElement("button");
        selectAllBtn.type = "button";
        selectAllBtn.className = "btn btn-sm btn-outline-secondary ms-2";
        selectAllBtn.textContent = "Select All";
        
        selectAllBtn.addEventListener("click", function() {
            const moduleDiv = moduleHeader.closest(".mb-4");
            const checkboxes = moduleDiv.querySelectorAll("input[type=checkbox]");
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(cb => cb.checked = !allChecked);
            selectAllBtn.textContent = allChecked ? "Select All" : "Deselect All";
        });
        
        moduleHeader.appendChild(selectAllBtn);
    });
});
</script>';

// Include admin footer
require_once __DIR__ . '/../../includes/footer.php';
?>