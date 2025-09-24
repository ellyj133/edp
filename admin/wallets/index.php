<?php
/**
 * Wallet Management Module - Admin Panel
 * E-Commerce Platform
 * 
 * Features:
 * - View all user wallets and balances
 * - Add funds to user wallets
 * - Suspend/reactivate wallets
 * - View wallet transaction history
 * - Wallet audit and management
 */

session_start();
require_once __DIR__ . '/../../setup_simple.php';

// Simple admin authentication check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /simple_login.php');
    exit;
}

$db = db();
$action = $_GET['action'] ?? 'list';
$userId = $_GET['user_id'] ?? null;
$message = '';
$error = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    switch ($postAction) {
        case 'add_funds':
            $userId = $_POST['user_id'] ?? 0;
            $amount = (float)($_POST['amount'] ?? 0);
            $description = $_POST['description'] ?? 'Admin credit';
            
            if ($userId && $amount > 0) {
                try {
                    $db->beginTransaction();
                    
                    // Get or create buyer record
                    $buyerStmt = $db->prepare("SELECT * FROM buyers WHERE user_id = ?");
                    $buyerStmt->execute([$userId]);
                    $buyer = $buyerStmt->fetch();
                    
                    if (!$buyer) {
                        $db->prepare("INSERT INTO buyers (user_id) VALUES (?)")->execute([$userId]);
                        $buyerId = $db->lastInsertId();
                    } else {
                        $buyerId = $buyer['id'];
                    }
                    
                    // Get or create wallet
                    $walletStmt = $db->prepare("SELECT * FROM buyer_wallets WHERE buyer_id = ?");
                    $walletStmt->execute([$buyerId]);
                    $wallet = $walletStmt->fetch();
                    
                    if (!$wallet) {
                        $db->prepare("INSERT INTO buyer_wallets (buyer_id, currency, balance) VALUES (?, 'USD', ?)")
                           ->execute([$buyerId, $amount]);
                        $walletId = $db->lastInsertId();
                        $newBalance = $amount;
                    } else {
                        $walletId = $wallet['id'];
                        $newBalance = $wallet['balance'] + $amount;
                        $db->prepare("UPDATE buyer_wallets SET balance = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                           ->execute([$newBalance, $walletId]);
                    }
                    
                    // Log transaction
                    $db->prepare("INSERT INTO buyer_wallet_entries (wallet_id, transaction_type, amount, balance_after, description, created_at) VALUES (?, 'credit', ?, ?, ?, CURRENT_TIMESTAMP)")
                       ->execute([$walletId, $amount, $newBalance, $description]);
                    
                    $db->commit();
                    $message = "Successfully added $" . number_format($amount, 2) . " to user wallet";
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = "Failed to add funds: " . $e->getMessage();
                }
            } else {
                $error = "Invalid user ID or amount";
            }
            break;
            
        case 'suspend_wallet':
            $walletId = $_POST['wallet_id'] ?? 0;
            if ($walletId) {
                $db->prepare("UPDATE buyer_wallets SET status = 'suspended', updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                   ->execute([$walletId]);
                $message = "Wallet suspended successfully";
            }
            break;
            
        case 'activate_wallet':
            $walletId = $_POST['wallet_id'] ?? 0;
            if ($walletId) {
                $db->prepare("UPDATE buyer_wallets SET status = 'active', updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                   ->execute([$walletId]);
                $message = "Wallet activated successfully";
            }
            break;
    }
}

// Get all users with their wallet information
$walletsQuery = "
    SELECT 
        u.id as user_id,
        u.username,
        u.email,
        u.first_name,
        u.last_name,
        u.role,
        u.status as user_status,
        b.id as buyer_id,
        w.id as wallet_id,
        w.balance,
        w.currency,
        w.status as wallet_status,
        w.created_at as wallet_created,
        w.updated_at as wallet_updated
    FROM users u
    LEFT JOIN buyers b ON u.id = b.user_id
    LEFT JOIN buyer_wallets w ON b.id = w.buyer_id
    ORDER BY u.id
";
$walletsStmt = $db->query($walletsQuery);
$wallets = $walletsStmt->fetchAll();

// Get specific user transactions if viewing details
$transactions = [];
if ($action === 'transactions' && $userId) {
    $transQuery = "
        SELECT 
            we.id,
            we.transaction_type,
            we.amount,
            we.balance_after,
            we.description,
            we.created_at
        FROM buyer_wallet_entries we
        JOIN buyer_wallets w ON we.wallet_id = w.id
        JOIN buyers b ON w.buyer_id = b.id
        WHERE b.user_id = ?
        ORDER BY we.created_at DESC
        LIMIT 50
    ";
    $transStmt = $db->prepare($transQuery);
    $transStmt->execute([$userId]);
    $transactions = $transStmt->fetchAll();
    
    // Get user info for display
    $userStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet Management - Admin</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0; 
            background: #f5f5f5; 
            color: #333;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-links a {
            color: #ecf0f1;
            text-decoration: none;
            margin-left: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat .number {
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 0.5rem;
        }
        .stat .label {
            color: #7f8c8d;
            text-transform: uppercase;
            font-size: 0.875rem;
            font-weight: 500;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .btn:hover { background: #2980b9; }
        .btn-success { background: #27ae60; }
        .btn-success:hover { background: #229954; }
        .btn-warning { background: #f39c12; }
        .btn-warning:hover { background: #e67e22; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-secondary { background: #95a5a6; }
        .btn-secondary:hover { background: #7f8c8d; }
        
        .status {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status.active { background: #d5edda; color: #155724; }
        .status.suspended { background: #f8d7da; color: #721c24; }
        .status.pending { background: #fff3cd; color: #856404; }
        
        .balance {
            font-weight: bold;
            font-size: 1.1rem;
        }
        .balance.positive { color: #27ae60; }
        .balance.zero { color: #7f8c8d; }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .modal.active { display: block; }
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .message.success {
            background: #d5edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .breadcrumb {
            margin-bottom: 1rem;
        }
        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }
        .breadcrumb a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Wallet Management</h1>
        <div class="nav-links">
            <a href="/admin/">Dashboard</a>
            <a href="/admin/users/">Users</a>
            <a href="?">Wallets</a>
            <a href="/logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <div class="stats">
                <div class="stat">
                    <div class="number"><?= count($wallets) ?></div>
                    <div class="label">Total Users</div>
                </div>
                <div class="stat">
                    <div class="number"><?= count(array_filter($wallets, fn($w) => $w['wallet_id'] !== null)) ?></div>
                    <div class="label">Active Wallets</div>
                </div>
                <div class="stat">
                    <div class="number">$<?= number_format(array_sum(array_map(fn($w) => $w['balance'] ?? 0, $wallets)), 2) ?></div>
                    <div class="label">Total Balance</div>
                </div>
                <div class="stat">
                    <div class="number"><?= count(array_filter($wallets, fn($w) => ($w['wallet_status'] ?? '') === 'suspended')) ?></div>
                    <div class="label">Suspended Wallets</div>
                </div>
            </div>

            <div class="card">
                <h2>User Wallets Overview</h2>
                <p>Manage user wallets, view balances, and perform administrative actions.</p>
                
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Wallet Balance</th>
                            <th>Wallet Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($wallets as $wallet): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($wallet['first_name'] . ' ' . $wallet['last_name']) ?></strong><br>
                                    <small>@<?= htmlspecialchars($wallet['username']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($wallet['email']) ?></td>
                                <td><span class="status <?= strtolower($wallet['role']) ?>"><?= ucfirst($wallet['role']) ?></span></td>
                                <td>
                                    <?php if ($wallet['wallet_id']): ?>
                                        <span class="balance <?= $wallet['balance'] > 0 ? 'positive' : 'zero' ?>">
                                            $<?= number_format($wallet['balance'], 2) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="balance zero">No Wallet</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($wallet['wallet_id']): ?>
                                        <span class="status <?= $wallet['wallet_status'] ?? 'active' ?>"><?= ucfirst($wallet['wallet_status'] ?? 'active') ?></span>
                                    <?php else: ?>
                                        <span class="status pending">Not Created</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-success" onclick="showAddFunds(<?= $wallet['user_id'] ?>, '<?= htmlspecialchars($wallet['first_name'] . ' ' . $wallet['last_name']) ?>')">Add Funds</button>
                                    
                                    <?php if ($wallet['wallet_id']): ?>
                                        <a href="?action=transactions&user_id=<?= $wallet['user_id'] ?>" class="btn btn-secondary">View Transactions</a>
                                        
                                        <?php if ($wallet['wallet_status'] === 'active'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="suspend_wallet">
                                                <input type="hidden" name="wallet_id" value="<?= $wallet['wallet_id'] ?>">
                                                <button type="submit" class="btn btn-warning" onclick="return confirm('Suspend this wallet?')">Suspend</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="activate_wallet">
                                                <input type="hidden" name="wallet_id" value="<?= $wallet['wallet_id'] ?>">
                                                <button type="submit" class="btn btn-success" onclick="return confirm('Activate this wallet?')">Activate</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($action === 'transactions' && $userId): ?>
            <div class="breadcrumb">
                <a href="?">← Back to Wallet Overview</a>
            </div>
            
            <div class="card">
                <h2>Transaction History: <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h2>
                <p>Email: <?= htmlspecialchars($user['email']) ?> | User ID: <?= $userId ?></p>
                
                <?php if (empty($transactions)): ?>
                    <p>No transactions found for this user.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Balance After</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $trans): ?>
                                <tr>
                                    <td><?= date('M j, Y g:i A', strtotime($trans['created_at'])) ?></td>
                                    <td><span class="status <?= $trans['transaction_type'] ?>"><?= ucfirst($trans['transaction_type']) ?></span></td>
                                    <td class="balance <?= $trans['amount'] > 0 ? 'positive' : 'zero' ?>">
                                        <?= ($trans['amount'] > 0 ? '+' : '') ?>$<?= number_format($trans['amount'], 2) ?>
                                    </td>
                                    <td>$<?= number_format($trans['balance_after'], 2) ?></td>
                                    <td><?= htmlspecialchars($trans['description']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Funds Modal -->
    <div id="addFundsModal" class="modal">
        <div class="modal-content">
            <h3>Add Funds to Wallet</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_funds">
                <input type="hidden" name="user_id" id="modalUserId">
                
                <div class="form-group">
                    <label>User:</label>
                    <div id="modalUserName" style="font-weight: bold; padding: 0.75rem; background: #f8f9fa; border-radius: 4px;"></div>
                </div>
                
                <div class="form-group">
                    <label>Amount ($):</label>
                    <input type="number" name="amount" step="0.01" min="0.01" max="10000" required>
                </div>
                
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" rows="3" placeholder="Admin credit, bonus, refund, etc.">Admin credit</textarea>
                </div>
                
                <div style="text-align: right; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Funds</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddFunds(userId, userName) {
            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalUserName').textContent = userName;
            document.getElementById('addFundsModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('addFundsModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        document.getElementById('addFundsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>