<?php
// SECURE THIS PAGE - Only authenticated users can access
require_once 'auth_check.php';

// If $conn isn't set by auth_check.php, load db.php directly
if (!isset($conn) || $conn === null) {
    require_once 'db.php';
}

// ── Fetch products + variants for Quotation Calculator ───────────────────────
$_ac_sql = "SELECT
    p.product_id, p.product_name,
    pv.variant_id, pv.sku,
    c.color_name, s.size_name, s.sell_unit, s.pieces_per_box,
    MIN(CASE WHEN pr.price_type = 'retail'    THEN pr.price END) AS retail_price,
    MIN(CASE WHEN pr.price_type = 'wholesale' THEN pr.price END) AS wholesale_price,
    MIN(CASE WHEN pr.price_type = 'wholesale' THEN pr.min_quantity END) AS wholesale_min_qty
FROM product p
LEFT JOIN product_variant pv ON p.product_id = pv.product_id
LEFT JOIN color  c  ON pv.color_id  = c.color_id
LEFT JOIN size   s  ON pv.size_id   = s.size_id
LEFT JOIN price  pr ON pv.variant_id = pr.variant_id
WHERE s.size_name IS NOT NULL AND s.size_name <> ''
GROUP BY p.product_id, pv.variant_id
ORDER BY p.product_name ASC, pv.variant_id ASC";

$_ac_result   = $conn->query($_ac_sql);
$_ac_products = [];
if ($_ac_result) {
    while ($_ac_row = $_ac_result->fetch_assoc()) {
        $pid = $_ac_row['product_id'];
        if (!isset($_ac_products[$pid])) {
            $_ac_products[$pid] = [
                'product_id'   => (int)$pid,
                'product_name' => $_ac_row['product_name'],
                'variants'     => [],
            ];
        }
        if (!$_ac_row['variant_id']) continue;

        $label = '';
        if (!empty($_ac_row['color_name'])) $label .= $_ac_row['color_name'] . ' – ';
        $label .= $_ac_row['size_name'] ?? 'No size';
        if (!empty($_ac_row['sku']))        $label .= ' (' . $_ac_row['sku'] . ')';

        $_ac_products[$pid]['variants'][] = [
            'variant_id'        => (int)$_ac_row['variant_id'],
            'label'             => trim($label),
            'size_name'         => $_ac_row['size_name'] ?? '',
            'sell_unit'         => $_ac_row['sell_unit'] ?? 'piece',
            'pieces_per_box'    => $_ac_row['pieces_per_box'] ? (int)$_ac_row['pieces_per_box'] : null,
            'retail_price'      => $_ac_row['retail_price']      ? (float)$_ac_row['retail_price']      : null,
            'wholesale_price'   => $_ac_row['wholesale_price']   ? (float)$_ac_row['wholesale_price']   : null,
            'wholesale_min_qty' => $_ac_row['wholesale_min_qty'] ? (int)$_ac_row['wholesale_min_qty']   : null,
        ];
    }
}
$_ac_list = array_values($_ac_products);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Product Management</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <link rel="icon" type="image/png" href="assets/images/gw.png">
    <link rel="stylesheet" type="text/css" href="admin.css">
    <style>
        [id$="BulkBar"] { animation: slideDown 0.18s ease; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
        
        .copy-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            background: #fff;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .copy-card h3 { margin: 0 0 18px 0; color: #1f2937; font-size: 15px; }
        .copy-card .form-group { margin-bottom: 18px; }
        .copy-card label { font-weight: 600; color: #374151; display: block; margin-bottom: 6px; }
        .copy-card input[type="text"], .copy-card textarea, .copy-card select {
            width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;
        }
    </style>
</head>
<style>
@keyframes acFadeIn  { from { opacity:0 }          to { opacity:1 } }
@keyframes acSlideUp { from { opacity:0; transform:translateY(20px) } to { opacity:1; transform:translateY(0) } }
 
.ac-label {
    display:block;font-size:12px;font-weight:600;color:#374151;
    margin-bottom:6px;letter-spacing:.2px;
}
.ac-input {
    width:100%;padding:10px 12px;border:1.5px solid #d1d5db;border-radius:8px;
    font-size:13px;color:#1f2937;background:#fff;outline:none;
    transition:border-color .18s,box-shadow .18s;box-sizing:border-box;
    appearance:auto;
}
.ac-input:focus { border-color:#648E37; box-shadow:0 0 0 3px rgba(100,142,55,.15); }
.ac-input:disabled { background:#f9fafb; color:#9ca3af; cursor:not-allowed; }
 
.ac-result-row {
    display:flex;justify-content:space-between;align-items:center;
    padding:9px 14px;border-bottom:1px solid #f3f4f6;
    font-size:13px;gap:16px;
}
.ac-result-row:last-child { border-bottom:none; }
.ac-rlabel { color:#6b7280;font-weight:500;flex-shrink:0; }
.ac-rvalue { font-weight:700;color:#1f2937;text-align:right; }
.ac-rvalue.green  { color:#648E37;font-size:15px; }
.ac-rvalue.muted  { color:#9ca3af;font-size:12px;font-weight:500; }
 
.ac-sum-row {
    display:flex;justify-content:space-between;align-items:center;
    font-size:13px;margin-bottom:6px;
}
.ac-saved-item {
    display:flex;align-items:center;gap:10px;padding:12px 14px;
    background:#f8fdf4;border-radius:8px;border:1px solid #e5e7eb;margin-bottom:8px;
}
.ac-saved-item-info { flex:1;min-width:0; }
.ac-saved-item-name { font-size:13px;font-weight:600;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.ac-saved-item-meta { font-size:11px;color:#6b7280;margin-top:2px; }
.ac-saved-item-cost { font-size:14px;font-weight:700;color:#648E37;flex-shrink:0;white-space:nowrap; }
.ac-saved-remove {
    width:28px;height:28px;background:#fef2f2;border:none;border-radius:50%;
    display:flex;align-items:center;justify-content:center;cursor:pointer;
    color:#dc2626;font-size:14px;flex-shrink:0;transition:background .15s;
}
.ac-saved-remove:hover { background:#fee2e2; }
.ac-badge {
    display:inline-block;font-size:10px;font-weight:700;padding:2px 7px;
    border-radius:20px;vertical-align:middle;margin-left:5px;
}
.ac-badge-retail    { background:#dcfce7;color:#15803d; }
.ac-badge-wholesale { background:#f0fdf4;color:#648E37; }
 
@media (max-width:560px) {
    #adminCalcModal { border-radius:12px 12px 0 0; margin-bottom:0; max-height:96vh; }
    #adminCalcOverlay { align-items:flex-end; padding:0; }
    .ac-result-row { flex-direction:column;align-items:flex-start;gap:2px; }
}
</style>
<body>
    <div class="container">
        <div class="header">
          <div class="header-logo-text">
            <img src="../assets/images/nobg.png" alt="Greenwood Logo" />
            <div class="header-text">
              <h1>Admin Dashboard</h1>
              <p>Product Management System</p>
            </div>
            <div class="admin-info">
              <span>👤 <?php echo htmlspecialchars($adminUsername); ?></span>
              <button
                id="adminCalcTriggerBtn"
                onclick="openAdminCalc()"
                title="Open Quotation Calculator"
                style="
                    display:inline-flex;align-items:center;gap:7px;
                    background:#648E37;color:#fff;border:none;
                    padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;
                    cursor:pointer;transition:background .18s,transform .12s;
                    box-shadow:0 2px 8px rgba(100,142,55,.25);
                    white-space:nowrap;
                "
                onmouseover="this.style.background='#527530'"
                onmouseout="this.style.background='#648E37'"
                onmousedown="this.style.transform='scale(.97)'"
                onmouseup="this.style.transform='scale(1)'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="2" width="16" height="20" rx="2"/>
                    <line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="11" y2="11"/>
                    <line x1="8" y1="15" x2="11" y2="15"/><line x1="14" y1="11" x2="16" y2="13"/>
                    <line x1="16" y1="11" x2="14" y2="13"/><line x1="14" y1="15" x2="16" y2="17"/>
                    <line x1="16" y1="15" x2="14" y2="17"/>
                </svg>
                Quotation Calculator
            </button>
              <a href="?logout=true" class="btn btn-secondary btn-sm" 
                 onclick="return confirm('Are you sure you want to logout?')" 
                 style="text-decoration: none;">
                🔓 Logout
              </a>

            </div>
          </div>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('products')">Products</button>
            <button class="tab" onclick="showTab('variants')">Product Variants</button>
            <button class="tab" onclick="showTab('prices')">Prices</button>
            <button class="tab" onclick="showTab('categories')">Categories</button>
            <button class="tab" onclick="showTab('colors')">Colors</button>
            <button class="tab" onclick="showTab('sizes')">Sizes</button>
            <button class="tab" onclick="showTab('product-types')">Product Types</button>
            <button class="tab" onclick="showTab('projects')">Projects</button>
            <button class="tab" onclick="showTab('locations')">Warehouse Locations</button>
            <button class="tab" onclick="showTab('upcoming')">Upcoming Branches</button>
            <button class="tab" onclick="showTab('contacts')">Branch Contacts</button>
            <button class="tab" onclick="showTab('influencers')">Influencers</button>
            <button class="tab" onclick="showTab('announcements')">📢 Announcements</button>
        </div>

        <!-- Alert Messages -->
        <div id="alertBox"></div>

        <!-- Products Tab -->
        <div id="products" class="content active">
            <div class="action-bar">
                <div class="search-box">
                    <input type="text" id="productSearch" placeholder="Search products..." onkeyup="filterTable('products')">
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                    <button class="btn btn-success btn-sm" onclick="exportToExcel('productsTable', 'products')">📥 Export</button>
                    <button class="btn btn-primary" onclick="openProductModal()">+ Add New Product</button>
                </div>
            </div>

            <div class="filter-controls">
                <select id="productCategoryFilter" onchange="filterTable('products')">
                    <option value="">All Categories</option>
                </select>
                <select id="productTypeFilter" onchange="filterTable('products')">
                    <option value="">All Types</option>
                </select>
                <button class="btn btn-sm" onclick="clearFilters('products')">Clear Filters</button>
            </div>

            <div id="productsBulkBar" style="display:none;align-items:center;gap:10px;padding:10px 14px;background:#fffbe6;border:1.5px solid #f0c040;border-radius:8px;margin-bottom:12px;flex-wrap:wrap;">
    <span id="productsSelectedCount" style="font-weight:600;font-size:14px;color:#7a5c00;">0 selected</span>
    <button class="btn btn-sm" style="background:#648E37;color:#fff;" onclick="bulkCopy('products')">📋 Copy Selected</button>
    <button class="btn btn-sm btn-danger" onclick="bulkDelete('products','api_products.php','product_id',loadProducts)">🗑 Delete Selected</button>
    <button class="btn btn-sm btn-secondary" onclick="clearSelection('products')">✕ Deselect All</button>
</div>

            <table id="productsTable">
                <thead>
                    <tr>
                        <th class="center" style="width:36px;"><input type="checkbox" id="selectAll_products" onchange="toggleSelectAll('products','product_id',this)"></th>
                        <th class="center sortable" onclick="sortTable('products', 1, 'number')">ID</th>
                        <th class="center sortable" onclick="sortTable('products', 2, 'string')">Product Name</th>
                        <th class="center">Description</th>
                        <th class="center sortable" onclick="sortTable('products', 4, 'string')">Category</th>
                        <th class="center sortable" onclick="sortTable('products', 5, 'string')">Type</th>
                        <th class="center">Image</th>
                        <th class="center">Banner</th>
                        <th class="center sortable" onclick="sortTable('products', 8, 'date')">Created At</th>
                        <th class="center">Actions</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    <tr><td colspan="10" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Product Variants Tab -->
        <div id="variants" class="content">
            <div class="action-bar">
                <div class="search-box">
                    <input type="text" id="variantSearch" placeholder="Search variants..." onkeyup="filterTable('variants')">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-success btn-sm" onclick="exportToExcel('variantsTable', 'variants')">📥 Export</button>
                    <button class="btn btn-primary" onclick="openVariantModal()">+ Add Product Variant</button>
                </div>
            </div>

            <div class="filter-controls">
                <select id="variantProductFilter" onchange="filterTable('variants')">
                    <option value="">All Products</option>
                </select>
                <select id="variantColorFilter" onchange="filterTable('variants')">
                    <option value="">All Colors</option>
                </select>
                <select id="variantSizeFilter" onchange="filterTable('variants')">
                    <option value="">All Sizes</option>
                </select>
                <button class="btn btn-sm" onclick="clearFilters('variants')">Clear Filters</button>
            </div>

            <div id="variantsBulkBar" style="display:none;align-items:center;gap:10px;padding:10px 14px;background:#fffbe6;border:1.5px solid #f0c040;border-radius:8px;margin-bottom:12px;flex-wrap:wrap;">
    <span id="variantsSelectedCount" style="font-weight:600;font-size:14px;color:#7a5c00;">0 selected</span>
    <button class="btn btn-sm" style="background:#648E37;color:#fff;" onclick="bulkCopy('variants')">📋 Copy Selected</button>
    <button class="btn btn-sm btn-danger" onclick="bulkDelete('variants','api_product_variant.php','variant_id',loadVariants)">🗑 Delete Selected</button>
    <button class="btn btn-sm btn-secondary" onclick="clearSelection('variants')">✕ Deselect All</button>
</div>

            <table id="variantsTable">
                <thead>
                    <tr>
                        <th class="center" style="width:36px;"><input type="checkbox" id="selectAll_variants" onchange="toggleSelectAll('variants','variant_id',this)"></th>
                        <th class="center">ID</th>
                        <th class="center">Product</th>
                        <th class="center">Color</th>
                        <th class="center">Size</th>
                        <th class="center">Sell Unit</th>
                        <th class="center">Pcs/Box</th>
                        <th class="center">SKU</th>
                        <th class="center">Stock</th>
                        <th class="center">Retail Price</th>
                        <th class="center">Wholesale Price</th>
                        <th class="center">Wholesale Min Qty</th>
                        <th class="center">Image</th>
                        <th class="center">Actions</th>
                    </tr>
                </thead>
                <tbody id="variantsTableBody">
                    <tr><td colspan="14" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Prices Tab -->
        <div id="prices" class="content">
            <div class="action-bar">
                <div class="search-box">
                    <input type="text" id="priceSearch" placeholder="Search prices..." onkeyup="filterTable('prices')">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-success btn-sm" onclick="exportToExcel('pricesTable', 'prices')">📥 Export</button>
                    <button class="btn btn-primary" onclick="openPriceModal()">+ Add Price</button>
                </div>
            </div>

            <div class="filter-controls">
                <select id="priceTypeFilter" onchange="filterTable('prices')">
                    <option value="">All Price Types</option>
                    <option value="retail">Retail</option>
                    <option value="wholesale">Wholesale</option>
                </select>
                <button class="btn btn-sm" onclick="clearFilters('prices')">Clear Filters</button>
            </div>

            <div id="pricesBulkBar" style="display:none;align-items:center;gap:10px;padding:10px 14px;background:#fffbe6;border:1.5px solid #f0c040;border-radius:8px;margin-bottom:12px;flex-wrap:wrap;">
                <span id="pricesSelectedCount" style="font-weight:600;font-size:14px;color:#7a5c00;">0 selected</span>
                <button class="btn btn-sm btn-danger" onclick="bulkDelete('prices','api_prices.php','price_id',loadPrices)">🗑 Delete Selected</button>
                <button class="btn btn-sm btn-secondary" onclick="clearSelection('prices')">✕ Deselect All</button>
            </div>

            <table id="pricesTable">
                <thead>
                    <tr>
                        <th class="center" style="width:36px;"><input type="checkbox" id="selectAll_prices" onchange="toggleSelectAll('prices','price_id',this)"></th>
                        <th class="center">ID</th>
                        <th class="center">Product</th>
                        <th class="center">Color</th>
                        <th class="center">Size</th>
                        <th class="center">SKU</th>
                        <th class="center">Price Type</th>
                        <th class="center">Min Quantity</th>
                        <th class="center">Price</th>
                        <th class="center">Actions</th>
                    </tr>
                </thead>
                <tbody id="pricesTableBody">
                    <tr><td colspan="10" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Categories Tab -->
        <div id="categories" class="content">
            <div class="action-bar">
                <div class="search-box">
                    <input type="text" id="categorySearch" placeholder="Search categories..." onkeyup="filterTable('categories')">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-success btn-sm" onclick="exportToExcel('categoriesTable', 'categories')">📥 Export</button>
                    <button class="btn btn-primary" onclick="openCategoryModal()">+ Add New Category</button>
                </div>
            </div>

            <div id="categoriesBulkBar" style="display:none;align-items:center;gap:10px;padding:10px 14px;background:#fffbe6;border:1.5px solid #f0c040;border-radius:8px;margin-bottom:12px;flex-wrap:wrap;">
    <span id="categoriesSelectedCount" style="font-weight:600;font-size:14px;color:#7a5c00;">0 selected</span>
    <button class="btn btn-sm" style="background:#648E37;color:#fff;" onclick="bulkCopy('categories')">📋 Copy Selected</button>
    <button class="btn btn-sm btn-danger" onclick="bulkDelete('categories','api_categories.php','category_id',loadCategories,[loadProducts])">🗑 Delete Selected</button>
    <button class="btn btn-sm btn-secondary" onclick="clearSelection('categories')">✕ Deselect All</button>
</div>

            <table id="categoriesTable">
                <thead>
                    <tr>
                        <th class="center" style="width:36px;"><input type="checkbox" id="selectAll_categories" onchange="toggleSelectAll('categories','category_id',this)"></th>
                        <th class="center sortable" onclick="sortTable('categories', 1, 'number')">ID</th>
                        <th class="center sortable" onclick="sortTable('categories', 2, 'string')">Category Name</th>
                        <th class="center sortable" onclick="sortTable('categories', 3, 'string')">Slug</th>
                        <th class="center">Description</th>
                        <th class="center sortable" onclick="sortTable('categories', 5, 'date')">Created At</th>
                        <th class="center">Actions</th>
                    </tr>
                </thead>
                <tbody id="categoriesTableBody">
                    <tr><td colspan="7" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Colors Tab -->
        <div id="colors" class="content">
            <div class="action-bar">
                <div class="search-box">
                    <input type="text" id="colorSearch" placeholder="Search colors..." onkeyup="filterTable('colors')">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-success btn-sm" onclick="exportToExcel('colorsTable', 'colors')">📥 Export</button>
                    <button class="btn btn-primary" onclick="openColorModal()">+ Add New Color</button>
                </div>
            </div>

            <div id="colorsBulkBar" style="display:none;align-items:center;gap:10px;padding:10px 14px;background:#fffbe6;border:1.5px solid #f0c040;border-radius:8px;margin-bottom:12px;flex-wrap:wrap;">
                <span id="colorsSelectedCount" style="font-weight:600;font-size:14px;color:#7a5c00;">0 selected</span>
                <button class="btn btn-sm" style="background:#648E37;color:#fff;" onclick="bulkCopy('colors')">📋 Copy Selected</button>
                <button class="btn btn-sm btn-danger" onclick="bulkDelete('colors','api_colors.php','color_id',loadColors)">🗑 Delete Selected</button>
                <button class="btn btn-sm btn-secondary" onclick="clearSelection('colors')">✕ Deselect All</button>
            </div>

            <table id="colorsTable">
                <thead>
                    <tr>
                        <th class="center" style="width:36px;"><input type="checkbox" id="selectAll_colors" onchange="toggleSelectAll('colors','color_id',this)"></th>
                        <th class="center sortable" onclick="sortTable('colors', 1, 'number')">ID</th>
                        <th class="center sortable" onclick="sortTable('colors', 2, 'string')">Color Name</th>
                        <th class="center sortable" onclick="sortTable('colors', 3, 'string')">Hex Code</th>
                        <th class="center">Preview</th>
                        <th class="center">Actions</th>
                    </tr>
                </thead>
                <tbody id="colorsTableBody">
                    <tr><td colspan="6" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Sizes Tab -->
        <div id="sizes" class="content">
            <div class="action-bar">
                <div class="search-box">
                    <input type="text" id="sizeSearch" placeholder="Search sizes..." onkeyup="filterTable('sizes')">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-success btn-sm" onclick="exportToExcel('sizesTable', 'sizes')">📥 Export</button>
                    <button class="btn btn-primary" onclick="openSizeModal()">+ Add New Size</button>
                </div>
            </div>

            <div id="sizesBulkBar" style="display:none;align-items:center;gap:10px;padding:10px 14px;background:#fffbe6;border:1.5px solid #f0c040;border-radius:8px;margin-bottom:12px;flex-wrap:wrap;">
                <span id="sizesSelectedCount" style="font-weight:600;font-size:14px;color:#7a5c00;">0 selected</span>
                <button class="btn btn-sm" style="background:#648E37;color:#fff;" onclick="bulkCopy('sizes')">📋 Copy Selected</button>
                <button class="btn btn-sm btn-danger" onclick="bulkDelete('sizes','api_sizes.php','size_id',loadSizes)">🗑 Delete Selected</button>
                <button class="btn btn-sm btn-secondary" onclick="clearSelection('sizes')">✕ Deselect All</button>
            </div>

            <table id="sizesTable">
                <thead>
                    <tr>
                        <th class="center" style="width:36px;"><input type="checkbox" id="selectAll_sizes" onchange="toggleSelectAll('sizes','size_id',this)"></th>
                        <th class="center sortable" onclick="sortTable('sizes', 1, 'number')">ID</th>
                        <th class="center sortable" onclick="sortTable('sizes', 2, 'string')">Size Name</th>
                        <th class="center">Sell Unit</th>
                        <th class="center">Pcs/Box</th>
                        <th class="center">Actions</th>
                    </tr>
                </thead>
                <tbody id="sizesTableBody">
                    <tr><td colspan="6" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Product Types Tab -->
        <div id="product-types" class="content">
            <div class="action-bar">
                <div class="search-box">
                    <input type="text" id="typeSearch" placeholder="Search product types..." onkeyup="filterTable('product-types')">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-success btn-sm" onclick="exportToExcel('productTypesTable', 'product_types')">📥 Export</button>
                    <button class="btn btn-primary" onclick="openProductTypeModal()">+ Add Product Type</button>
                </div>
            </div>

            <div id="typesBulkBar" style="display:none;align-items:center;gap:10px;padding:10px 14px;background:#fffbe6;border:1.5px solid #f0c040;border-radius:8px;margin-bottom:12px;flex-wrap:wrap;">
                <span id="typesSelectedCount" style="font-weight:600;font-size:14px;color:#7a5c00;">0 selected</span>
                <button class="btn btn-sm" style="background:#648E37;color:#fff;" onclick="bulkCopy('types')">📋 Copy Selected</button>
                <button class="btn btn-sm btn-danger" onclick="bulkDelete('types','api_product_types.php','product_type_id',loadProductTypes)">🗑 Delete Selected</button>
                <button class="btn btn-sm btn-secondary" onclick="clearSelection('types')">✕ Deselect All</button>
            </div>

            <table id="productTypesTable">
                <thead>
                    <tr>
                        <th class="center" style="width:36px;"><input type="checkbox" id="selectAll_types" onchange="toggleSelectAll('types','product_type_id',this)"></th>
                        <th class="center sortable" onclick="sortTable('product-types', 1, 'number')">ID</th>
                        <th class="center sortable" onclick="sortTable('product-types', 2, 'string')">Type Name</th>
                        <th class="center">Actions</th>
                    </tr>
                </thead>
                <tbody id="productTypesTableBody">
                    <tr><td colspan="4" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>

    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="productModalTitle">Add Product</h2>
                <span class="close" onclick="closeProductModal()">&times;</span>
            </div>
            <form id="productForm" onsubmit="saveProduct(event)">
                <input type="hidden" id="productId">
                <div class="form-group">
                    <label for="productName">Product Name *</label>
                    <input type="text" id="productName" required>
                </div>
                <div class="form-group">
                    <label for="productDescription">Description</label>
                    <textarea id="productDescription" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="productCategory">Category</label>
                    <select id="productCategory">
                        <option value="">None</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="productType">Product Type</label>
                    <select id="productType">
                        <option value="">None</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="productImage">Product Image</label>
                    <input type="file" id="productImage" accept="image/*">
                    <input type="hidden" id="productImagePath">
                    <small>Upload a main image for this product</small>
                </div>
                <div class="form-group">
                    <label for="productBannerImage">Banner Image <span style="color:#999;font-weight:400;">(shown as background on product page)</span></label>
                    <input type="file" id="productBannerImage" accept="image/*">
                    <input type="hidden" id="productBannerImagePath">
                    <div id="productBannerPreviewWrap" style="display:none;margin-top:8px;">
                        <img id="productBannerPreviewImg" src="" style="max-height:100px;border-radius:6px;object-fit:cover;width:100%;border:1px solid #e0e0e0;">
                        <button type="button" onclick="clearBannerImage()" style="margin-top:6px;font-size:12px;color:#c0392b;background:none;border:none;cursor:pointer;padding:0;">✕ Remove banner</button>
                    </div>
                    <small>Recommended: wide landscape image (min 1200×400px). Shown blurred behind product title.</small>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeProductModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="productSubmitBtn">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== BULK COPY MODAL ===================== -->
    <div id="bulkCopyModal" class="modal">
        <div class="modal-content modal-lg" style="max-width:760px;max-height:92vh;display:flex;flex-direction:column;">
            <div class="modal-header" style="flex-shrink:0;">
                <div>
                    <h2 id="bulkCopyTitle">Copy Items</h2>
                    <p style="font-size:13px;color:#777;margin:4px 0 0;">Review and edit before saving</p>
                </div>
                <span class="close" onclick="closeBulkCopyModal()">&times;</span>
            </div>
            <div id="bulkCopyList" style="overflow-y:auto;flex:1;padding:20px 24px;display:flex;flex-direction:column;gap:20px;"></div>
            <div class="modal-actions" style="flex-shrink:0;border-top:1px solid #e8e8e8;padding:16px 24px;display:flex;justify-content:flex-end;gap:10px;background:#fafbf7;">
                <button class="btn btn-secondary" onclick="closeBulkCopyModal()">Cancel</button>
                <button class="btn btn-primary" id="bulkCopySaveBtn" onclick="saveBulkCopies()">💾 Save All Copies</button>
            </div>
        </div>
    </div>
    <!-- =========================================================== -->

    <!-- PROJECTS TAB -->
    <div id="projects" class="content">
        <div class="action-bar">
            <div class="search-box">
                <input type="text" id="projectSearch" placeholder="Search projects..." onkeyup="filterTable('projects')">
            </div>
            <div style="display: flex; gap: 10px;">
                <button class="btn btn-success btn-sm" onclick="exportToExcel('projectsTable', 'finished_projects')">📥 Export</button>
                <button class="btn btn-primary" onclick="openProjectModal()">+ Add New Project</button>
            </div>
        </div>

        <div class="filter-controls">
            <select id="projectCategoryFilter" onchange="filterTable('projects')">
                <option value="">All Categories</option>
                <option value="residential">Residential</option>
                <option value="commercial">Commercial</option>
                <option value="outdoor">Outdoor</option>
            </select>
            <select id="projectYearFilter" onchange="filterTable('projects')">
                <option value="">All Years</option>
            </select>
            <select id="projectFeaturedFilter" onchange="filterTable('projects')">
                <option value="">All Projects</option>
                <option value="1">Featured Only</option>
                <option value="0">Non-Featured</option>
            </select>
            <button class="btn btn-sm" onclick="clearFilters('projects')">Clear Filters</button>
        </div>


        <div class="table-wrapper">
            <div id="projectsBulkBar" style="display:none;align-items:center;gap:10px;padding:10px 14px;background:#fffbe6;border:1.5px solid #f0c040;border-radius:8px;margin-bottom:12px;flex-wrap:wrap;">
                <span id="projectsSelectedCount" style="font-weight:600;font-size:14px;color:#7a5c00;">0 selected</span>
                <button class="btn btn-sm" style="background:#648E37;color:#fff;" onclick="bulkCopy('projects')">📋 Copy Selected</button>
                <button class="btn btn-sm btn-danger" onclick="bulkDelete('projects','api_project_images.php','id',loadProjects)">🗑 Delete Selected</button>
                <button class="btn btn-sm btn-secondary" onclick="clearSelection('projects')">✕ Deselect All</button>
            </div>
            <table id="projectsTable">
                <thead>
                    <tr>
                        <th class="center" style="width:36px;"><input type="checkbox" id="selectAll_projects" onchange="toggleSelectAll('projects','id',this)"></th>
                        <th class="center sortable" onclick="sortTable('projects', 1, 'number')" data-column="1">ID</th>
                        <th class="center">Image</th>
                        <th class="center sortable" onclick="sortTable('projects', 3, 'string')" data-column="3">Title</th>
                        <th class="center sortable" onclick="sortTable('projects', 4, 'string')" data-column="4">Album</th>
                        <th class="center">Description</th>
                        <th class="center sortable" onclick="sortTable('projects', 6, 'string')" data-column="6">Category</th>
                        <th class="center sortable" onclick="sortTable('projects', 7, 'string')" data-column="7">Location</th>
                        <th class="center sortable" onclick="sortTable('projects', 8, 'number')" data-column="8">Year</th>
                        <th class="center">Products Used</th>
                        <th class="center">Featured</th>
                        <th class="center sortable" onclick="sortTable('projects', 11, 'number')" data-column="11">Order</th>
                        <th class="center sortable" onclick="sortTable('projects', 12, 'date')" data-column="12">Uploaded</th>
                        <th class="center">Actions</th>
                    </tr>
                </thead>
                <tbody id="projectsTableBody">
                    <tr><td colspan="14" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PROJECT MODAL -->
    <div id="projectModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2 id="projectModalTitle">Add Project</h2>
                <span class="close" onclick="closeProjectModal()">&times;</span>
            </div>
            <form id="projectForm" onsubmit="saveProject(event)">
                <input type="hidden" id="projectId">
                
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label for="projectTitle">Project Title *</label>
                        <input type="text" id="projectTitle" required placeholder="e.g., Modern Living Room">
                    </div>
                    <div class="form-group">
                        <label for="projectCategory">Category *</label>
                        <select id="projectCategory" required>
                            <option value="">Select Category</option>
                            <option value="residential">Residential</option>
                            <option value="commercial">Commercial</option>
                            <option value="outdoor">Outdoor</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="projectAlbum">Album Name *</label>
                    <input type="text" id="projectAlbum" required placeholder="e.g., BGC Project 2024">
                    <small>Group multiple images under the same album name</small>
                </div>

                <div class="form-group">
                    <label for="projectDescription">Description *</label>
                    <textarea id="projectDescription" rows="3" required placeholder="Describe the project..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label for="projectLocation">Location</label>
                        <input type="text" id="projectLocation" placeholder="e.g., BGC, Taguig">
                    </div>
                    <div class="form-group">
                        <label for="projectCity">City</label>
                        <input type="text" id="projectCity" placeholder="e.g., Metro Manila">
                    </div>
                    <div class="form-group">
                        <label for="projectYear">Year</label>
                        <input type="number" id="projectYear" min="2000" max="2099" placeholder="2024">
                    </div>
                </div>

                <div class="form-group">
                    <label for="projectProducts">Products Used</label>
                    <input type="text" id="projectProducts" placeholder="e.g., WPC Wall Panels, SPC Flooring">
                    <small>Separate multiple products with commas</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="projectImage">Project Image *</label>
                        <input type="file" id="projectImage" accept="image/*">
                        <input type="hidden" id="projectImagePath">
                        <small>Recommended size: 1200x800px</small>
                    </div>
                    <div class="form-group">
                        <label for="projectDisplayOrder">Display Order</label>
                        <input type="number" id="projectDisplayOrder" value="0" min="0">
                        <small>Lower numbers appear first</small>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="projectFeatured">
                        <span>Feature this project on homepage carousel</span>
                    </label>
                </div>

                <div id="projectImagePreview" style="display: none; margin-top: 15px;">
                    <label>Current Image:</label>
                    <div style="margin-top: 10px;">
                        <img id="projectPreviewImg" src="" style="max-width: 300px; max-height: 200px; border-radius: 8px; border: 2px solid #ddd;">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeProjectModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="projectSubmitBtn">Save Project</button>
                </div>
            </form>
        </div>
    </div>


        <!-- WAREHOUSE LOCATIONS TAB -->
        <div id="locations" class="content">
            <div class="action-bar">
                <div class="search-box">
                    <input type="text" id="locationSearch" placeholder="Search locations..." onkeyup="filterTable('locations')">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-success btn-sm" onclick="exportToExcel('locationsTable', 'warehouse_locations')">📥 Export</button>
                    <button class="btn btn-primary" onclick="openLocationModal()">+ Add New Location</button>
                </div>
            </div>

            <div class="filter-controls">
                <select id="locationStatusFilter" onchange="filterTable('locations')">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <select id="locationProvinceFilter" onchange="filterTable('locations')">
                    <option value="">All Provinces</option>
                </select>
                <button class="btn btn-sm" onclick="clearFilters('locations')">Clear Filters</button>
            </div>

            <div id="locationsBulkBar" style="display:none;align-items:center;gap:10px;padding:10px 14px;background:#fffbe6;border:1.5px solid #f0c040;border-radius:8px;margin-bottom:12px;flex-wrap:wrap;">
                <span id="locationsSelectedCount" style="font-weight:600;font-size:14px;color:#7a5c00;">0 selected</span>
                <button class="btn btn-sm" style="background:#648E37;color:#fff;" onclick="bulkCopy('locations')">📋 Copy Selected</button>
                <button class="btn btn-sm btn-danger" onclick="bulkDelete('locations','api_locations.php','location_id',loadLocations)">🗑 Delete Selected</button>
                <button class="btn btn-sm btn-secondary" onclick="clearSelection('locations')">✕ Deselect All</button>
            </div>

            <table id="locationsTable">
                <thead>
                    <tr>
                        <th class="center" style="width:36px;"><input type="checkbox" id="selectAll_locations" onchange="toggleSelectAll('locations','location_id',this)"></th>
                        <th class="center sortable" onclick="sortTable('locations', 1, 'number')">ID</th>
                        <th class="center sortable" onclick="sortTable('locations', 2, 'string')">Location Name</th>
                        <th class="center">Address</th>
                        <th class="center sortable" onclick="sortTable('locations', 4, 'string')">City</th>
                        <th class="center sortable" onclick="sortTable('locations', 5, 'string')">Province</th>
                        <th class="center">Contact</th>
                        <th class="center">Status</th>
                        <th class="center sortable" onclick="sortTable('locations', 8, 'number')">Display Order</th>
                        <th class="center">Actions</th>
                    </tr>
                </thead>
                <tbody id="locationsTableBody">
                    <tr><td colspan="10" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- UPCOMING BRANCHES TAB -->
        <div id="upcoming" class="content">
            <div class="action-bar">
                <div class="search-box">
                    <input type="text" id="upcomingSearch" placeholder="Search upcoming branches..." onkeyup="filterTable('upcoming')">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-success btn-sm" onclick="exportToExcel('upcomingTable', 'upcoming_branches')">📥 Export</button>
                    <button class="btn btn-primary" onclick="openUpcomingModal()">+ Add Upcoming Branch</button>
                </div>
            </div>

            <div class="filter-controls">
                <select id="upcomingStatusFilter" onchange="filterTable('upcoming')">
                    <option value="">All Status Types</option>
                    <option value="planning">Planning</option>
                    <option value="under_construction">Under Construction</option>
                    <option value="coming_soon">Coming Soon</option>
                </select>
                <select id="upcomingActiveFilter" onchange="filterTable('upcoming')">
                    <option value="">All Visibility</option>
                    <option value="1">Active (Shown)</option>
                    <option value="0">Inactive (Hidden)</option>
                </select>
                <button class="btn btn-sm" onclick="clearFilters('upcoming')">Clear Filters</button>
            </div>

            <div id="upcomingBulkBar" style="display:none;align-items:center;gap:10px;padding:10px 14px;background:#fffbe6;border:1.5px solid #f0c040;border-radius:8px;margin-bottom:12px;flex-wrap:wrap;">
                <span id="upcomingSelectedCount" style="font-weight:600;font-size:14px;color:#7a5c00;">0 selected</span>
                <button class="btn btn-sm" style="background:#648E37;color:#fff;" onclick="bulkCopy('upcoming')">📋 Copy Selected</button>
                <button class="btn btn-sm btn-danger" onclick="bulkDelete('upcoming','api_upcoming.php','upcoming_id',loadUpcoming)">🗑 Delete Selected</button>
                <button class="btn btn-sm btn-secondary" onclick="clearSelection('upcoming')">✕ Deselect All</button>
            </div>

            <table id="upcomingTable">
                <thead>
                    <tr>
                        <th class="center" style="width:36px;"><input type="checkbox" id="selectAll_upcoming" onchange="toggleSelectAll('upcoming','upcoming_id',this)"></th>
                        <th class="center sortable" onclick="sortTable('upcoming', 1, 'number')">ID</th>
                        <th class="center">Icon</th>
                        <th class="center sortable" onclick="sortTable('upcoming', 3, 'string')">Branch Name</th>
                        <th class="center sortable" onclick="sortTable('upcoming', 4, 'string')">City</th>
                        <th class="center sortable" onclick="sortTable('upcoming', 5, 'string')">Province</th>
                        <th class="center sortable" onclick="sortTable('upcoming', 6, 'date')">Est. Opening</th>
                        <th class="center sortable" onclick="sortTable('upcoming', 7, 'string')">Status</th>
                        <th class="center">Description</th>
                        <th class="center">Visibility</th>
                        <th class="center sortable" onclick="sortTable('upcoming', 10, 'number')">Display Order</th>
                        <th class="center">Actions</th>
                    </tr>
                </thead>
                <tbody id="upcomingTableBody">
                    <tr><td colspan="12" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Branch Contacts Tab -->
        <div id="contacts" class="content">
            <div class="action-bar">
                <div class="search-box">
                    <input type="text" id="contactSearch" placeholder="Search contacts..." onkeyup="filterTable('contacts')">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-success btn-sm" onclick="exportToExcel('contactsTable', 'branch_contacts')">📥 Export</button>
                    <button class="btn btn-primary" onclick="openContactModal()">+ Add Branch Contact</button>
                </div>
            </div>

            <div class="filter-controls">
                <select id="contactLocationFilter" onchange="filterTable('contacts')">
                    <option value="">All Locations</option>
                </select>
                <select id="contactPrimaryFilter" onchange="filterTable('contacts')">
                    <option value="">All Contacts</option>
                    <option value="1">Primary Only</option>
                    <option value="0">Secondary Only</option>
                </select>
                <select id="contactActiveFilter" onchange="filterTable('contacts')">
                    <option value="">All Status</option>
                    <option value="1">Active Only</option>
                    <option value="0">Inactive Only</option>
                </select>
                <button class="btn btn-sm" onclick="clearFilters('contacts')">Clear Filters</button>
            </div>

            <div id="contactsBulkBar" style="display:none;align-items:center;gap:10px;padding:10px 14px;background:#fffbe6;border:1.5px solid #f0c040;border-radius:8px;margin-bottom:12px;flex-wrap:wrap;">
                <span id="contactsSelectedCount" style="font-weight:600;font-size:14px;color:#7a5c00;">0 selected</span>
                <button class="btn btn-sm" style="background:#648E37;color:#fff;" onclick="bulkCopy('contacts')">📋 Copy Selected</button>
                <button class="btn btn-sm btn-danger" onclick="bulkDelete('contacts','api_contacts.php','contact_id',loadContacts)">🗑 Delete Selected</button>
                <button class="btn btn-sm btn-secondary" onclick="clearSelection('contacts')">✕ Deselect All</button>
            </div>

            <table id="contactsTable">
                <thead>
                    <tr>
                        <th class="center" style="width:36px;"><input type="checkbox" id="selectAll_contacts" onchange="toggleSelectAll('contacts','contact_id',this)"></th>
                        <th class="center sortable" onclick="sortTable('contacts', 1, 'number')" data-column="1">ID</th>
                        <th class="center sortable" onclick="sortTable('contacts', 2, 'string')" data-column="2">Location</th>
                        <th class="center sortable" onclick="sortTable('contacts', 3, 'string')" data-column="3">Contact Name</th>
                        <th class="center sortable" onclick="sortTable('contacts', 4, 'string')" data-column="4">Phone Number</th>
                        <th class="center sortable" onclick="sortTable('contacts', 5, 'string')" data-column="5">Email</th>
                        <th class="center sortable" onclick="sortTable('contacts', 6, 'string')" data-column="6">Role</th>
                        <th class="center">Primary</th>
                        <th class="center sortable" onclick="sortTable('contacts', 8, 'number')" data-column="8">Display Order</th>
                        <th class="center">Status</th>
                        <th class="center sortable" onclick="sortTable('contacts', 10, 'date')" data-column="10">Created</th>
                        <th class="center">Actions</th>
                    </tr>
                </thead>
                <tbody id="contactsTableBody">
                    <tr><td colspan="12" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- WAREHOUSE LOCATION MODAL -->
        <div id="locationModal" class="modal">
            <div class="modal-content modal-lg">
                <div class="modal-header">
                    <h2 id="locationModalTitle">Add Warehouse Location</h2>
                    <span class="close" onclick="closeLocationModal()">&times;</span>
                </div>
                <form id="locationForm" onsubmit="saveLocation(event)">
                    <input type="hidden" id="locationId">
                    
                    <div class="form-group">
                        <label for="locationName">Location Name *</label>
                        <input type="text" id="locationName" required placeholder="e.g., Angeles, Pampanga">
                        <small>City/Area name as it will appear on the website</small>
                    </div>

                    <div class="form-group">
                        <label for="locationAddress1">Address Line 1 *</label>
                        <input type="text" id="locationAddress1" required placeholder="Street address">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="locationAddress2">Address Line 2</label>
                            <input type="text" id="locationAddress2" placeholder="Additional address info">
                        </div>
                        <div class="form-group">
                            <label for="locationAddress3">Address Line 3</label>
                            <input type="text" id="locationAddress3" placeholder="Additional address info">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="locationCity">City *</label>
                            <input type="text" id="locationCity" required placeholder="City name">
                        </div>
                        <div class="form-group">
                            <label for="locationProvince">Province</label>
                            <input type="text" id="locationProvince" placeholder="Province name">
                        </div>
                        <div class="form-group">
                            <label for="locationPostal">Postal Code</label>
                            <input type="text" id="locationPostal" placeholder="ZIP code">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="locationContact">Contact Number</label>
                            <input type="text" id="locationContact" placeholder="+63 xxx xxx xxxx">
                        </div>
                        <div class="form-group">
                            <label for="locationEmail">Email</label>
                            <input type="email" id="locationEmail" placeholder="branch@greenwood.ph">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="locationFacebook">Facebook URL</label>
                        <input type="url" id="locationFacebook" placeholder="https://www.facebook.com/...">
                    </div>

                    <div class="form-group">
                        <label for="locationGoogleMaps">Google Maps URL</label>
                        <input type="url" id="locationGoogleMaps" placeholder="https://maps.google.com/...">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="locationLatitude">Latitude</label>
                            <input type="number" step="any" id="locationLatitude" placeholder="14.5995">
                            <small>GPS coordinate</small>
                        </div>
                        <div class="form-group">
                            <label for="locationLongitude">Longitude</label>
                            <input type="number" step="any" id="locationLongitude" placeholder="120.9842">
                            <small>GPS coordinate</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="locationHours">Operating Hours</label>
                        <textarea id="locationHours" rows="2" placeholder="Mon-Sat: 8:00 AM - 5:00 PM"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="locationNote">Special Note</label>
                        <input type="text" id="locationNote" placeholder="e.g., Front of Floor Center">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="locationDisplayOrder">Display Order</label>
                            <input type="number" id="locationDisplayOrder" value="0" min="0">
                            <small>Lower numbers appear first</small>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="locationActive" checked>
                                <span>Active (Show on website)</span>
                            </label>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeLocationModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="locationSubmitBtn">Save Location</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- UPCOMING BRANCH MODAL -->
        <div id="upcomingModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="upcomingModalTitle">Add Upcoming Branch</h2>
                    <span class="close" onclick="closeUpcomingModal()">&times;</span>
                </div>
                <form id="upcomingForm" onsubmit="saveUpcoming(event)">
                    <input type="hidden" id="upcomingId">
                    
                    <div class="form-group">
                        <label for="upcomingBranchName">Branch Name *</label>
                        <input type="text" id="upcomingBranchName" required placeholder="e.g., Cebu City">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="upcomingCity">City *</label>
                            <input type="text" id="upcomingCity" required placeholder="City name">
                        </div>
                        <div class="form-group">
                            <label for="upcomingProvince">Province</label>
                            <input type="text" id="upcomingProvince" placeholder="Province name">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="upcomingDate">Estimated Opening Date</label>
                            <input type="date" id="upcomingDate">
                        </div>
                        <div class="form-group">
                            <label for="upcomingStatus">Status *</label>
                            <select id="upcomingStatus" required>
                                <option value="coming_soon">Coming Soon</option>
                                <option value="under_construction">Under Construction</option>
                                <option value="planning">Planning</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="upcomingDescription">Description</label>
                        <textarea id="upcomingDescription" rows="2" placeholder="Brief description or teaser..."></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="upcomingIcon">Icon/Emoji</label>
                            <input type="text" id="upcomingIcon" value="⏳" maxlength="4">
                            <small>Emoji or icon to display</small>
                        </div>
                        <div class="form-group">
                            <label for="upcomingDisplayOrder">Display Order</label>
                            <input type="number" id="upcomingDisplayOrder" value="0" min="0">
                            <small>Lower numbers appear first</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="upcomingActive" checked>
                            <span>Active (Show on website)</span>
                        </label>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeUpcomingModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="upcomingSubmitBtn">Save Branch</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- BRANCH CONTACT MODAL -->
        <div id="contactModal" class="modal">
            <div class="modal-content modal-lg">
                <div class="modal-header">
                    <h2 id="contactModalTitle">Add Branch Contact</h2>
                    <span class="close" onclick="closeContactModal()">&times;</span>
                </div>
                <form id="contactForm" onsubmit="saveContact(event)">
                    <input type="hidden" id="contactId">
                    
                    <div class="form-group">
                        <label for="contactLocation">Branch Location *</label>
                        <select id="contactLocation" required>
                            <option value="">Select Location</option>
                            <!-- Will be populated dynamically -->
                        </select>
                        <small>Select the warehouse/branch location for this contact</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label for="contactName">Contact Person Name *</label>
                            <input type="text" id="contactName" required placeholder="e.g., Juan Dela Cruz">
                        </div>
                        <div class="form-group">
                            <label for="contactRole">Role/Department</label>
                            <input type="text" id="contactRole" placeholder="e.g., Sales Manager">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contactNumber">Contact Number *</label>
                            <input type="text" id="contactNumber" required placeholder="09XX XXX XXXX">
                            <small>Format: 09XXXXXXXXX</small>
                        </div>
                        <div class="form-group">
                            <label for="contactEmail">Email Address</label>
                            <input type="email" id="contactEmail" placeholder="contact@greenwood.ph">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="contactPrimary">Contact Type</label>
                            <select id="contactPrimary">
                                <option value="1">Primary Contact</option>
                                <option value="0" selected>Secondary Contact</option>
                            </select>
                            <small>Primary contacts are displayed first</small>
                        </div>
                        <div class="form-group">
                            <label for="contactOrder">Display Order</label>
                            <input type="number" id="contactOrder" min="0" value="0" placeholder="0">
                            <small>Lower numbers appear first (0 = first)</small>
                        </div>
                        <div class="form-group">
                            <label for="contactActive">Status</label>
                            <select id="contactActive">
                                <option value="1" selected>Active (Shown on Website)</option>
                                <option value="0">Inactive (Hidden)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeContactModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Contact</button>
                    </div>
                </form>
            </div>
        </div>


    <!-- Variant Modal -->
    <div id="variantModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="variantModalTitle">Add Product Variant</h2>
                <span class="close" onclick="closeVariantModal()">&times;</span>
            </div>
            <form id="variantForm" onsubmit="saveVariant(event)">
                <input type="hidden" id="variantId">
                <div class="form-group">
                    <label for="variantProduct">Product *</label>
                    <select id="variantProduct" required>
                        <option value="">Select Product</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="variantColor">Color *</label>
                    <select id="variantColor" required>
                        <option value="">Select Color</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="variantSize">Size *</label>
                    <select id="variantSize" required>
                        <option value="">Select Size</option>
                    </select>
                </div>
                <div class="form-group">
    <label for="variantSellUnit">Sell Unit <small style="color:#888;font-weight:normal;">(set on the Size — read only)</small></label>
    <select id="variantSellUnit" disabled style="background:#f5f5f5;color:#666;cursor:not-allowed;">
        <option value="piece">Piece</option>
        <option value="box">Box</option>
    </select>
</div>
<div class="form-group" id="piecesPerBoxGroup" style="display:none;">
    <label for="variantPiecesPerBox">Pieces per Box</label>
    <input type="number" id="variantPiecesPerBox" min="1" placeholder="e.g. 6" readonly style="background:#f5f5f5;color:#666;cursor:not-allowed;">
</div>

                <div class="form-group">
                    <label for="variantSKU">SKU *</label>
                    <input type="text" id="variantSKU" required>
                </div>
                <div class="form-group">
                    <label for="variantStock">Stock</label>
                    <input type="number" id="variantStock" value="0" min="0">
                </div>
                <div class="form-group">
                    <label for="variantImage">Image</label>
                    <input type="file" id="variantImage" accept="image/*">
                    <input type="hidden" id="variantImagePath">
                </div>
                
                <!-- Pricing Section -->
                <div style="border-top: 2px solid #e0e0e0; margin: 20px 0; padding-top: 20px;">
                    <h3 style="margin-bottom: 15px; color: #2c5f2d;">Pricing Information</h3>
                    <div class="form-group">
                        <label for="retailPrice">Retail Price *</label>
                        <input type="number" id="retailPrice" step="0.01" min="0" required placeholder="0.00">
                        <small>Price for retail customers (per unit)</small>
                    </div>
                    <div class="form-group">
                        <label for="wholesalePrice">Wholesale Price *</label>
                        <input type="number" id="wholesalePrice" step="0.01" min="0" required placeholder="0.00">
                        <small>Price for wholesale/bulk orders</small>
                    </div>
                    <div class="form-group">
                        <label for="wholesaleMinQty">Wholesale Minimum Quantity *</label>
                        <input type="number" id="wholesaleMinQty" min="1" value="1" required placeholder="1">
                        <small>Minimum quantity required to get wholesale price (e.g., 10 pieces)</small>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeVariantModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="variantSubmitBtn">Save Variant</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Price Modal -->
    <div id="priceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="priceModalTitle">Add Price</h2>
                <span class="close" onclick="closePriceModal()">&times;</span>
            </div>
            <form id="priceForm" onsubmit="savePrice(event)">
                <input type="hidden" id="priceId">
                <div class="form-group">
                    <label for="priceVariant">Product Variant *</label>
                    <select id="priceVariant" required>
                        <option value="">Select Variant</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="priceType">Price Type *</label>
                    <select id="priceType" required>
                        <option value="retail">Retail</option>
                        <option value="wholesale">Wholesale</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="minQuantity">Minimum Quantity</label>
                    <input type="number" id="minQuantity" value="1" min="1">
                </div>
                <div class="form-group">
                    <label for="priceAmount">Price *</label>
                    <input type="number" id="priceAmount" step="0.01" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closePriceModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="priceSubmitBtn">Save Price</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="categoryModalTitle">Add Category</h2>
                <span class="close" onclick="closeCategoryModal()">&times;</span>
            </div>
            <form id="categoryForm" onsubmit="saveCategory(event)">
                <input type="hidden" id="categoryId">
                <div class="form-group">
                    <label for="categoryName">Category Name *</label>
                    <input type="text" id="categoryName" required>
                </div>
                <div class="form-group">
                    <label for="categorySlug">Slug</label>
                    <input type="text" id="categorySlug">
                    <small>Leave empty to auto-generate from name</small>
                </div>
                <div class="form-group">
                    <label for="categoryDescription">Description</label>
                    <textarea id="categoryDescription" rows="3"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="categorySubmitBtn">Save Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Color Modal -->
    <div id="colorModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="colorModalTitle">Add Color</h2>
                <span class="close" onclick="closeColorModal()">&times;</span>
            </div>
            <form id="colorForm" onsubmit="saveColor(event)">
                <input type="hidden" id="colorId">
                <div class="form-group">
                    <label for="colorName">Color Name *</label>
                    <input type="text" id="colorName" required>
                </div>
                <div class="form-group">
                    <label for="hexCode">Hex Code *</label>
                    <input type="text" id="hexCode" placeholder="#000000" required>
                    <input type="color" id="colorPicker" style="margin-top: 5px;">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeColorModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="colorSubmitBtn">Save Color</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Size Modal -->
    <div id="sizeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="sizeModalTitle">Add Size</h2>
                <span class="close" onclick="closeSizeModal()">&times;</span>
            </div>
            <form id="sizeForm" onsubmit="saveSize(event)">
    <input type="hidden" id="sizeId">
    <div class="form-group">
        <label for="sizeName">Size Name *</label>
        <input type="text" id="sizeName" required>
    </div>
    <div class="form-group">
        <label for="sizeSellUnit">Sell Unit</label>
    <select id="sizeSellUnit">
        <option value="piece">Piece</option>
        <option value="box">Box</option>
    </select>
</div>
<div class="form-group" id="sizePiecesPerBoxGroup" style="display:none;">
    <label for="sizePiecesPerBox">Pieces per Box</label>
    <input type="number" id="sizePiecesPerBox" min="1" placeholder="e.g. 6">
</div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeSizeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="sizeSubmitBtn">Save Size</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Product Type Modal -->
    <div id="productTypeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="productTypeModalTitle">Add Product Type</h2>
                <span class="close" onclick="closeProductTypeModal()">&times;</span>
            </div>
            <form id="productTypeForm" onsubmit="saveProductType(event)">
                <input type="hidden" id="productTypeId">
                <div class="form-group">
                    <label for="productTypeName">Type Name *</label>
                    <input type="text" id="productTypeName" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeProductTypeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="typeSubmitBtn">Save Type</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Project Modal -->
    <div id="projectModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2 id="projectModalTitle">Add Project</h2>
                <span class="close" onclick="closeProjectModal()">&times;</span>
            </div>
            <form id="projectForm" onsubmit="saveProject(event)">
                <input type="hidden" id="projectId">
                <input type="hidden" id="projectImagePath">

                <div class="form-group">
                    <label for="projectTitle">Project Title *</label>
                    <input type="text" id="projectTitle" required>
                </div>

                <div class="form-group">
                    <label for="projectDescription">Description</label>
                    <textarea id="projectDescription" rows="3"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="projectCategory">Category *</label>
                        <select id="projectCategory" required>
                            <option value="">Select Category</option>
                            <option value="Residential">Residential</option>
                            <option value="Commercial">Commercial</option>
                            <option value="Outdoor">Outdoor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="projectYear">Year</label>
                        <input type="number" id="projectYear" min="1900" max="2100">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="projectLocation">Location</label>
                        <input type="text" id="projectLocation" placeholder="e.g., Downtown Area">
                    </div>

                    <div class="form-group">
                        <label for="projectCity">City</label>
                        <input type="text" id="projectCity" placeholder="e.g., Manila">
                    </div>
                </div>

                <div class="form-group">
                    <label for="projectProducts">Products Used</label>
                    <textarea id="projectProducts" rows="2" placeholder="List the products used in this project"></textarea>
                </div>

                <div class="form-group">
                    <label for="projectImage">Project Image *</label>
                    <input type="file" id="projectImage" accept="image/*">
                    <div id="projectImagePreview" style="margin-top: 10px; display: none;">
                        <img id="projectPreviewImg" src="" alt="Preview" style="max-width: 200px; border-radius: 8px;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="projectFeatured">
                            <span>Featured Project</span>
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="projectDisplayOrder">Display Order</label>
                        <input type="number" id="projectDisplayOrder" value="0" min="0">
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeProjectModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="projectSubmitBtn">Save Project</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div id="imagePreviewModal" class="image-modal" style="display:none;">
        <img id="imagePreviewModalImg">
    </div>


    <!-- ═══════════════════════════════════════════════════════
     MODAL OVERLAY
     ═══════════════════════════════════════════════════════ -->
<div id="adminCalcOverlay" onclick="closeAdminCalcOnOverlay(event)" style="
    display:none;position:fixed;inset:0;z-index:99990;
    background:rgba(0,0,0,.55);backdrop-filter:blur(3px);
    align-items:center;justify-content:center;padding:16px;
    animation:acFadeIn .2s ease;
">
<div id="adminCalcModal" style="
    background:#fff;border-radius:16px;width:100%;max-width:780px;
    max-height:92vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);
    animation:acSlideUp .25s cubic-bezier(.4,0,.2,1);
    font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
">
 
    <!-- Modal Header -->
    <div style="
        display:flex;align-items:center;justify-content:space-between;
        padding:20px 24px 16px;border-bottom:2px solid #648E37;
        position:sticky;top:0;background:#fff;z-index:2;border-radius:16px 16px 0 0;
    ">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;background:#648E37;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="7" x2="16" y2="7"/>
                    <line x1="8" y1="11" x2="11" y2="11"/><line x1="8" y1="15" x2="11" y2="15"/>
                    <line x1="14" y1="11" x2="16" y2="13"/><line x1="16" y1="11" x2="14" y2="13"/>
                    <line x1="14" y1="15" x2="16" y2="17"/><line x1="16" y1="15" x2="14" y2="17"/>
                </svg>
            </div>
            <div>
                <div style="font-size:16px;font-weight:700;color:#1f2937;">Quotation Calculator</div>
                <div style="font-size:12px;color:#6b7280;">Admin — includes discount &amp; shipping</div>
            </div>
        </div>
        <button onclick="closeAdminCalc()" style="
            background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;
            line-height:1;padding:4px;border-radius:6px;transition:color .15s,background .15s;
        " onmouseover="this.style.background='#f3f4f6';this.style.color='#374151'"
           onmouseout="this.style.background='none';this.style.color='#9ca3af'">&times;</button>
    </div>
 
    <!-- Modal Body -->
    <div style="padding:24px;">
 
        <!-- ── Row 1: Area + Unit ── -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
            <div>
                <label class="ac-label">Area Size</label>
                <input type="number" id="ac_areaSize" class="ac-input" placeholder="e.g. 25" step="0.01" min="0">
            </div>
            <div>
                <label class="ac-label">Unit</label>
                <select id="ac_areaUnit" class="ac-input">
                    <option value="sqm">sq. meters (m²)</option>
                    <option value="sqft">sq. feet (ft²)</option>
                    <option value="sqcm">sq. cm (cm²)</option>
                    <option value="sqin">sq. inches (in²)</option>
                </select>
            </div>
        </div>
 
        <!-- ── Row 2: Product + Variant ── -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
            <div>
                <label class="ac-label">Select Product</label>
                <select id="ac_product" class="ac-input" onchange="acOnProductChange()">
                    <option value="">Choose a product…</option>
                    <?php foreach ($_ac_list as $_acp): ?>
                        <?php if (!empty($_acp['variants'])): ?>
                        <option value="<?php echo (int)$_acp['product_id']; ?>">
                            <?php echo htmlspecialchars($_acp['product_name']); ?>
                        </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="ac-label">Select Variant</label>
                <select id="ac_variant" class="ac-input" disabled>
                    <option value="">Choose a variant…</option>
                </select>
            </div>
        </div>
 
        <!-- ── Row 3: Discount + Shipping ── -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
            <div>
                <label class="ac-label">
                    Discount Amount
                    <span style="font-size:11px;font-weight:500;color:#9ca3af;margin-left:4px;">(₱ deducted from total)</span>
                </label>
                <div style="position:relative;">
                    <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#6b7280;font-weight:600;font-size:14px;">₱</span>
                    <input type="number" id="ac_discount" class="ac-input" placeholder="0.00" step="0.01" min="0"
                        style="padding-left:26px;" oninput="acUpdateTotals()">
                </div>
            </div>
            <div>
                <label class="ac-label">
                    Shipping Fee
                    <span style="font-size:11px;font-weight:500;color:#9ca3af;margin-left:4px;">(₱ added to total)</span>
                </label>
                <div style="position:relative;">
                    <span style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#6b7280;font-weight:600;font-size:14px;">₱</span>
                    <input type="number" id="ac_shipping" class="ac-input" placeholder="0.00" step="0.01" min="0"
                        style="padding-left:26px;" oninput="acUpdateTotals()">
                </div>
            </div>
        </div>
 
        <!-- ── Action Buttons ── -->
        <div style="display:flex;gap:10px;margin-bottom:20px;">
            <button onclick="acCalculate()" style="
                flex:1;background:#648E37;color:#fff;border:none;padding:12px 20px;
                border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;
                display:flex;align-items:center;justify-content:center;gap:8px;
                transition:background .18s;
            " onmouseover="this.style.background='#527530'" onmouseout="this.style.background='#648E37'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Calculate
            </button>
            <button onclick="acReset()" style="
                background:#f3f4f6;color:#374151;border:1px solid #d1d5db;padding:12px 18px;
                border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;transition:background .18s;
            " onmouseover="this.style.background='#e5e7eb'" onmouseout="this.style.background='#f3f4f6'"
               title="Reset calculator">↺ Reset</button>
        </div>
 
        <!-- ── Result Card ── -->
        <div id="ac_result" style="display:none;">
 
            <!-- Coverage Details -->
            <div style="background:#f8fdf4;border:2px solid #648E37;border-radius:12px;padding:20px;margin-bottom:14px;">
                <div style="font-size:11px;font-weight:700;color:#648E37;text-transform:uppercase;letter-spacing:.7px;margin-bottom:14px;display:flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#648E37" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Estimated Coverage
                </div>
                <div id="ac_resultRows" style="background:#fff;border-radius:8px;border-left:4px solid #648E37;overflow:hidden;"></div>
                <div style="font-size:11px;color:#9ca3af;margin-top:12px;padding:8px 12px;background:rgba(100,142,55,.07);border-radius:6px;display:flex;gap:8px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#648E37" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span><strong>Note:</strong> This is an estimate. Actual requirements may vary. Recommend ordering 10–15% extra.</span>
                </div>
            </div>
 
            <!-- Price Summary Card -->
            <div style="background:#fff;border:2px solid #e5e7eb;border-radius:12px;padding:20px;margin-bottom:14px;">
                <div style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.7px;margin-bottom:16px;">
                    💰 Price Summary
                </div>
                <div id="ac_priceSummary"></div>
 
                <!-- Subtotal → Discount → Shipping → Grand Total -->
                <div style="border-top:2px dashed #e5e7eb;margin-top:12px;padding-top:12px;">
                    <div class="ac-sum-row">
                        <span style="color:#6b7280;">Subtotal</span>
                        <span id="ac_subtotalDisplay" style="font-weight:600;color:#374151;">₱0.00</span>
                    </div>
                    <div class="ac-sum-row" id="ac_discountRow" style="display:none;">
                        <span style="color:#dc2626;">Discount</span>
                        <span id="ac_discountDisplay" style="font-weight:600;color:#dc2626;">−₱0.00</span>
                    </div>
                    <div class="ac-sum-row" id="ac_shippingRow" style="display:none;">
                        <span style="color:#2563eb;">Shipping Fee</span>
                        <span id="ac_shippingDisplay" style="font-weight:600;color:#2563eb;">+₱0.00</span>
                    </div>
                    <div style="border-top:2px solid #648E37;margin-top:10px;padding-top:12px;display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:15px;font-weight:700;color:#1f2937;text-transform:uppercase;letter-spacing:.5px;">Grand Total</span>
                        <span id="ac_grandTotal" style="font-size:22px;font-weight:800;color:#648E37;">₱0.00</span>
                    </div>
                </div>
            </div>
 
            <!-- Save Estimate Button -->
            <button onclick="acSaveEstimate()" style="
                width:100%;background:#648E37;color:#fff;border:none;
                padding:13px 20px;border-radius:8px;font-size:14px;font-weight:600;
                cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;
                transition:background .18s;
            " onmouseover="this.style.background='#527530'" onmouseout="this.style.background='#648E37'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                </svg>
                Save This Estimate
            </button>
        </div>
 
        <!-- ── Saved Estimates ── -->
        <div id="ac_savedSection" style="display:none;margin-top:14px;">
            <div style="background:#fff;border:2px solid #648E37;border-radius:12px;padding:20px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                    <div style="font-size:11px;font-weight:700;color:#648E37;text-transform:uppercase;letter-spacing:.7px;">📋 Saved Estimates</div>
                    <button onclick="acClearAll()" style="
                        background:none;border:1px solid #fca5a5;color:#dc2626;
                        padding:4px 10px;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;
                        transition:background .15s;
                    " onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='none'">
                        🗑 Clear All
                    </button>
                </div>
                <div id="ac_savedItems"></div>
 
                <!-- Saved total with discount/shipping aggregated -->
                <div style="border-top:2px solid #648E37;padding-top:14px;margin-top:4px;">
                    <div class="ac-sum-row" style="margin-bottom:4px;">
                        <span style="color:#6b7280;font-size:13px;">Items subtotal</span>
                        <span id="ac_savedSubtotal" style="font-weight:600;color:#374151;font-size:13px;">₱0.00</span>
                    </div>
                    <div class="ac-sum-row" id="ac_savedDiscRow" style="display:none;margin-bottom:4px;">
                        <span style="color:#dc2626;font-size:13px;">Total discounts</span>
                        <span id="ac_savedDiscDisplay" style="font-weight:600;color:#dc2626;font-size:13px;">−₱0.00</span>
                    </div>
                    <div class="ac-sum-row" id="ac_savedShipRow" style="display:none;margin-bottom:4px;">
                        <span style="color:#2563eb;font-size:13px;">Total shipping</span>
                        <span id="ac_savedShipDisplay" style="font-weight:600;color:#2563eb;font-size:13px;">+₱0.00</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
                        <span style="font-size:15px;font-weight:700;color:#1f2937;text-transform:uppercase;letter-spacing:.5px;">Grand Total</span>
                        <span id="ac_savedGrand" style="font-size:24px;font-weight:800;color:#648E37;">₱0.00</span>
                    </div>
                </div>
            </div>
        </div>
 
    </div><!-- /Modal Body -->
</div><!-- /Modal -->
</div><!-- /Overlay -->

<script>
// ============================================
// GLOBAL VARIABLES
// ============================================
let products = [];
let variants = [];
let prices = [];
let categories = [];
let colors = [];
let sizes = [];
let productTypes = [];
let projectsData = [];
let contactsData = [];
let contactLocations = [];
let locationsData = [];
let upcomingData = [];
let influencersData = [];

// Sorting state
let sortState = {};

// PERSISTENT SELECTION SETS
const sel = {
    products: new Set(),
    variants: new Set(),
    prices: new Set(),
    categories: new Set(),
    colors: new Set(),
    sizes: new Set(),
    types: new Set(),
    projects: new Set(),
    locations: new Set(),
    upcoming: new Set(),
    contacts: new Set(),
    influencers: new Set()
};

// ===================== DETAILED BULK COPY ENGINE =====================
let currentBulkTable = null;
let currentBulkItems = [];

        // Generic bulk UI updater
        function updateBulkBar(tableKey, count) {
            const bar = document.getElementById(tableKey + 'BulkBar');
            const label = document.getElementById(tableKey + 'SelectedCount');
            const master = document.getElementById('selectAll_' + tableKey);
            if (bar) bar.style.display = count > 0 ? 'flex' : 'none';
            if (label) label.textContent = count + ' selected';
        }
        function toggleSelectAll(tableKey, idAttr, masterCb) {
            const checked = masterCb.checked;
            document.querySelectorAll(`.cb-${tableKey}`).forEach(cb => {
                cb.checked = checked;
                if (checked) sel[tableKey].add(String(cb.value));
                else sel[tableKey].delete(String(cb.value));
            });
            updateBulkBar(tableKey, sel[tableKey].size);
        }
        function onCheckboxChange(tableKey, id, checked) {
            if (checked) sel[tableKey].add(String(id));
            else sel[tableKey].delete(String(id));
            const all = document.querySelectorAll(`.cb-${tableKey}`);
            const checkedCount = [...all].filter(c => c.checked).length;
            const master = document.getElementById('selectAll_' + tableKey);
            if (master) {
                master.checked = all.length > 0 && checkedCount === all.length;
                master.indeterminate = checkedCount > 0 && checkedCount < all.length;
            }
            updateBulkBar(tableKey, sel[tableKey].size);
        }
        function restoreCheckboxes(tableKey) {
            document.querySelectorAll(`.cb-${tableKey}`).forEach(cb => {
                if (sel[tableKey].has(String(cb.value))) cb.checked = true;
            });
            const all = document.querySelectorAll(`.cb-${tableKey}`);
            const checkedCount = [...all].filter(c => c.checked).length;
            const master = document.getElementById('selectAll_' + tableKey);
            if (master) {
                master.checked = all.length > 0 && checkedCount === all.length;
                master.indeterminate = checkedCount > 0 && checkedCount < all.length;
            }
            updateBulkBar(tableKey, sel[tableKey].size);
        }
        function clearSelection(tableKey) {
            sel[tableKey].clear();
            document.querySelectorAll(`.cb-${tableKey}`).forEach(cb => cb.checked = false);
            const master = document.getElementById('selectAll_' + tableKey);
            if (master) { master.checked = false; master.indeterminate = false; }
            updateBulkBar(tableKey, 0);
        }

        // Generic bulk delete helper
        async function bulkDelete(tableKey, apiFile, idField, loadFn, extra = []) {
            const ids = [...sel[tableKey]];
            if (!ids.length) return;
            if (!confirm(`Delete ${ids.length} item(s)? This cannot be undone.`)) return;
            let ok = 0, fail = [];
            for (const id of ids) {
                const result = await apiRequest(apiFile, 'DELETE', { [idField]: id });
                if (result.success) ok++;
                else fail.push(`ID ${id}: ${result.message}`);
            }
            clearSelection(tableKey);
            if (ok) showAlert(`${ok} item(s) deleted.`, 'success');
            if (fail.length) showAlert(fail.join(' | '), 'error');
            loadFn();
            extra.forEach(fn => fn());
        }

        // Bulk bar HTML builder
        function bulkBarHTML(tableKey, onCopy, onDelete) {
            return `<div id="${tableKey}BulkBar" style="display:none;align-items:center;gap:10px;padding:10px 14px;background:#fffbe6;border:1.5px solid #f0c040;border-radius:8px;margin-bottom:12px;flex-wrap:wrap;">
                <span id="${tableKey}SelectedCount" style="font-weight:600;font-size:14px;color:#7a5c00;">0 selected</span>
                ${onCopy ? `<button class="btn btn-sm" style="background:#648E37;color:#fff;" onclick="${onCopy}">📋 Copy Selected</button>` : ''}
                <button class="btn btn-sm btn-danger" onclick="${onDelete}">🗑 Delete Selected</button>
                <button class="btn btn-sm btn-secondary" onclick="clearSelection('${tableKey}')">✕ Deselect All</button>
            </div>`;
        }

        // ============================================
        // UTILITY FUNCTIONS
        // ============================================
        async function apiRequest(endpoint, method = 'GET', data = null) {
            try {
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                    }
                };
                
                if (data && method !== 'GET') {
                    options.body = JSON.stringify(data);
                }
                
                const response = await fetch(endpoint, options);
                return await response.json();
            } catch (error) {
                console.error('API Error:', error);
                return { success: false, message: 'Network error occurred' };
            }
        }

        function showAlert(message, type = 'info') {
            const alertBox = document.getElementById('alertBox');
            alertBox.innerHTML = `<div class="alert alert-${escapeHtml(type)}">${escapeHtml(message)}</div>`;
            setTimeout(() => {
                alertBox.innerHTML = '';
            }, 5000);
        }

        function formatDateTime(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric'
            });
        }

        // ============================================
        // HELPER FUNCTIONS
        // ============================================
        
        /**
         * Truncate text and add ellipsis with title attribute for full text on hover
         * @param {string} text - Text to truncate
         * @param {number} maxLength - Maximum length before truncation
         * @param {string} className - CSS class for styling
         * @returns {string} HTML string with truncated text
         */
        function truncateText(text, maxLength = 50, className = 'truncate') {
            if (!text || text === '-') return text || '-';
            const cleanText = String(text).trim();
            if (cleanText.length <= maxLength) {
                return `<span class="${className}">${cleanText}</span>`;
            }
            return `<span class="${className}" title="${cleanText.replace(/"/g, '&quot;')}">${cleanText}</span>`;
        }

        /**
         * Escape HTML to prevent XSS
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showTab(tabName) {
            const contents = document.querySelectorAll('.content');
            const tabs = document.querySelectorAll('.tab');
            
            contents.forEach(content => content.classList.remove('active'));
            tabs.forEach(tab => tab.classList.remove('active'));
            
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }

        async function uploadImage(fileInput, uploadType = 'variant-image') {
            if (!fileInput.files || !fileInput.files[0]) return null;
            
            const formData = new FormData();
            formData.append('image', fileInput.files[0]);
            formData.append('upload_type', uploadType);
            
            try {
                const response = await fetch('upload_image.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    return result.path;
                } else {
                    showAlert(result.message || 'Image upload failed', 'error');
                    return null;
                }
            } catch (error) {
                console.error('Upload error:', error);
                showAlert('Image upload failed', 'error');
                return null;
            }
        }

        function filterTable(tableType) {
            let tableId, searchId, dataArray;
            
            switch(tableType) {
                case 'products':
                    tableId = 'productsTableBody';
                    searchId = 'productSearch';
                    dataArray = products;
                    break;
                case 'variants':
                    tableId = 'variantsTableBody';
                    searchId = 'variantSearch';
                    dataArray = variants;
                    break;
                case 'prices':
                    tableId = 'pricesTableBody';
                    searchId = 'priceSearch';
                    dataArray = prices;
                    break;
                case 'categories':
                    tableId = 'categoriesTableBody';
                    searchId = 'categorySearch';
                    dataArray = categories;
                    break;
                case 'colors':
                    tableId = 'colorsTableBody';
                    searchId = 'colorSearch';
                    dataArray = colors;
                    break;
                case 'sizes':
                    tableId = 'sizesTableBody';
                    searchId = 'sizeSearch';
                    dataArray = sizes;
                    break;
                case 'product-types':
                    tableId = 'productTypesTableBody';
                    searchId = 'typeSearch';
                    dataArray = productTypes;
                    break;
                case 'projects':
                    filterProjects();
                    return;
                case 'locations':
                    filterLocations();
                    return;
                case 'upcoming':
                    filterUpcoming();
                    return;
                case 'contacts':
                    filterContactsTable();
                    return;
                default:
                    return;
            }
            
            const searchValue = document.getElementById(searchId)?.value.toLowerCase() || '';
            
            // Get filter values
            let categoryFilter = '', typeFilter = '', colorFilter = '', sizeFilter = '', productFilter = '', priceTypeFilter = '';
            
            if (tableType === 'products') {
                categoryFilter = document.getElementById('productCategoryFilter')?.value || '';
                typeFilter = document.getElementById('productTypeFilter')?.value || '';
            } else if (tableType === 'variants') {
                productFilter = document.getElementById('variantProductFilter')?.value || '';
                colorFilter = document.getElementById('variantColorFilter')?.value || '';
                sizeFilter = document.getElementById('variantSizeFilter')?.value || '';
            } else if (tableType === 'prices') {
                priceTypeFilter = document.getElementById('priceTypeFilter')?.value || '';
            }
            
            // Filter data
            const filteredData = dataArray.filter(item => {
                // Search filter
                const searchMatch = !searchValue || Object.values(item).some(val => 
                    String(val).toLowerCase().includes(searchValue)
                );
                
                // Specific filters
                let specificMatch = true;
                if (tableType === 'products') {
                    if (categoryFilter && item.category_id != categoryFilter) specificMatch = false;
                    if (typeFilter && item.product_type_id != typeFilter) specificMatch = false;
                } else if (tableType === 'variants') {
                    if (productFilter && item.product_id != productFilter) specificMatch = false;
                    if (colorFilter && item.color_id != colorFilter) specificMatch = false;
                    if (sizeFilter && item.size_id != sizeFilter) specificMatch = false;
                } else if (tableType === 'prices') {
                    if (priceTypeFilter && item.price_type != priceTypeFilter) specificMatch = false;
                }
                
                return searchMatch && specificMatch;
            });
            
            // Re-render with filtered data
            switch(tableType) {
                case 'products':
                    renderProductsWithData(filteredData);
                    break;
                case 'variants':
                    renderVariantsWithData(filteredData);
                    break;
                case 'prices':
                    renderPricesWithData(filteredData);
                    break;
                case 'categories':
                    renderCategoriesWithData(filteredData);
                    break;
                case 'colors':
                    renderColorsWithData(filteredData);
                    break;
                case 'sizes':
                    renderSizesWithData(filteredData);
                    break;
                case 'product-types':
                    renderProductTypesWithData(filteredData);
                    break;
            }
        }

        function clearFilters(tableType) {
            // Clear search box
            const searchBoxes = {
                'products': 'productSearch',
                'variants': 'variantSearch',
                'prices': 'priceSearch',
                'categories': 'categorySearch',
                'colors': 'colorSearch',
                'sizes': 'sizeSearch',
                'product-types': 'typeSearch',
                'locations': 'locationSearch',
                'upcoming': 'upcomingSearch',
                'contacts': 'contactSearch'
            };
            
            const searchId = searchBoxes[tableType];
            if (searchId) {
                const searchBox = document.getElementById(searchId);
                if (searchBox) searchBox.value = '';
            }
            
            // Clear dropdowns based on table type
            if (tableType === 'products') {
                document.getElementById('productCategoryFilter').selectedIndex = 0;
                document.getElementById('productTypeFilter').selectedIndex = 0;
            } else if (tableType === 'variants') {
                document.getElementById('variantProductFilter').selectedIndex = 0;
                document.getElementById('variantColorFilter').selectedIndex = 0;
                document.getElementById('variantSizeFilter').selectedIndex = 0;
            } else if (tableType === 'prices') {
                document.getElementById('priceTypeFilter').selectedIndex = 0;
            }
            else if (tableType === 'projects') {
                document.getElementById('projectCategoryFilter').selectedIndex = 0;
                document.getElementById('projectYearFilter').selectedIndex = 0;
                document.getElementById('projectFeaturedFilter').selectedIndex = 0;
            }
            else if (tableType === 'locations') {
                document.getElementById('locationStatusFilter').selectedIndex = 0;
                document.getElementById('locationProvinceFilter').selectedIndex = 0;
            }
            else if (tableType === 'upcoming') {
                document.getElementById('upcomingStatusFilter').selectedIndex = 0;
                document.getElementById('upcomingActiveFilter').selectedIndex = 0;
            }
            else if (tableType === 'contacts') {
                document.getElementById('contactLocationFilter').selectedIndex = 0;
                document.getElementById('contactPrimaryFilter').selectedIndex = 0;
                document.getElementById('contactActiveFilter').selectedIndex = 0;
            }
            
            filterTable(tableType);
        }

        function sortTable(tableType, column, type) {
            const tableId = `${tableType}Table`;
            const table = document.getElementById(tableId);
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Toggle sort direction
            const key = `${tableType}-${column}`;
            if (!sortState[key]) sortState[key] = 'asc';
            else if (sortState[key] === 'asc') sortState[key] = 'desc';
            else sortState[key] = 'asc';
            
            const direction = sortState[key];
            
            // Update header indicators - find the header with matching data-column attribute
            const headers = table.querySelectorAll('th.sortable');
            headers.forEach(th => {
                th.classList.remove('asc', 'desc');
                const thColumn = parseInt(th.getAttribute('data-column'));
                if (thColumn === column) {
                    th.classList.add(direction);
                }
            });
            
            // Sort rows
            rows.sort((a, b) => {
                const cellA = a.cells[column];
                const cellB = b.cells[column];
                
                if (!cellA || !cellB) return 0;
                
                let valA = cellA.textContent.trim();
                let valB = cellB.textContent.trim();
                
                // Handle different data types
                if (type === 'number') {
                    valA = parseFloat(valA) || 0;
                    valB = parseFloat(valB) || 0;
                } else if (type === 'date') {
                    valA = new Date(valA).getTime() || 0;
                    valB = new Date(valB).getTime() || 0;
                }
                
                if (valA < valB) return direction === 'asc' ? -1 : 1;
                if (valA > valB) return direction === 'asc' ? 1 : -1;
                return 0;
            });
            
            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
        }

        function exportToExcel(tableId, filename) {
            // Build data-driven export: uses in-memory arrays, skips checkbox/image/actions columns
            // Data starts at column B (one blank column A), matching the expected layout
            const date = new Date().toISOString().split('T')[0];
            let rows = [];

            if (tableId === 'productsTable') {
                rows.push(['ID','Product Name','Description','Category','Type','Created At']);
                (filterTable._lastFiltered?.products || products).forEach(p => rows.push([
                    p.product_id, p.product_name, p.description || '', p.category_name || '', p.product_type_name || '',
                    p.created_at ? new Date(p.created_at).toLocaleDateString() : ''
                ]));
            } else if (tableId === 'variantsTable') {
                rows.push(['ID','Product','Color','Size','Sell Unit','Pcs/Box','SKU','Stock','Retail Price','Wholesale Price','Wholesale Min Qty','Created At']);
                (filterTable._lastFiltered?.variants || variants).forEach(v => rows.push([
                    v.variant_id, v.product_name, v.color_name, v.size_name,
                    v.sell_unit || '', v.pieces_per_box != null ? v.pieces_per_box : '',
                    v.sku, v.stock,
                    v.retail_price != null ? parseFloat(v.retail_price) : '',
                    v.wholesale_price != null ? parseFloat(v.wholesale_price) : '',
                    v.wholesale_min_qty || '',
                    v.created_at ? new Date(v.created_at).toLocaleDateString() : ''
                ]));
            } else if (tableId === 'pricesTable') {
                rows.push(['ID','Product','Color','Size','SKU','Price Type','Min Quantity','Price']);
                (filterTable._lastFiltered?.prices || prices).forEach(p => rows.push([
                    p.price_id, p.product_name, p.color_name, p.size_name,
                    p.sku, p.price_type, p.min_quantity, parseFloat(p.price)
                ]));
            } else if (tableId === 'categoriesTable') {
                rows.push(['ID','Category Name','Slug','Description','Created At']);
                (filterTable._lastFiltered?.categories || categories).forEach(c => rows.push([
                    c.category_id, c.category_name, c.slug || '', c.description || '',
                    c.created_at ? new Date(c.created_at).toLocaleDateString() : ''
                ]));
            } else if (tableId === 'colorsTable') {
                rows.push(['ID','Color Name','Hex Code','Created At']);
                (filterTable._lastFiltered?.colors || colors).forEach(c => rows.push([
                    c.color_id, c.color_name, c.hex_code || '',
                    c.created_at ? new Date(c.created_at).toLocaleDateString() : ''
                ]));
            } else if (tableId === 'sizesTable') {
                rows.push(['ID','Size Name','Sell Unit','Pcs/Box','Created At']);
                (filterTable._lastFiltered?.sizes || sizes).forEach(s => rows.push([
                    s.size_id, s.size_name, s.sell_unit || '',
                    s.pieces_per_box != null ? s.pieces_per_box : '',
                    s.created_at ? new Date(s.created_at).toLocaleDateString() : ''
                ]));
            } else if (tableId === 'productTypesTable') {
                rows.push(['ID','Type Name','Created At']);
                (filterTable._lastFiltered?.types || productTypes).forEach(t => rows.push([
                    t.product_type_id, t.product_type_name,
                    t.created_at ? new Date(t.created_at).toLocaleDateString() : ''
                ]));
            } else if (tableId === 'projectsTable') {
                rows.push(['ID','Title','Album','Description','Category','Location','Year','Products Used','Featured','Order','Uploaded']);
                (filterTable._lastFiltered?.projects || projectsData).forEach(p => rows.push([
                    p.id, p.title || '', p.album_name || '', p.description || '',
                    p.category_name || '', p.location_name || '', p.year || '',
                    p.products_used || '', p.is_featured ? 'Yes' : 'No', p.display_order || '',
                    p.created_at ? new Date(p.created_at).toLocaleDateString() : ''
                ]));
            } else if (tableId === 'locationsTable') {
                rows.push(['ID','Location Name','Address','City','Province','Contact','Status','Display Order','Created At']);
                (filterTable._lastFiltered?.locations || locationsData).forEach(l => rows.push([
                    l.location_id, l.location_name, l.address || '', l.city || '',
                    l.province || '', l.contact_number || '', l.status || '',
                    l.display_order || '',
                    l.created_at ? new Date(l.created_at).toLocaleDateString() : ''
                ]));
            } else if (tableId === 'upcomingTable') {
                rows.push(['ID','Branch Name','City','Province','Est. Opening','Status','Description','Visibility','Display Order','Created At']);
                (filterTable._lastFiltered?.upcoming || upcomingData).forEach(u => rows.push([
                    u.upcoming_id, u.branch_name, u.city || '', u.province || '',
                    u.est_opening || '', u.status || '', u.description || '',
                    u.is_active ? 'Active' : 'Hidden', u.display_order || '',
                    u.created_at ? new Date(u.created_at).toLocaleDateString() : ''
                ]));
            } else if (tableId === 'contactsTable') {
                rows.push(['ID','Location','Contact Name','Phone Number','Email','Role','Primary','Display Order','Status','Created At']);
                (filterTable._lastFiltered?.contacts || contactsData).forEach(c => rows.push([
                    c.contact_id, c.location_name || '', c.contact_name, c.phone_number || '',
                    c.email || '', c.role || '', c.is_primary ? 'Yes' : 'No',
                    c.display_order || '', c.is_active ? 'Active' : 'Inactive',
                    c.created_at ? new Date(c.created_at).toLocaleDateString() : ''
                ]));
            } else if (tableId === 'influencersTable') {
                rows.push(['ID','Name','Platform','Description','Reaction URL','Created At']);
                (filterTable._lastFiltered?.influencers || influencersData).forEach(i => rows.push([
                    i.id, i.name, i.platform || '', i.description || '',
                    i.reaction_url || '',
                    i.created_at ? new Date(i.created_at).toLocaleDateString() : ''
                ]));
            } else {
                // Fallback: DOM-based export stripping checkbox + last column (Actions)
                const table = document.getElementById(tableId);
                const domRows = [...table.querySelectorAll('tr')].map(tr =>
                    [...tr.querySelectorAll('th,td')]
                        .filter((_, i) => i !== 0) // skip checkbox col
                        .slice(0, -1)              // skip Actions col
                        .map(cell => cell.innerText.trim())
                );
                const ws = XLSX.utils.aoa_to_sheet([[], ...domRows.map(r => ['', ...r])]);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, filename);
                XLSX.writeFile(wb, `${filename}_${date}.xlsx`);
                return;
            }

            // Build sheet with blank column A (data starts at B1)
            const sheetData = rows.map(r => ['', ...r]);
            const ws = XLSX.utils.aoa_to_sheet(sheetData);

            // Auto-size columns based on content
            const colWidths = sheetData[0].map((_, ci) => ({
                wch: Math.min(60, Math.max(10,
                    ...sheetData.map(r => String(r[ci] ?? '').length)
                ))
            }));
            ws['!cols'] = colWidths;

            // Style header row (row 1): bold
            const headerCols = sheetData[0].length;
            for (let c = 1; c < headerCols; c++) {
                const cellRef = XLSX.utils.encode_cell({ r: 0, c });
                if (ws[cellRef]) {
                    ws[cellRef].s = { font: { bold: true } };
                }
            }

            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, filename.replace(/_/g, ' '));
            XLSX.writeFile(wb, `${filename}_${date}.xlsx`);
        }

        // ============================================
        // PRODUCTS CRUD
        // ============================================
        async function loadProducts() {
            const result = await apiRequest('api_products.php');
            if (result.success) {
                products = result.data || [];
                renderProducts();
                populateProductDropdowns();
            } else {
                showAlert('Failed to load products', 'error');
            }
        }

        function renderProducts() {
            renderProductsWithData(products);
        }

        function renderProductsWithData(data) {
            const tbody = document.getElementById('productsTableBody');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="empty-state">No products found</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(p => `
                <tr>
                    <td class="center"><input type="checkbox" class="cb-products" value="${p.product_id}" onchange="onCheckboxChange('products',${p.product_id},this.checked)"></td>
                    <td class="center">${p.product_id}</td>
                    <td class="truncate-md" title="${p.product_name ? escapeHtml(p.product_name) : ''}">${p.product_name ? escapeHtml(p.product_name) : '-'}</td>
                    <td class="truncate-lg" title="${p.description ? escapeHtml(p.description) : ''}">${p.description ? escapeHtml(p.description) : '-'}</td>
                    <td class="center">${p.category_name ? escapeHtml(p.category_name) : '-'}</td>
                    <td class="center">${p.product_type_name ? escapeHtml(p.product_type_name) : '-'}</td>
                    <td class="center">${p.image_path ? `<img src="${p.image_path}" class="image-preview" style="width:50px;height:50px;object-fit:cover;cursor:pointer;">` : '-'}</td>
                    <td class="center">${p.banner_image ? `<img src="${p.banner_image}" class="image-preview" title="Banner image" style="width:80px;height:36px;object-fit:cover;cursor:pointer;border-radius:4px;">` : '<span style="color:#ccc;font-size:11px;">none</span>'}</td>
                    <td class="center">${p.created_at ? new Date(p.created_at).toLocaleDateString() : '-'}</td>
                    <td class="actions" style="white-space:nowrap;">
                        <button class="btn btn-sm btn-warning" onclick="editProduct(${p.product_id})">Edit</button>
                        <button class="btn btn-sm" style="background:#3b82f6;color:#fff;" onclick="copySingleProduct(${p.product_id})">Copy</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProduct(${p.product_id})">Delete</button>
                    </td>
                </tr>
            `).join('');
            restoreCheckboxes('products');
        }

// ===================== DETAILED BULK COPY (Like Original Products) =====================

function bulkCopy(tableKey) {
    const ids = [...sel[tableKey]];
    if (!ids.length) return;

    let items = [];
    if (tableKey === 'products')           items = products.filter(p => ids.includes(String(p.product_id)));
    else if (tableKey === 'variants')      items = variants.filter(v => ids.includes(String(v.variant_id)));
    else if (tableKey === 'categories')    items = categories.filter(c => ids.includes(String(c.category_id)));
    else if (tableKey === 'colors')        items = colors.filter(c => ids.includes(String(c.color_id)));
    else if (tableKey === 'sizes')         items = sizes.filter(s => ids.includes(String(s.size_id)));
    else if (tableKey === 'types' || tableKey === 'product-types') items = productTypes.filter(t => ids.includes(String(t.product_type_id)));
    else if (tableKey === 'projects')      items = projectsData.filter(p => ids.includes(String(p.id)));
    else if (tableKey === 'locations')     items = locationsData.filter(l => ids.includes(String(l.location_id)));
    else if (tableKey === 'upcoming')      items = upcomingData.filter(u => ids.includes(String(u.upcoming_id)));
    else if (tableKey === 'contacts')      items = contactsData.filter(c => ids.includes(String(c.contact_id)));
    else if (tableKey === 'influencers')   items = influencersData.filter(i => ids.includes(String(i.id)));

    if (items.length === 0) {
        showAlert('No items found.', 'error');
        return;
    }

    currentBulkTable = tableKey;
    currentBulkItems = items;
    openDetailedBulkCopyModal(items, tableKey);
    // NOTE: clearSelection is called inside saveBulkCopies() after a successful save,
    // not here — so the user's selection state is preserved if they close without saving.
}

function copySingleProduct(id) {
    const p = products.find(x => String(x.product_id) === String(id));
    if (!p) return;
    openDetailedBulkCopyModal([p], 'products');
}

function openDetailedBulkCopyModal(items, tableKey) {
    const modal = document.getElementById('bulkCopyModal');
    const title = document.getElementById('bulkCopyTitle');
    const list = document.getElementById('bulkCopyList');

    title.textContent = `Copy ${items.length} ${tableKey.replace('-', ' ').toUpperCase()}`;

    let html = '';

    if (tableKey === 'products') {
        const catOptions = categories.map(c => `<option value="${c.category_id}">${escapeHtml(c.category_name)}</option>`).join('');
        const typeOptions = productTypes.map(t => `<option value="${t.product_type_id}">${escapeHtml(t.product_type_name)}</option>`).join('');

        html = items.map((p, i) => `
            <div class="copy-card" data-index="${i}">
                <h3>Copy of: ${escapeHtml(p.product_name)}</h3>
                <div class="form-group">
                    <label>New Product Name *</label>
                    <input type="text" class="copy-name" required value="${escapeHtml('Copy of ' + p.product_name)}">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea class="copy-desc" rows="3">${escapeHtml(p.description || '')}</textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label>Category</label>
                        <select class="copy-category"><option value="">None</option>${catOptions}</select>
                    </div>
                    <div class="form-group">
                        <label>Product Type</label>
                        <select class="copy-type"><option value="">None</option>${typeOptions}</select>
                    </div>
                </div>
                <div class="form-group">
                    <label>New Image (optional)</label>
                    <input type="file" class="copy-image" accept="image/*">
                    <input type="hidden" class="original-image" value="${p.image_path || ''}">
                </div>
            </div>
        `).join('');
    } 
    else if (tableKey === 'variants') {
        html = items.map((v, i) => `
            <div class="copy-card" data-index="${i}">
                <h3>Copy of: ${escapeHtml(v.product_name || v.sku)} <small style="font-weight:normal;color:#888;font-size:13px;">(${escapeHtml(v.sku)})</small></h3>
                
                <div class="form-group">
                    <label>New SKU *</label>
                    <input type="text" class="copy-sku" required value="${escapeHtml((v.sku || '') + '-COPY')}">
                </div>

                <div style="background:#fff8e1;border:1.5px solid #f0c040;border-radius:6px;padding:10px 14px;margin-bottom:12px;font-size:13px;color:#7a5c00;">
                    ⚠️ <strong>Product + Color + Size must be a unique combination.</strong> You must change at least the <strong>Color</strong> or <strong>Size</strong> from the original.
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label>Product</label>
                        <select class="copy-product">${products.map(p => `<option value="${p.product_id}" ${p.product_id == v.product_id ? 'selected' : ''}>${escapeHtml(p.product_name)}</option>`).join('')}</select>
                    </div>
                    <div class="form-group">
                        <label>Color <span style="color:#c0392b;font-size:11px;">(change if same product+size)</span></label>
                        <select class="copy-color" data-original="${v.color_id}">${colors.map(c => `<option value="${c.color_id}" ${c.color_id == v.color_id ? 'selected' : ''}>${escapeHtml(c.color_name)}</option>`).join('')}</select>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label>Size <span style="color:#c0392b;font-size:11px;">(change if same product+color)</span></label>
                        <select class="copy-size" data-original="${v.size_id}">${sizes.map(s => `<option value="${s.size_id}" ${s.size_id == v.size_id ? 'selected' : ''}>${escapeHtml(s.size_name)}</option>`).join('')}</select>
                    </div>
                    <div class="form-group">
                        <label>Sell Unit</label>
                        <select class="copy-sellunit">
                            <option value="piece" ${v.sell_unit === 'piece' ? 'selected' : ''}>Piece</option>
                            <option value="box" ${v.sell_unit === 'box' ? 'selected' : ''}>Box</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" class="copy-stock" value="${v.stock || 0}">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label>Retail Price</label>
                        <input type="number" step="0.01" class="copy-retail" value="${v.retail_price || ''}">
                    </div>
                    <div class="form-group">
                        <label>Wholesale Price</label>
                        <input type="number" step="0.01" class="copy-wholesale" value="${v.wholesale_price || ''}">
                    </div>
                    <div class="form-group">
                        <label>Wholesale Min Qty</label>
                        <input type="number" class="copy-minqty" value="${v.wholesale_min_qty || 1}">
                    </div>
                </div>

                <div class="form-group">
                    <label>New Image (optional)</label>
                    <input type="file" class="copy-image" accept="image/*">
                    <input type="hidden" class="original-image" value="${v.image_path || ''}">
                </div>
            </div>
        `).join('');
    } 
    else {
        // Simple for other tables
        html = items.map((item, i) => {
            let name = item.product_name || item.category_name || item.color_name || item.size_name || 
                       item.product_type_name || item.title || item.branch_name || item.location_name || 
                       item.contact_name || item.name || 'Item';
            return `
                <div class="copy-card" data-index="${i}">
                    <h3>Copy of: ${escapeHtml(name)}</h3>
                    <div class="form-group">
                        <label>New Name *</label>
                        <input type="text" class="copy-name" required value="Copy of ${escapeHtml(name)}">
                    </div>
                    <div class="form-group">
                        <label>New Image (optional)</label>
                        <input type="file" class="copy-image" accept="image/*">
                        <input type="hidden" class="original-image" value="${item.image_path || item.profile_photo || ''}">
                    </div>
                </div>`;
        }).join('');
    }

    list.innerHTML = html;
    modal.classList.add('active');
}

async function saveBulkCopies() {
    const cards = document.querySelectorAll('#bulkCopyList .copy-card');
    const saveBtn = document.getElementById('bulkCopySaveBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = 'Saving...';

    let ok = 0, fail = 0;

    for (const card of cards) {
        const idx = parseInt(card.dataset.index);
        const original = currentBulkItems[idx];
        let imagePath = card.querySelector('.original-image') ? card.querySelector('.original-image').value : '';

        // New image upload
        const fileInput = card.querySelector('.copy-image');
        if (fileInput && fileInput.files && fileInput.files[0]) {
            const uploadType = (currentBulkTable === 'products' || currentBulkTable === 'variants' || currentBulkTable === 'projects') 
                ? 'parent-image' : 'variant-image';
            const uploaded = await uploadImage(fileInput, uploadType);
            if (uploaded) imagePath = uploaded;
        }

        const payload = { ...original };

        // === NAME / SKU HANDLING + DUPLICATE FIX ===
        if (currentBulkTable === 'variants') {
            // Strip fields not needed for POST
            delete payload.variant_id;
            delete payload.product_name;
            delete payload.color_name;
            delete payload.hex_code;
            delete payload.size_name;
            delete payload.created_at;

            let newSKU = card.querySelector('.copy-sku').value.trim();
            payload.sku = newSKU;
        } else {
            const nameInput = card.querySelector('.copy-name');
            if (nameInput) {
                if (currentBulkTable === 'products') payload.product_name = nameInput.value.trim();
                else if (currentBulkTable === 'categories') payload.category_name = nameInput.value.trim();
                else if (currentBulkTable === 'colors') payload.color_name = nameInput.value.trim();
                else if (currentBulkTable === 'sizes') payload.size_name = nameInput.value.trim();
                else if (currentBulkTable === 'types' || currentBulkTable === 'product-types') payload.product_type_name = nameInput.value.trim();
            }
        }

        // Variant extra fields
        if (currentBulkTable === 'variants') {
            payload.product_id = card.querySelector('.copy-product')?.value || original.product_id;
            payload.color_id   = card.querySelector('.copy-color')?.value || original.color_id;
            payload.size_id    = card.querySelector('.copy-size')?.value || original.size_id;

            // Client-side guard: product+color+size must differ from original
            const sameCombo = String(payload.product_id) === String(original.product_id) &&
                              String(payload.color_id)   === String(original.color_id)   &&
                              String(payload.size_id)    === String(original.size_id);
            if (sameCombo) {
                fail++;
                showAlert(`Copy of "${original.product_name || original.sku}": You must change the Color or Size — the original Product + Color + Size combination already exists.`, 'error');
                continue;
            }
            payload.stock      = parseInt(card.querySelector('.copy-stock')?.value) || 0;
            payload.sell_unit  = card.querySelector('.copy-sellunit')?.value || original.sell_unit;
            const _rv = card.querySelector('.copy-retail')?.value; payload.retail_price = (_rv !== '' && _rv != null) ? parseFloat(_rv) : null;
            const _wv = card.querySelector('.copy-wholesale')?.value; payload.wholesale_price = (_wv !== '' && _wv != null) ? parseFloat(_wv) : null;
            payload.wholesale_min_qty = parseInt(card.querySelector('.copy-minqty')?.value) || 1;
        }

        if (imagePath) {
            if (currentBulkTable === 'influencers') payload.profile_photo = imagePath;
            else payload.image_path = imagePath;
        }

        // Endpoint
        let endpoint = `api_${currentBulkTable}.php`;
        if (currentBulkTable === 'product-types' || currentBulkTable === 'types') endpoint = 'api_product_types.php';
        if (currentBulkTable === 'projects') endpoint = 'api_project_images.php';
        if (currentBulkTable === 'variants') endpoint = 'api_product_variant.php';

        const result = await apiRequest(endpoint, 'POST', payload);

        if (result.success) ok++;
        else {
            fail++;
            // Show specific API error for combo duplicate (comes from PHP with COMBO_DUPLICATE prefix)
            if (result.message && result.message.includes('COMBO_DUPLICATE')) {
                showAlert(`Copy of "${original.product_name || original.sku}": ${result.message.replace('COMBO_DUPLICATE: ', '')}`, 'error');
            }
        }
    }

    saveBtn.disabled = false;
    saveBtn.textContent = '💾 Save All Copies';
    closeBulkCopyModal();

    // Clear the selection now that the copy operation is done
    if (currentBulkTable) clearSelection(currentBulkTable);

    if (ok) showAlert(`${ok} item(s) copied successfully.`, 'success');
    // Note: specific per-item error alerts are already shown above for combo/SKU conflicts

    // Refresh
    if (currentBulkTable === 'products') loadProducts();
    else if (currentBulkTable === 'variants') loadVariants();
    else if (currentBulkTable === 'categories') loadCategories();
    else if (currentBulkTable === 'colors') loadColors();
    else if (currentBulkTable === 'sizes') loadSizes();
    else if (currentBulkTable === 'types' || currentBulkTable === 'product-types') loadProductTypes();
    else if (currentBulkTable === 'projects') loadProjects();
    else if (currentBulkTable === 'locations') loadLocations();
    else if (currentBulkTable === 'upcoming') loadUpcoming();
    else if (currentBulkTable === 'contacts') loadContacts();
    else if (currentBulkTable === 'influencers') loadInfluencers();
}

function closeBulkCopyModal() {
    document.getElementById('bulkCopyModal').classList.remove('active');
}

        function populateProductDropdowns() {
            const selects = ['productCategory', 'variantProduct', 'productCategoryFilter'];
            selects.forEach(selectId => {
                const select = document.getElementById(selectId);
                if (select && selectId === 'productCategory') {
                    select.innerHTML = '<option value="">None</option>' + 
                        categories.map(c => `<option value="${c.category_id}">${c.category_name}</option>`).join('');
                } else if (select && selectId === 'variantProduct') {
                    select.innerHTML = '<option value="">Select Product</option>' + 
                        products.map(p => `<option value="${p.product_id}">${p.product_name}</option>`).join('');
                } else if (select && selectId === 'productCategoryFilter') {
                    select.innerHTML = '<option value="">All Categories</option>' + 
                        categories.map(c => `<option value="${c.category_id}">${c.category_name}</option>`).join('');
                }
            });

            const typeSelect = document.getElementById('productType');
            if (typeSelect) {
                typeSelect.innerHTML = '<option value="">None</option>' + 
                    productTypes.map(t => `<option value="${t.product_type_id}">${t.product_type_name}</option>`).join('');
            }

            const typeFilter = document.getElementById('productTypeFilter');
            if (typeFilter) {
                typeFilter.innerHTML = '<option value="">All Types</option>' + 
                    productTypes.map(t => `<option value="${t.product_type_id}">${t.product_type_name}</option>`).join('');
            }
        }

        function openProductModal(id = null) {
            const modal = document.getElementById('productModal');
            const title = document.getElementById('productModalTitle');
            const form = document.getElementById('productForm');
            
            form.reset();
            // CRITICAL FIX: Clear hidden ID field after reset
            document.getElementById('productId').value = '';
            document.getElementById('productImagePath').value = '';
            document.getElementById('productBannerImagePath').value = '';
            document.getElementById('productBannerPreviewWrap').style.display = 'none';
            
            if (id) {
                const product = products.find(p => p.product_id == id);
                if (product) {
                    title.textContent = 'Edit Product';
                    document.getElementById('productId').value = product.product_id;
                    document.getElementById('productName').value = product.product_name;
                    document.getElementById('productDescription').value = product.description || '';
                    document.getElementById('productCategory').value = product.category_id || '';
                    document.getElementById('productType').value = product.product_type_id || '';
                    document.getElementById('productImagePath').value = product.image_path || '';
                    document.getElementById('productBannerImagePath').value = product.banner_image || '';
                    if (product.banner_image) {
                        document.getElementById('productBannerPreviewImg').src = '../' + product.banner_image;
                        document.getElementById('productBannerPreviewWrap').style.display = 'block';
                    }
                }
            } else {
                title.textContent = 'Add Product';
            }
            
            modal.classList.add('active');
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.remove('active');
        }

        function clearBannerImage() {
            document.getElementById('productBannerImagePath').value = '';
            document.getElementById('productBannerImage').value = '';
            document.getElementById('productBannerPreviewWrap').style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const bannerInput = document.getElementById('productBannerImage');
            if (bannerInput) {
                bannerInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.getElementById('productBannerPreviewImg').src = e.target.result;
                            document.getElementById('productBannerPreviewWrap').style.display = 'block';
                        };
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
        });

        async function saveProduct(event) {
            event.preventDefault();
            
            const submitBtn = document.getElementById('productSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span> Saving...';
            
            // Handle image upload
            const imageFile = document.getElementById('productImage');
            let imagePath = document.getElementById('productImagePath').value;
            
            if (imageFile.files && imageFile.files[0]) {
                const uploadedPath = await uploadImage(imageFile, 'parent-image');
                if (uploadedPath) {
                    imagePath = uploadedPath;
                }
            }

            // Handle banner image upload
            const bannerFile = document.getElementById('productBannerImage');
            let bannerPath = document.getElementById('productBannerImagePath').value;
            
            if (bannerFile.files && bannerFile.files[0]) {
                const uploadedBanner = await uploadImage(bannerFile, 'banner-image');
                if (uploadedBanner) {
                    bannerPath = uploadedBanner;
                }
            }
            
            const id = document.getElementById('productId').value;
            const productData = {
                product_name: document.getElementById('productName').value,
                description: document.getElementById('productDescription').value || null,
                category_id: document.getElementById('productCategory').value || null,
                product_type_id: document.getElementById('productType').value || null,
                image_path: imagePath || null,
                banner_image: bannerPath || null
            };

            let result;
            if (id) {
                productData.product_id = parseInt(id);
                result = await apiRequest('api_products.php', 'PUT', productData);
            } else {
                result = await apiRequest('api_products.php', 'POST', productData);
            }

            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Product';

            if (result.success) {
                showAlert(result.message, 'success');
                closeProductModal();
                loadProducts();
            } else {
                showAlert(result.message || 'Failed to save product', 'error');
            }
        }

        function editProduct(id) {
            openProductModal(id);
        }

        async function deleteProduct(id) {
            if (!confirm('Are you sure you want to delete this product?')) return;
            
            const result = await apiRequest('api_products.php', 'DELETE', { product_id: id });
            
            if (result.success) {
                showAlert(result.message, 'success');
                loadProducts();
            } else {
                showAlert(result.message || 'Failed to delete product', 'error');
            }
        }

        // ============================================
        // VARIANTS CRUD
        // ============================================
        async function loadVariants() {
            const result = await apiRequest('api_product_variant.php');
            if (result.success) {
                variants = result.data || [];
                renderVariants();
                populateVariantDropdowns();
            } else {
                showAlert('Failed to load variants', 'error');
            }
        }

        function renderVariants() {
            renderVariantsWithData(variants);
        }

        function renderVariantsWithData(data) {
            const tbody = document.getElementById('variantsTableBody');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="14" class="empty-state">No variants found</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(v => `
                <tr>
                    <td class="center"><input type="checkbox" class="cb-variants" value="${v.variant_id}" onchange="onCheckboxChange('variants',${v.variant_id},this.checked)"></td>
                    <td class="center">${v.variant_id}</td>
                    <td>${v.product_name ? escapeHtml(v.product_name) : '-'}</td>
                    <td class="center">
                        <span style="display:inline-block;width:20px;height:20px;background-color:${v.hex_code ? escapeHtml(v.hex_code) : '#ccc'};border:1px solid #ccc;border-radius:3px;vertical-align:middle;"></span>
                        ${v.color_name ? escapeHtml(v.color_name) : '-'}
                    </td>
                    <td class="center">${v.size_name ? escapeHtml(v.size_name) : '-'}</td>
                    <td class="center">${v.sell_unit ? escapeHtml(v.sell_unit) : '-'}</td>
                    <td class="center">${v.pieces_per_box != null ? v.pieces_per_box : '-'}</td>
                    <td class="center">${v.sku ? escapeHtml(v.sku) : '-'}</td>
                    <td class="center">${v.stock}</td>
                    <td class="center">₱${v.retail_price ? parseFloat(v.retail_price).toFixed(2) : '-'}</td>
                    <td class="center">₱${v.wholesale_price ? parseFloat(v.wholesale_price).toFixed(2) : '-'}</td>
                    <td class="center">${v.wholesale_min_qty || '-'} pcs</td>
                    <td class="center">${v.image_path ? `<img src="${v.image_path}" class="image-preview" style="width:50px;height:50px;object-fit:cover;cursor:pointer;">` : '-'}</td>
                    <td class="center" style="white-space:nowrap;">
                        <button class="btn btn-sm btn-warning" onclick="editVariant(${v.variant_id})">Edit</button>
                        <button class="btn btn-sm" style="background:#3b82f6;color:#fff;" onclick="(function(id){const item=variants.find(x=>String(x.variant_id)===String(id));if(!item)return;currentBulkTable='variants';currentBulkItems=[item];openDetailedBulkCopyModal([item],'variants');})(${v.variant_id})">Copy</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteVariant(${v.variant_id})">Delete</button>
                    </td>
                </tr>
            `).join('');
            restoreCheckboxes('variants');
        }

        function populateVariantDropdowns() {
            const colorSelect = document.getElementById('variantColor');
            if (colorSelect) {
                colorSelect.innerHTML = '<option value="">Select Color</option>' + 
                    colors.map(c => `<option value="${c.color_id}">${c.color_name}</option>`).join('');
            }

            const sizeSelect = document.getElementById('variantSize');
            if (sizeSelect) {
                sizeSelect.innerHTML = '<option value="">Select Size</option>' + 
                    sizes.map(s => `<option value="${s.size_id}">${s.size_name}</option>`).join('');
            }

            const priceVariantSelect = document.getElementById('priceVariant');
            if (priceVariantSelect) {
                priceVariantSelect.innerHTML = '<option value="">Select Variant</option>' + 
                    variants.map(v => `<option value="${v.variant_id}">${v.product_name} - ${v.color_name} - ${v.size_name} (${v.sku})</option>`).join('');
            }

            // Populate filter dropdowns
            const variantColorFilter = document.getElementById('variantColorFilter');
            if (variantColorFilter) {
                variantColorFilter.innerHTML = '<option value="">All Colors</option>' + 
                    colors.map(c => `<option value="${c.color_id}">${c.color_name}</option>`).join('');
            }

            const variantSizeFilter = document.getElementById('variantSizeFilter');
            if (variantSizeFilter) {
                variantSizeFilter.innerHTML = '<option value="">All Sizes</option>' + 
                    sizes.map(s => `<option value="${s.size_id}">${s.size_name}</option>`).join('');
            }

            const variantProductFilter = document.getElementById('variantProductFilter');
            if (variantProductFilter) {
                variantProductFilter.innerHTML = '<option value="">All Products</option>' + 
                    products.map(p => `<option value="${p.product_id}">${p.product_name}</option>`).join('');
            }
        }

        function openVariantModal(id = null) {
            const modal = document.getElementById('variantModal');
            const title = document.getElementById('variantModalTitle');
            const form = document.getElementById('variantForm');
            
            form.reset();
            // CRITICAL FIX: Clear hidden ID field after reset
            document.getElementById('variantId').value = '';
            document.getElementById('variantImagePath').value = '';
            document.getElementById('retailPrice').value = '';
            document.getElementById('wholesalePrice').value = '';
            document.getElementById('wholesaleMinQty').value = '1';
            
            if (id) {
                const variant = variants.find(v => v.variant_id == id);
                if (variant) {
                    title.textContent = 'Edit Product Variant';
                    document.getElementById('variantId').value = variant.variant_id;
                    document.getElementById('variantProduct').value = variant.product_id;
                    document.getElementById('variantColor').value = variant.color_id;
                    document.getElementById('variantSize').value = variant.size_id;
                    document.getElementById('variantSKU').value = variant.sku;
                    document.getElementById('variantStock').value = variant.stock;
                    document.getElementById('variantImagePath').value = variant.image_path || '';
                    // sell_unit and pieces_per_box are read from the size table (via GET join) — display only
                    document.getElementById('variantSellUnit').value = variant.sell_unit || 'piece';
                    const ppbGroup = document.getElementById('piecesPerBoxGroup');
                    ppbGroup.style.display = variant.sell_unit === 'box' ? 'block' : 'none';
                    document.getElementById('variantPiecesPerBox').value = variant.pieces_per_box != null ? variant.pieces_per_box : '';
                    
                    // Populate price fields
                    document.getElementById('retailPrice').value = variant.retail_price || '';
                    document.getElementById('wholesalePrice').value = variant.wholesale_price || '';
                    document.getElementById('wholesaleMinQty').value = variant.wholesale_min_qty || '1';
                }
            } else {
                title.textContent = 'Add Product Variant';
                document.getElementById('variantSellUnit').value = 'piece';
                document.getElementById('piecesPerBoxGroup').style.display = 'none';
                document.getElementById('variantPiecesPerBox').value = '';
            }
            
            modal.classList.add('active');
        }

        function closeVariantModal() {
            document.getElementById('variantModal').classList.remove('active');
        }

        async function saveVariant(event) {
            event.preventDefault();
            
            const submitBtn = document.getElementById('variantSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span> Saving...';
            
            const imageInput = document.getElementById('variantImage');
            let imagePath = document.getElementById('variantImagePath').value;
            
            if (imageInput.files && imageInput.files[0]) {
                const uploadedPath = await uploadImage(imageInput);
                if (uploadedPath) {
                    imagePath = uploadedPath;
                }
            }
            
            const id = document.getElementById('variantId').value;

            // FIX: parse price fields as null when empty — never send 0
            // "parseFloat('') || 0" was sending 0 to PHP, which caused the
            // upsert to try inserting price=0 and hitting duplicate PK errors.
            const retailVal = document.getElementById('retailPrice').value.trim();
            const wholesaleVal = document.getElementById('wholesalePrice').value.trim();
            const minQtyVal = document.getElementById('wholesaleMinQty').value.trim();

            const variantData = {
                // FIX: guard parseInt so empty select never sends NaN
                product_id: parseInt(document.getElementById('variantProduct').value) || null,
                color_id: parseInt(document.getElementById('variantColor').value) || null,
                size_id: document.getElementById('variantSize').value || null,
                sku: document.getElementById('variantSKU').value.trim() || null,
                stock: parseInt(document.getElementById('variantStock').value) || 0,
                image_path: imagePath || null,
                // FIX: send null (not 0) when field is empty so PHP skips the upsert
                retail_price: retailVal !== '' ? parseFloat(retailVal) : null,
                wholesale_price: wholesaleVal !== '' ? parseFloat(wholesaleVal) : null,
                wholesale_min_qty: minQtyVal !== '' ? parseInt(minQtyVal) : 1,
                // NOTE: sell_unit and pieces_per_box belong to the size table, not product_variant.
                // They are read-only on variants, inherited from the selected size. Not sent to API.
            };

            let result;
            if (id) {
                variantData.variant_id = parseInt(id);
                result = await apiRequest('api_product_variant.php', 'PUT', variantData);
            } else {
                result = await apiRequest('api_product_variant.php', 'POST', variantData);
            }

            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Variant';

            if (result.success) {
                showAlert(result.message, 'success');
                closeVariantModal();
                loadVariants();
                loadPrices(); // Reload prices to reflect changes
            } else {
                showAlert(result.message || 'Failed to save variant', 'error');
            }
        }

        function editVariant(id) {
            openVariantModal(id);
        }

        async function deleteVariant(id) {
            if (!confirm('Are you sure you want to delete this variant?')) return;
            
            const result = await apiRequest('api_product_variant.php', 'DELETE', { variant_id: id });
            
            if (result.success) {
                showAlert(result.message, 'success');
                loadVariants();
            } else {
                showAlert(result.message || 'Failed to delete variant', 'error');
            }
        }

        // ============================================
        // PRICES CRUD
        // ============================================
        async function loadPrices() {
            const result = await apiRequest('api_prices.php');
            if (result.success) {
                prices = result.data || [];
                renderPrices();
            } else {
                showAlert('Failed to load prices', 'error');
            }
        }

        function renderPrices() {
            renderPricesWithData(prices);
        }

        function renderPricesWithData(data) {
            const tbody = document.getElementById('pricesTableBody');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="empty-state">No prices found</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(p => `
                <tr>
                    <td class="center"><input type="checkbox" class="cb-prices" value="${p.price_id}" onchange="onCheckboxChange('prices',${p.price_id},this.checked)"></td>
                    <td class="center">${p.price_id}</td>
                    <td>${p.product_name}</td>
                    <td class="center">${p.color_name}</td>
                    <td class="center">${p.size_name}</td>
                    <td class="center">${p.sku}</td>
                    <td class="center"><span class="badge">${p.price_type}</span></td>
                    <td class="center">${p.min_quantity}</td>
                    <td class="center">₱${parseFloat(p.price).toFixed(2)}</td>
                    <td class="center" style="white-space:nowrap;">
                        <button class="btn btn-sm btn-warning" onclick="editPrice(${p.price_id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deletePrice(${p.price_id})">Delete</button>
                    </td>
                </tr>
            `).join('');
            restoreCheckboxes('prices');
        }

        function openPriceModal(id = null) {
            const modal = document.getElementById('priceModal');
            const title = document.getElementById('priceModalTitle');
            const form = document.getElementById('priceForm');
            
            form.reset();
            // CRITICAL FIX: Clear hidden ID field after reset
            document.getElementById('priceId').value = '';
            
            if (id) {
                const price = prices.find(p => p.price_id == id);
                if (price) {
                    title.textContent = 'Edit Price';
                    document.getElementById('priceId').value = price.price_id;
                    document.getElementById('priceVariant').value = price.variant_id;
                    document.getElementById('priceType').value = price.price_type;
                    document.getElementById('minQuantity').value = price.min_quantity;
                    document.getElementById('priceAmount').value = price.price;
                }
            } else {
                title.textContent = 'Add Price';
            }
            
            modal.classList.add('active');
        }

        function closePriceModal() {
            document.getElementById('priceModal').classList.remove('active');
        }

        async function savePrice(event) {
            event.preventDefault();
            
            const submitBtn = document.getElementById('priceSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span> Saving...';
            
            const id = document.getElementById('priceId').value;
            const priceData = {
                variant_id: parseInt(document.getElementById('priceVariant').value),
                price_type: document.getElementById('priceType').value,
                min_quantity: parseInt(document.getElementById('minQuantity').value) || 1,
                price: parseFloat(document.getElementById('priceAmount').value)
            };

            let result;
            if (id) {
                priceData.price_id = parseInt(id);
                result = await apiRequest('api_prices.php', 'PUT', priceData);
            } else {
                result = await apiRequest('api_prices.php', 'POST', priceData);
            }

            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Price';

            if (result.success) {
                showAlert(result.message, 'success');
                closePriceModal();
                loadPrices();
            } else {
                showAlert(result.message || 'Failed to save price', 'error');
            }
        }

        function editPrice(id) {
            openPriceModal(id);
        }

        async function deletePrice(id) {
            if (!confirm('Are you sure you want to delete this price?')) return;
            
            const result = await apiRequest('api_prices.php', 'DELETE', { price_id: id });
            
            if (result.success) {
                showAlert(result.message, 'success');
                loadPrices();
            } else {
                showAlert(result.message || 'Failed to delete price', 'error');
            }
        }

        // ============================================
        // CATEGORIES CRUD
        // ============================================
        async function loadCategories() {
            const result = await apiRequest('api_categories.php');
            if (result.success) {
                categories = result.data || [];
                renderCategories();
                populateProductDropdowns();
            } else {
                showAlert('Failed to load categories', 'error');
            }
        }

        function renderCategories() {
            renderCategoriesWithData(categories);
        }

        function renderCategoriesWithData(data) {
            const tbody = document.getElementById('categoriesTableBody');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No categories found</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(c => `
                <tr>
                    <td class="center"><input type="checkbox" class="cb-categories" value="${c.category_id}" onchange="onCheckboxChange('categories',${c.category_id},this.checked)"></td>
                    <td class="center">${c.category_id}</td>
                    <td class="center">${c.category_name}</td>
                    <td class="center">${c.slug}</td>
                    <td class="center">${c.description || '-'}</td>
                    <td class="center">${c.created_at ? new Date(c.created_at).toLocaleDateString() : '-'}</td>
                    <td class="center" style="white-space:nowrap;">
                        <button class="btn btn-sm btn-warning" onclick="editCategory(${c.category_id})">Edit</button>
                        <button class="btn btn-sm" style="background:#3b82f6;color:#fff;" onclick="(function(id){const item=categories.find(x=>String(x.category_id)===String(id));if(!item)return;currentBulkTable='categories';currentBulkItems=[item];openDetailedBulkCopyModal([item],'categories');})(${c.category_id})">Copy</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteCategory(${c.category_id})">Delete</button>
                    </td>
                </tr>
            `).join('');
            restoreCheckboxes('categories');
        }

        function openCategoryModal(id = null) {
            const modal = document.getElementById('categoryModal');
            const title = document.getElementById('categoryModalTitle');
            const form = document.getElementById('categoryForm');
            
            form.reset();
            // CRITICAL FIX: Clear hidden ID field after reset
            document.getElementById('categoryId').value = '';
            
            if (id) {
                const category = categories.find(c => c.category_id == id);
                if (category) {
                    title.textContent = 'Edit Category';
                    document.getElementById('categoryId').value = category.category_id;
                    document.getElementById('categoryName').value = category.category_name;
                    document.getElementById('categorySlug').value = category.slug;
                    document.getElementById('categoryDescription').value = category.description || '';
                }
            } else {
                title.textContent = 'Add Category';
            }
            
            modal.classList.add('active');
        }

        function closeCategoryModal() {
            document.getElementById('categoryModal').classList.remove('active');
        }

        async function saveCategory(event) {
            event.preventDefault();
            
            const submitBtn = document.getElementById('categorySubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span> Saving...';
            
            const id = document.getElementById('categoryId').value;
            const categoryData = {
                category_name: document.getElementById('categoryName').value,
                slug: document.getElementById('categorySlug').value || null,
                description: document.getElementById('categoryDescription').value || null
            };

            let result;
            if (id) {
                categoryData.category_id = parseInt(id);
                result = await apiRequest('api_categories.php', 'PUT', categoryData);
            } else {
                result = await apiRequest('api_categories.php', 'POST', categoryData);
            }

            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Category';

            if (result.success) {
                showAlert(result.message, 'success');
                closeCategoryModal();
                loadCategories();
                loadProducts();
            } else {
                showAlert(result.message || 'Failed to save category', 'error');
            }
        }

        function editCategory(id) {
            openCategoryModal(id);
        }

        async function deleteCategory(id) {
            if (!confirm('Are you sure you want to delete this category?')) return;
            
            const result = await apiRequest('api_categories.php', 'DELETE', { category_id: id });
            
            if (result.success) {
                showAlert(result.message, 'success');
                loadCategories();
                loadProducts();
            } else {
                showAlert(result.message || 'Failed to delete category', 'error');
            }
        }

        // ============================================
        // COLORS CRUD
        // ============================================
        async function loadColors() {
            const result = await apiRequest('api_colors.php');
            if (result.success) {
                colors = result.data || [];
                renderColors();
                populateVariantDropdowns();
            } else {
                showAlert('Failed to load colors', 'error');
            }
        }

        function renderColors() {
            renderColorsWithData(colors);
        }

        function renderColorsWithData(data) {
            const tbody = document.getElementById('colorsTableBody');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No colors found</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(c => `
                <tr>
                    <td class="center"><input type="checkbox" class="cb-colors" value="${c.color_id}" onchange="onCheckboxChange('colors',${c.color_id},this.checked)"></td>
                    <td class="center">${c.color_id}</td>
                    <td class="center">${c.color_name}</td>
                    <td class="center">${c.hex_code}</td>
                    <td class="center"><span style="display:inline-block;width:40px;height:40px;background-color:${c.hex_code};border:2px solid #ccc;border-radius:5px;"></span></td>
                    <td class="center" style="white-space:nowrap;">
                        <button class="btn btn-sm btn-warning" onclick="editColor(${c.color_id})">Edit</button>
                        <button class="btn btn-sm" style="background:#3b82f6;color:#fff;" onclick="(function(id){const item=colors.find(x=>String(x.color_id)===String(id));if(!item)return;currentBulkTable='colors';currentBulkItems=[item];openDetailedBulkCopyModal([item],'colors');})(${c.color_id})">Copy</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteColor(${c.color_id})">Delete</button>
                    </td>
                </tr>
            `).join('');
            restoreCheckboxes('colors');
        }

        function openColorModal(id = null) {
            const modal = document.getElementById('colorModal');
            const title = document.getElementById('colorModalTitle');
            const form = document.getElementById('colorForm');
            
            form.reset();
            // CRITICAL FIX: Clear hidden ID field after reset
            document.getElementById('colorId').value = '';
            
            if (id) {
                const color = colors.find(c => c.color_id == id);
                if (color) {
                    title.textContent = 'Edit Color';
                    document.getElementById('colorId').value = color.color_id;
                    document.getElementById('colorName').value = color.color_name;
                    document.getElementById('hexCode').value = color.hex_code;
                    document.getElementById('colorPicker').value = color.hex_code;
                }
            } else {
                title.textContent = 'Add Color';
            }
            
            modal.classList.add('active');
        }

        function closeColorModal() {
            document.getElementById('colorModal').classList.remove('active');
        }

        async function saveColor(event) {
            event.preventDefault();
            
            const submitBtn = document.getElementById('colorSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span> Saving...';
            
            const id = document.getElementById('colorId').value;
            const colorData = {
                color_name: document.getElementById('colorName').value,
                hex_code: document.getElementById('hexCode').value
            };

            let result;
            if (id) {
                colorData.color_id = parseInt(id);
                result = await apiRequest('api_colors.php', 'PUT', colorData);
            } else {
                result = await apiRequest('api_colors.php', 'POST', colorData);
            }

            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Color';

            if (result.success) {
                showAlert(result.message, 'success');
                closeColorModal();
                loadColors();
                loadVariants();
            } else {
                showAlert(result.message || 'Failed to save color', 'error');
            }
        }

        function editColor(id) {
            openColorModal(id);
        }

        async function deleteColor(id) {
            if (!confirm('Are you sure you want to delete this color?')) return;
            
            const result = await apiRequest('api_colors.php', 'DELETE', { color_id: id });
            
            if (result.success) {
                showAlert(result.message, 'success');
                loadColors();
                loadVariants();
            } else {
                showAlert(result.message || 'Failed to delete color', 'error');
            }
        }

        // Sync color picker with hex input
        document.addEventListener('DOMContentLoaded', function() {
            const hexInput = document.getElementById('hexCode');
            const colorPicker = document.getElementById('colorPicker');
            
            if (hexInput && colorPicker) {
                hexInput.addEventListener('input', function() {
                    if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                        colorPicker.value = this.value;
                    }
                });
                
                colorPicker.addEventListener('input', function() {
                    hexInput.value = this.value;
                });
            }
        });

        // ============================================
        // SIZES CRUD
        // ============================================
        async function loadSizes() {
            const result = await apiRequest('api_sizes.php');
            if (result.success) {
                sizes = result.data || [];
                renderSizes();
                populateVariantDropdowns();
            } else {
                showAlert('Failed to load sizes', 'error');
            }
        }

        function renderSizes() {
            renderSizesWithData(sizes);
        }

        function renderSizesWithData(data) {
            const tbody = document.getElementById('sizesTableBody');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No sizes found</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(s => `
                <tr>
                    <td class="center"><input type="checkbox" class="cb-sizes" value="${s.size_id}" onchange="onCheckboxChange('sizes',${s.size_id},this.checked)"></td>
                    <td class="center">${s.size_id}</td>
                    <td class="center">${s.size_name}</td>
                    <td class="center">${s.sell_unit || '-'}</td>
                    <td class="center">${s.pieces_per_box != null ? s.pieces_per_box : '-'}</td>
                    <td class="center" style="white-space:nowrap;">
                        <button class="btn btn-sm btn-warning" onclick="editSize(${s.size_id})">Edit</button>
                        <button class="btn btn-sm" style="background:#3b82f6;color:#fff;" onclick="(function(id){const item=sizes.find(x=>String(x.size_id)===String(id));if(!item)return;currentBulkTable='sizes';currentBulkItems=[item];openDetailedBulkCopyModal([item],'sizes');})(${s.size_id})">Copy</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteSize(${s.size_id})">Delete</button>
                    </td>
                </tr>
            `).join('');
            restoreCheckboxes('sizes');
        }

        function openSizeModal(id = null) {
            const modal = document.getElementById('sizeModal');
            const title = document.getElementById('sizeModalTitle');
            const form = document.getElementById('sizeForm');
            
            form.reset();
            document.getElementById('sizeId').value = '';
            
            if (id) {
                const size = sizes.find(s => s.size_id == id);
                if (size) {
                    title.textContent = 'Edit Size';
                    document.getElementById('sizeId').value = size.size_id;
                    document.getElementById('sizeName').value = size.size_name;
                    document.getElementById('sizeSellUnit').value = size.sell_unit || 'piece';
                    document.getElementById('sizePiecesPerBoxGroup').style.display = size.sell_unit === 'box' ? 'block' : 'none';
                    document.getElementById('sizePiecesPerBox').value = size.pieces_per_box || '';
                }
            } else {
                title.textContent = 'Add Size';
                document.getElementById('sizeSellUnit').value = 'piece';
                document.getElementById('sizePiecesPerBoxGroup').style.display = 'none';
                document.getElementById('sizePiecesPerBox').value = '';
            }
            
            modal.classList.add('active');
        }

        function closeSizeModal() {
            document.getElementById('sizeModal').classList.remove('active');
        }

        async function saveSize(event) {
            event.preventDefault();
            
            const submitBtn = document.getElementById('sizeSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span> Saving...';
            
            const id = document.getElementById('sizeId').value;
            const sizeData = {
                size_name: document.getElementById('sizeName').value,
                sell_unit: document.getElementById('sizeSellUnit').value || 'piece',
                pieces_per_box: document.getElementById('sizeSellUnit').value === 'box'
                    ? (parseInt(document.getElementById('sizePiecesPerBox').value) || null)
                    : null
            };

            let result;
            if (id) {
                sizeData.size_id = parseInt(id);
                result = await apiRequest('api_sizes.php', 'PUT', sizeData);
            } else {
                result = await apiRequest('api_sizes.php', 'POST', sizeData);
            }

            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Size';

            if (result.success) {
                showAlert(result.message, 'success');
                closeSizeModal();
                loadSizes();
                loadVariants();
            } else {
                showAlert(result.message || 'Failed to save size', 'error');
            }
        }

        function editSize(id) {
            openSizeModal(id);
        }

        async function deleteSize(id) {
            if (!confirm('Are you sure you want to delete this size?')) return;
            
            const result = await apiRequest('api_sizes.php', 'DELETE', { size_id: id });
            
            if (result.success) {
                showAlert(result.message, 'success');
                loadSizes();
                loadVariants();
            } else {
                showAlert(result.message || 'Failed to delete size', 'error');
            }
        }

        // ============================================
        // PRODUCT TYPES CRUD
        // ============================================
        async function loadProductTypes() {
            const result = await apiRequest('api_product_types.php');
            if (result.success) {
                productTypes = result.data || [];
                renderProductTypes();
                populateProductDropdowns();
            } else {
                showAlert('Failed to load product types', 'error');
            }
        }

        function renderProductTypes() {
            renderProductTypesWithData(productTypes);
        }

        function renderProductTypesWithData(data) {
            const tbody = document.getElementById('productTypesTableBody');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="empty-state">No product types found</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(t => `
                <tr>
                    <td class="center"><input type="checkbox" class="cb-types" value="${t.product_type_id}" onchange="onCheckboxChange('types',${t.product_type_id},this.checked)"></td>
                    <td class="center">${t.product_type_id}</td>
                    <td class="center">${t.product_type_name}</td>
                    <td class="center" style="white-space:nowrap;">
                        <button class="btn btn-sm btn-warning" onclick="editProductType(${t.product_type_id})">Edit</button>
                        <button class="btn btn-sm" style="background:#3b82f6;color:#fff;" onclick="(function(id){const item=productTypes.find(x=>String(x.product_type_id)===String(id));if(!item)return;currentBulkTable='types';currentBulkItems=[item];openDetailedBulkCopyModal([item],'types');})(${t.product_type_id})">Copy</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteProductType(${t.product_type_id})">Delete</button>
                    </td>
                </tr>
            `).join('');
            restoreCheckboxes('types');
        }

        function openProductTypeModal(id = null) {
            const modal = document.getElementById('productTypeModal');
            const title = document.getElementById('productTypeModalTitle');
            const form = document.getElementById('productTypeForm');
            
            form.reset();
            // CRITICAL FIX: Clear hidden ID field after reset
            document.getElementById('productTypeId').value = '';
            
            if (id) {
                const type = productTypes.find(t => t.product_type_id == id);
                if (type) {
                    title.textContent = 'Edit Product Type';
                    document.getElementById('productTypeId').value = type.product_type_id;
                    document.getElementById('productTypeName').value = type.product_type_name;
                }
            } else {
                title.textContent = 'Add Product Type';
            }
            
            modal.classList.add('active');
        }

        function closeProductTypeModal() {
            document.getElementById('productTypeModal').classList.remove('active');
        }

        async function saveProductType(event) {
            event.preventDefault();
            
            const submitBtn = document.getElementById('typeSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span> Saving...';
            
            const id = document.getElementById('productTypeId').value;
            const typeData = {
                product_type_name: document.getElementById('productTypeName').value
            };

            let result;
            if (id) {
                typeData.product_type_id = parseInt(id);
                result = await apiRequest('api_product_types.php', 'PUT', typeData);
            } else {
                result = await apiRequest('api_product_types.php', 'POST', typeData);
            }

            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Type';

            if (result.success) {
                showAlert(result.message, 'success');
                closeProductTypeModal();
                loadProductTypes();
                loadProducts();
            } else {
                showAlert(result.message || 'Failed to save product type', 'error');
            }
        }

        function editProductType(id) {
            openProductTypeModal(id);
        }

        async function deleteProductType(id) {
            if (!confirm('Are you sure you want to delete this product type?')) return;
            
            const result = await apiRequest('api_product_types.php', 'DELETE', { product_type_id: id });
            
            if (result.success) {
                showAlert(result.message, 'success');
                loadProductTypes();
                loadProducts();
            } else {
                showAlert(result.message || 'Failed to delete product type', 'error');
            }
        }

        async function loadProjects() {
            const result = await apiRequest('api_project_images.php');
            if (result.success) {
                projectsData = result.data || [];
                renderProjects();
                populateProjectYearFilter();
            } else {
                showAlert('Failed to load projects', 'error');
            }
        }

        function renderProjects() {
    renderProjectsWithData(projectsData);
}

function renderProjectsWithData(data) {
    const tbody = document.getElementById('projectsTableBody');
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="14" class="empty-state">No projects found</td></tr>';
        return;
    }
    tbody.innerHTML = data.map(p => `
        <tr>
            <td class="center"><input type="checkbox" class="cb-projects" value="${p.id}" onchange="onCheckboxChange('projects',${p.id},this.checked)"></td>
            <td class="center">${p.id}</td>
            <td class="center">
                ${p.image_path ? `<img src="${p.image_path}" class="image-preview" style="width:80px;height:60px;object-fit:cover;cursor:pointer;border-radius:5px;">` : '<span style="color:#999;">No image</span>'}
            </td>
            <td class="truncate-md" title="${p.title ? escapeHtml(p.title) : ''}"><strong>${p.title}</strong></td>
            <td class="center">
                ${p.album ? `<span class="badge badge-info">${p.album}</span>` : '<span style="color:#999;">-</span>'}
            </td>
            <td class="truncate-lg" title="${p.description ? escapeHtml(p.description) : ''}">${p.description || '-'}</td>
            <td class="center"><span class="badge badge-${p.category}">${p.category}</span></td>
            <td class="center truncate-sm" title="${p.location || ''}">${p.location || '-'}</td>
            <td class="center">${p.year || '-'}</td>
            <td class="truncate-md" title="${p.products_used ? escapeHtml(p.products_used) : ''}">${p.products_used || '-'}</td>
            <td class="center">
                ${p.is_featured == 1 ? '<span class="badge badge-success">★ Featured</span>' : '<span style="color:#999;">-</span>'}
            </td>
            <td class="center">${p.display_order}</td>
            <td class="center">${p.uploaded_at ? new Date(p.uploaded_at).toLocaleDateString() : '-'}</td>
            <td class="actions" style="white-space:nowrap;">
                <button class="btn btn-sm btn-warning" onclick="editProject(${p.id})">Edit</button>
                <button class="btn btn-sm" style="background:#3b82f6;color:#fff;" onclick="(function(id){const item=projectsData.find(x=>String(x.id)===String(id));if(!item)return;currentBulkTable='projects';currentBulkItems=[item];openDetailedBulkCopyModal([item],'projects');})(${p.id})">Copy</button>
                <button class="btn btn-sm btn-danger" onclick="deleteProject(${p.id})">Delete</button>
            </td>
        </tr>
    `).join('');
    restoreCheckboxes('projects');
}


function populateProjectYearFilter() {
    const years = [...new Set(projectsData.map(p => p.year).filter(y => y))].sort((a, b) => b - a);
    const yearFilter = document.getElementById('projectYearFilter');
    yearFilter.innerHTML = '<option value="">All Years</option>' + 
        years.map(y => `<option value="${y}">${y}</option>`).join('');
}

function filterProjects() {
    const searchValue = document.getElementById('projectSearch').value.toLowerCase();
    const categoryFilter = document.getElementById('projectCategoryFilter').value;
    const yearFilter = document.getElementById('projectYearFilter').value;
    const featuredFilter = document.getElementById('projectFeaturedFilter').value;
    
    const filteredData = projectsData.filter(project => {
        const searchMatch = !searchValue || 
            project.title.toLowerCase().includes(searchValue) ||
            (project.description && project.description.toLowerCase().includes(searchValue)) ||
            (project.location && project.location.toLowerCase().includes(searchValue)) ||
            (project.products_used && project.products_used.toLowerCase().includes(searchValue));
        
        const categoryMatch = !categoryFilter || project.category === categoryFilter;
        const yearMatch = !yearFilter || project.year == yearFilter;
        const featuredMatch = !featuredFilter || project.is_featured == featuredFilter;
        
        return searchMatch && categoryMatch && yearMatch && featuredMatch;
    });
    
    renderProjectsWithData(filteredData);
}

function openProjectModal(id = null) {
    const modal = document.getElementById('projectModal');
    const title = document.getElementById('projectModalTitle');
    const form = document.getElementById('projectForm');
    const imagePreview = document.getElementById('projectImagePreview');
    
    form.reset();
    document.getElementById('projectId').value = '';
    document.getElementById('projectImagePath').value = '';
    imagePreview.style.display = 'none';
    
    if (id) {
        const project = projectsData.find(p => p.id == id);
        if (project) {
            title.textContent = 'Edit Project';
            document.getElementById('projectId').value = project.id;
            document.getElementById('projectTitle').value = project.title;
            document.getElementById('projectAlbum').value = project.album || '';
            document.getElementById('projectDescription').value = project.description || '';
            document.getElementById('projectCategory').value = project.category;
            document.getElementById('projectLocation').value = project.location || '';
            document.getElementById('projectCity').value = project.city || '';
            document.getElementById('projectYear').value = project.year || '';
            document.getElementById('projectProducts').value = project.products_used || '';
            document.getElementById('projectFeatured').checked = (project.is_featured == 1);
            document.getElementById('projectDisplayOrder').value = project.display_order;
            document.getElementById('projectImagePath').value = project.image_path || '';
            
            if (project.image_path) {
                imagePreview.style.display = 'block';
                document.getElementById('projectPreviewImg').src = '../' + project.image_path;
            }
        }
        } else {
            title.textContent = 'Add Project';
            const maxOrder = projectsData.length > 0 ? Math.max(...projectsData.map(p => p.display_order)) : 0;
            document.getElementById('projectDisplayOrder').value = maxOrder + 1;
        }
        
        modal.classList.add('active');
    }

    function closeProjectModal() {
        document.getElementById('projectModal').classList.remove('active');
    }

    async function saveProject(event) {
        event.preventDefault();
        
        const submitBtn = document.getElementById('projectSubmitBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading"></span> Saving...';
        
        const imageFile = document.getElementById('projectImage');
        let imagePath = document.getElementById('projectImagePath').value;
        let imageFilename = null;
        
        if (imageFile.files && imageFile.files[0]) {
            const uploadedPath = await uploadImage(imageFile, 'project-image');
            if (uploadedPath) {
                imagePath = uploadedPath;
                imageFilename = imageFile.files[0].name;
            }
        } else if (!imagePath) {
            showAlert('Please select an image for the project', 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Project';
            return;
        }
        
        const id = document.getElementById('projectId').value;
        const projectData = {
            title: document.getElementById('projectTitle').value,
            album: document.getElementById('projectAlbum').value || null,
            description: document.getElementById('projectDescription').value || null,
            category: document.getElementById('projectCategory').value,
            location: document.getElementById('projectLocation').value || null,
            city: document.getElementById('projectCity').value || null,
            year: document.getElementById('projectYear').value || null,
            image_filename: imageFilename || (imagePath ? imagePath.split('/').pop() : null),
            image_path: imagePath,
            products_used: document.getElementById('projectProducts').value || null,
            is_featured: document.getElementById('projectFeatured').checked ? 1 : 0,
            display_order: parseInt(document.getElementById('projectDisplayOrder').value) || 0
        };

        let result;
        if (id) {
            projectData.id = parseInt(id);
            result = await apiRequest('api_project_images.php', 'PUT', projectData);
        } else {
            result = await apiRequest('api_project_images.php', 'POST', projectData);
        }

        submitBtn.disabled = false;
        submitBtn.textContent = 'Save Project';

        if (result.success) {
            showAlert(result.message, 'success');
            closeProjectModal();
            loadProjects();
        } else {
            showAlert(result.message || 'Failed to save project', 'error');
        }
    }

    function editProject(id) {
        openProjectModal(id);
    }

    async function deleteProject(id) {
        if (!confirm('Are you sure you want to delete this project? This action cannot be undone.')) return;
        
        const result = await apiRequest('api_project_images.php', 'DELETE', { id: id });
        
        if (result.success) {
            showAlert(result.message, 'success');
            loadProjects();
        } else {
            showAlert(result.message || 'Failed to delete project', 'error');
        }
    }



        // ============================================
        // WAREHOUSE LOCATIONS MANAGEMENT
        // ============================================
        async function loadLocations() {
            const result = await apiRequest('api_locations.php', 'GET');
            if (result.success) {
                locationsData = result.data;
                renderLocations();
                populateLocationProvinceFilter();
            } else {
                document.getElementById('locationsTableBody').innerHTML = 
                    '<tr><td colspan="10" class="empty-state">Error loading locations</td></tr>';
            }
        }

        function renderLocations() {
            const tbody = document.getElementById('locationsTableBody');
            if (locationsData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="empty-state">No locations found</td></tr>';
                return;
            }

            tbody.innerHTML = locationsData.map(loc => {
                const fullAddress = [loc.address_line1, loc.address_line2, loc.address_line3].filter(Boolean).join(', ');
                return `
                <tr>
                    <td class="center">${loc.location_id}</td>
                    <td class="center truncate-md" title="${loc.location_name ? escapeHtml(loc.location_name) : ''}"><strong>${loc.location_name}</strong></td>
                    <td class="truncate-lg" title="${fullAddress ? escapeHtml(fullAddress) : ''}">${fullAddress}</td>
                    <td class="center">${loc.city}</td>
                    <td class="center">${loc.province || '-'}</td>
                    <td class="center truncate-sm" title="${loc.contact_number || ''}${loc.email ? '\n' + loc.email : ''}">
                        ${loc.contact_number ? `📞 ${loc.contact_number}<br>` : ''}
                        ${loc.email ? `✉️ ${loc.email}` : loc.contact_number ? '' : '-'}
                    </td>
                    <td class="center">
                        <span class="badge ${loc.is_active == 1 ? 'badge-success' : 'badge-danger'}">
                            ${loc.is_active == 1 ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td class="center">${loc.display_order}</td>
                    <td class="actions">
                        <button class="btn btn-warning btn-sm" onclick="editLocation(${loc.location_id})">Edit</button>
                        <button class="btn btn-sm" style="background:#3b82f6;color:#fff;" onclick="(function(id){const item=locationsData.find(x=>String(x.location_id)===String(id));if(!item)return;currentBulkTable='locations';currentBulkItems=[item];openDetailedBulkCopyModal([item],'locations');})(${loc.location_id})">Copy</button>
                        <button class="btn btn-danger btn-sm" onclick="deleteLocation(${loc.location_id})">Delete</button>
                    </td>
                </tr>
            `}).join('');
        }

        function populateLocationProvinceFilter() {
            const provinces = [...new Set(locationsData.map(l => l.province).filter(Boolean))].sort();
            const filter = document.getElementById('locationProvinceFilter');
            filter.innerHTML = '<option value="">All Provinces</option>' + 
                provinces.map(p => `<option value="${p}">${p}</option>`).join('');
        }

        function filterLocations() {
            const searchValue = document.getElementById('locationSearch').value.toLowerCase();
            const statusFilter = document.getElementById('locationStatusFilter').value;
            const provinceFilter = document.getElementById('locationProvinceFilter').value;

            const filteredData = locationsData.filter(loc => {
                const searchMatch = !searchValue || 
                    (loc.location_name && loc.location_name.toLowerCase().includes(searchValue)) ||
                    (loc.city && loc.city.toLowerCase().includes(searchValue)) ||
                    (loc.province && loc.province.toLowerCase().includes(searchValue)) ||
                    (loc.address_line1 && loc.address_line1.toLowerCase().includes(searchValue));
                
                const statusMatch = !statusFilter || loc.is_active == statusFilter;
                const provinceMatch = !provinceFilter || loc.province === provinceFilter;
                
                return searchMatch && statusMatch && provinceMatch;
            });

            renderLocationsWithData(filteredData);
        }

        function renderLocationsWithData(data) {
            const tbody = document.getElementById('locationsTableBody');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="empty-state">No matching locations found</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(loc => {
                const fullAddress = [loc.address_line1, loc.address_line2, loc.address_line3].filter(Boolean).join(', ');
                return `
                <tr>
                    <td class="center"><input type="checkbox" class="cb-locations" value="${loc.location_id}" onchange="onCheckboxChange('locations',${loc.location_id},this.checked)"></td>
                    <td class="center">${loc.location_id}</td>
                    <td class="center truncate-md" title="${loc.location_name ? escapeHtml(loc.location_name) : ''}"><strong>${loc.location_name}</strong></td>
                    <td class="truncate-lg" title="${fullAddress ? escapeHtml(fullAddress) : ''}">${fullAddress}</td>
                    <td class="center">${loc.city}</td>
                    <td class="center">${loc.province || '-'}</td>
                    <td class="center truncate-sm" title="${loc.contact_number || ''}${loc.email ? '\n' + loc.email : ''}">
                        ${loc.contact_number ? `📞 ${loc.contact_number}<br>` : ''}
                        ${loc.email ? `✉️ ${loc.email}` : loc.contact_number ? '' : '-'}
                    </td>
                    <td class="center">
                        <span class="badge ${loc.is_active == 1 ? 'badge-success' : 'badge-danger'}">
                            ${loc.is_active == 1 ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td class="center">${loc.display_order}</td>
                    <td class="actions" style="white-space:nowrap;">
                        <button class="btn btn-warning btn-sm" onclick="editLocation(${loc.location_id})">Edit</button>
                        <button class="btn btn-danger btn-sm" onclick="deleteLocation(${loc.location_id})">Delete</button>
                    </td>
                </tr>
            `}).join('');
            restoreCheckboxes('locations');
        }

        function openLocationModal(id = null) {
            const modal = document.getElementById('locationModal');
            const title = document.getElementById('locationModalTitle');
            const form = document.getElementById('locationForm');
            
            form.reset();
            document.getElementById('locationId').value = '';
            document.getElementById('locationActive').checked = true;
            
            if (id) {
                const location = locationsData.find(l => l.location_id == id);
                if (location) {
                    title.textContent = 'Edit Warehouse Location';
                    document.getElementById('locationId').value = location.location_id;
                    document.getElementById('locationName').value = location.location_name;
                    document.getElementById('locationAddress1').value = location.address_line1;
                    document.getElementById('locationAddress2').value = location.address_line2 || '';
                    document.getElementById('locationAddress3').value = location.address_line3 || '';
                    document.getElementById('locationCity').value = location.city;
                    document.getElementById('locationProvince').value = location.province || '';
                    document.getElementById('locationPostal').value = location.postal_code || '';
                    document.getElementById('locationContact').value = location.contact_number || '';
                    document.getElementById('locationEmail').value = location.email || '';
                    document.getElementById('locationFacebook').value = location.facebook_url || '';
                    document.getElementById('locationGoogleMaps').value = location.google_maps_url || '';
                    document.getElementById('locationLatitude').value = location.latitude || '';
                    document.getElementById('locationLongitude').value = location.longitude || '';
                    document.getElementById('locationHours').value = location.operating_hours || '';
                    document.getElementById('locationNote').value = location.special_note || '';
                    document.getElementById('locationDisplayOrder').value = location.display_order;
                    document.getElementById('locationActive').checked = (location.is_active == 1);
                }
            } else {
                title.textContent = 'Add Warehouse Location';
                const maxOrder = locationsData.length > 0 ? Math.max(...locationsData.map(l => l.display_order)) : 0;
                document.getElementById('locationDisplayOrder').value = maxOrder + 1;
            }
            
            modal.classList.add('active');
        }

        function closeLocationModal() {
            document.getElementById('locationModal').classList.remove('active');
        }

        async function saveLocation(event) {
            event.preventDefault();
            
            const submitBtn = document.getElementById('locationSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span> Saving...';
            
            const id = document.getElementById('locationId').value;
            const locationData = {
                location_name: document.getElementById('locationName').value,
                address_line1: document.getElementById('locationAddress1').value,
                address_line2: document.getElementById('locationAddress2').value || null,
                address_line3: document.getElementById('locationAddress3').value || null,
                city: document.getElementById('locationCity').value,
                province: document.getElementById('locationProvince').value || null,
                postal_code: document.getElementById('locationPostal').value || null,
                contact_number: document.getElementById('locationContact').value || null,
                email: document.getElementById('locationEmail').value || null,
                facebook_url: document.getElementById('locationFacebook').value || null,
                google_maps_url: document.getElementById('locationGoogleMaps').value || null,
                latitude: document.getElementById('locationLatitude').value || null,
                longitude: document.getElementById('locationLongitude').value || null,
                operating_hours: document.getElementById('locationHours').value || null,
                special_note: document.getElementById('locationNote').value || null,
                display_order: parseInt(document.getElementById('locationDisplayOrder').value) || 0,
                is_active: document.getElementById('locationActive').checked ? 1 : 0
            };

            let result;
            if (id) {
                locationData.location_id = parseInt(id);
                result = await apiRequest('api_locations.php', 'PUT', locationData);
            } else {
                result = await apiRequest('api_locations.php', 'POST', locationData);
            }

            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Location';

            if (result.success) {
                showAlert(result.message, 'success');
                closeLocationModal();
                loadLocations();
            } else {
                showAlert(result.message || 'Failed to save location', 'error');
            }
        }

        function editLocation(id) {
            openLocationModal(id);
        }

        async function deleteLocation(id) {
            if (!confirm('Are you sure you want to delete this warehouse location? This action cannot be undone.')) return;
            
            const result = await apiRequest('api_locations.php', 'DELETE', { location_id: id });
            
            if (result.success) {
                showAlert(result.message, 'success');
                loadLocations();
            } else {
                showAlert(result.message || 'Failed to delete location', 'error');
            }
        }

        // ============================================
        // UPCOMING BRANCHES MANAGEMENT
        // ============================================
        async function loadUpcoming() {
            const result = await apiRequest('api_upcoming.php', 'GET');
            if (result.success) {
                upcomingData = result.data;
                renderUpcoming();
            } else {
                document.getElementById('upcomingTableBody').innerHTML = 
                    '<tr><td colspan="12" class="empty-state">Error loading upcoming branches</td></tr>';
            }
        }

        function renderUpcoming() {
            const tbody = document.getElementById('upcomingTableBody');
            if (upcomingData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="11" class="empty-state">No upcoming branches found</td></tr>';
                return;
            }

            tbody.innerHTML = upcomingData.map(branch => `
                <tr>
                    <td class="center">${branch.upcoming_id}</td>
                    <td class="center" style="font-size: 24px;">${branch.icon || '⏳'}</td>
                    <td class="center truncate-md" title="${branch.branch_name ? escapeHtml(branch.branch_name) : ''}"><strong>${branch.branch_name}</strong></td>
                    <td class="center">${branch.city}</td>
                    <td class="center">${branch.province || '-'}</td>
                    <td class="center">${branch.estimated_opening ? new Date(branch.estimated_opening).toLocaleDateString() : '-'}</td>
                    <td class="center">
                        <span class="badge ${
                            branch.status === 'coming_soon' ? 'badge-success' : 
                            branch.status === 'under_construction' ? 'badge-warning' : 
                            'badge-info'
                        }">
                            ${branch.status.replace('_', ' ').toUpperCase()}
                        </span>
                    </td>
                    <td class="truncate-md" title="${branch.description ? escapeHtml(branch.description) : ''}">${branch.description || '-'}</td>
                    <td class="center">
                        <span class="badge ${branch.is_active == 1 ? 'badge-success' : 'badge-danger'}">
                            ${branch.is_active == 1 ? 'Shown' : 'Hidden'}
                        </span>
                    </td>
                    <td class="center">${branch.display_order}</td>
                    <td class="actions">
                        <button class="btn btn-warning btn-sm" onclick="editUpcoming(${branch.upcoming_id})">Edit</button>
                        <button class="btn btn-sm" style="background:#3b82f6;color:#fff;" onclick="(function(id){const item=upcomingData.find(x=>String(x.upcoming_id)===String(id));if(!item)return;currentBulkTable='upcoming';currentBulkItems=[item];openDetailedBulkCopyModal([item],'upcoming');})(${branch.upcoming_id})">Copy</button>
                        <button class="btn btn-danger btn-sm" onclick="deleteUpcoming(${branch.upcoming_id})">Delete</button>
                    </td>
                </tr>
            `).join('');
        }

        function filterUpcoming() {
            const searchValue = document.getElementById('upcomingSearch').value.toLowerCase();
            const statusFilter = document.getElementById('upcomingStatusFilter').value;
            const activeFilter = document.getElementById('upcomingActiveFilter').value;

            const filteredData = upcomingData.filter(branch => {
                const searchMatch = !searchValue || 
                    (branch.branch_name && branch.branch_name.toLowerCase().includes(searchValue)) ||
                    (branch.city && branch.city.toLowerCase().includes(searchValue)) ||
                    (branch.province && branch.province.toLowerCase().includes(searchValue)) ||
                    (branch.description && branch.description.toLowerCase().includes(searchValue));
                
                const statusMatch = !statusFilter || branch.status === statusFilter;
                const activeMatch = !activeFilter || branch.is_active == activeFilter;
                
                return searchMatch && statusMatch && activeMatch;
            });

            renderUpcomingWithData(filteredData);
        }

        function renderUpcomingWithData(data) {
            const tbody = document.getElementById('upcomingTableBody');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="12" class="empty-state">No matching branches found</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(branch => `
                <tr>
                    <td class="center"><input type="checkbox" class="cb-upcoming" value="${branch.upcoming_id}" onchange="onCheckboxChange('upcoming',${branch.upcoming_id},this.checked)"></td>
                    <td class="center">${branch.upcoming_id}</td>
                    <td class="center" style="font-size:24px;">${branch.icon || '⏳'}</td>
                    <td class="center truncate-md" title="${branch.branch_name ? escapeHtml(branch.branch_name) : ''}"><strong>${branch.branch_name}</strong></td>
                    <td class="center">${branch.city}</td>
                    <td class="center">${branch.province || '-'}</td>
                    <td class="center">${branch.estimated_opening ? new Date(branch.estimated_opening).toLocaleDateString() : '-'}</td>
                    <td class="center">
                        <span class="badge ${branch.status === 'coming_soon' ? 'badge-success' : branch.status === 'under_construction' ? 'badge-warning' : 'badge-info'}">
                            ${branch.status.replace('_', ' ').toUpperCase()}
                        </span>
                    </td>
                    <td class="truncate-md" title="${branch.description ? escapeHtml(branch.description) : ''}">${branch.description || '-'}</td>
                    <td class="center">
                        <span class="badge ${branch.is_active == 1 ? 'badge-success' : 'badge-danger'}">
                            ${branch.is_active == 1 ? 'Shown' : 'Hidden'}
                        </span>
                    </td>
                    <td class="center">${branch.display_order}</td>
                    <td class="actions" style="white-space:nowrap;">
                        <button class="btn btn-warning btn-sm" onclick="editUpcoming(${branch.upcoming_id})">Edit</button>
                        <button class="btn btn-danger btn-sm" onclick="deleteUpcoming(${branch.upcoming_id})">Delete</button>
                    </td>
                </tr>
            `).join('');
            restoreCheckboxes('upcoming');
        }

        function openUpcomingModal(id = null) {
            const modal = document.getElementById('upcomingModal');
            const title = document.getElementById('upcomingModalTitle');
            const form = document.getElementById('upcomingForm');
            
            form.reset();
            document.getElementById('upcomingId').value = '';
            document.getElementById('upcomingActive').checked = true;
            document.getElementById('upcomingIcon').value = '⏳';
            
            if (id) {
                const branch = upcomingData.find(b => b.upcoming_id == id);
                if (branch) {
                    title.textContent = 'Edit Upcoming Branch';
                    document.getElementById('upcomingId').value = branch.upcoming_id;
                    document.getElementById('upcomingBranchName').value = branch.branch_name;
                    document.getElementById('upcomingCity').value = branch.city;
                    document.getElementById('upcomingProvince').value = branch.province || '';
                    document.getElementById('upcomingDate').value = branch.estimated_opening || '';
                    document.getElementById('upcomingStatus').value = branch.status;
                    document.getElementById('upcomingDescription').value = branch.description || '';
                    document.getElementById('upcomingIcon').value = branch.icon || '⏳';
                    document.getElementById('upcomingDisplayOrder').value = branch.display_order;
                    document.getElementById('upcomingActive').checked = (branch.is_active == 1);
                }
            } else {
                title.textContent = 'Add Upcoming Branch';
                const maxOrder = upcomingData.length > 0 ? Math.max(...upcomingData.map(b => b.display_order)) : 0;
                document.getElementById('upcomingDisplayOrder').value = maxOrder + 1;
            }
            
            modal.classList.add('active');
        }

        function closeUpcomingModal() {
            document.getElementById('upcomingModal').classList.remove('active');
        }

        async function saveUpcoming(event) {
            event.preventDefault();
            
            const submitBtn = document.getElementById('upcomingSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading"></span> Saving...';
            
            const id = document.getElementById('upcomingId').value;
            const branchData = {
                branch_name: document.getElementById('upcomingBranchName').value,
                city: document.getElementById('upcomingCity').value,
                province: document.getElementById('upcomingProvince').value || null,
                estimated_opening: document.getElementById('upcomingDate').value || null,
                status: document.getElementById('upcomingStatus').value,
                description: document.getElementById('upcomingDescription').value || null,
                icon: document.getElementById('upcomingIcon').value || '⏳',
                display_order: parseInt(document.getElementById('upcomingDisplayOrder').value) || 0,
                is_active: document.getElementById('upcomingActive').checked ? 1 : 0
            };

            let result;
            if (id) {
                branchData.upcoming_id = parseInt(id);
                result = await apiRequest('api_upcoming.php', 'PUT', branchData);
            } else {
                result = await apiRequest('api_upcoming.php', 'POST', branchData);
            }

            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Branch';

            if (result.success) {
                showAlert(result.message, 'success');
                closeUpcomingModal();
                loadUpcoming();
            } else {
                showAlert(result.message || 'Failed to save branch', 'error');
            }
        }

        function editUpcoming(id) {
            openUpcomingModal(id);
        }

        async function deleteUpcoming(id) {
            if (!confirm('Are you sure you want to delete this upcoming branch? This action cannot be undone.')) return;
            
            const result = await apiRequest('api_upcoming.php', 'DELETE', { upcoming_id: id });
            
            if (result.success) {
                showAlert(result.message, 'success');
                loadUpcoming();
            } else {
                showAlert(result.message || 'Failed to delete branch', 'error');
            }
        }

        // ============================================
// LOAD CONTACTS DATA
// ============================================
async function loadContacts() {
    try {
        const result = await apiRequest('api_contacts.php', 'GET');
        if (result.success) {
            contactsData = result.data || [];
            renderContactsTable(contactsData);
            populateContactFilters();
        } else {
            showAlert(result.message || 'Failed to load contacts', 'error');
        }
    } catch (error) {
        console.error('Error loading contacts:', error);
        showAlert('Error loading contacts: ' + error.message, 'error');
    }
}

// ============================================
// RENDER CONTACTS TABLE
// ============================================
function renderContactsTable(data) {
    const tbody = document.getElementById('contactsTableBody');
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="12" class="empty-state">No contacts found</td></tr>';
        return;
    }
    tbody.innerHTML = data.map(contact => `
        <tr>
            <td class="center"><input type="checkbox" class="cb-contacts" value="${contact.contact_id}" onchange="onCheckboxChange('contacts',${contact.contact_id},this.checked)"></td>
            <td class="center">${contact.contact_id}</td>
            <td class="center"><strong>${contact.location_name || 'N/A'}</strong></td>
            <td class="center">${contact.contact_name}</td>
            <td class="center"><a href="tel:${contact.contact_number}">${contact.contact_number}</a></td>
            <td class="center">${contact.contact_email ? `<a href="mailto:${contact.contact_email}">${contact.contact_email}</a>` : '-'}</td>
            <td class="center">${contact.contact_role || '-'}</td>
            <td class="center">
                <span class="badge ${contact.is_primary == 1 ? 'badge-primary' : 'badge-secondary'}">
                    ${contact.is_primary == 1 ? 'Primary' : 'Secondary'}
                </span>
            </td>
            <td class="center">${contact.display_order}</td>
            <td class="center">
                <span class="badge ${contact.is_active == 1 ? 'badge-success' : 'badge-inactive'}">
                    ${contact.is_active == 1 ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td class="center">${formatDateTime(contact.created_at)}</td>
            <td class="center" style="white-space:nowrap;">
                <button class="btn btn-warning btn-sm" onclick="editContact(${contact.contact_id})">Edit</button>
                <button class="btn btn-sm" style="background:#3b82f6;color:#fff;" onclick="(function(id){const item=contactsData.find(x=>String(x.contact_id)===String(id));if(!item)return;currentBulkTable='contacts';currentBulkItems=[item];openDetailedBulkCopyModal([item],'contacts');})(${contact.contact_id})">Copy</button>
                <button class="btn btn-danger btn-sm" onclick="deleteContact(${contact.contact_id})">Delete</button>
            </td>
        </tr>
    `).join('');
    restoreCheckboxes('contacts');
}

// ============================================
// POPULATE CONTACT FILTERS
// ============================================
function populateContactFilters() {
    // Populate location filter
    const locationFilter = document.getElementById('contactLocationFilter');
    const uniqueLocations = [...new Set(contactsData.map(c => c.location_name).filter(Boolean))];
    locationFilter.innerHTML = '<option value="">All Locations</option>' +
        uniqueLocations.map(loc => `<option value="${loc}">${loc}</option>`).join('');
}

// ============================================
// POPULATE CONTACT LOCATION DROPDOWN (FOR MODAL)
// ============================================
async function populateContactLocationDropdown() {
    try {
        // Fetch locations from api_locations.php
        const result = await apiRequest('api_locations.php', 'GET');
        if (result.success) {
            const locations = result.data || [];
            const dropdown = document.getElementById('contactLocation');
            dropdown.innerHTML = '<option value="">Select Location</option>' +
                locations.map(loc => 
                    `<option value="${loc.location_id}">${loc.location_name} - ${loc.address_line1 || ''}</option>`
                ).join('');
        }
    } catch (error) {
        console.error('Error loading locations for dropdown:', error);
    }
}

// ============================================
// OPEN CONTACT MODAL (ADD/EDIT)
// ============================================
function openContactModal() {
    document.getElementById('contactModalTitle').textContent = 'Add Branch Contact';
    document.getElementById('contactForm').reset();
    document.getElementById('contactId').value = '';
    populateContactLocationDropdown(); // Load locations
    document.getElementById('contactModal').style.display = 'block';
}

function closeContactModal() {
    document.getElementById('contactModal').style.display = 'none';
}

// ============================================
// EDIT CONTACT
// ============================================
function editContact(id) {
    const contact = contactsData.find(c => c.contact_id == id);
    if (!contact) {
        showAlert('Contact not found', 'error');
        return;
    }

    document.getElementById('contactModalTitle').textContent = 'Edit Branch Contact';
    document.getElementById('contactId').value = contact.contact_id;
    
    // Populate location dropdown first, then set value
    populateContactLocationDropdown().then(() => {
        document.getElementById('contactLocation').value = contact.location_id;
    });
    
    document.getElementById('contactName').value = contact.contact_name;
    document.getElementById('contactNumber').value = contact.contact_number;
    document.getElementById('contactEmail').value = contact.contact_email || '';
    document.getElementById('contactRole').value = contact.contact_role || '';
    document.getElementById('contactPrimary').value = contact.is_primary;
    document.getElementById('contactOrder').value = contact.display_order;
    document.getElementById('contactActive').value = contact.is_active;

    document.getElementById('contactModal').style.display = 'block';
}

// ============================================
// SAVE CONTACT (ADD OR UPDATE)
// ============================================
async function saveContact(event) {
    event.preventDefault();
    
    const id = document.getElementById('contactId').value;
    const contactData = {
        location_id: parseInt(document.getElementById('contactLocation').value),
        contact_name: document.getElementById('contactName').value.trim(),
        contact_number: document.getElementById('contactNumber').value.trim(),
        contact_email: document.getElementById('contactEmail').value.trim() || null,
        contact_role: document.getElementById('contactRole').value.trim() || null,
        is_primary: parseInt(document.getElementById('contactPrimary').value),
        display_order: parseInt(document.getElementById('contactOrder').value),
        is_active: parseInt(document.getElementById('contactActive').value)
    };

    try {
        let result;
        if (id) {
            // Update existing contact
            contactData.contact_id = parseInt(id);
            result = await apiRequest('api_contacts.php', 'PUT', contactData);
        } else {
            // Add new contact
            result = await apiRequest('api_contacts.php', 'POST', contactData);
        }

        if (result.success) {
            showAlert(result.message || (id ? 'Contact updated successfully' : 'Contact added successfully'), 'success');
            closeContactModal();
            loadContacts(); // Reload data
        } else {
            showAlert(result.message || 'Operation failed', 'error');
        }
    } catch (error) {
        console.error('Error saving contact:', error);
        showAlert('Error: ' + error.message, 'error');
    }
}

// ============================================
// DELETE CONTACT
// ============================================
async function deleteContact(id) {
    const contact = contactsData.find(c => c.contact_id == id);
    if (!confirm(`Delete contact "${contact.contact_name}" from ${contact.location_name}?`)) {
        return;
    }

    try {
        const result = await apiRequest('api_contacts.php', 'DELETE', { contact_id: id });
        if (result.success) {
            showAlert(result.message || 'Contact deleted successfully', 'success');
            loadContacts(); // Reload data
        } else {
            showAlert(result.message || 'Failed to delete contact', 'error');
        }
    } catch (error) {
        console.error('Error deleting contact:', error);
        showAlert('Error: ' + error.message, 'error');
    }
}

// ============================================
// FILTER CONTACTS TABLE
// ============================================
// The existing filterTable() function in index.php should handle this,
// but here's the specific logic for contacts if needed:

function filterContactsTable() {
    const searchTerm = document.getElementById('contactSearch').value.toLowerCase();
    const locationFilter = document.getElementById('contactLocationFilter').value;
    const primaryFilter = document.getElementById('contactPrimaryFilter').value;
    const activeFilter = document.getElementById('contactActiveFilter').value;

    let filtered = contactsData.filter(contact => {
        // Search filter
        const matchesSearch = !searchTerm || 
            contact.contact_name?.toLowerCase().includes(searchTerm) ||
            contact.contact_number?.toLowerCase().includes(searchTerm) ||
            contact.contact_email?.toLowerCase().includes(searchTerm) ||
            contact.location_name?.toLowerCase().includes(searchTerm) ||
            contact.contact_role?.toLowerCase().includes(searchTerm);

        // Location filter
        const matchesLocation = !locationFilter || contact.location_name === locationFilter;

        // Primary filter
        const matchesPrimary = !primaryFilter || contact.is_primary == primaryFilter;

        // Active filter
        const matchesActive = !activeFilter || contact.is_active == activeFilter;

        return matchesSearch && matchesLocation && matchesPrimary && matchesActive;
    });

    renderContactsTable(filtered);
}

// ============================================
// CLEAR CONTACTS FILTERS
// ============================================
function clearContactsFilters() {
    document.getElementById('contactSearch').value = '';
    document.getElementById('contactLocationFilter').value = '';
    document.getElementById('contactPrimaryFilter').value = '';
    document.getElementById('contactActiveFilter').value = '';
    renderContactsTable(contactsData);
}



        // ============================================
        // INFLUENCERS CRUD
        // ============================================
        async function loadInfluencers() {
            const result = await apiRequest('api_influencers.php', 'GET');
            if (result.success) {
                influencersData = result.data || [];
                renderInfluencers();
            } else {
                document.getElementById('influencersTableBody').innerHTML =
                    '<tr><td colspan="9" class="empty-state">Error loading influencers</td></tr>';
            }
        }

        function renderInfluencers() {
            renderInfluencersWithData(influencersData);
        }

        function renderInfluencersWithData(data) {
            const tbody = document.getElementById('influencersTableBody');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="empty-state">No influencers found</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(inf => `
                <tr>
                    <td class="center"><input type="checkbox" class="cb-influencers" value="${inf.id}" onchange="onCheckboxChange('influencers',${inf.id},this.checked)"></td>
                    <td class="center">${inf.id}</td>
                    <td class="center">
                        ${inf.profile_photo
                            ? `<img src="${inf.profile_photo}" class="image-preview" style="width:80px;height:60px;object-fit:cover;border-radius:5px;cursor:pointer;">`
                            : '<span style="color:#999;">No photo</span>'}
                    </td>
                    <td><strong>${escapeHtml(inf.name)}</strong></td>
                    <td class="center"><span class="badge">${inf.platform}</span></td>
                    <td class="truncate-lg" title="${inf.description ? escapeHtml(inf.description) : ''}">${inf.description || '-'}</td>
                    <td class="center">
                        ${inf.reaction_url
                            ? `<a href="${inf.reaction_url}" target="_blank" class="btn btn-sm" style="font-size:11px;">View Post</a>`
                            : '<span style="color:#999;">-</span>'}
                    </td>
                    <td class="center">${inf.created_at ? new Date(inf.created_at).toLocaleDateString() : '-'}</td>
                    <td class="actions" style="white-space:nowrap;">
                        <button class="btn btn-sm btn-warning" onclick="editInfluencer(${inf.id})">Edit</button>
                        <button class="btn btn-sm" style="background:#3b82f6;color:#fff;" onclick="(function(id){const item=influencersData.find(x=>String(x.id)===String(id));if(!item)return;currentBulkTable='influencers';currentBulkItems=[item];openDetailedBulkCopyModal([item],'influencers');})(${inf.id})">Copy</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteInfluencer(${inf.id})">Delete</button>
                    </td>
                </tr>
            `).join('');
            restoreCheckboxes('influencers');
        }

        function filterInfluencers() {
            const search = document.getElementById('influencerSearch').value.toLowerCase();
            const platform = document.getElementById('influencerPlatformFilter').value;
            const filtered = influencersData.filter(inf => {
                const matchSearch = !search ||
                    inf.name.toLowerCase().includes(search) ||
                    (inf.description && inf.description.toLowerCase().includes(search));
                const matchPlatform = !platform || inf.platform === platform;
                return matchSearch && matchPlatform;
            });
            renderInfluencersWithData(filtered);
        }

        function clearInfluencerFilters() {
            document.getElementById('influencerSearch').value = '';
            document.getElementById('influencerPlatformFilter').value = '';
            renderInfluencers();
        }

        function openInfluencerModal(id = null) {
            const form = document.getElementById('influencerForm');
            form.reset();
            document.getElementById('influencerId').value = '';
            document.getElementById('influencerImagePath').value = '';
            document.getElementById('influencerImagePreview').style.display = 'none';

            if (id) {
                const inf = influencersData.find(i => i.id == id);
                if (inf) {
                    document.getElementById('influencerModalTitle').textContent = 'Edit Influencer';
                    document.getElementById('influencerId').value = inf.id;
                    document.getElementById('influencerName').value = inf.name;
                    document.getElementById('influencerPlatform').value = inf.platform;
                    document.getElementById('influencerDescription').value = inf.description || '';
                    document.getElementById('influencerReactionUrl').value = inf.reaction_url || '';
                    document.getElementById('influencerImagePath').value = inf.profile_photo || '';
                    if (inf.profile_photo) {
                        document.getElementById('influencerPreviewImg').src = inf.profile_photo;
                        document.getElementById('influencerImagePreview').style.display = 'block';
                    }
                }
            } else {
                document.getElementById('influencerModalTitle').textContent = 'Add Influencer';
            }

            document.getElementById('influencerModal').classList.add('active');
        }

        function closeInfluencerModal() {
            document.getElementById('influencerModal').classList.remove('active');
        }

        function previewInfluencerImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('influencerPreviewImg').src = e.target.result;
                    document.getElementById('influencerImagePreview').style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        async function saveInfluencer(event) {
    event.preventDefault();
    const submitBtn = document.getElementById('influencerSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="loading"></span> Saving...';

    // ── Upload photo directly to api_influencers.php (expects $_FILES['file']) ──
    const imageFile = document.getElementById('influencerImage');
    let imagePath = document.getElementById('influencerImagePath').value;

    if (imageFile.files && imageFile.files[0]) {
        try {
            const formData = new FormData();
            formData.append('file', imageFile.files[0]); // 'file' matches $_FILES['file']

            const uploadResponse = await fetch('api_influencers.php', {
                method: 'POST',
                body: formData
                // NOTE: Do NOT set Content-Type header — browser sets it with boundary automatically
            });
            const uploadResult = await uploadResponse.json();

            if (uploadResult.success) {
                imagePath = uploadResult.data.path;
            } else {
                showAlert('Photo upload failed: ' + (uploadResult.message || 'Unknown error'), 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Influencer';
                return;
            }
        } catch (err) {
            showAlert('Photo upload error: ' + err.message, 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Influencer';
            return;
        }
    }

    // ── Now save the influencer record via JSON ──
    const id = document.getElementById('influencerId').value;
    const data = {
        name:          document.getElementById('influencerName').value.trim(),
        platform:      document.getElementById('influencerPlatform').value,
        description:   document.getElementById('influencerDescription').value.trim() || null,
        reaction_url:  document.getElementById('influencerReactionUrl').value.trim() || null,
        profile_photo: imagePath || null
    };

    let result;
    if (id) {
        data.id = parseInt(id);
        result = await apiRequest('api_influencers.php', 'PUT', data);
    } else {
        result = await apiRequest('api_influencers.php', 'POST', data);
    }

    submitBtn.disabled = false;
    submitBtn.textContent = 'Save Influencer';

    if (result.success) {
        showAlert(result.message, 'success');
        closeInfluencerModal();
        loadInfluencers();
    } else {
        showAlert(result.message || 'Failed to save influencer', 'error');
    }
}

        function editInfluencer(id) {
            openInfluencerModal(id);
        }

        async function deleteInfluencer(id) {
            const inf = influencersData.find(i => i.id == id);
            if (!confirm(`Delete influencer "${inf.name}"? This cannot be undone.`)) return;
            const result = await apiRequest('api_influencers.php', 'DELETE', { id: id });
            if (result.success) {
                showAlert(result.message, 'success');
                loadInfluencers();
            } else {
                showAlert(result.message || 'Failed to delete influencer', 'error');
            }
        }

        // ============================================
        // INITIALIZE
        // ============================================
        window.onload = function() {
            loadCategories();
            loadColors();
            loadSizes();
            loadProductTypes();
            loadProducts();
            loadVariants();
            loadPrices();
            loadProjects();
            loadLocations();
            loadUpcoming();
            loadContacts();
            loadInfluencers();
        };
    </script>

    <!-- Image Preview Modal Script -->
    <script>
        const modal = document.getElementById('imagePreviewModal');
        const modalImg = document.getElementById('imagePreviewModalImg');

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('image-preview')) {
                modalImg.src = e.target.src;
                modal.style.display = 'flex';
            }
        });

        modal.addEventListener('click', function(e) {
            if (e.target !== modalImg) {
                modal.style.display = 'none';
            }
        });
    </script>
    <script>
// sell_unit and pieces_per_box on variants are inherited from the selected size — not editable.
// When the size dropdown changes, auto-populate these read-only fields from the sizes array.
document.getElementById('variantSize').addEventListener('change', function() {
    const sizeId = parseInt(this.value);
    const size = sizes.find(s => s.size_id === sizeId);
    if (size) {
        document.getElementById('variantSellUnit').value = size.sell_unit || 'piece';
        const ppbGroup = document.getElementById('piecesPerBoxGroup');
        ppbGroup.style.display = size.sell_unit === 'box' ? 'block' : 'none';
        document.getElementById('variantPiecesPerBox').value = size.pieces_per_box != null ? size.pieces_per_box : '';
    } else {
        document.getElementById('variantSellUnit').value = 'piece';
        document.getElementById('piecesPerBoxGroup').style.display = 'none';
        document.getElementById('variantPiecesPerBox').value = '';
    }
});
document.getElementById('sizeSellUnit').addEventListener('change', function() {
    document.getElementById('sizePiecesPerBoxGroup').style.display =
        this.value === 'box' ? 'block' : 'none';
});
</script>

<script>
(function () {
    /* ── Product data from PHP ── */
    var AC_PRODUCTS = <?php echo json_encode(array_values($_ac_list), JSON_UNESCAPED_UNICODE); ?>;
    var AC_MAP = {};
    AC_PRODUCTS.forEach(function (p) { AC_MAP[p.product_id] = p; });
 
    /* ── State ── */
    var acLastCalc    = null;   // most recent calculation result
    var acSaved       = [];     // list of saved estimates
 
    /* ─────────────────────── helpers ─────────────────────── */
    function peso(n) {
        return '₱' + n.toLocaleString('en-PH', { minimumFractionDigits:2, maximumFractionDigits:2 });
    }
    function pesoVal(id) { return Math.max(0, parseFloat(document.getElementById(id).value) || 0); }
 
    function unitFactor(u) {
        u = (u||'mm').toLowerCase().trim();
        if (u==='m')  return 1;
        if (u==='mm') return 0.001;
        if (u==='cm') return 0.01;
        if (u==='in'||u==='inch'||u==='inches') return 0.0254;
        if (u==='ft'||u==='feet')               return 0.3048;
        return 0.001;
    }
    function parseSize(str) {
        if (!str) return null;
        str = str.trim();
        var sqmM = str.match(/^(\d+(?:\.\d+)?)\s*sqm$/i);
        if (sqmM) return parseFloat(sqmM[1]);
        var re = /(\d+(?:\.\d+)?)\s*(mm|cm|m\b|in\b|inch|inches|ft|feet)?/gi;
        var tokens = [], m;
        while ((m = re.exec(str)) !== null) tokens.push({ val:parseFloat(m[1]), unit:m[2]||null });
        if (tokens.length < 2) return null;
        var fb = 'mm';
        for (var i = tokens.length-1; i >= 0; i--) { if (tokens[i].unit) { fb = tokens[i].unit; break; } }
        return tokens[0].val * unitFactor(tokens[0].unit||fb)
             * tokens[1].val * unitFactor(tokens[1].unit||fb);
    }
 
    function acToast(msg, type) {
        // Lightweight toast — reuses existing gwToast if present, else console
        if (window.gwToast) {
            if (type==='error')   gwToast.error('Calculator', msg);
            else if (type==='warn') gwToast.warning('Calculator', msg);
            else                  gwToast.info('Calculator', msg);
            return;
        }
        // Fallback: small inline alert
        var d = document.createElement('div');
        d.textContent = msg;
        d.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);' +
            'background:#1f2937;color:#fff;padding:10px 20px;border-radius:8px;font-size:13px;' +
            'z-index:999999;box-shadow:0 4px 16px rgba(0,0,0,.25);pointer-events:none;';
        document.body.appendChild(d);
        setTimeout(function(){d.remove();},3000);
    }
 
    /* ─────────────────────── modal open/close ─────────────────────── */
    window.openAdminCalc = function () {
        var ov = document.getElementById('adminCalcOverlay');
        ov.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        // re-animate modal
        var modal = document.getElementById('adminCalcModal');
        modal.style.animation = 'none';
        setTimeout(function(){ modal.style.animation = 'acSlideUp .25s cubic-bezier(.4,0,.2,1)'; }, 10);
    };
    window.closeAdminCalc = function () {
        document.getElementById('adminCalcOverlay').style.display = 'none';
        document.body.style.overflow = '';
    };
    window.closeAdminCalcOnOverlay = function (e) {
        if (e.target === document.getElementById('adminCalcOverlay')) closeAdminCalc();
    };
 
    // ESC key to close
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            var ov = document.getElementById('adminCalcOverlay');
            if (ov && ov.style.display !== 'none') closeAdminCalc();
        }
    });
 
    /* ─────────────────────── product/variant dropdowns ─────────────────────── */
    window.acOnProductChange = function () {
        var pid    = document.getElementById('ac_product').value;
        var varSel = document.getElementById('ac_variant');
        varSel.innerHTML = '<option value="">Choose a variant…</option>';
        document.getElementById('ac_result').style.display = 'none';
        acLastCalc = null;
 
        if (!pid || !AC_MAP[pid]) { varSel.disabled = true; return; }
        AC_MAP[pid].variants.forEach(function (v) {
            var opt = document.createElement('option');
            opt.value = v.variant_id;
            opt.textContent = v.label;
            varSel.appendChild(opt);
        });
        varSel.disabled = false;
    };
 
    /* ─────────────────────── calculate ─────────────────────── */
    window.acCalculate = function () {
        var areaSize = parseFloat(document.getElementById('ac_areaSize').value);
        var areaUnit = document.getElementById('ac_areaUnit').value;
        var pid      = document.getElementById('ac_product').value;
        var vid      = document.getElementById('ac_variant').value;
 
        if (!areaSize || areaSize <= 0) { acToast('Enter a valid area size > 0', 'error'); return; }
        if (!pid)                        { acToast('Please select a product', 'warn');   return; }
        if (!vid)                        { acToast('Please select a variant', 'warn');   return; }
 
        var prod    = AC_MAP[pid];
        var variant = null;
        for (var i=0;i<prod.variants.length;i++) {
            if (prod.variants[i].variant_id == vid) { variant = prod.variants[i]; break; }
        }
        if (!variant) { acToast('Variant data not found', 'error'); return; }
 
        // Convert area to sqm
        var areaSqm, unitLabel;
        switch (areaUnit) {
            case 'sqft': areaSqm = areaSize * 0.092903;   unitLabel='ft²'; break;
            case 'sqcm': areaSqm = areaSize / 10000;      unitLabel='cm²'; break;
            case 'sqin': areaSqm = areaSize * 0.00064516; unitLabel='in²'; break;
            default:     areaSqm = areaSize;              unitLabel='m²';
        }
 
        var pieceSqm = parseSize(variant.size_name);
        if (pieceSqm === null || pieceSqm <= 0) {
            acToast('Size "' + variant.size_name + '" has only one dimension — cannot auto-calculate.', 'error');
            return;
        }
 
        var sellUnit  = variant.sell_unit || 'piece';
        var pcsPerBox = variant.pieces_per_box || null;
        var isBox     = (sellUnit === 'box' && pcsPerBox);
 
        var exactPieces, recPieces, boxesToBuy, totalPcsInBoxes;
        if (isBox) {
            exactPieces     = Math.ceil(areaSqm / pieceSqm);
            recPieces       = Math.ceil(exactPieces * 1.1);
            boxesToBuy      = Math.ceil(recPieces / pcsPerBox);
            totalPcsInBoxes = boxesToBuy * pcsPerBox;
        } else {
            exactPieces = Math.ceil(areaSqm / pieceSqm);
            recPieces   = Math.ceil(exactPieces * 1.1);
        }
 
var exactBoxes   = isBox ? Math.ceil(exactPieces / pcsPerBox) : null;
var costUnits    = isBox ? exactBoxes : exactPieces;
var recCostUnits = isBox ? boxesToBuy : recPieces;

        var retailPrice = variant.retail_price;
        var wsPrice     = variant.wholesale_price;
        var wsMin       = variant.wholesale_min_qty;
        var wsEligible  = !!(wsPrice && wsMin && recPieces >= wsMin);
 
        var subtotal, priceType, unitPrice;
        if (wsEligible) {
            subtotal  = costUnits * wsPrice;
            priceType = 'wholesale';
            unitPrice = wsPrice;
        } else if (retailPrice) {
            subtotal  = costUnits * retailPrice;
            priceType = 'retail';
            unitPrice = retailPrice;
        } else {
            subtotal  = 0;
            priceType = 'none';
            unitPrice = 0;
        }
 
        // Store for save + live discount/shipping
        acLastCalc = {
            productName:  prod.product_name,
            variantLabel: variant.label,
            sizeName:     variant.size_name,
            pieceSqm:     pieceSqm,
            areaSize:     areaSize,
            unitLabel:    unitLabel,
            areaSqm:      areaSqm,
            exactPieces:  exactPieces,
            recPieces:    recPieces,
            isBox:        isBox,
            boxesToBuy:   isBox ? boxesToBuy : null,
            totalPcsInBoxes: isBox ? totalPcsInBoxes : null,
            costUnits:    costUnits,
            recCostUnits: recCostUnits,
            costUnitLabel: isBox ? 'box' : 'pcs',
            priceType:    priceType,
            unitPrice:    unitPrice,
            subtotal:     subtotal,
            retailPrice:  retailPrice,
            wsPrice:      wsPrice,
            wsMin:        wsMin,
            wsEligible:   wsEligible,
            id:           Date.now()
        };
 
        acRenderResult();
        document.getElementById('ac_result').style.display = 'block';
 
        // Wholesale tip
        if (!wsEligible && wsPrice && wsMin) {
            var gap = wsMin - recPieces;
            if (gap > 0 && gap <= 10) {
                acToast('Add ' + gap + ' more piece(s) to qualify for wholesale pricing!', 'info');
            }
        }
    };
 
    /* ─────────────────────── render result rows ─────────────────────── */
    function acRenderResult() {
        if (!acLastCalc) return;
        var c = acLastCalc;
 
        var badge = c.priceType === 'wholesale'
            ? '<span class="ac-badge ac-badge-wholesale">Wholesale</span>'
            : c.priceType === 'retail'
                ? '<span class="ac-badge ac-badge-retail">Retail</span>'
                : '';
 
        var rows = [
            ['Product &amp; Variant',  c.productName + ' — ' + c.variantLabel, ''],
            ['Panel Size',             c.sizeName, ''],
            ['Area per Piece',         c.pieceSqm.toFixed(4) + ' m² per piece', ''],
            ['Total Area to Cover',    c.areaSize.toFixed(2) + ' ' + c.unitLabel + (c.unitLabel !== 'm²' ? ' (' + c.areaSqm.toFixed(4) + ' m²)' : ''), ''],
            ['Pieces Needed (exact)',  c.exactPieces + ' pieces', 'green'],
            ['Recommended Qty (+10%)', c.recPieces   + ' pieces', 'green'],
        ];
 
        if (c.isBox) {
            rows.push(['Boxes to Purchase', c.boxesToBuy + ' box' + (c.boxesToBuy>1?'es':'') + ' (' + c.totalPcsInBoxes + ' pcs)', 'green']);
        }
 
        rows.push(['Estimated Cost ' + badge + ' <small style="color:#9ca3af;font-size:11px;font-weight:400;">(exact qty)</small>',
    c.priceType === 'none'
        ? 'No price available'
        : peso(c.subtotal) + ' <small style="color:#9ca3af;font-size:11px;font-weight:500;">(' + c.costUnits + ' ' + c.costUnitLabel + ' × ' + peso(c.unitPrice) + ')</small>',
    c.priceType === 'none' ? 'muted' : 'green'
]);
rows.push(['Estimated Cost ' + badge + ' <small style="color:#9ca3af;font-size:11px;font-weight:400;">(+10% allowance)</small>',
    c.priceType === 'none'
        ? 'No price available'
        : peso(c.recCostUnits * c.unitPrice) + ' <small style="color:#9ca3af;font-size:11px;font-weight:500;">(' + c.recCostUnits + ' ' + c.costUnitLabel + ' × ' + peso(c.unitPrice) + ')</small>',
    c.priceType === 'none' ? 'muted' : 'green'
]);
 
        // Alt row
        if (c.wsEligible && c.retailPrice) {
            rows.push(['Retail reference', peso(c.costUnits * c.retailPrice) + ' (' + c.costUnits + ' ' + c.costUnitLabel + ' × ' + peso(c.retailPrice) + ')', 'muted']);
        } else if (!c.wsEligible && c.wsPrice && c.wsMin) {
            var gap     = c.wsMin - c.recPieces;
            var savings = c.retailPrice ? (c.retailPrice - c.wsPrice) * c.costUnits : null;
            rows.push(['Wholesale available at ' + c.wsMin + ' pcs',
                gap + ' more piece(s) needed' + (savings ? ' — saves ' + peso(savings) : ''), 'muted']);
        }
 
        var html = '';
        rows.forEach(function (r) {
            html += '<div class="ac-result-row">' +
                '<span class="ac-rlabel">'+ r[0] +'</span>' +
                '<span class="ac-rvalue '+ r[2] +'">'+ r[1] +'</span>' +
                '</div>';
        });
        document.getElementById('ac_resultRows').innerHTML = html;
 
        acRenderPriceSummary();
    }
 
    /* ─────────────────────── price summary (live) ─────────────────────── */
    function acRenderPriceSummary() {
        if (!acLastCalc) return;
        var c        = acLastCalc;
        var discount = pesoVal('ac_discount');
        var shipping = pesoVal('ac_shipping');
        var subtotal = c.subtotal;
        var grand    = Math.max(0, subtotal - discount) + shipping;
 
        // Summary detail row
        var badge = c.priceType === 'wholesale'
            ? '<span class="ac-badge ac-badge-wholesale">Wholesale</span>'
            : c.priceType === 'retail'
                ? '<span class="ac-badge ac-badge-retail">Retail</span>'
                : '';
 
        var summaryHtml = '';
        if (c.priceType !== 'none') {
            summaryHtml = '<div class="ac-sum-row" style="margin-bottom:4px;">' +
                '<span style="color:#6b7280;font-size:13px;">' + c.costUnits + ' ' + c.costUnitLabel + ' × ' + peso(c.unitPrice) + badge + '</span>' +
                '<span style="font-weight:600;color:#374151;font-size:13px;">' + peso(subtotal) + '</span>' +
                '</div>';
        } else {
            summaryHtml = '<div style="color:#9ca3af;font-size:13px;">No price available for this variant.</div>';
        }
        document.getElementById('ac_priceSummary').innerHTML = summaryHtml;
 
        // Subtotal
        document.getElementById('ac_subtotalDisplay').textContent = peso(subtotal);
 
        // Discount row
        var discRow = document.getElementById('ac_discountRow');
        if (discount > 0) {
            discRow.style.display = 'flex';
            document.getElementById('ac_discountDisplay').textContent = '−' + peso(discount);
        } else {
            discRow.style.display = 'none';
        }
 
        // Shipping row
        var shipRow = document.getElementById('ac_shippingRow');
        if (shipping > 0) {
            shipRow.style.display = 'flex';
            document.getElementById('ac_shippingDisplay').textContent = '+' + peso(shipping);
        } else {
            shipRow.style.display = 'none';
        }
 
        // Grand total
        document.getElementById('ac_grandTotal').textContent = peso(grand);
    }
    window.acUpdateTotals = function () { acRenderPriceSummary(); };
 
    /* ─────────────────────── save estimate ─────────────────────── */
    window.acSaveEstimate = function () {
        if (!acLastCalc) { acToast('Run a calculation first', 'warn'); return; }
        if (acLastCalc.priceType === 'none') { acToast('No price for this variant — cannot save', 'error'); return; }
 
        var discount = pesoVal('ac_discount');
        var shipping = pesoVal('ac_shipping');
        var grand    = Math.max(0, acLastCalc.subtotal - discount) + shipping;
 
        acSaved.push({
            id:           Date.now(),
            productName:  acLastCalc.productName,
            variantLabel: acLastCalc.variantLabel,
            pieces:       acLastCalc.recPieces,
            boxes:        acLastCalc.isBox ? acLastCalc.boxesToBuy : null,
            subtotal:     acLastCalc.subtotal,
            discount:     discount,
            shipping:     shipping,
            grand:        grand,
            priceType:    acLastCalc.priceType,
        });
 
        acRenderSaved();
        document.getElementById('ac_savedSection').style.display = 'block';
        acToast(acLastCalc.productName + ' saved to estimate list!', 'info');
    };
 
    function acRenderSaved() {
        var container  = document.getElementById('ac_savedItems');
        var totalSub   = 0, totalDisc = 0, totalShip = 0;
 
        if (acSaved.length === 0) {
            container.innerHTML = '<p style="color:#9ca3af;text-align:center;font-size:13px;padding:12px 0;">No saved estimates</p>';
        } else {
            var html = '';
            acSaved.forEach(function (est) {
                var badge = est.priceType === 'wholesale'
                    ? '<span class="ac-badge ac-badge-wholesale">WS</span>'
                    : '<span class="ac-badge ac-badge-retail">Retail</span>';
                var meta = est.pieces + ' pcs' + (est.boxes ? ' / ' + est.boxes + ' box(es)' : '') + badge;
                if (est.discount > 0) meta += ' · Disc: −' + peso(est.discount);
                if (est.shipping > 0) meta += ' · Ship: +' + peso(est.shipping);
 
                html += '<div class="ac-saved-item">' +
                    '<div class="ac-saved-item-info">' +
                        '<div class="ac-saved-item-name">' + est.productName + ' — ' + est.variantLabel + '</div>' +
                        '<div class="ac-saved-item-meta">' + meta + '</div>' +
                    '</div>' +
                    '<div class="ac-saved-item-cost">' + peso(est.grand) + '</div>' +
                    '<button class="ac-saved-remove" onclick="acRemoveSaved(' + est.id + ')" title="Remove">×</button>' +
                '</div>';
 
                totalSub  += est.subtotal;
                totalDisc += est.discount;
                totalShip += est.shipping;
            });
            container.innerHTML = html;
        }
 
        document.getElementById('ac_savedSubtotal').textContent = peso(totalSub);
 
        var discRow = document.getElementById('ac_savedDiscRow');
        if (totalDisc > 0) {
            discRow.style.display = 'flex';
            document.getElementById('ac_savedDiscDisplay').textContent = '−' + peso(totalDisc);
        } else { discRow.style.display = 'none'; }
 
        var shipRow = document.getElementById('ac_savedShipRow');
        if (totalShip > 0) {
            shipRow.style.display = 'flex';
            document.getElementById('ac_savedShipDisplay').textContent = '+' + peso(totalShip);
        } else { shipRow.style.display = 'none'; }
 
        var grandTotal = Math.max(0, totalSub - totalDisc) + totalShip;
        document.getElementById('ac_savedGrand').textContent = peso(grandTotal);
    }
 
    window.acRemoveSaved = function (id) {
        acSaved = acSaved.filter(function (e) { return e.id !== id; });
        acRenderSaved();
        if (acSaved.length === 0) document.getElementById('ac_savedSection').style.display = 'none';
    };
 
    window.acClearAll = function () {
        if (!confirm('Clear all saved estimates?')) return;
        acSaved = [];
        acRenderSaved();
        document.getElementById('ac_savedSection').style.display = 'none';
    };
 
    /* ─────────────────────── reset ─────────────────────── */
    window.acReset = function () {
        document.getElementById('ac_areaSize').value  = '';
        document.getElementById('ac_areaUnit').value  = 'sqm';
        document.getElementById('ac_product').value   = '';
        document.getElementById('ac_discount').value  = '';
        document.getElementById('ac_shipping').value  = '';
        var v = document.getElementById('ac_variant');
        v.innerHTML = '<option value="">Choose a variant…</option>';
        v.disabled  = true;
        document.getElementById('ac_result').style.display = 'none';
        acLastCalc = null;
    };
 
})();
</script>

        <!-- INFLUENCERS TAB -->
        <div id="influencers" class="content">
            <div class="action-bar">
                <div class="search-box">
                    <input type="text" id="influencerSearch" placeholder="Search influencers..." onkeyup="filterInfluencers()">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-success btn-sm" onclick="exportToExcel('influencersTable', 'influencers')">📥 Export</button>
                    <button class="btn btn-primary" onclick="openInfluencerModal()">+ Add Influencer</button>
                </div>
            </div>

            <div class="filter-controls">
                <select id="influencerPlatformFilter" onchange="filterInfluencers()">
                    <option value="">All Platforms</option>
                    <option value="Facebook">Facebook</option>
                    <option value="Instagram">Instagram</option>
                    <option value="TikTok">TikTok</option>
                    <option value="YouTube">YouTube</option>
                    <option value="Twitter">Twitter</option>
                </select>
                <button class="btn btn-sm" onclick="clearInfluencerFilters()">Clear Filters</button>
            </div>

            <div id="influencersBulkBar" style="display:none;align-items:center;gap:10px;padding:10px 14px;background:#fffbe6;border:1.5px solid #f0c040;border-radius:8px;margin-bottom:12px;flex-wrap:wrap;">
                <span id="influencersSelectedCount" style="font-weight:600;font-size:14px;color:#7a5c00;">0 selected</span>
                <button class="btn btn-sm" style="background:#648E37;color:#fff;" onclick="bulkCopy('influencers')">📋 Copy Selected</button>
                <button class="btn btn-sm btn-danger" onclick="bulkDelete('influencers','api_influencers.php','id',loadInfluencers)">🗑 Delete Selected</button>
                <button class="btn btn-sm btn-secondary" onclick="clearSelection('influencers')">✕ Deselect All</button>
            </div>

            <table id="influencersTable">
                <thead>
                    <tr>
                        <th class="center" style="width:36px;"><input type="checkbox" id="selectAll_influencers" onchange="toggleSelectAll('influencers','id',this)"></th>
                        <th class="center sortable" onclick="sortTable('influencers', 1, 'number')">ID</th>
                        <th class="center">Photo</th>
                        <th class="center sortable" onclick="sortTable('influencers', 3, 'string')">Name</th>
                        <th class="center sortable" onclick="sortTable('influencers', 4, 'string')">Platform</th>
                        <th class="center">Description</th>
                        <th class="center">Reaction URL</th>
                        <th class="center sortable" onclick="sortTable('influencers', 7, 'date')">Created At</th>
                        <th class="center">Actions</th>
                    </tr>
                </thead>
                <tbody id="influencersTableBody">
                    <tr><td colspan="9" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- INFLUENCER MODAL -->
        <div id="influencerModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="influencerModalTitle">Add Influencer</h2>
                    <span class="close" onclick="closeInfluencerModal()">&times;</span>
                </div>
                <form id="influencerForm" onsubmit="saveInfluencer(event)">
                    <input type="hidden" id="influencerId">
                    <input type="hidden" id="influencerImagePath">

                    <div class="form-group">
                        <label for="influencerName">Name *</label>
                        <input type="text" id="influencerName" required placeholder="e.g. Maria Santos">
                    </div>

                    <div class="form-group">
                        <label for="influencerPlatform">Platform *</label>
                        <select id="influencerPlatform" required>
                            <option value="Facebook">Facebook</option>
                            <option value="Instagram">Instagram</option>
                            <option value="TikTok">TikTok</option>
                            <option value="YouTube">YouTube</option>
                            <option value="Twitter">Twitter</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="influencerDescription">Description / Quote</label>
                        <textarea id="influencerDescription" rows="3" placeholder="Their reaction about the product..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="influencerReactionUrl">Reaction URL</label>
                        <input type="url" id="influencerReactionUrl" placeholder="https://facebook.com/...">
                        <small>Link to their Facebook post, TikTok video, etc.</small>
                    </div>

                    <div class="form-group">
                        <label for="influencerImage">Profile Photo</label>
                        <input type="file" id="influencerImage" accept="image/*" onchange="previewInfluencerImage(this)">
                        <small>This photo appears as the card image on the website</small>
                        <div id="influencerImagePreview" style="display:none; margin-top:10px;">
                            <img id="influencerPreviewImg" src="" style="max-width:200px; max-height:200px; border-radius:8px; border:2px solid #ddd;">
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeInfluencerModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="influencerSubmitBtn">Save Influencer</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ANNOUNCEMENTS TAB -->
        <div id="announcements" class="content">
            <div class="action-bar">
                <div class="search-box">
                    <input type="text" id="announcementSearch" placeholder="Search announcements..." onkeyup="filterAnnouncements()">
                </div>
                <div style="display:flex;gap:10px;">
                    <button class="btn btn-primary" onclick="openAnnouncementModal()">+ Add Announcement</button>
                </div>
            </div>

            <div id="announcementsBulkBar" style="display:none;align-items:center;gap:10px;padding:10px 14px;background:#fffbe6;border:1.5px solid #f0c040;border-radius:8px;margin-bottom:12px;flex-wrap:wrap;">
                <span id="announcementsSelectedCount" style="font-weight:600;font-size:14px;color:#7a5c00;">0 selected</span>
                <button class="btn btn-sm btn-danger" onclick="bulkDeleteAnnouncements()">🗑 Delete Selected</button>
                <button class="btn btn-sm btn-secondary" onclick="clearAnnouncementSelection()">✕ Deselect All</button>
            </div>

            <table id="announcementsTable">
                <thead>
                    <tr>
                        <th class="center" style="width:36px;"><input type="checkbox" id="selectAll_announcements" onchange="toggleAnnouncementSelectAll(this)"></th>
                        <th class="center">ID</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th class="center">Status</th>
                        <th class="center">Show Once</th>
                        <th class="center">Schedule Start</th>
                        <th class="center">Schedule End</th>
                        <th class="center">Actions</th>
                    </tr>
                </thead>
                <tbody id="announcementsTableBody">
                    <tr><td colspan="9" class="empty-state">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- ANNOUNCEMENT MODAL -->
        <div id="announcementModal" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:600px;">
                <div class="modal-header">
                    <h2 id="announcementModalTitle">Add Announcement</h2>
                    <span class="close" onclick="closeAnnouncementModal()">&times;</span>
                </div>
                <div style="padding:24px;display:flex;flex-direction:column;gap:16px;">
                    <input type="hidden" id="announcementId">

                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" id="announcementTitle" placeholder="e.g. Summer Sale 2026" style="width:100%;padding:10px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;">
                    </div>

                    <div class="form-group">
                        <label>Message *</label>
                        <textarea id="announcementMessage" rows="3" placeholder="Popup body text..." style="width:100%;padding:10px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;resize:vertical;"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Banner Image <small>(optional — upload or paste URL)</small></label>
                        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                            <input type="text" id="announcementImage" placeholder="Will be filled automatically after upload..." style="flex:1;min-width:180px;padding:10px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;">
                            <label for="annImgUpload" style="cursor:pointer;background:#648E37;color:#fff;padding:9px 14px;border-radius:8px;font-size:13px;font-weight:600;white-space:nowrap;user-select:none;">📁 Upload</label>
                            <input type="file" id="annImgUpload" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" onchange="uploadAnnImage(this)">
                        </div>
                        <div id="annImgStatus" style="font-size:12px;margin-top:4px;color:#6b7280;"></div>
                        <div id="annImgPreviewWrap" style="display:none;margin-top:8px;">
                            <img id="annImgPreview" src="" style="max-width:100%;max-height:160px;border-radius:8px;border:1.5px solid #e5e7eb;object-fit:cover;">
                            <button type="button" onclick="clearAnnImage()" style="display:block;margin-top:4px;font-size:12px;color:#dc2626;background:none;border:none;cursor:pointer;">✕ Remove image</button>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label>CTA Button Label <small>(optional)</small></label>
                            <input type="text" id="announcementBtnLabel" placeholder="e.g. Shop Now" style="width:100%;padding:10px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;">
                        </div>
                        <div class="form-group">
                            <label>CTA Button URL <small>(optional)</small></label>
                            <input type="text" id="announcementBtnUrl" placeholder="/catalog.php" style="width:100%;padding:10px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label>Popup Background Color</label>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <input type="color" id="announcementBgColor" value="#ffffff" style="width:44px;height:38px;border:1.5px solid #d1d5db;border-radius:6px;cursor:pointer;">
                                <input type="text" id="announcementBgColorHex" value="#ffffff" placeholder="#ffffff" style="flex:1;padding:10px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;" oninput="document.getElementById('announcementBgColor').value=this.value">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Display Delay (ms)</label>
                            <input type="number" id="announcementDelay" value="1000" min="0" style="width:100%;padding:10px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                        <div class="form-group">
                            <label>Schedule Start <small>(leave blank = immediate)</small></label>
                            <input type="datetime-local" id="announcementStart" style="width:100%;padding:10px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;">
                        </div>
                        <div class="form-group">
                            <label>Schedule End <small>(leave blank = no expiry)</small></label>
                            <input type="datetime-local" id="announcementEnd" style="width:100%;padding:10px;border:1.5px solid #d1d5db;border-radius:8px;font-size:14px;">
                        </div>
                    </div>

                    <div style="display:flex;gap:24px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" id="announcementActive" checked style="width:16px;height:16px;">
                            <span style="font-size:14px;font-weight:600;">Active</span>
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                            <input type="checkbox" id="announcementShowOnce" style="width:16px;height:16px;">
                            <span style="font-size:14px;font-weight:600;">Show once per visitor (cookie)</span>
                        </label>
                    </div>

                    <div class="modal-actions" style="display:flex;justify-content:flex-end;gap:10px;margin-top:4px;">
                        <button class="btn btn-secondary" onclick="closeAnnouncementModal()">Cancel</button>
                        <button class="btn btn-primary" onclick="saveAnnouncement()">Save Announcement</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // ── Announcements ─────────────────────────────────────────────────────
        let announcementsData = [];
        let selectedAnnouncements = new Set();

        async function uploadAnnImage(input) {
            const file = input.files[0];
            if (!file) return;
            const status  = document.getElementById('annImgStatus');
            const preview = document.getElementById('annImgPreviewWrap');
            status.style.color = '#6b7280';
            status.textContent = '⏳ Uploading...';
            const fd = new FormData();
            fd.append('action', 'upload_image');
            fd.append('image', file);
            try {
                const res  = await fetch('api_announcements.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('announcementImage').value = data.path;
                    document.getElementById('annImgPreview').src = data.path;
                    preview.style.display = 'block';
                    status.style.color = '#15803d';
                    status.textContent = '✔ Uploaded successfully.';
                } else {
                    status.style.color = '#dc2626';
                    status.textContent = '✖ ' + (data.message || 'Upload failed.');
                }
            } catch(e) {
                status.style.color = '#dc2626';
                status.textContent = '✖ Network error during upload.';
            }
            input.value = ''; // reset so same file can be re-selected
        }

        function clearAnnImage() {
            document.getElementById('announcementImage').value = '';
            document.getElementById('annImgPreview').src = '';
            document.getElementById('annImgPreviewWrap').style.display = 'none';
            document.getElementById('annImgStatus').textContent = '';
        }

        async function loadAnnouncements() {
            const result = await apiRequest('api_announcements.php', 'GET');
            if (result && result.success) {
                announcementsData = result.data || [];
                renderAnnouncements(announcementsData);
            }
        }

        function renderAnnouncements(data) {
            const tbody = document.getElementById('announcementsTableBody');
            if (!data.length) {
                tbody.innerHTML = '<tr><td colspan="9" class="empty-state">No announcements found</td></tr>';
                return;
            }
            tbody.innerHTML = data.map(a => {
                const statusBadge = a.is_active == 1
                    ? '<span style="background:#dcfce7;color:#15803d;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;">Active</span>'
                    : '<span style="background:#fee2e2;color:#dc2626;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;">Inactive</span>';
                const start = a.schedule_start ? a.schedule_start.replace('T', ' ').substring(0,16) : '<em style="color:#9ca3af">Immediate</em>';
                const end   = a.schedule_end   ? a.schedule_end.replace('T', ' ').substring(0,16)   : '<em style="color:#9ca3af">No expiry</em>';
                const once  = a.show_once == 1 ? '✔' : '—';
                return `<tr>
                    <td class="center"><input type="checkbox" class="cb-announcements" value="${a.id}" onchange="onAnnouncementCheck(${a.id},this.checked)"></td>
                    <td class="center">${a.id}</td>
                    <td><strong>${escHtml(a.title)}</strong></td>
                    <td style="max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(a.message)}</td>
                    <td class="center">${statusBadge}</td>
                    <td class="center">${once}</td>
                    <td class="center" style="font-size:12px;">${start}</td>
                    <td class="center" style="font-size:12px;">${end}</td>
                    <td class="center">
                        <button class="btn btn-sm btn-secondary" onclick="editAnnouncement(${a.id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteAnnouncement(${a.id})">Delete</button>
                    </td>
                </tr>`;
            }).join('');
        }

        function escHtml(str) {
            return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        function filterAnnouncements() {
            const q = document.getElementById('announcementSearch').value.toLowerCase();
            renderAnnouncements(announcementsData.filter(a =>
                a.title.toLowerCase().includes(q) || a.message.toLowerCase().includes(q)
            ));
        }

        function openAnnouncementModal(a = null) {
            document.getElementById('announcementModalTitle').textContent = a ? 'Edit Announcement' : 'Add Announcement';
            document.getElementById('announcementId').value        = a ? a.id : '';
            document.getElementById('announcementTitle').value     = a ? a.title : '';
            document.getElementById('announcementMessage').value   = a ? a.message : '';
            document.getElementById('announcementImage').value     = a ? (a.image_url || '') : '';
            // Show image preview if editing and image exists
            const prevWrap = document.getElementById('annImgPreviewWrap');
            const prevImg  = document.getElementById('annImgPreview');
            const prevStat = document.getElementById('annImgStatus');
            if (a && a.image_url) {
                prevImg.src = a.image_url;
                prevWrap.style.display = 'block';
                prevStat.textContent = '';
            } else {
                prevImg.src = '';
                prevWrap.style.display = 'none';
                prevStat.textContent = '';
            }
            document.getElementById('announcementBtnLabel').value  = a ? (a.button_label || '') : '';
            document.getElementById('announcementBtnUrl').value    = a ? (a.button_url || '') : '';
            document.getElementById('announcementBgColor').value   = a ? (a.bg_color || '#ffffff') : '#ffffff';
            document.getElementById('announcementBgColorHex').value= a ? (a.bg_color || '#ffffff') : '#ffffff';
            document.getElementById('announcementDelay').value     = a ? (a.display_delay ?? 1000) : 1000;
            document.getElementById('announcementStart').value     = a && a.schedule_start ? a.schedule_start.replace(' ','T').substring(0,16) : '';
            document.getElementById('announcementEnd').value       = a && a.schedule_end   ? a.schedule_end.replace(' ','T').substring(0,16)   : '';
            document.getElementById('announcementActive').checked  = a ? a.is_active == 1 : true;
            document.getElementById('announcementShowOnce').checked= a ? a.show_once == 1 : false;
            document.getElementById('announcementModal').style.display = 'flex';
        }

        function closeAnnouncementModal() {
            document.getElementById('announcementModal').style.display = 'none';
        }

        function editAnnouncement(id) {
            const a = announcementsData.find(x => x.id == id);
            if (a) openAnnouncementModal(a);
        }

        async function saveAnnouncement() {
            const id = document.getElementById('announcementId').value;
            // Convert datetime-local "2026-04-27T08:49" → MySQL "2026-04-27 08:49:00"
            function toMysqlDatetime(val) {
                if (!val) return null;
                return val.replace('T', ' ') + (val.length === 16 ? ':00' : '');
            }
            const payload = {
                id:             id ? parseInt(id) : undefined,
                title:          document.getElementById('announcementTitle').value.trim(),
                message:        document.getElementById('announcementMessage').value.trim(),
                image_url:      document.getElementById('announcementImage').value.trim(),
                button_label:   document.getElementById('announcementBtnLabel').value.trim(),
                button_url:     document.getElementById('announcementBtnUrl').value.trim(),
                bg_color:       document.getElementById('announcementBgColorHex').value.trim() || document.getElementById('announcementBgColor').value,
                display_delay:  parseInt(document.getElementById('announcementDelay').value) || 1000,
                schedule_start: toMysqlDatetime(document.getElementById('announcementStart').value),
                schedule_end:   toMysqlDatetime(document.getElementById('announcementEnd').value),
                is_active:      document.getElementById('announcementActive').checked ? 1 : 0,
                show_once:      document.getElementById('announcementShowOnce').checked ? 1 : 0,
            };
            if (!payload.title || !payload.message) { alert('Title and message are required.'); return; }
            const method = id ? 'PUT' : 'POST';
            const result = await apiRequest('api_announcements.php', method, payload);
            if (result && result.success) {
                closeAnnouncementModal();
                loadAnnouncements();
                showToast(id ? 'Announcement updated.' : 'Announcement created.');
            } else {
                alert(result?.message || 'Failed to save.');
            }
        }

        async function deleteAnnouncement(id) {
            if (!confirm('Delete this announcement?')) return;
            const result = await apiRequest('api_announcements.php', 'DELETE', { ids: [id] });
            if (result && result.success) { loadAnnouncements(); showToast('Deleted.'); }
        }

        function onAnnouncementCheck(id, checked) {
            checked ? selectedAnnouncements.add(id) : selectedAnnouncements.delete(id);
            const bar = document.getElementById('announcementsBulkBar');
            bar.style.display = selectedAnnouncements.size ? 'flex' : 'none';
            document.getElementById('announcementsSelectedCount').textContent = selectedAnnouncements.size + ' selected';
        }

        function toggleAnnouncementSelectAll(cb) {
            document.querySelectorAll('.cb-announcements').forEach(c => {
                c.checked = cb.checked;
                onAnnouncementCheck(parseInt(c.value), cb.checked);
            });
        }

        function clearAnnouncementSelection() {
            selectedAnnouncements.clear();
            document.querySelectorAll('.cb-announcements').forEach(c => c.checked = false);
            document.getElementById('selectAll_announcements').checked = false;
            document.getElementById('announcementsBulkBar').style.display = 'none';
        }

        async function bulkDeleteAnnouncements() {
            if (!selectedAnnouncements.size || !confirm('Delete selected announcements?')) return;
            const result = await apiRequest('api_announcements.php', 'DELETE', { ids: [...selectedAnnouncements] });
            if (result && result.success) { clearAnnouncementSelection(); loadAnnouncements(); showToast('Deleted.'); }
        }

        // Sync color picker → hex input
        document.getElementById('announcementBgColor').addEventListener('input', function() {
            document.getElementById('announcementBgColorHex').value = this.value;
        });

        // Load on tab open
        const _origShowTab = typeof showTab === 'function' ? showTab : null;
        // Hook into existing showTab — patch loadAnnouncements on tab switch
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(btn => {
                if (btn.textContent.includes('Announcements')) {
                    btn.addEventListener('click', () => setTimeout(loadAnnouncements, 50));
                }
            });
        });
        </script>

</body>
</html>