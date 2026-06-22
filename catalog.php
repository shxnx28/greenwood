<?php
require_once 'admin/db.php';

// Fetch categories for filter
$categories = [];
$sql = "SELECT category_id, category_name, slug FROM category ORDER BY category_name ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Fetch product types for filter
$product_types = [];
$sql = "SELECT product_type_id, product_type_name FROM product_type ORDER BY product_type_name ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $product_types[] = $row;
    }
}

// Fetch all products with their variants, colors, sizes, and prices
$sql = "SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.image_path as parent_image_path,
            cat.category_id,
            cat.category_name,
            cat.slug AS category_slug,
            pt.product_type_name,
            pt.product_type_id,
            pv.variant_id,
            pv.image_path,
            pv.sku,
            pv.stock,
            c.color_name,
            c.hex_code,
            s.size_name,
            s.sell_unit,
            s.pieces_per_box,
            MIN(CASE WHEN pr.price_type = 'retail' THEN pr.price END) as retail_price,
            MIN(CASE WHEN pr.price_type = 'wholesale' THEN pr.price END) as wholesale_price,
            MIN(CASE WHEN pr.price_type = 'wholesale' THEN pr.min_quantity END) as wholesale_min_qty,
            GROUP_CONCAT(DISTINCT c.color_name ORDER BY c.color_name SEPARATOR ', ') as all_colors,
            GROUP_CONCAT(DISTINCT s.size_name ORDER BY s.size_name SEPARATOR ', ') as all_sizes
        FROM product p
        LEFT JOIN category cat ON p.category_id = cat.category_id
        LEFT JOIN product_type pt ON p.product_type_id = pt.product_type_id
        LEFT JOIN product_variant pv ON p.product_id = pv.product_id
        LEFT JOIN color c ON pv.color_id = c.color_id
        LEFT JOIN size s ON pv.size_id = s.size_id
        LEFT JOIN price pr ON pv.variant_id = pr.variant_id
        GROUP BY p.product_id, pv.variant_id
        ORDER BY p.product_id DESC, pv.variant_id ASC";

$result = $conn->query($sql);
$raw_products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $raw_products[] = $row;
    }
}

// Group variants by product
$products_grouped = [];
foreach ($raw_products as $row) {
    $pid = $row['product_id'];

    if (!isset($products_grouped[$pid])) {
        $products_grouped[$pid] = [
            'product_id'          => $row['product_id'],
            'product_name'        => $row['product_name'],
            'description'         => $row['description'],
            'category_id'         => $row['category_id'],
            'category_name'       => $row['category_name'],
            'category_slug'       => $row['category_slug'],
            'product_type_name'   => $row['product_type_name'],
            'product_type_id'     => $row['product_type_id'],
            'all_colors'          => $row['all_colors'],
            'all_sizes'           => $row['all_sizes'],
            'variants'            => [],
            'first_image'         => null,
            'parent_image_path'   => $row['parent_image_path'],
            'first_variant_image' => null,
            'min_retail_price'    => null,
            'min_wholesale_price' => null,
            'wholesale_min_qty'   => null
        ];
    }

    if ($row['image_path'] && !$products_grouped[$pid]['first_variant_image']) {
        $products_grouped[$pid]['first_variant_image'] = $row['image_path'];
    }
    if ($row['retail_price']) {
        if ($products_grouped[$pid]['min_retail_price'] === null || $row['retail_price'] < $products_grouped[$pid]['min_retail_price']) {
            $products_grouped[$pid]['min_retail_price'] = $row['retail_price'];
        }
    }
    if ($row['wholesale_price']) {
        if ($products_grouped[$pid]['min_wholesale_price'] === null || $row['wholesale_price'] < $products_grouped[$pid]['min_wholesale_price']) {
            $products_grouped[$pid]['min_wholesale_price'] = $row['wholesale_price'];
            $products_grouped[$pid]['wholesale_min_qty']   = $row['wholesale_min_qty'];
        }
    }

    // Store variant for the calculator JS data
    if ($row['variant_id']) {
        $label = '';
        if (!empty($row['color_name'])) $label .= $row['color_name'] . ' – ';
        $label .= ($row['size_name'] ?? 'No size');
        if (!empty($row['sku']))        $label .= ' (' . $row['sku'] . ')';

        $products_grouped[$pid]['variants'][] = [
            'variant_id'        => (int)$row['variant_id'],
            'label'             => trim($label),
            'size_name'         => $row['size_name'] ?? '',
            'sell_unit'         => $row['sell_unit'] ?? 'piece',
            'pieces_per_box'    => $row['pieces_per_box'] ? (int)$row['pieces_per_box'] : null,
            'retail_price'      => $row['retail_price']      ? (float)$row['retail_price']      : null,
            'wholesale_price'   => $row['wholesale_price']   ? (float)$row['wholesale_price']   : null,
            'wholesale_min_qty' => $row['wholesale_min_qty'] ? (int)$row['wholesale_min_qty']   : null,
        ];
    }
}

foreach ($products_grouped as $pid => &$product) {
    $product['first_image'] = $product['parent_image_path'] ?: $product['first_variant_image'];
}
unset($product);

$products = array_values($products_grouped);

// Build calculator product list: only products with at least one sized variant
$calc_products_list = [];
foreach ($products_grouped as $pid => $p) {
    $sized_variants = array_filter($p['variants'], fn($v) => !empty($v['size_name']));
    if (!empty($sized_variants)) {
        $calc_products_list[] = [
            'product_id'   => (int)$pid,
            'product_name' => $p['product_name'],
            'variants'     => array_values($sized_variants),
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Building Materials Catalog | Greenwood Philippines</title>
    <meta name="description" content="Browse Greenwood Philippines' full catalog of premium wall panels, flooring, ceiling systems, and fence solutions. Filter by category, compare prices, and calculate area coverage.">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Greenwood Philippines">
    <?php
    $canon_category = isset($_GET['category']) ? trim($_GET['category']) : '';
    $canonical_url = 'https://greenwoodphilippines.com/catalog.php';
    if ($canon_category !== '') {
        $canonical_url = 'https://greenwoodphilippines.com' . gw_category_url($canon_category);
    }
    ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://greenwoodphilippines.com/catalog.php">
    <meta property="og:title" content="Products Catalog | Greenwood Philippines">
    <meta property="og:description" content="Explore our full range of wall panels, flooring, ceiling systems, and fence solutions for Filipino homes and contractors. Filter, compare, and calculate coverage.">
    <meta property="og:image" content="https://greenwoodphilippines.com/assets/images/nobg.webp">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Greenwood Philippines product catalog">
    <meta property="og:site_name" content="Greenwood Philippines">
    <meta property="og:locale" content="en_PH">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Products Catalog | Greenwood Philippines">
    <meta name="twitter:description" content="Browse wall panels, flooring, ceiling systems, and fence solutions from Greenwood Philippines.">
    <meta name="twitter:image" content="https://greenwoodphilippines.com/assets/images/nobg.webp">
    <meta name="twitter:image:alt" content="Greenwood Philippines product catalog">
    <!-- Schema.org Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "CollectionPage",
      "name": "Products Catalog – Greenwood Philippines",
      "description": "Browse our full range of wall panels, flooring, ceiling systems, and fence solutions.",
      "url": "https://greenwoodphilippines.com/catalog.php",
      "provider": {
        "@type": "Organization",
        "name": "Greenwood Philippines",
        "url": "https://greenwoodphilippines.com"
      }
    }
    </script>

    <!-- Schema.org BreadcrumbList -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://greenwoodphilippines.com/" },
<?php if ($canon_category !== ''): ?>
        { "@type": "ListItem", "position": 2, "name": "Catalog", "item": "https://greenwoodphilippines.com/catalog.php" },
        { "@type": "ListItem", "position": 3, "name": "<?php echo htmlspecialchars(ucfirst($canon_category)); ?>", "item": "<?php echo htmlspecialchars($canonical_url); ?>" }
<?php else: ?>
        { "@type": "ListItem", "position": 2, "name": "Catalog", "item": "https://greenwoodphilippines.com/catalog.php" }
<?php endif; ?>
      ]
    }
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"></noscript>
    <link rel="preload" href="https://unpkg.com/aos@2.3.1/dist/aos.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css"></noscript>
    <link rel="icon" type="image/png" href="assets/images/gw.png">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/catalog.css?v=2">

    <style>
        /* ── Toast Notification System ── */
        #gw-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 360px;
            width: calc(100vw - 40px);
            pointer-events: none;
        }
        #gw-toast-container > * {
            pointer-events: auto;
        }
        .gw-toast {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.13), 0 1.5px 6px rgba(0,0,0,0.08);
            padding: 14px 16px;
            border-left: 4px solid #303823;
            position: relative;
            overflow: hidden;
            animation: toastIn 0.28s cubic-bezier(.4,0,.2,1) both;
        }
        .gw-toast.removing {
            animation: toastOut 0.28s cubic-bezier(.4,0,.2,1) both;
        }
        @keyframes toastIn {
            from { opacity: 0; transform: translateX(60px) scale(0.96); }
            to   { opacity: 1; transform: translateX(0)    scale(1);    }
        }
        @keyframes toastOut {
            from { opacity: 1; transform: translateX(0)    scale(1);    max-height: 120px; margin-bottom: 0; }
            to   { opacity: 0; transform: translateX(60px) scale(0.95); max-height: 0;     margin-bottom: -10px; }
        }
        .gw-toast-success { border-left-color: #303823; }
        .gw-toast-error   { border-left-color: #dc3545; }
        .gw-toast-info    { border-left-color: #0d6efd; }
        .gw-toast-warning { border-left-color: #fd7e14; }

        .gw-toast-icon {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
            margin-top: 1px;
            background: #e8f5e9;
            color: #303823;
        }
        .gw-toast-success .gw-toast-icon { background: #e8f5e9; color: #303823; }
        .gw-toast-error   .gw-toast-icon { background: #fdecea; color: #dc3545; }
        .gw-toast-info    .gw-toast-icon { background: #e7f0ff; color: #0d6efd; }
        .gw-toast-warning .gw-toast-icon { background: #fff3e0; color: #fd7e14; }

        .gw-toast-body { flex: 1; min-width: 0; }
        .gw-toast-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: #222;
            margin-bottom: 2px;
            line-height: 1.3;
        }
        .gw-toast-msg {
            font-size: 0.82rem;
            color: #555;
            line-height: 1.4;
        }
        .gw-toast-close {
            background: none;
            border: none;
            font-size: 18px;
            line-height: 1;
            color: #aaa;
            cursor: pointer;
            padding: 0;
            flex-shrink: 0;
            margin-top: -2px;
            transition: color 0.15s;
        }
        .gw-toast-close:hover { color: #555; }

        .gw-toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            width: 100%;
            background: currentColor;
            opacity: 0.18;
            transform-origin: left;
            animation: toastProgress linear forwards;
            animation-duration: inherit;
        }
        @keyframes toastProgress {
            from { transform: scaleX(1); }
            to   { transform: scaleX(0); }
        }

        /* Confirm dialog inside toast */
        .gw-toast-confirm { flex-direction: column; gap: 0; }
        .gw-toast-confirm .gw-toast-top { display: flex; align-items: flex-start; gap: 12px; width: 100%; }
        .gw-toast-confirm .gw-confirm-actions { display: flex; gap: 8px; padding-left: 34px; margin-top: 10px; }
        .gw-confirm-yes, .gw-confirm-no {
            border: none;
            border-radius: 6px;
            padding: 5px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.15s;
        }
        .gw-confirm-yes { background: #dc3545; color: #fff; }
        .gw-confirm-no  { background: #e9ecef; color: #444; }
        .gw-confirm-yes:hover { opacity: 0.88; }
        .gw-confirm-no:hover  { opacity: 0.80; }

        /* ── Calculator Styles ── */
        .calc-section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 14px;
            border-bottom: 2px solid #303823;
        }
        .calc-section-header i  { font-size: 1.3rem; color: #303823; }
        .calc-section-header h4 { margin: 0; color: #303823; font-weight: 700; font-size: 1.1rem; }

        /* ── Estimation Result ── */
        .estimation-result {
            background: #f8fdf4;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            border: 2px solid #303823;
            display: none;
        }
        .estimation-result.show { display: block; animation: slideDown 0.3s ease; }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .result-title {
            color: #303823;
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .result-details {
            background: #fff;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #303823;
        }
        .result-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            gap: 16px;
        }
        .result-row:last-child { border-bottom: none; }
        .result-label { font-size: 0.9rem; color: #666; font-weight: 500; flex-shrink: 0; }
        .result-value { font-size: 1.05rem; color: #333; font-weight: 700; text-align: right; }
        .result-value.highlight   { color: #303823; font-size: 1.2rem; }
        .result-value.retail-cost { color: #2c5f2d; }
        .result-value.ws-cost     { color: #303823; }
        .result-value.muted-note  { color: #888; font-size: 0.85rem; font-weight: 500; }

        .price-type-badge {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            vertical-align: middle;
            margin-left: 6px;
        }
        .badge-retail    { background: #e8f5e9; color: #2c5f2d; }
        .badge-wholesale { background: #f0f9e6; color: #303823; }

        .disclaimer {
            font-size: 0.8rem;
            color: #999;
            margin-top: 12px;
            padding: 8px 12px;
            background: rgba(100,142,55,0.08);
            border-radius: 6px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        .disclaimer i { color: #303823; margin-top: 2px; flex-shrink: 0; }

        /* ── Saved Estimates ── */
        .saved-estimates-section {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            border: 2px solid #303823;
            display: none;
        }
        .saved-estimates-section.show { display: block; animation: slideDown 0.3s ease; }

        .saved-item {
            background: #f8fdf4;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 10px;
            border: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .saved-item:last-child { margin-bottom: 0; }

        .saved-item-info { flex: 1; min-width: 0; }
        .saved-item-name {
            font-weight: 700;
            color: #333;
            font-size: 0.95rem;
            margin-bottom: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .saved-item-details { font-size: 0.85rem; color: #666; }
        .saved-item-cost {
            font-weight: 700;
            color: #303823;
            font-size: 1.05rem;
            text-align: right;
            flex-shrink: 0;
        }
        .saved-item-remove {
            background: #dc3545;
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }
        .saved-item-remove:hover { background: #c82333; transform: scale(1.1); }

        .saved-total {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 2px solid #303823;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .saved-total-label {
            font-size: 1.1rem;
            font-weight: 700;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .saved-total-value { font-size: 1.4rem; font-weight: 800; color: #303823; }

        select:disabled { background-color: #f5f5f5; cursor: not-allowed; opacity: 0.7; }

        /* ── Mobile Responsive Fixes ── */
        @media (max-width: 768px) {
            #gw-toast-container { top: 12px; right: 12px; left: 12px; width: auto; max-width: none; }

            .calc-section-header { flex-direction: column; align-items: flex-start; gap: 8px; }
            .calc-section-header h4 { font-size: 1rem; }

            .result-row { flex-direction: column; align-items: flex-start; gap: 4px; padding: 12px 0; }
            .result-label { font-size: 0.85rem; }
            .result-value { font-size: 1rem; text-align: left; width: 100%; }
            .result-value.highlight { font-size: 1.15rem; }
            .disclaimer { font-size: 0.75rem; }

            .saved-item { flex-direction: column; align-items: stretch; gap: 10px; }
            .saved-item-info { width: 100%; }
            .saved-item-name { white-space: normal; line-height: 1.3; }
            .saved-item-cost { text-align: left; font-size: 1.1rem; }

            .saved-total { flex-direction: column; align-items: stretch; gap: 8px; }
            .saved-total-label { font-size: 1rem; }
            .saved-total-value { font-size: 1.3rem; text-align: left; }
        }

        @media (max-width: 576px) {
            .filter-section,
            .estimation-section { padding: 16px; }
            .form-label { font-size: 0.9rem; }
            .form-control-lg, .form-select-lg { font-size: 0.95rem; padding: 10px; }
            .btn-lg { padding: 10px 16px; font-size: 0.95rem; }
        }
    </style>
<?php include 'pixel.php'; ?>
</head>
<body>
    <?php include 'navbar.php'; ?>
<main id="main-content">

    <!-- Fixed toast container — always on top, outside content flow -->
    <div id="gw-toast-container"></div>

    <!-- Page Header -->
    <section class="catalog-hero">
        <div class="catalog-hero-bg"></div>
        <div class="catalog-hero-overlay"></div>
        <div class="container catalog-hero-content">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center" data-aos="fade-up">
                    <div class="catalog-hero-logo">
                        <img src="assets/images/nobg.webp" alt="Greenwood Philippines" class="catalog-hero-logo-img">
                    </div>
                    <h1 class="catalog-hero-title">Our <span class="catalog-hero-accent">Products</span></h1>
                    <p class="catalog-hero-sub">Premium quality materials for modern Filipino homes</p>
                    <div class="catalog-hero-actions">
                        <button class="catalog-toolbar-btn" id="filterToggleBtn" onclick="toggleFilterPanel()" aria-expanded="false">
                            Filter &amp; Search
                            <span class="filter-active-dot" id="filterActiveDot" style="display:none;"></span>
                            <svg class="filter-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <button class="catalog-toolbar-btn catalog-toolbar-btn--calc" id="calcOpenBtn" onclick="openCalcModal()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/></svg>
                            Area Calculator
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Collapsible Filter Panel ── -->
    <div id="filterPanel" class="filter-panel" aria-hidden="true">
        <div class="container">
            <div class="filter-panel-inner">
                <div class="filter-panel-header">
                    <span class="filter-panel-title">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
                        Filter Products
                    </span>
                    <button class="filter-reset-btn" onclick="resetFilters()" title="Clear all filters">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.58"/></svg>
                        Reset Filters
                    </button>
                </div>
                <div class="filter-panel-row">
                    <div class="filter-panel-field filter-field-search">
                        <label class="filter-label" for="searchInput">Search</label>
                        <div class="filter-search-wrap">
                            <svg class="filter-search-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" id="searchInput" class="filter-input" placeholder="Search by name...">
                        </div>
                    </div>
                    <div class="filter-panel-field">
                        <label class="filter-label" for="categoryFilter">Category</label>
                        <select id="categoryFilter" class="filter-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo strtolower($category['category_name']); ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-panel-field">
                        <label class="filter-label" for="productTypeFilter">Product Type</label>
                        <select id="productTypeFilter" class="filter-select">
                            <option value="">All Types</option>
                            <?php foreach ($product_types as $type): ?>
                                <option value="<?php echo strtolower($type['product_type_name']); ?>">
                                    <?php echo htmlspecialchars($type['product_type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-panel-field">
                        <label class="filter-label" for="sortFilter">Sort By</label>
                        <select id="sortFilter" class="filter-select">
                            <option value="name-asc">Name (A–Z)</option>
                            <option value="name-desc">Name (Z–A)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Products Section -->
    <section class="py-5">
        <div class="container">

            <!-- Results Count -->
            <div class="catalog-results-bar mb-4" data-aos="fade-up">
                <p class="catalog-results-count">Showing <strong id="resultCount"><?php echo count($products); ?></strong> products</p>
            </div>

            <!-- Products Grid -->
            <div class="products-loading" style="position: relative;">
                <div class="products-loading-overlay" id="productsLoadingOverlay">
                    <img loading="lazy" decoding="async" src="assets/images/nobg.webp" alt="Greenwood Logo" class="loading-logo">
                    <div class="loading-spinner"></div>
                    <div class="loading-text">Loading Products...</div>
                </div>

                <div class="row g-4" id="productsGrid" style="opacity: 0; transition: opacity 0.3s ease;">
                <?php if (empty($products)): ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <p class="lead text-muted">No products found. Please check back later!</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 product-item"
                             data-aos="fade-up"
                             data-name="<?php echo strtolower(htmlspecialchars($product['product_name'])); ?>"
                             data-category="<?php echo strtolower(htmlspecialchars($product['category_name'] ?? '')); ?>"
                             data-type="<?php echo strtolower(htmlspecialchars($product['product_type_name'] ?? '')); ?>">

                            <a href="<?php echo gw_product_url($product['product_id'], $product['product_name']); ?>" class="product-catalog-card-link">
                                <div class="product-catalog-card h-100">

                                    <div class="product-hover-overlay">
                                        <div class="hover-text">Click to See<br>Available Variants</div>
                                    </div>

                                    <div class="product-image-wrapper">
                                        <?php
                                        if (!empty($product['first_image'])) {
                                            $raw = $product['first_image'];
                                            if (strpos($raw, 'uploads/') === 0)       $img = 'admin/' . $raw;
                                            elseif (strpos($raw, 'products/') === 0)  $img = 'admin/uploads/' . $raw;
                                            else                                       $img = $raw;
                                        } else {
                                            $img = 'assets/images/nobg.webp';
                                        }
                                        ?>
                                        <img
                                            src="<?php echo htmlspecialchars($img); ?>"
                                            alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                            class="product-image"
                                            onerror="this.onerror=null;this.src='assets/images/nobg.webp';">

                                        <?php if (!empty($product['product_type_name'])): ?>
                                            <span class="product-badge">
                                                <?php echo htmlspecialchars($product['product_type_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="product-info">
                                        <div class="product-header-section">
                                            <h3 class="product-title">
                                                <?php echo htmlspecialchars($product['product_name']); ?>
                                            </h3>
                                        </div>

                                        <p class="product-description">
                                            <?php echo !empty($product['description'])
                                                ? htmlspecialchars($product['description'])
                                                : 'Click to view available variants, colors, and sizes for this product.'; ?>
                                        </p>

                                        <?php if ($product['min_retail_price'] || $product['min_wholesale_price']): ?>
                                            <div class="product-pricing" style="margin-top:12px;padding-top:12px;border-top:1px solid #e0e0e0;">
                                                <?php if ($product['min_retail_price']): ?>
                                                    <div class="price-row" style="margin-bottom:4px;">
                                                        <span style="font-size:.85em;color:#666;">Retail:</span>
                                                        <strong style="color:#2c5f2d;font-size:1.1em;">₱<?php echo number_format($product['min_retail_price'], 2); ?></strong>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($product['min_wholesale_price']): ?>
                                                    <div class="price-row">
                                                        <span style="font-size:.85em;color:#666;">Wholesale:</span>
                                                        <strong style="color:#97bc62;font-size:1.1em;">₱<?php echo number_format($product['min_wholesale_price'], 2); ?></strong>
                                                        <small style="color:#999;font-size:.8em;">(Min. <?php echo $product['wholesale_min_qty']; ?> pcs)</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
            </div>

            <!-- No Results -->
            <div id="noResults" class="text-center py-5" style="display:none;">
                <img loading="lazy" decoding="async" src="assets/images/nobg.webp" height="60" class="mb-3 opacity-50" alt="">
                <h3 class="text-muted">No products found</h3>
                <p class="text-muted">Try adjusting your filters or search terms</p>
            </div>

        </div>
    </section>

    <!-- Contact CTA -->
    <section class="cta-section py-5 bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8" data-aos="fade-right">
                    <h2 class="fw-bold mb-3">Can't find what you're looking for?</h2>
                    <p class="lead text-muted mb-0">Choose your nearest branch and get in touch with us directly</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0" data-aos="fade-left">
                    <a href="index.php#locations" class="btn-choose-branch">
                        <i class="fas fa-map-marker-alt"></i>Choose Your Branch
                    </a>
                </div>
            </div>
        </div>
    </section>

    </main><!-- /#main-content -->
    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
    AOS.init({ duration: 800, once: true });

    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            document.getElementById('productsLoadingOverlay').classList.add('hidden');
            document.getElementById('productsGrid').style.opacity = '1';
        }, 500);
        loadSavedEstimates();
    });

    /* ══════════════════════════════════════════
       TOAST NOTIFICATION SYSTEM
    ══════════════════════════════════════════ */
    var gwToast = (function () {
        var DURATION = 4500;
        var ICONS    = { success: '&#10003;', error: '&#10005;', info: 'i', warning: '!' };

        function getContainer() {
            return document.getElementById('gw-toast-container');
        }

        function _remove(toast) {
            if (!toast || !toast.parentNode) return;
            clearTimeout(toast._timer);
            clearTimeout(toast._fallbackTimer);
            toast.classList.add('removing');
            toast._fallbackTimer = setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
            toast.addEventListener('animationend', function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, { once: true });
        }

        function _show(type, title, msg, duration) {
            var container = getContainer();
            if (!container) { console.warn('gwToast: container not found'); return; }
            duration = duration || DURATION;

            var toast = document.createElement('div');
            toast.className = 'gw-toast gw-toast-' + type;
            toast.innerHTML =
                '<div class="gw-toast-icon">' + (ICONS[type] || 'i') + '</div>' +
                '<div class="gw-toast-body">' +
                    '<div class="gw-toast-title">' + title + '</div>' +
                    (msg ? '<div class="gw-toast-msg">' + msg + '</div>' : '') +
                '</div>' +
                '<button class="gw-toast-close" aria-label="Dismiss">&times;</button>' +
                '<div class="gw-toast-progress" style="animation-duration:' + duration + 'ms;"></div>';

            toast.querySelector('.gw-toast-close').addEventListener('click', function () {
                _remove(toast);
            });
            toast._timer = setTimeout(function () { _remove(toast); }, duration);
            container.appendChild(toast);
        }

        function _confirm(title, msg, onYes, onNo) {
            var container = getContainer();
            if (!container) { console.warn('gwToast: container not found'); return; }

            var toast = document.createElement('div');
            toast.className = 'gw-toast gw-toast-warning gw-toast-confirm';
            toast.innerHTML =
                '<div class="gw-toast-top">' +
                    '<div class="gw-toast-icon">!</div>' +
                    '<div class="gw-toast-body">' +
                        '<div class="gw-toast-title">' + title + '</div>' +
                        (msg ? '<div class="gw-toast-msg">' + msg + '</div>' : '') +
                    '</div>' +
                    '<button class="gw-toast-close" aria-label="Dismiss">&times;</button>' +
                '</div>' +
                '<div class="gw-confirm-actions">' +
                    '<button class="gw-confirm-yes">Yes, proceed</button>' +
                    '<button class="gw-confirm-no">Cancel</button>' +
                '</div>';

            function dismiss() { _remove(toast); }

            toast.querySelector('.gw-toast-close').addEventListener('click', dismiss);
            toast.querySelector('.gw-confirm-yes').addEventListener('click', function () {
                dismiss();
                if (typeof onYes === 'function') onYes();
            });
            toast.querySelector('.gw-confirm-no').addEventListener('click', function () {
                dismiss();
                if (typeof onNo === 'function') onNo();
            });

            container.appendChild(toast);
        }

        return {
            success: function (title, msg)               { _show('success', title, msg); },
            error:   function (title, msg)               { _show('error',   title, msg); },
            info:    function (title, msg)               { _show('info',    title, msg); },
            warning: function (title, msg)               { _show('warning', title, msg); },
            confirm: function (title, msg, onYes, onNo)  { _confirm(title, msg, onYes, onNo); }
        };
    })();

    /* ══════════════════════════════════════════
       CALCULATOR DATA
    ══════════════════════════════════════════ */
    var CALC_PRODUCTS = <?php echo json_encode($calc_products_list, JSON_UNESCAPED_UNICODE); ?>;
    var CALC_MAP = {};
    CALC_PRODUCTS.forEach(function (p) { CALC_MAP[p.product_id] = p; });

    var savedEstimates  = [];
    var lastCalculation = null;

    function onCalcProductChange() {
        var pid    = document.getElementById('calcProductSelect').value;
        var varSel = document.getElementById('calcVariantSelect');

        varSel.innerHTML = '<option value="">Choose a variant...</option>';
        document.getElementById('estimationResult').classList.remove('show');

        if (!pid || !CALC_MAP[pid]) { varSel.disabled = true; return; }

        CALC_MAP[pid].variants.forEach(function (v) {
            var opt         = document.createElement('option');
            opt.value       = v.variant_id;
            opt.textContent = v.label;
            varSel.appendChild(opt);
        });
        varSel.disabled = false;
    }

    function unitFactor(u) {
        u = (u || 'mm').toLowerCase().trim();
        if (u === 'm')                                     return 1;
        if (u === 'mm')                                    return 0.001;
        if (u === 'cm')                                    return 0.01;
        if (u === 'in' || u === 'inch' || u === 'inches')  return 0.0254;
        if (u === 'ft' || u === 'feet')                    return 0.3048;
        return 0.001;
    }

    function parseSize(str) {
        if (!str) return null;
        str = str.trim();
        var sqmMatch = str.match(/^(\d+(?:\.\d+)?)\s*sqm$/i);
        if (sqmMatch) return parseFloat(sqmMatch[1]);

        var tokenRe = /(\d+(?:\.\d+)?)\s*(mm|cm|m\b|in\b|inch|inches|ft|feet)?/gi;
        var tokens = [], m;
        while ((m = tokenRe.exec(str)) !== null) tokens.push({ val: parseFloat(m[1]), unit: m[2] || null });
        if (tokens.length < 2) return null;

        var fallbackUnit = 'mm';
        for (var i = tokens.length - 1; i >= 0; i--) {
            if (tokens[i].unit) { fallbackUnit = tokens[i].unit; break; }
        }
        return tokens[0].val * unitFactor(tokens[0].unit || fallbackUnit)
             * tokens[1].val * unitFactor(tokens[1].unit || fallbackUnit);
    }

    function peso(n) {
        return '\u20b1' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function calculateCoverage() {
    var areaSize = parseFloat(document.getElementById('areaSize').value);
    var areaUnit = document.getElementById('areaUnit').value;
    var pid      = document.getElementById('calcProductSelect').value;
    var vid      = document.getElementById('calcVariantSelect').value;

    if (!areaSize || areaSize <= 0) { gwToast.error('Invalid area', 'Please enter a valid area size greater than 0.'); return; }
    if (!pid)                        { gwToast.warning('No product selected', 'Please select a product before calculating.'); return; }
    if (!vid)                        { gwToast.warning('No variant selected', 'Please select a product variant before calculating.'); return; }

    var prod    = CALC_MAP[pid];
    var variant = null;
    for (var i = 0; i < prod.variants.length; i++) {
        if (prod.variants[i].variant_id == vid) { variant = prod.variants[i]; break; }
    }
    if (!variant) { gwToast.error('Variant not found', 'Could not load variant data. Please try again.'); return; }

    var areaSqm, unitLabel;
    switch (areaUnit) {
        case 'sqft': areaSqm = areaSize * 0.092903;   unitLabel = 'ft²'; break;
        case 'sqcm': areaSqm = areaSize / 10000;      unitLabel = 'cm²'; break;
        case 'sqin': areaSqm = areaSize * 0.00064516; unitLabel = 'in²'; break;
        default:     areaSqm = areaSize;              unitLabel = 'm²';
    }

    var pieceSqm = parseSize(variant.size_name);
    if (pieceSqm === null || pieceSqm <= 0) {
        gwToast.error('Cannot calculate area', 'Size "' + variant.size_name + '" has only one dimension. Please contact us for this product.');
        return;
    }

    var sellUnit  = variant.sell_unit || 'piece';
    var pcsPerBox = variant.pieces_per_box || null;
    var isBox     = (sellUnit === 'box' && pcsPerBox);

    var exactPieces, recPieces, boxesToBuy, totalPcsInBoxes;
    var piecesMinText, piecesRecText, boxesText;

    if (isBox) {
        // Compute based on actual plank/tile area
        exactPieces     = Math.ceil(areaSqm / pieceSqm);
        recPieces       = Math.ceil(exactPieces * 1.1);

        // Round up to full boxes — can't sell partial box
        boxesToBuy      = Math.ceil(recPieces / pcsPerBox);
        totalPcsInBoxes = boxesToBuy * pcsPerBox;

        piecesMinText = exactPieces + ' pieces';
        piecesRecText = recPieces   + ' pieces';
        boxesText     = boxesToBuy  + ' box' + (boxesToBuy > 1 ? 'es' : '') + ' (' + totalPcsInBoxes + ' pcs)';
    } else {
        exactPieces   = Math.ceil(areaSqm / pieceSqm);
        recPieces     = Math.ceil(exactPieces * 1.1);
        piecesMinText = exactPieces + ' pieces';
        piecesRecText = recPieces   + ' pieces';
    }

    // Cost: box products → price per box × boxes; piece products → price per piece × pieces
    var exactBoxes    = isBox ? Math.ceil(exactPieces / pcsPerBox) : null;
var costUnits     = isBox ? exactBoxes : exactPieces;
var recCostUnits  = isBox ? boxesToBuy : recPieces;
var costUnitLabel = isBox ? 'box' : 'pcs';

    var retailPrice = variant.retail_price;
    var wsPrice     = variant.wholesale_price;
    var wsMin       = variant.wholesale_min_qty;

    // Wholesale eligibility based on recommended pieces
    var wsEligible = (wsPrice && wsMin && recPieces >= wsMin);

    var costLabelHTML, costHTML, costClass, costValue;
var recCostHTML, recCostClass, recCostValue;
if (wsEligible) {
    costValue     = costUnits * wsPrice;
    costLabelHTML = 'Estimated Cost <span class="price-type-badge badge-wholesale">Wholesale</span>';
    costHTML      = peso(costValue) + ' <small style="color:#888;font-size:.8em;font-weight:500;">(' + costUnits + ' ' + costUnitLabel + ' × ' + peso(wsPrice) + ')</small>';
    costClass     = 'result-value ws-cost';
    recCostValue  = recCostUnits * wsPrice;
    recCostHTML   = peso(recCostValue) + ' <small style="color:#888;font-size:.8em;font-weight:500;">(' + recCostUnits + ' ' + costUnitLabel + ' × ' + peso(wsPrice) + ')</small>';
    recCostClass  = 'result-value ws-cost';
} else if (retailPrice) {
    costValue     = costUnits * retailPrice;
    costLabelHTML = 'Estimated Cost <span class="price-type-badge badge-retail">Retail</span>';
    costHTML      = peso(costValue) + ' <small style="color:#888;font-size:.8em;font-weight:500;">(' + costUnits + ' ' + costUnitLabel + ' × ' + peso(retailPrice) + ')</small>';
    costClass     = 'result-value retail-cost';
    recCostValue  = recCostUnits * retailPrice;
    recCostHTML   = peso(recCostValue) + ' <small style="color:#888;font-size:.8em;font-weight:500;">(' + recCostUnits + ' ' + costUnitLabel + ' × ' + peso(retailPrice) + ')</small>';
    recCostClass  = 'result-value retail-cost';
} else {
    costValue     = 0;
    costLabelHTML = 'Estimated Cost';
    costHTML      = 'No price available for this variant';
    costClass     = 'result-value muted-note';
    recCostValue  = 0;
    recCostHTML   = 'No price available for this variant';
    recCostClass  = 'result-value muted-note';
}

    var showAlt = false, altLabel = '', altValue = '';
    if (wsEligible && retailPrice) {
        altLabel = 'Retail price reference';
        altValue = peso(costUnits * retailPrice) + ' (' + costUnits + ' ' + costUnitLabel + ' × ' + peso(retailPrice) + ')';
        showAlt  = true;
    } else if (!wsEligible && wsPrice && wsMin) {
        var gap     = wsMin - recPieces;
        var savings = retailPrice ? (retailPrice - wsPrice) * costUnits : null;
        altLabel = 'Wholesale available at ' + wsMin + ' pcs minimum';
        altValue = gap + ' more piece' + (gap > 1 ? 's' : '') + ' needed to qualify'
                 + (savings !== null ? ' — saves ' + peso(savings) : '');
        showAlt = true;
    }

    // ── Update DOM ──
    document.getElementById('rVariantName').textContent = prod.product_name + ' — ' + variant.label;
    document.getElementById('rSize').textContent        = variant.size_name;
    document.getElementById('rPieceArea').textContent   = pieceSqm.toFixed(4) + ' m² per piece';
    document.getElementById('rArea').textContent        = areaSize.toFixed(2) + ' ' + unitLabel + (areaUnit !== 'sqm' ? ' (' + areaSqm.toFixed(4) + ' m²)' : '');
    document.getElementById('rPiecesMin').textContent   = piecesMinText;
    document.getElementById('rPiecesRec').textContent   = piecesRecText;

    // Show/hide boxes row
    var boxesRow = document.getElementById('rBoxesRow');
    if (isBox) {
        document.getElementById('rBoxesToBuy').textContent = boxesText;
        boxesRow.style.display = '';
    } else {
        boxesRow.style.display = 'none';
    }

    document.getElementById('rCostLabel').innerHTML    = costLabelHTML + ' <small class="text-muted fw-normal">(exact qty)</small>';
document.getElementById('rCost').innerHTML         = costHTML;
document.getElementById('rCost').className         = costClass;
document.getElementById('rCostRecLabel').innerHTML = costLabelHTML + ' <small class="text-muted fw-normal">(+10% allowance)</small>';
document.getElementById('rCostRec').innerHTML      = recCostHTML;
document.getElementById('rCostRec').className      = recCostClass;

    var altRow = document.getElementById('rAltRow');
    if (showAlt) {
        document.getElementById('rAltLabel').textContent = altLabel;
        document.getElementById('rAltValue').textContent = altValue;
        altRow.style.display = '';
    } else {
        altRow.style.display = 'none';
    }

    lastCalculation = {
        productName:  prod.product_name,
        variantLabel: variant.label,
        pieces:       exactPieces,
        boxes:        isBox ? boxesToBuy : null,
        cost:         costValue,
        priceType:    wsEligible ? 'wholesale' : 'retail'
    };

    document.getElementById('estimationResult').classList.add('show');

    if (!wsEligible && wsPrice && wsMin) {
        var tipGap = wsMin - recPieces;
        if (tipGap > 0 && tipGap <= 5) {
            gwToast.info('Almost at wholesale!', 'Add ' + tipGap + ' more piece' + (tipGap > 1 ? 's' : '') + ' to qualify for a lower wholesale price.');
        }
    }
}

    function saveCurrentEstimate() {
        if (!lastCalculation) {
            gwToast.warning('Nothing to save', 'Please run a calculation first.');
            return;
        }
        if (lastCalculation.cost === 0) {
            gwToast.error('No price available', 'This variant has no pricing. Cannot save estimate.');
            return;
        }
        savedEstimates.push(Object.assign({}, lastCalculation, { id: Date.now() }));
        updateSavedEstimatesDisplay();
        document.getElementById('savedEstimatesSection').classList.add('show');
        gwToast.success('Estimate saved!', lastCalculation.productName + ' has been added to your list.');
    }

    function removeSavedEstimate(id) {
        savedEstimates = savedEstimates.filter(function (e) { return e.id !== id; });
        updateSavedEstimatesDisplay();
        if (savedEstimates.length === 0) {
            document.getElementById('savedEstimatesSection').classList.remove('show');
        }
        gwToast.info('Estimate removed', 'The item has been removed from your list.');
    }

    function clearAllEstimates() {
        gwToast.confirm(
            'Clear all estimates?',
            'This will permanently remove all saved items.',
            function () {
                savedEstimates = [];
                updateSavedEstimatesDisplay();
                document.getElementById('savedEstimatesSection').classList.remove('show');
                gwToast.success('All cleared', 'Your saved estimates have been removed.');
            }
        );
    }

    function updateSavedEstimatesDisplay() {
        var container  = document.getElementById('savedItemsContainer');
        var totalValue = document.getElementById('savedTotalValue');

        if (savedEstimates.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-3">No saved estimates yet</p>';
            totalValue.textContent = peso(0);
            localStorage.setItem('greenwood_saved_estimates', JSON.stringify(savedEstimates));
            return;
        }

        var html = '', total = 0;
        savedEstimates.forEach(function (est) {
            var badge = est.priceType === 'wholesale'
                ? '<span class="price-type-badge badge-wholesale">WS</span>'
                : '<span class="price-type-badge badge-retail">Retail</span>';
            html +=
                '<div class="saved-item">' +
                    '<div class="saved-item-info">' +
                        '<div class="saved-item-name">' + est.productName + ' \u2014 ' + est.variantLabel + '</div>' +
                        '<div class="saved-item-details">' + est.pieces + ' pieces ' + badge + '</div>' +
                    '</div>' +
                    '<div class="saved-item-cost">' + peso(est.cost) + '</div>' +
                    '<button class="saved-item-remove" onclick="removeSavedEstimate(' + est.id + ')" title="Remove">' +
                        '<i class="fas fa-times"></i>' +
                    '</button>' +
                '</div>';
            total += est.cost;
        });

        container.innerHTML = html;
        totalValue.textContent = peso(total);
        localStorage.setItem('greenwood_saved_estimates', JSON.stringify(savedEstimates));
    }

    function loadSavedEstimates() {
        var saved = localStorage.getItem('greenwood_saved_estimates');
        if (saved) {
            try {
                savedEstimates = JSON.parse(saved);
                if (savedEstimates.length > 0) {
                    updateSavedEstimatesDisplay();
                    document.getElementById('savedEstimatesSection').classList.add('show');
                }
            } catch (e) { console.error('Failed to load saved estimates', e); }
        }
    }

    function resetCalculator() {
        document.getElementById('areaSize').value          = '';
        document.getElementById('areaUnit').value          = 'sqm';
        document.getElementById('calcProductSelect').value = '';
        var varSel = document.getElementById('calcVariantSelect');
        varSel.innerHTML = '<option value="">Choose a variant...</option>';
        varSel.disabled  = true;
        document.getElementById('estimationResult').classList.remove('show');
        lastCalculation = null;
    }

    /* ══════════════════════════════════════════
       FILTER & SEARCH
    ══════════════════════════════════════════ */
    function filterProducts() {
        var searchTerm     = document.getElementById('searchInput').value.toLowerCase();
        var categoryFilter = document.getElementById('categoryFilter').value.toLowerCase();
        var typeFilter     = document.getElementById('productTypeFilter').value.toLowerCase();
        var sortFilter     = document.getElementById('sortFilter').value;
        var items          = Array.from(document.querySelectorAll('.product-item'));
        var visibleCount   = 0;

        items.forEach(function (p) { p.classList.remove('fade-in'); p.classList.add('fade-out'); });

        setTimeout(function () {
            items.forEach(function (item) {
                var name     = item.dataset.name     || '';
                var category = item.dataset.category || '';
                var type     = item.dataset.type     || '';
                var visible  = name.includes(searchTerm)
                            && (!categoryFilter || category === categoryFilter)
                            && (!typeFilter     || type     === typeFilter);
                item.style.display = visible ? '' : 'none';
                if (visible) visibleCount++;
            });

            sortProducts(items, sortFilter);
            document.getElementById('resultCount').textContent = visibleCount;
            document.getElementById('noResults').style.display = visibleCount === 0 ? 'block' : 'none';

            setTimeout(function () {
                items.forEach(function (p) {
                    if (p.style.display !== 'none') { p.classList.remove('fade-out'); p.classList.add('fade-in'); }
                });
            }, 50);
        }, 150);
    }

    function sortProducts(items, sortType) {
        var grid = document.getElementById('productsGrid');
        items.sort(function (a, b) {
            return sortType === 'name-desc'
                ? b.dataset.name.localeCompare(a.dataset.name)
                : a.dataset.name.localeCompare(b.dataset.name);
        });
        items.forEach(function (p) { grid.appendChild(p); });
    }

    function resetFilters() {
        document.getElementById('searchInput').value       = '';
        document.getElementById('categoryFilter').value    = '';
        document.getElementById('productTypeFilter').value = '';
        document.getElementById('sortFilter').value        = 'name-asc';
        filterProducts();
        updateFilterActiveDot();
    }

    function updateFilterActiveDot() {
        var active = (
            document.getElementById('searchInput').value !== '' ||
            document.getElementById('categoryFilter').value !== '' ||
            document.getElementById('productTypeFilter').value !== '' ||
            document.getElementById('sortFilter').value !== 'name-asc'
        );
        document.getElementById('filterActiveDot').style.display = active ? 'inline-block' : 'none';
    }

    function toggleFilterPanel() {
        var panel = document.getElementById('filterPanel');
        var btn   = document.getElementById('filterToggleBtn');
        var open  = panel.classList.toggle('filter-panel--open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    function openCalcModal() {
        var modal = document.getElementById('calcModal');
        modal.style.display = 'flex';
        document.body.classList.add('calc-modal-open');
        setTimeout(function(){ modal.classList.add('calc-modal--visible'); }, 10);
    }

    function closeCalcModal() {
        var modal = document.getElementById('calcModal');
        modal.classList.remove('calc-modal--visible');
        document.body.classList.remove('calc-modal-open');
        setTimeout(function(){ modal.style.display = 'none'; }, 280);
    }

    function closeCalcModalOnBackdrop(e) {
        if (e.target === document.getElementById('calcModal')) closeCalcModal();
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeCalcModal();
    });

    document.getElementById('searchInput').addEventListener('keyup', function(){ filterProducts(); updateFilterActiveDot(); });
    document.getElementById('categoryFilter').addEventListener('change', function(){ filterProducts(); updateFilterActiveDot(); });
    document.getElementById('productTypeFilter').addEventListener('change', function(){ filterProducts(); updateFilterActiveDot(); });
    document.getElementById('sortFilter').addEventListener('change', function(){ filterProducts(); updateFilterActiveDot(); });

    document.addEventListener('DOMContentLoaded', function () {
        var cat = <?php echo json_encode($canon_category !== '' ? $canon_category : ''); ?>;
        if (!cat) { cat = new URLSearchParams(window.location.search).get('category') || ''; }
        if (cat) { document.getElementById('categoryFilter').value = cat.toLowerCase(); filterProducts(); }
    });

    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            var target = document.querySelector(this.getAttribute('href'));
            if (target) target.scrollIntoView({ behavior: 'smooth' });
        });
    });
</script>
    <!-- ══════════════════════════════════
         AREA COVERAGE CALCULATOR MODAL
         ══════════════════════════════════ -->
    <div id="calcModal" class="calc-modal-backdrop" onclick="closeCalcModalOnBackdrop(event)" aria-modal="true" role="dialog" aria-label="Area Coverage Calculator" style="display:none;">
        <div class="calc-modal">
            <div class="calc-modal-header">
                <div class="calc-modal-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/></svg>
                    Area Coverage Calculator
                </div>
                <button class="calc-modal-close" onclick="closeCalcModal()" aria-label="Close">&times;</button>
            </div>
            <div class="calc-modal-body">
                <div class="calc-inputs-grid">
                    <div class="calc-field">
                        <label class="calc-field-label">Area Size</label>
                        <input type="number" id="areaSize" class="calc-input" placeholder="e.g. 25" step="0.01" min="0">
                    </div>
                    <div class="calc-field">
                        <label class="calc-field-label">Unit</label>
                        <select id="areaUnit" class="calc-select">
                            <option value="sqm">sq. meters (m²)</option>
                            <option value="sqft">sq. feet (ft²)</option>
                            <option value="sqcm">sq. cm (cm²)</option>
                            <option value="sqin">sq. inches (in²)</option>
                        </select>
                    </div>
                    <div class="calc-field">
                        <label class="calc-field-label">Select Product</label>
                        <select id="calcProductSelect" class="calc-select" onchange="onCalcProductChange()">
                            <option value="">Choose a product...</option>
                            <?php foreach ($calc_products_list as $cp): ?>
                                <option value="<?php echo $cp['product_id']; ?>">
                                    <?php echo htmlspecialchars($cp['product_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="calc-field">
                        <label class="calc-field-label">Select Variant</label>
                        <select id="calcVariantSelect" class="calc-select" disabled>
                            <option value="">Choose a variant...</option>
                        </select>
                    </div>
                </div>
                <div class="calc-modal-actions">
                    <button class="calc-btn-calculate" onclick="calculateCoverage()">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="12" y2="14"/></svg>
                        Calculate
                    </button>
                    <button class="calc-btn-reset" onclick="resetCalculator()">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.58"/></svg>
                        Reset
                    </button>
                </div>

                <!-- Result -->
                <div class="estimation-result" id="estimationResult">
                    <div class="result-title">
                        <i class="fas fa-check-circle"></i>
                        Estimated Coverage
                    </div>
                    <div class="result-details">
                        <div class="result-row">
                            <span class="result-label">Product &amp; Variant</span>
                            <span class="result-value" id="rVariantName">—</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Panel Size</span>
                            <span class="result-value" id="rSize">—</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Area per Piece</span>
                            <span class="result-value" id="rPieceArea">—</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Total Area to Cover</span>
                            <span class="result-value" id="rArea">—</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Pieces Needed (exact)</span>
                            <span class="result-value highlight" id="rPiecesMin">—</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label">Recommended Qty <small>(+10% allowance)</small></span>
                            <span class="result-value highlight" id="rPiecesRec">—</span>
                        </div>
                        <div class="result-row" id="rBoxesRow" style="display:none;">
                            <span class="result-label">Boxes to Purchase <small>(rounded up)</small></span>
                            <span class="result-value highlight" id="rBoxesToBuy">—</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label" id="rCostLabel">Est. Cost <small>(exact qty)</small></span>
                            <span class="result-value" id="rCost">—</span>
                        </div>
                        <div class="result-row">
                            <span class="result-label" id="rCostRecLabel">Est. Cost <small>(+10% allowance)</small></span>
                            <span class="result-value" id="rCostRec">—</span>
                        </div>
                        <div class="result-row" id="rAltRow" style="display:none;">
                            <span class="result-label" id="rAltLabel"></span>
                            <span class="result-value muted-note" id="rAltValue"></span>
                        </div>
                    </div>
                    <div class="disclaimer">
                        <i class="fas fa-info-circle"></i>
                        <span><strong>Note:</strong> Actual requirements may vary based on cutting waste and installation method. We recommend ordering 10–15% extra material.</span>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-success" onclick="saveCurrentEstimate()">
                            <i class="fas fa-save me-2"></i>Save This Estimate
                        </button>
                    </div>
                </div>

                <!-- Saved Estimates -->
                <div class="saved-estimates-section" id="savedEstimatesSection">
                    <div class="result-title mb-3">
                        <i class="fas fa-list-check"></i>
                        Saved Estimates
                    </div>
                    <div id="savedItemsContainer"></div>
                    <div class="saved-total">
                        <span class="saved-total-label">Total Estimate</span>
                        <span class="saved-total-value" id="savedTotalValue">₱0.00</span>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-outline-danger" onclick="clearAllEstimates()">
                            <i class="fas fa-trash me-2"></i>Clear All Estimates
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>