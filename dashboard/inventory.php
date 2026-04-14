<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'warehouse') {
    header("Location: /Bathala/LandingPage/login.php");
    exit;
}
$user = $_SESSION['user'];

$conn = new mysqli("localhost", "root", "", "smartpos");
if ($conn->connect_error) die("Connection failed");

// Fetch all products
$products_result = $conn->query("SELECT * FROM products ORDER BY category ASC, name ASC");
$products = [];
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}

// Stats
$total_products = count($products);
$low_stock      = array_filter($products, fn($p) => $p['stock'] <= 10 && $p['stock'] > 0);
$out_of_stock   = array_filter($products, fn($p) => $p['stock'] == 0);
$total_stock    = array_sum(array_column($products, 'stock'));

// Low stock threshold
$low_stock_threshold = 10;

// Stock history
$history_result = $conn->query("
    SELECT sh.*, p.name as product_name
    FROM stock_history sh
    JOIN products p ON sh.product_id = p.id
    ORDER BY sh.created_at DESC
    LIMIT 20
");
$history = [];
while ($row = $history_result->fetch_assoc()) {
    $history[] = $row;
}

$success = $_SESSION['success'] ?? '';
$error   = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory - DeiTrack</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="/Bathala/assets/css/inventory.css">
</head>
<body>

<div class="inv-layout">

    <!-- ═══ SIDEBAR ═══ -->
    <div class="inv-sidebar">
        <div class="inv-sidebar-brand">
            <img src="/Bathala/src/logo.png" alt="Logo">
            <div>
                <div class="inv-brand-name">DeiTrack</div>
                <div class="inv-brand-sub">Bathala Tissue</div>
            </div>
        </div>

        <div class="inv-sidebar-user">
            <div class="inv-user-avatar">
                <?php echo strtoupper(substr($user['name'] ?? $user['email'], 0, 1)); ?>
            </div>
            <div>
                <div class="inv-user-name"><?php echo htmlspecialchars($user['name'] ?? $user['email']); ?></div>
                <div class="inv-user-role">Warehouse Staff</div>
            </div>
        </div>

        <nav class="inv-nav">
            <div class="inv-nav-label">Menu</div>
            <a href="#" class="inv-nav-item active" onclick="showSection('products')">
                <i class="bi bi-box-seam"></i> Products
            </a>
            <a href="#" class="inv-nav-item" onclick="showSection('history')">
                <i class="bi bi-clock-history"></i> Stock History
            </a>
            <?php if (count($low_stock) > 0): ?>
            <a href="#" class="inv-nav-item" onclick="showSection('alerts')">
                <i class="bi bi-bell"></i> Low Stock Alerts
                <span class="inv-nav-badge"><?php echo count($low_stock); ?></span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="inv-sidebar-footer">
            <a href="/Bathala/backend/logout.php" class="inv-logout">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>

    <!-- ═══ MAIN ═══ -->
    <div class="inv-main">

        <!-- TOP BAR -->
        <div class="inv-topbar">
            <div>
                <div class="inv-page-title">📦 Inventory Management</div>
                <div class="inv-page-sub">Manage your products and stock levels</div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <div class="inv-search">
                    <i class="bi bi-search"></i>
                    <input type="text" id="invSearch" placeholder="Search products...">
                </div>
                <button class="inv-export-btn" onclick="exportToExcel()">
                    <i class="bi bi-file-earmark-excel"></i> Export
                </button>
                <button class="inv-add-btn" onclick="openAddModal()">
                    <i class="bi bi-plus-lg"></i> Add Product
                </button>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="inv-content">

            <?php if ($success): ?>
            <div class="inv-alert success"><i class="bi bi-check-circle-fill"></i> <?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="inv-alert error"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- LOW STOCK NOTIFICATION BAR -->
            <?php if (count($low_stock) > 0 || count($out_of_stock) > 0): ?>
            <div class="inv-notification-bar">
                <i class="bi bi-bell-fill"></i>
                <span>
                    <?php if (count($out_of_stock) > 0): ?>
                        <strong><?php echo count($out_of_stock); ?> product(s) out of stock</strong> —
                    <?php endif; ?>
                    <?php if (count($low_stock) > 0): ?>
                        <strong><?php echo count($low_stock); ?> product(s) running low</strong>
                    <?php endif; ?>
                </span>
                <button onclick="showSection('alerts')" class="inv-notif-btn">View Alerts</button>
            </div>
            <?php endif; ?>

            <!-- STAT CARDS -->
            <div class="inv-stats">
                <div class="inv-stat-card">
                    <div class="inv-stat-icon" style="background:linear-gradient(135deg,#00a8c6,#0064ff);">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <div class="inv-stat-value"><?php echo $total_products; ?></div>
                        <div class="inv-stat-label">Total Products</div>
                    </div>
                </div>
                <div class="inv-stat-card">
                    <div class="inv-stat-icon" style="background:linear-gradient(135deg,#22c55e,#16a34a);">
                        <i class="bi bi-stack"></i>
                    </div>
                    <div>
                        <div class="inv-stat-value"><?php echo number_format($total_stock); ?></div>
                        <div class="inv-stat-label">Total Stock</div>
                    </div>
                </div>
                <div class="inv-stat-card">
                    <div class="inv-stat-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="inv-stat-value"><?php echo count($low_stock); ?></div>
                        <div class="inv-stat-label">Low Stock</div>
                    </div>
                </div>
                <div class="inv-stat-card">
                    <div class="inv-stat-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div>
                        <div class="inv-stat-value"><?php echo count($out_of_stock); ?></div>
                        <div class="inv-stat-label">Out of Stock</div>
                    </div>
                </div>
            </div>

            <!-- ═══ PRODUCTS SECTION ═══ -->
            <div id="section-products">
                <div class="inv-table-wrap">
                    <div class="inv-table-header">
                        <div style="padding:14px 24px;border-bottom:1px solid var(--border);display:flex;gap:8px;flex-wrap:wrap;align-items:center;background:#fafbfc;">
                        <span style="font-size:0.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;margin-right:4px;">Filter:</span>
                        <button class="inv-cat-filter active" onclick="filterByCategory('all', this)">
                            <i class="bi bi-grid-fill" style="font-size:0.7rem;"></i> All
                        </button>
                        <button class="inv-cat-filter" onclick="filterByCategory('Bathroom Tissue', this)">Bathroom Tissue</button>
                        <button class="inv-cat-filter" onclick="filterByCategory('Facial Tissue', this)">Facial Tissue</button>
                        <button class="inv-cat-filter" onclick="filterByCategory('Interleaved Tissue', this)">Interleaved Tissue</button>
                        <button class="inv-cat-filter" onclick="filterByCategory('Hand Roll Tissue', this)">Hand Roll Tissue</button>
                        <button class="inv-cat-filter" onclick="filterByCategory('Jumbo Roll Tissue', this)">Jumbo Roll Tissue</button>
                        <button class="inv-cat-filter" onclick="filterByCategory('Kitchen Towel', this)">Kitchen Towel</button>
                        <button class="inv-cat-filter" onclick="filterByCategory('Interfolded Paper Towel', this)">Interfolded Paper Towel</button>
                        <button class="inv-cat-filter" onclick="filterByCategory('Quatro Folded', this)">Quatro Folded</button>
                        <button class="inv-cat-filter" onclick="filterByCategory('Pre-Cut', this)">Pre-Cut</button>
                    </div>
                        <div style="font-size:0.82rem;color:#94a3b8;"><?php echo $total_products; ?> products total</div>
                    </div>
                    <table class="inv-table" id="invTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                            <tr data-name="<?php echo strtolower(htmlspecialchars($p['name'])); ?>">
                                <td>
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <div class="inv-product-img">
                                            <?php if (!empty($p['image'])): ?>
                                                <img src="/Bathala/assets/images/products/<?php echo htmlspecialchars($p['image']); ?>" alt="">
                                            <?php else: ?>
                                                <i class="bi bi-image"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="inv-product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                            <div class="inv-product-desc"><?php echo htmlspecialchars(substr($p['description'] ?? '', 0, 50)) . (strlen($p['description'] ?? '') > 50 ? '...' : ''); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="inv-category-badge"><?php echo htmlspecialchars($p['category'] ?? '-'); ?></span></td>
                                <td><strong style="font-family:'Syne',sans-serif;color:#0d1b2a;">₱<?php echo number_format($p['price'], 2); ?></strong></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <span style="font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;"><?php echo $p['stock']; ?></span>
                                        <?php if ($p['stock'] <= 10 && $p['stock'] > 0): ?>
                                            <i class="bi bi-exclamation-triangle-fill" style="color:#f59e0b;" title="Low stock"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($p['stock'] == 0): ?>
                                        <span class="inv-status out">Out of Stock</span>
                                    <?php elseif ($p['stock'] <= 10): ?>
                                        <span class="inv-status low">Low Stock</span>
                                    <?php else: ?>
                                        <span class="inv-status ok">In Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;">
                                        <button class="inv-action-btn restock" onclick="openRestockModal(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['name']); ?>', <?php echo $p['stock']; ?>)" title="Restock">
                                            <i class="bi bi-plus-circle"></i>
                                        </button>
                                        <button class="inv-action-btn edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($p)); ?>)" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="inv-action-btn delete" onclick="confirmDelete(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['name']); ?>')" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ═══ STOCK HISTORY SECTION ═══ -->
            <div id="section-history" style="display:none;">
                <div class="inv-table-wrap">
                    <div class="inv-table-header">
                        <div class="inv-table-title">📊 Stock History</div>
                        <div style="font-size:0.82rem;color:#94a3b8;">Last 20 transactions</div>
                    </div>
                    <?php if (!empty($history)): ?>
                    <table class="inv-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Action</th>
                                <th>Quantity</th>
                                <th>Previous Stock</th>
                                <th>New Stock</th>
                                <th>Notes</th>
                                <th>By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($h['product_name']); ?></strong></td>
                                <td>
                                    <?php if ($h['action'] === 'restock'): ?>
                                        <span class="inv-status ok">Restock</span>
                                    <?php elseif ($h['action'] === 'sold'): ?>
                                        <span class="inv-status low">Sold</span>
                                    <?php else: ?>
                                        <span class="inv-status out">Adjusted</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-family:'Syne',sans-serif;font-weight:700;color:<?php echo $h['action'] === 'restock' ? '#22c55e' : '#ef4444'; ?>;">
                                        <?php echo $h['action'] === 'restock' ? '+' : '-'; ?><?php echo $h['quantity']; ?>
                                    </span>
                                </td>
                                <td><?php echo $h['previous_stock']; ?></td>
                                <td><strong><?php echo $h['new_stock']; ?></strong></td>
                                <td style="color:#94a3b8;font-size:0.82rem;"><?php echo htmlspecialchars($h['notes'] ?? '-'); ?></td>
                                <td style="font-size:0.82rem;"><?php echo htmlspecialchars($h['created_by'] ?? '-'); ?></td>
                                <td style="font-size:0.82rem;color:#94a3b8;"><?php echo date('M d, Y h:i A', strtotime($h['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align:center;padding:40px;color:#94a3b8;">
                        <i class="bi bi-clock-history" style="font-size:2.5rem;display:block;margin-bottom:12px;"></i>
                        <p>No stock history yet.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ═══ LOW STOCK ALERTS SECTION ═══ -->
            <div id="section-alerts" style="display:none;">
                <div class="inv-table-wrap">
                    <div class="inv-table-header">
                        <div class="inv-table-title">🔔 Low Stock Alerts</div>
                        <div style="font-size:0.82rem;color:#94a3b8;">Products needing attention</div>
                    </div>
                    <?php
                    $alert_products = array_merge(
                        array_values(array_filter($products, fn($p) => $p['stock'] == 0)),
                        array_values(array_filter($products, fn($p) => $p['stock'] > 0 && $p['stock'] <= 10))
                    );
                    ?>
                    <?php if (!empty($alert_products)): ?>
                    <table class="inv-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alert_products as $p): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <div class="inv-product-img">
                                            <?php if (!empty($p['image'])): ?>
                                                <img src="/Bathala/assets/images/products/<?php echo htmlspecialchars($p['image']); ?>" alt="">
                                            <?php else: ?>
                                                <i class="bi bi-image"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="inv-product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                    </div>
                                </td>
                                <td><span class="inv-category-badge"><?php echo htmlspecialchars($p['category']); ?></span></td>
                                <td>
                                    <span style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.1rem;color:<?php echo $p['stock'] == 0 ? '#ef4444' : '#f59e0b'; ?>;">
                                        <?php echo $p['stock']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($p['stock'] == 0): ?>
                                        <span class="inv-status out">Out of Stock</span>
                                    <?php else: ?>
                                        <span class="inv-status low">Low Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="inv-action-btn restock" onclick="openRestockModal(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['name']); ?>', <?php echo $p['stock']; ?>)">
                                        <i class="bi bi-plus-circle"></i> Restock
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="text-align:center;padding:40px;color:#22c55e;">
                        <i class="bi bi-check-circle-fill" style="font-size:2.5rem;display:block;margin-bottom:12px;"></i>
                        <p>All products are well stocked!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ═══ ADD PRODUCT MODAL ═══ -->
<div class="inv-modal-overlay" id="addModal">
    <div class="inv-modal">
        <div class="inv-modal-header">
            <div class="inv-modal-title">➕ Add New Product</div>
            <button onclick="closeInvModal('addModal')" class="inv-modal-close"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="/Bathala/backend/add_product.php" method="POST" enctype="multipart/form-data">
            <div class="inv-form-row">
                <div class="inv-form-field">
                    <label>Product Name *</label>
                    <input type="text" name="name" class="inv-input" placeholder="Product name" required>
                </div>
                <div class="inv-form-field">
                    <label>Category *</label>
                    <select name="category" class="inv-input" required>
                        <option value="">Select category</option>
                        <option>Bathroom Tissue</option>
                        <option>Interleaved Tissue</option>
                        <option>Facial Tissue</option>
                        <option>Hand Roll Tissue</option>
                        <option>Jumbo Roll Tissue</option>
                        <option>Kitchen Towel</option>
                        <option>Interfolded Paper Towel</option>
                        <option>Quatro Folded</option>
                        <option>Pre-Cut</option>
                    </select>
                </div>
            </div>
            <div class="inv-form-row">
                <div class="inv-form-field">
                    <label>Price (₱) *</label>
                    <input type="number" name="price" class="inv-input" placeholder="0.00" step="0.01" required>
                </div>
                <div class="inv-form-field">
                    <label>Initial Stock *</label>
                    <input type="number" name="stock" class="inv-input" placeholder="0" required>
                </div>
            </div>
            <div class="inv-form-field">
                <label>Description</label>
                <textarea name="description" class="inv-input inv-textarea" placeholder="Product description..."></textarea>
            </div>
            <div class="inv-form-field">
                <label>Product Photo</label>
                <div class="inv-upload-area" id="addUploadArea" onclick="document.getElementById('addPhoto').click()">
                    <div id="addImgPreview" style="display:none;margin-bottom:8px;">
                        <img id="addImgPreviewImg" style="max-height:120px;border-radius:8px;object-fit:contain;">
                    </div>
                    <i class="bi bi-cloud-upload" style="font-size:2rem;color:#94a3b8;display:block;margin-bottom:8px;" id="addUploadIcon"></i>
                    <div style="font-size:0.85rem;color:#94a3b8;">Click to upload image</div>
                    <div id="addPhotoName" style="font-size:0.82rem;color:#00a8c6;margin-top:8px;"></div>
                </div>
                <input type="file" id="addPhoto" name="image" accept="image/*" style="display:none;" onchange="previewImage('addPhoto','addImgPreviewImg','addImgPreview','addUploadIcon','addPhotoName')">
            </div>
            <div class="inv-modal-btns">
                <button type="button" onclick="closeInvModal('addModal')" class="inv-btn-cancel">Cancel</button>
                <button type="submit" class="inv-btn-confirm"><i class="bi bi-plus-lg"></i> Add Product</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ EDIT PRODUCT MODAL ═══ -->
<div class="inv-modal-overlay" id="editModal">
    <div class="inv-modal">
        <div class="inv-modal-header">
            <div class="inv-modal-title">✏️ Edit Product</div>
            <button onclick="closeInvModal('editModal')" class="inv-modal-close"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="/Bathala/backend/edit_product.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="editId">
            <div class="inv-form-row">
                <div class="inv-form-field">
                    <label>Product Name *</label>
                    <input type="text" name="name" id="editName" class="inv-input" required>
                </div>
                <div class="inv-form-field">
                    <label>Category *</label>
                    <select name="category" id="editCategory" class="inv-input" required>
                        <option>Bathroom Tissue</option>
                        <option>Interleaved Tissue</option>
                        <option>Facial Tissue</option>
                        <option>Hand Roll Tissue</option>
                        <option>Jumbo Roll Tissue</option>
                        <option>Kitchen Towel</option>
                        <option>Interfolded Paper Towel</option>
                        <option>Quatro Folded</option>
                        <option>Pre-Cut</option>
                    </select>
                </div>
            </div>
            <div class="inv-form-row">
                <div class="inv-form-field">
                    <label>Price (₱) *</label>
                    <input type="number" name="price" id="editPrice" class="inv-input" step="0.01" required>
                </div>
                <div class="inv-form-field">
                    <label>Stock *</label>
                    <input type="number" name="stock" id="editStock" class="inv-input" required>
                </div>
            </div>
            <div class="inv-form-field">
                <label>Description</label>
                <textarea name="description" id="editDescription" class="inv-input inv-textarea"></textarea>
            </div>
            <div class="inv-form-field">
                <label>Product Photo</label>
                <div id="editCurrentImg" style="margin-bottom:10px;"></div>
                <div class="inv-upload-area" onclick="document.getElementById('editPhoto').click()">
                    <div id="editImgPreview" style="display:none;margin-bottom:8px;">
                        <img id="editImgPreviewImg" style="max-height:120px;border-radius:8px;object-fit:contain;">
                    </div>
                    <i class="bi bi-cloud-upload" style="font-size:2rem;color:#94a3b8;display:block;margin-bottom:8px;" id="editUploadIcon"></i>
                    <div style="font-size:0.85rem;color:#94a3b8;">Click to change photo</div>
                    <div id="editPhotoName" style="font-size:0.82rem;color:#00a8c6;margin-top:8px;"></div>
                </div>
                <input type="file" id="editPhoto" name="image" accept="image/*" style="display:none;" onchange="previewImage('editPhoto','editImgPreviewImg','editImgPreview','editUploadIcon','editPhotoName')">
            </div>
            <div class="inv-modal-btns">
                <button type="button" onclick="closeInvModal('editModal')" class="inv-btn-cancel">Cancel</button>
                <button type="submit" class="inv-btn-confirm"><i class="bi bi-check-lg"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ RESTOCK MODAL ═══ -->
<div class="inv-modal-overlay" id="restockModal">
    <div class="inv-modal" style="max-width:420px;">
        <div class="inv-modal-header">
            <div class="inv-modal-title">📦 Restock Product</div>
            <button onclick="closeInvModal('restockModal')" class="inv-modal-close"><i class="bi bi-x-lg"></i></button>
        </div>
        <form action="/Bathala/backend/restock_product.php" method="POST">
            <input type="hidden" name="id" id="restockId">

            <div style="background:#f0f4f8;border-radius:12px;padding:14px;margin-bottom:20px;">
                <div style="font-size:0.75rem;color:#94a3b8;font-weight:600;text-transform:uppercase;margin-bottom:4px;">Product</div>
                <div id="restockName" style="font-weight:700;color:#0d1b2a;font-size:0.95rem;"></div>
                <div style="font-size:0.82rem;color:#94a3b8;margin-top:4px;">Current stock: <strong id="restockCurrent" style="color:#0d1b2a;"></strong></div>
            </div>

            <div class="inv-form-field">
                <label>Quantity to Add *</label>
                <input type="number" name="quantity" id="restockQty" class="inv-input" placeholder="e.g. 50" min="1" required style="font-size:1.2rem;font-weight:700;text-align:center;">
            </div>

            <div class="inv-form-field">
                <label>Notes (optional)</label>
                <input type="text" name="notes" class="inv-input" placeholder="e.g. Monthly restock from supplier">
            </div>

            <div class="inv-modal-btns">
                <button type="button" onclick="closeInvModal('restockModal')" class="inv-btn-cancel">Cancel</button>
                <button type="submit" class="inv-btn-confirm"><i class="bi bi-plus-circle"></i> Restock</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ DELETE MODAL ═══ -->
<div class="inv-modal-overlay" id="deleteModal">
    <div class="inv-modal" style="max-width:400px;">
        <div class="inv-modal-header">
            <div class="inv-modal-title">🗑️ Delete Product</div>
            <button onclick="closeInvModal('deleteModal')" class="inv-modal-close"><i class="bi bi-x-lg"></i></button>
        </div>
        <p style="font-size:0.9rem;color:#64748b;margin-bottom:8px;">Are you sure you want to delete:</p>
        <div id="deleteProductName" style="font-weight:700;font-size:1rem;color:#0d1b2a;margin-bottom:24px;padding:12px;background:#f0f4f8;border-radius:10px;"></div>
        <form action="/Bathala/backend/delete_product.php" method="POST">
            <input type="hidden" name="id" id="deleteId">
            <div class="inv-modal-btns">
                <button type="button" onclick="closeInvModal('deleteModal')" class="inv-btn-cancel">Cancel</button>
                <button type="submit" class="inv-btn-delete"><i class="bi bi-trash"></i> Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- TOAST -->
<div class="inv-toast" id="invToast">
    <i class="bi bi-check-circle-fill"></i>
    <span id="invToastMsg"></span>
</div>

<!-- Export data for JS -->
<script>
const exportData = <?php
    $export = array_map(fn($p) => [
        'Name'        => $p['name'],
        'Category'    => $p['category'],
        'Price'       => $p['price'],
        'Stock'       => $p['stock'],
        'Status'      => $p['stock'] == 0 ? 'Out of Stock' : ($p['stock'] <= 10 ? 'Low Stock' : 'In Stock'),
        'Description' => $p['description'] ?? ''
    ], $products);
    echo json_encode($export);
?>;
</script>

<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script src="/Bathala/assets/js/inventory.js"></script>
</body>
</html>
