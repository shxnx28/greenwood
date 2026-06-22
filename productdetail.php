<?php
require_once 'admin/db.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Return a real 404 (not a redirect) for missing/deleted products, so search
// engines drop those dead URLs cleanly instead of flagging "Page with redirect".
function product_not_found() {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, follow">
    <title>Product Not Found – Greenwood Philippines</title>
    <style>
        body{margin:0;font-family:'Inter','Segoe UI',sans-serif;background:#f6faf0;color:#303823;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;padding:20px;}
        .nf{max-width:480px;}
        .nf h1{font-size:4rem;margin:0;color:#648E37;}
        .nf h2{font-size:1.4rem;margin:.5rem 0 1rem;}
        .nf p{color:#5a5a5a;line-height:1.6;margin-bottom:1.5rem;}
        .nf a{display:inline-block;background:#648E37;color:#fff;text-decoration:none;font-weight:700;padding:.85rem 2rem;border-radius:8px;}
    </style>
</head>
<body>
    <div class="nf">
        <h1>404</h1>
        <h2>Product Not Found</h2>
        <p>Sorry, this product is no longer available or the link is incorrect. Browse our full catalog to find what you need.</p>
        <a href="/catalog.php">Browse Catalog</a>
    </div>
</body>
</html><?php
    exit;
}

if (!$product_id) {
    product_not_found();
}

// Check if user is admin (for SKU visibility)
session_start();
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Fetch product details
$sql = "SELECT p.*, c.category_name, c.slug, pt.product_type_name
        FROM product p
        LEFT JOIN category c ON p.category_id = c.category_id
        LEFT JOIN product_type pt ON p.product_type_id = pt.product_type_id
        WHERE p.product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    product_not_found();
}

// Helper function to ensure proper hex code format
function formatHexCode($hex) {
    if (empty($hex)) {
        return '#CCCCCC'; // Default gray if no color
    }
    
    // Remove # if present
    $hex = ltrim($hex, '#');
    
    // Validate hex code (must be 3 or 6 characters)
    if (preg_match('/^[0-9A-Fa-f]{3}$/', $hex)) {
        // Convert 3-char to 6-char
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    } elseif (!preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
        // Invalid hex code, return default
        return '#CCCCCC';
    }
    
    return '#' . strtoupper($hex);
}

// Fetch all variants with their colors, sizes, and prices
$sql = "SELECT 
            pv.variant_id,
            pv.sku,
            pv.stock,
            pv.image_path,
            c.color_id,
            c.color_name,
            c.hex_code,
            pv.size_id,
            s.size_name,
            GROUP_CONCAT(DISTINCT CONCAT(pr.price_type, ':', pr.price, ':', pr.min_quantity) SEPARATOR '|') as prices
        FROM product_variant pv
        INNER JOIN color c ON pv.color_id = c.color_id
        LEFT JOIN size s ON pv.size_id = s.size_id
        LEFT JOIN price pr ON pv.variant_id = pr.variant_id
        WHERE pv.product_id = ?
        GROUP BY pv.variant_id, pv.sku, pv.stock, pv.image_path, c.color_id, c.color_name, c.hex_code, pv.size_id, s.size_name
        ORDER BY c.color_name, s.size_name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$variants = [];
while ($row = $result->fetch_assoc()) {
    // Parse prices
    $parsed_prices = [];
    if ($row['prices']) {
        $price_items = explode('|', $row['prices']);
        foreach ($price_items as $item) {
            $parts = explode(':', $item);
            if (count($parts) === 3) {
                list($type, $price, $min_qty) = $parts;
                $parsed_prices[$type] = [
                    'price' => $price,
                    'min_quantity' => $min_qty
                ];
            }
        }
    }
    $row['parsed_prices'] = $parsed_prices;
    
    // Format hex code properly
    $row['hex_code'] = formatHexCode($row['hex_code']);
    
    $variants[] = $row;
}

// Fetch all active branches for the branch selection modal
$sql = "SELECT location_id, location_name, city, province, facebook_url 
        FROM warehouse_location 
        WHERE is_active = 1 
        ORDER BY display_order, location_name";
$branches_result = $conn->query($sql);
$branches = [];
while ($row = $branches_result->fetch_assoc()) {
    $branches[] = $row;
}

// Get unique colors and sizes
$colors = [];
$sizes = [];
foreach ($variants as $variant) {
    if (!isset($colors[$variant['color_id']])) {
        $colors[$variant['color_id']] = [
            'color_id' => $variant['color_id'],
            'color_name' => $variant['color_name'],
            'hex_code' => $variant['hex_code']
        ];
    }
    if (!isset($sizes[$variant['size_id']])) {
        $sizes[$variant['size_id']] = [
            'size_id' => $variant['size_id'],
            'size_name' => $variant['size_name'] ?? $variant['size_id']
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

    <?php
    $seoProductName = htmlspecialchars($product['product_name']);
    $seoCategory    = htmlspecialchars($product['category_name'] ?? '');
    $seoType        = htmlspecialchars($product['product_type_name'] ?? '');
    $seoDesc        = !empty($product['description'])
        ? htmlspecialchars(mb_substr(strip_tags($product['description']), 0, 155)) . '...'
        : 'Buy ' . $seoProductName . ' from Greenwood Philippines. Premium quality ' . ($seoCategory ?: 'building material') . ' for Filipino homes and contractors.';
    $seoImage       = !empty($product['image_path'])
        ? 'https://greenwoodphilippines.com/' . ltrim($product['image_path'], '/')
        : 'https://greenwoodphilippines.com/assets/images/nobg.webp';
    $canonicalUrl   = 'https://greenwoodphilippines.com/product-detail.php?id=' . intval($product_id);
    ?>

    <title><?php echo $seoProductName; ?> – <?php echo $seoCategory ?: 'Greenwood Philippines'; ?> | Greenwood Philippines</title>

    <!-- Primary SEO Meta Tags -->
    <meta name="description" content="<?php echo $seoDesc; ?>">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Greenwood Philippines">
    <link rel="canonical" href="<?php echo $canonicalUrl; ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="product">
    <meta property="og:url" content="<?php echo $canonicalUrl; ?>">
    <meta property="og:title" content="<?php echo $seoProductName; ?> | Greenwood Philippines">
    <meta property="og:description" content="<?php echo $seoDesc; ?>">
    <meta property="og:image" content="<?php echo $seoImage; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?php echo $seoProductName; ?> – Greenwood Philippines">
    <meta property="og:site_name" content="Greenwood Philippines">
    <meta property="og:locale" content="en_PH">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $seoProductName; ?> | Greenwood Philippines">
    <meta name="twitter:description" content="<?php echo $seoDesc; ?>">
    <meta name="twitter:image" content="<?php echo $seoImage; ?>">
    <meta name="twitter:image:alt" content="<?php echo $seoProductName; ?> – Greenwood Philippines">

    <!-- Schema.org Product Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Product",
      "name": "<?php echo $seoProductName; ?>",
      "description": "<?php echo $seoDesc; ?>",
      "image": "<?php echo $seoImage; ?>",
      "url": "<?php echo $canonicalUrl; ?>",
      "brand": {
        "@type": "Brand",
        "name": "Greenwood Philippines"
      },
      "category": "<?php echo $seoCategory; ?>",
      "offers": {
        "@type": "Offer",
        "availability": "https://schema.org/InStock",
        "priceCurrency": "PHP",
        <?php
        $seoPrice = null;
        foreach ($variants as $v) {
            if (!empty($v['parsed_prices']['retail']['price'])) {
                $seoPrice = number_format((float)$v['parsed_prices']['retail']['price'], 2, '.', '');
                break;
            }
        }
        if ($seoPrice): ?>
        "price": "<?php echo $seoPrice; ?>",
        <?php endif; ?>
        "seller": {
          "@type": "Organization",
          "name": "Greenwood Philippines"
        }
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
<?php if (!empty($product['category_name'])): ?>
        { "@type": "ListItem", "position": 2, "name": "<?php echo $seoCategory; ?>", "item": "https://greenwoodphilippines.com/catalog.php?category=<?php echo urlencode(strtolower($product['category_name'])); ?>" },
        { "@type": "ListItem", "position": 3, "name": "<?php echo $seoProductName; ?>", "item": "<?php echo $canonicalUrl; ?>" }
<?php else: ?>
        { "@type": "ListItem", "position": 2, "name": "<?php echo $seoProductName; ?>", "item": "<?php echo $canonicalUrl; ?>" }
<?php endif; ?>
      ]
    }
    </script>
    <link rel="icon" type="image/png" href="/assets/images/gw.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- AOS Animation Library -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"></noscript>
    <link rel="preload" href="https://unpkg.com/aos@2.3.1/dist/aos.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css"></noscript>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/product-detail.css">
    <?php include 'pixel.php'; ?>
</head>
<body<?php echo $is_admin ? ' class="admin-logged-in"' : ''; ?>>

    <!-- Navigation -->
    <?php include 'navbar.php'; ?>
<main id="main-content">

    <!-- Hero / Breadcrumb -->
    <?php
    $bannerHtml = '';
    if (!empty($product['banner_image'])) {
        $bannerRaw = $product['banner_image'];
        if (strpos($bannerRaw, 'uploads/') === 0) {
            $bannerUrl = '/admin/' . $bannerRaw;
        } else {
            $bannerUrl = $bannerRaw;
        }
        $bannerEsc = htmlspecialchars($bannerUrl, ENT_QUOTES);
        $bannerHtml = '<div class="breadcrumb-bg" style="background-image:url(\'' . $bannerEsc . '\')"></div>';
    }
    ?>
    <section class="breadcrumb-section">
        <?php echo $bannerHtml; ?>
        <div class="breadcrumb-overlay"></div>
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="/catalog.php">Products</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </li>
                </ol>
            </nav>

            <div class="product-header mt-3">
                <div>
                    <h1 class="product-name mb-3">
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </h1>
                    <div class="product-badges mb-3">
                        <?php if (!empty($product['product_type_name'])): ?>
                            <span class="product-type-badge">
                                <?php echo htmlspecialchars($product['product_type_name']); ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($product['category_name'])): ?>
                            <span class="category-badge">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($product['description'])): ?>
                        <div class="product-description">
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-5">
        <div class="container">
            
            <!-- Filters -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="variant-filters">
                        <div class="row">
                            <!-- Color Filter -->
                            <?php if (count($colors) > 1): ?>
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="filter-label">Filter by Color:</label>
                                    <div class="color-filters">
                                        <button class="color-filter-btn active" data-filter="all">
                                            All Colors
                                        </button>
                                        <?php foreach ($colors as $color): ?>
                                            <button class="color-filter-btn" data-filter="<?php echo htmlspecialchars($color['color_id']); ?>">
                                                <span class="color-swatch" style="background-color: <?php echo htmlspecialchars($color['hex_code']); ?>;"></span>
                                                <?php echo htmlspecialchars($color['color_name']); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Size Filter -->
                            <?php if (count($sizes) > 1): ?>
                                <div class="col-md-6">
                                    <label class="filter-label">Filter by Size:</label>
                                    <div class="size-filters">
                                        <button class="size-filter-btn active" data-filter="all">
                                            All Sizes
                                        </button>
                                        <?php foreach ($sizes as $size): ?>
                                            <button class="size-filter-btn" data-filter="<?php echo htmlspecialchars($size['size_id']); ?>">
                                                <?php echo htmlspecialchars($size['size_name']); ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Variants Grid -->
            <div id="variantsGrid" class="row">
                <?php if (!empty($variants)): ?>
                    <?php foreach ($variants as $variant): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 variant-item pd-fade-in"
                             data-color="<?php echo htmlspecialchars($variant['color_id']); ?>"
                             data-size="<?php echo htmlspecialchars($variant['size_id']); ?>">
                            <div class="variant-card">
                                <!-- Image -->
                                <div class="variant-image-wrapper">
                                    <?php if (!empty($variant['image_path'])): 
                                        $imagePathRaw = $variant['image_path'];
                                        
                                        // Fix image path based on format (same logic as catalog.php)
                                        if (strpos($imagePathRaw, 'uploads/') === 0) {
                                            $imagePath = '/admin/' . $imagePathRaw;
                                        } elseif (strpos($imagePathRaw, 'products/') === 0) {
                                            $imagePath = '/admin/uploads/' . $imagePathRaw;
                                        } else {
                                            $imagePath = $imagePathRaw;
                                        }
                                    ?>
                                        <img loading="lazy" decoding="async" src="<?php echo htmlspecialchars($imagePath); ?>" 
                                             alt="<?php echo htmlspecialchars($variant['color_name']); ?>" 
                                             class="variant-image"
                                             onclick="openLightbox(this.src, '<?php echo htmlspecialchars(addslashes($variant['color_name'])); ?>')"
                                             onerror="this.onerror=null;this.src='/assets/images/nobg.webp';">
                                    <?php else: ?>
                                        <img loading="lazy" decoding="async" src="/assets/images/nobg.webp" 
                                             alt="<?php echo htmlspecialchars($variant['color_name']); ?>" 
                                             class="variant-image"
                                             onclick="openLightbox(this.src, '<?php echo htmlspecialchars(addslashes($variant['color_name'])); ?>')">
                                    <?php endif; ?>
                                    
                                    <!-- Stock Badge -->
                                    <span class="stock-badge <?php echo $variant['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                                        <?php echo $variant['stock'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                    </span>
                                </div>

                                <!-- Info -->
                                <div class="variant-info">
                                    <div class="variant-attributes">
                                        <!-- Color -->
                                        <div class="attribute-row">
                                            <span class="attribute-label">Color:</span>
                                            <span class="attribute-value">
                                                <span class="color-dot" style="background-color: <?php echo htmlspecialchars($variant['hex_code']); ?>;"></span>
                                                <?php echo htmlspecialchars($variant['color_name']); ?>
                                            </span>
                                        </div>
                                        
                                        <!-- Size -->
                                        <div class="attribute-row">
                                            <span class="attribute-label">Size:</span>
                                            <span class="attribute-value"><?php echo htmlspecialchars($variant['size_name'] ?? $variant['size_id']); ?></span>
                                        </div>
                                        
                                        <!-- Stock -->
                                        <div class="attribute-row">
                                            <span class="attribute-label">Stock:</span>
                                            <span class="attribute-value stock-value <?php echo $variant['stock'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $variant['stock']; ?> units
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Pricing -->
                                    <?php if (!empty($variant['parsed_prices'])): ?>
                                        <div class="variant-pricing">
                                            <div class="price-items">
                                                <!-- Retail Price -->
                                                <?php if (isset($variant['parsed_prices']['retail'])): ?>
                                                    <div class="price-item retail-price">
                                                        <div class="price-header">
                                                            <span class="price-type">Retail Price</span>
                                                            <span class="price-amount retail">
                                                                ₱<?php echo number_format($variant['parsed_prices']['retail']['price'], 2); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Wholesale Price -->
                                                <?php if (isset($variant['parsed_prices']['wholesale'])): ?>
                                                    <div class="price-item wholesale-price">
                                                        <div class="price-header">
                                                            <span class="price-type">Wholesale Price</span>
                                                            <span class="price-amount">
                                                                ₱<?php echo number_format($variant['parsed_prices']['wholesale']['price'], 2); ?>
                                                            </span>
                                                        </div>
                                                        <div class="min-qty-info">
                                                            <span>Minimum order:</span>
                                                            <span class="min-qty-badge"><?php echo $variant['parsed_prices']['wholesale']['min_quantity']; ?> pcs</span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Inquire Button - Opens Branch Selection -->
                                    <button class="btn-inquire btn-messenger" 
                                        onclick="openBranchSelection('<?php echo addslashes($product['product_name']); ?>', '<?php echo addslashes($variant['color_name']); ?>', '<?php echo addslashes($variant['size_name'] ?? $variant['size_id']); ?>', '<?php echo addslashes($variant['sku']); ?>')">

                                        <!-- Messenger SVG Icon -->
                                        <svg xmlns="http://www.w3.org/2000/svg" 
                                             width="18" 
                                             height="18" 
                                             viewBox="0 0 24 24" 
                                             fill="currentColor" 
                                             style="vertical-align: middle; margin-right: 6px;">
                                            <path d="M12 2C6.477 2 2 6.145 2 11.25c0 2.88 1.437 5.45 3.688 7.125V22l3.375-1.875c.938.262 1.938.375 2.937.375 5.523 0 10-4.145 10-9.25S17.523 2 12 2zm1.063 12.375l-2.563-2.75-4.5 2.75 5.063-5.375 2.563 2.75 4.5-2.75-5.063 5.375z"/>
                                        </svg>
                                        Inquire via Messenger
                                    </button>

                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- No Results Message -->
            <div id="noVariants" class="text-center py-5" style="display: none;">
                <img loading="lazy" decoding="async" src="/assets/images/nobg.webp" height="60" class="mb-3 opacity-50" alt="">
                <h4 class="text-muted">No variants match your filters</h4>
                <p class="text-muted">Try selecting different color or size options</p>
            </div>

            <!-- Back to Products -->
            <div class="row mt-5">
                <div class="col-12 text-center">
                    <a href="/catalog.php" class="btn btn-outline-success btn-lg">
                        <i class="fas fa-arrow-left"></i> Back to All Products
                    </a>
                </div>
            </div>

        </div>
    </section>

    <!-- Footer -->
    </main><!-- /#main-content -->
    <?php include 'footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS Animation JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Lightbox functions
        function openLightbox(src, caption) {
            const modal = document.getElementById('lightboxModal');
            document.getElementById('lightboxImg').src = src;
            document.getElementById('lightboxCaption').textContent = caption;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            document.getElementById('lightboxModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close lightbox on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeLightbox();
        });

        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });

        // Filter state
        let selectedColor = 'all';
        let selectedSize = 'all';

        // Color filter
        document.querySelectorAll('.color-filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.color-filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                selectedColor = this.dataset.filter;
                filterVariants();
            });
        });

        // Size filter
        document.querySelectorAll('.size-filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.size-filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                selectedSize = this.dataset.filter;
                filterVariants();
            });
        });

        function filterVariants() {
            const variants = document.querySelectorAll('.variant-item');
            let visibleCount = 0;

            // Step 1: Fade out all variants
            variants.forEach(variant => {
                variant.classList.remove('pd-fade-in');
                variant.classList.add('fade-out');
            });

            // Step 2: After fade out, update visibility
            setTimeout(function() {
                variants.forEach(variant => {
                    const color = variant.dataset.color;
                    const size = variant.dataset.size;

                    const matchesColor = selectedColor === 'all' || color === selectedColor;
                    const matchesSize = selectedSize === 'all' || size === selectedSize;

                    if (matchesColor && matchesSize) {
                        variant.style.display = '';
                        visibleCount++;
                    } else {
                        variant.style.display = 'none';
                    }
                });

                document.getElementById('noVariants').style.display = visibleCount === 0 ? 'block' : 'none';

                // Step 3: Fade in visible variants
                setTimeout(function() {
                    variants.forEach(variant => {
                        if (variant.style.display !== 'none') {
                            variant.classList.remove('fade-out');
                            variant.classList.add('pd-fade-in');
                        }
                    });
                }, 50);
            }, 150);
        }

        // Show copy notification (kept for potential future use)
        function showCopyNotification() {
            const notification = document.createElement('div');
            notification.textContent = '✓ Product details copied to clipboard!';
            notification.style.cssText = `
                position: fixed;
                bottom: 30px;
                right: 30px;
                background: #28a745;
                color: white;
                padding: 15px 25px;
                border-radius: 8px;
                font-weight: 600;
                font-size: 14px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                animation: slideIn 0.3s ease-out;
            `;
            
            document.body.appendChild(notification);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    if (notification.parentNode) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }
        
        // Store current inquiry details
        let currentInquiry = {};
        
        // Open branch selection modal
        function openBranchSelection(productName, color, size, sku) {
            currentInquiry = {
                productName: productName,
                color: color,
                size: size,
                sku: sku
            };
            
            document.getElementById('branchModal').style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }
        
        // Close branch selection modal
        function closeBranchModal() {
            document.getElementById('branchModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Extract Facebook username from URL
        function extractFacebookUsername(facebookUrl) {
            if (!facebookUrl) return 'greenwoodphilippines'; // Fallback
            
            // Handle different Facebook URL formats
            // Format 1: https://www.facebook.com/greenwoodphilippines
            // Format 2: https://www.facebook.com/profile.php?id=61573851460333
            
            if (facebookUrl.includes('profile.php?id=')) {
                // Extract ID from profile URL
                const match = facebookUrl.match(/id=(\d+)/);
                return match ? match[1] : 'greenwoodphilippines';
            } else {
                // Extract username from regular URL
                const match = facebookUrl.match(/facebook\.com\/([^/?]+)/);
                return match ? match[1] : 'greenwoodphilippines';
            }
        }
        
        // Select branch and show tutorial modal before opening Messenger
        function selectBranch(branchName, facebookUrl) {
            closeBranchModal();
            
            const { productName, color, size, sku } = currentInquiry;
            
            // Prepare the message
            const message = `Hi! I'm interested in:
Product: ${productName}
Color: ${color}
Size: ${size}

I would like to inquire about availability and pricing.`;
            
            const pageUsername = extractFacebookUsername(facebookUrl);
            const messengerUrl = `https://www.facebook.com/messages/t/${pageUsername}`;
            
            // Set the proceed button URL
            document.getElementById('gwTutorialProceedBtn').href = messengerUrl;

            // Copy to clipboard then open tutorial modal
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(message).then(() => {
                    openTutorialModal(true);
                }).catch(() => {
                    openTutorialModal(false);
                });
            } else {
                // Fallback: try execCommand
                try {
                    const ta = document.createElement('textarea');
                    ta.value = message;
                    ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0;';
                    document.body.appendChild(ta);
                    ta.focus(); ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    openTutorialModal(true);
                } catch(e) {
                    openTutorialModal(false);
                }
            }
        }
        
        // Tutorial modal open/close
        function openTutorialModal(copied) {
            const modal = document.getElementById('gwTutorialModal');
            const banner = document.getElementById('gwTutorialCopiedBanner');
            if (banner) banner.style.display = copied ? 'flex' : 'none';
            modal.classList.remove('gw-tut-closing');
            modal.classList.add('gw-tut-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
        function closeTutorialModal() {
            const modal = document.getElementById('gwTutorialModal');
            modal.classList.add('gw-tut-closing');
            setTimeout(() => {
                modal.classList.remove('gw-tut-open', 'gw-tut-closing');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }, 350);
        }
        document.addEventListener('DOMContentLoaded', function() {
            const closeBtn = document.getElementById('gwTutorialClose');
            const backdrop = document.getElementById('gwTutorialBackdrop');
            if (closeBtn) closeBtn.addEventListener('click', closeTutorialModal);
            if (backdrop) backdrop.addEventListener('click', closeTutorialModal);
            document.addEventListener('keydown', function(e) {
                const modal = document.getElementById('gwTutorialModal');
                if (e.key === 'Escape' && modal && modal.classList.contains('gw-tut-open')) closeTutorialModal();
            });
        });
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('branchModal');
            if (event.target === modal) {
                closeBranchModal();
            }
        }
        
        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
            @keyframes fadeIn {
                from {
                    opacity: 0;
                }
                to {
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    </script>

    <!-- Lightbox Modal -->
    <div id="lightboxModal" onclick="closeLightbox()" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.92); z-index:99999; justify-content:center; align-items:center; padding:20px; cursor:zoom-out;">
        <button onclick="closeLightbox()" style="position:absolute; top:20px; right:24px; background:none; border:none; color:white; font-size:40px; cursor:pointer; line-height:1; opacity:0.8; z-index:100000;">&times;</button>
        <img id="lightboxImg" src="" alt="" style="max-width:90vw; max-height:90vh; object-fit:contain; border-radius:8px; box-shadow:0 20px 60px rgba(0,0,0,0.5); cursor:default;" onclick="event.stopPropagation()">
        <div id="lightboxCaption" style="position:absolute; bottom:24px; left:50%; transform:translateX(-50%); color:rgba(255,255,255,0.85); font-size:0.9rem; font-weight:500; letter-spacing:0.5px; background:rgba(0,0,0,0.4); padding:8px 20px; border-radius:20px; white-space:nowrap;"></div>
    </div>

    <!-- Branch Selection Modal -->
    <div id="branchModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; justify-content: center; align-items: center; padding: 20px; animation: fadeIn 0.3s ease;">
        <div style="background: white; border-radius: 16px; max-width: 500px; width: 100%; max-height: 80vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideIn 0.3s ease;">
            <!-- Modal Header -->
            <div style="padding: 24px 28px; border-bottom: 2px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="margin: 0; font-size: 22px; font-weight: 700; color: #2d2d2d;">
                        <i class="fas fa-map-marker-alt" style="color: #0084ff; margin-right: 8px;"></i>
                        Select Your Branch
                    </h3>
                    <p style="margin: 6px 0 0 0; font-size: 14px; color: #666;">Choose the branch you'd like to contact</p>
                </div>
                <button onclick="closeBranchModal()" style="background: none; border: none; font-size: 28px; color: #999; cursor: pointer; padding: 0; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all 0.2s;" onmouseover="this.style.background='#f5f5f5'; this.style.color='#333';" onmouseout="this.style.background='none'; this.style.color='#999';">
                    &times;
                </button>
            </div>
            
            <!-- Branch List -->
            <div style="padding: 16px 28px 28px 28px;">
                <?php foreach ($branches as $branch): ?>
                <button onclick="selectBranch('<?php echo addslashes($branch['location_name']); ?>', '<?php echo addslashes($branch['facebook_url']); ?>')" 
                        style="width: 100%; padding: 18px 20px; margin-bottom: 12px; background: white; border: 2px solid #e0e0e0; border-radius: 12px; cursor: pointer; text-align: left; transition: all 0.2s; display: flex; align-items: center; gap: 16px;"
                        onmouseover="this.style.borderColor='#0084ff'; this.style.background='#f8fbff'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,132,255,0.15)';"
                        onmouseout="this.style.borderColor='#e0e0e0'; this.style.background='white'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #0084ff 0%, #0066cc 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fas fa-store" style="color: white; font-size: 20px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 700; font-size: 16px; color: #2d2d2d; margin-bottom: 4px;">
                            <?php echo htmlspecialchars($branch['location_name']); ?>
                        </div>
                        <div style="font-size: 13px; color: #666;">
                            <i class="fas fa-location-dot" style="width: 14px; text-align: center; margin-right: 4px;"></i>
                            <?php echo htmlspecialchars($branch['city'] . ($branch['province'] ? ', ' . $branch['province'] : '')); ?>
                        </div>
                    </div>
                    <div>
                        <i class="fab fa-facebook-messenger" style="color: #0084ff; font-size: 24px;"></i>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Tutorial / Paste Guide Modal -->
    <div id="gwTutorialModal" class="gw-tut-modal" role="dialog" aria-modal="true" aria-hidden="true">
        <div id="gwTutorialBackdrop" class="gw-tut-backdrop"></div>
        <div class="gw-tut-container">
            <button id="gwTutorialClose" class="gw-tut-close" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>

            <div id="gwTutorialCopiedBanner" class="gw-tut-copied-banner">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Product details copied to your clipboard
            </div>

            <h2 class="gw-tut-title">How to send your inquiry</h2>

            <div class="gw-tut-steps">
                <div class="gw-tut-step">
                    <div class="gw-tut-step-num">1</div>
                    <div class="gw-tut-step-text">
                        <strong>On desktop</strong> — press <kbd>Ctrl</kbd> + <kbd>V</kbd> to paste in Messenger
                    </div>
                </div>
                <div class="gw-tut-step">
                    <div class="gw-tut-step-num">2</div>
                    <div class="gw-tut-step-text">
                        <strong>On mobile</strong> — tap the text area then tap <em>Paste</em>
                    </div>
                </div>
            </div>

            <div class="gw-tut-gifs">
                <div class="gw-tut-gif-item">
                    <p class="gw-tut-gif-caption">Desktop</p>
                    <div class="gw-tut-gif-box">
                        <img src="/assets/images/inquire-guide-desktop.gif" alt="Desktop paste tutorial" class="gw-tut-gif" onerror="this.closest('.gw-tut-gif-item').style.display='none'"/>
                    </div>
                </div>
                <div class="gw-tut-gif-item">
                    <p class="gw-tut-gif-caption">Mobile</p>
                    <div class="gw-tut-gif-box">
                        <img src="/assets/images/inquire-guide-mobile.gif" alt="Mobile paste tutorial" class="gw-tut-gif" onerror="this.closest('.gw-tut-gif-item').style.display='none'"/>
                    </div>
                </div>
            </div>

            <a id="gwTutorialProceedBtn" href="#" target="_blank" rel="noopener" class="gw-tut-proceed-btn">
                Open Messenger &amp; Send
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>

    <style>
    /* ── GREENWOOD TUTORIAL MODAL ── */
    .gw-tut-modal {
        position: fixed; inset: 0; z-index: 10000;
        display: flex; align-items: center; justify-content: center;
        pointer-events: none; visibility: hidden;
    }
    .gw-tut-modal.gw-tut-open { pointer-events: all; visibility: visible; }

    .gw-tut-backdrop {
        position: absolute; inset: 0;
        background: rgba(10, 20, 5, 0.78);
        backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
        opacity: 0; transition: opacity 0.3s ease;
    }
    .gw-tut-modal.gw-tut-open    .gw-tut-backdrop { opacity: 1; }
    .gw-tut-modal.gw-tut-closing .gw-tut-backdrop { opacity: 0; }

    .gw-tut-container {
        position: relative; z-index: 1;
        background: rgba(48, 56, 35, 0.85);
        backdrop-filter: blur(28px); -webkit-backdrop-filter: blur(28px);
        border: 1px solid rgba(143, 186, 82, 0.25);
        box-shadow: 0 8px 40px rgba(0,0,0,0.5), inset 0 1px 0 rgba(255,255,255,0.1);
        border-radius: 24px;
        padding: 40px 36px 36px;
        width: min(540px, 92vw); max-height: 90vh; overflow-y: auto;
        opacity: 0; transform: translateY(28px) scale(0.97);
        transition: opacity 0.35s ease, transform 0.35s cubic-bezier(0.34,1.4,0.64,1);
        scrollbar-width: thin; scrollbar-color: rgba(100,142,55,0.4) transparent;
    }
    .gw-tut-container::-webkit-scrollbar { width: 4px; }
    .gw-tut-container::-webkit-scrollbar-thumb { background: rgba(100,142,55,0.4); border-radius: 99px; }
    .gw-tut-modal.gw-tut-open    .gw-tut-container { opacity: 1; transform: translateY(0) scale(1); }
    .gw-tut-modal.gw-tut-closing .gw-tut-container { opacity: 0; transform: translateY(28px) scale(0.97); }

    .gw-tut-close {
        position: absolute; top: 16px; right: 16px;
        width: 34px; height: 34px; border-radius: 50%;
        background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.18);
        color: rgba(255,255,255,0.8); cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.2s, transform 0.2s;
    }
    .gw-tut-close:hover { background: rgba(239,68,68,0.35); border-color: #f87171; transform: scale(1.1); }
    .gw-tut-close svg { width: 15px; height: 15px; }

    .gw-tut-copied-banner {
        display: flex; align-items: center; gap: 10px;
        background: rgba(100, 142, 55, 0.18);
        border: 1px solid rgba(143, 186, 82, 0.4);
        border-radius: 12px; padding: 12px 16px;
        color: #8fba52;
        font-size: 0.9rem; font-weight: 700; margin-bottom: 24px;
    }
    .gw-tut-copied-banner svg { width: 18px; height: 18px; flex-shrink: 0; }

    .gw-tut-title {
        font-size: 1.35rem; font-weight: 800;
        color: #fff; margin-bottom: 20px;
    }

    .gw-tut-steps { display: flex; flex-direction: column; gap: 12px; margin-bottom: 28px; }
    .gw-tut-step  { display: flex; align-items: flex-start; gap: 14px; }
    .gw-tut-step-num {
        width: 28px; height: 28px; border-radius: 50%;
        background: linear-gradient(135deg, #303823, #648E37);
        color: #fff; font-size: 0.8rem; font-weight: 800;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0; margin-top: 1px;
    }
    .gw-tut-step-text { font-size: 0.9rem; color: rgba(255,255,255,0.8); line-height: 1.6; }
    .gw-tut-step-text strong { color: #fff; }
    .gw-tut-step-text em { font-style: normal; color: #8fba52; }
    .gw-tut-step-text kbd {
        display: inline-block;
        background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.25);
        border-radius: 5px; padding: 1px 7px; font-size: 0.8rem;
        font-family: monospace; color: #fff; line-height: 1.6;
    }

    .gw-tut-gifs { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 28px; }
    .gw-tut-gif-caption {
        font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em;
        color: #8fba52; text-transform: uppercase; margin-bottom: 8px;
    }
    .gw-tut-gif-box {
        border-radius: 12px; overflow: hidden;
        border: 1px solid rgba(143,186,82,0.2); background: rgba(0,0,0,0.3);
        min-height: 80px; max-height: 200px;
        display: flex; align-items: center; justify-content: center;
    }
    .gw-tut-gif { width: 100%; max-height: 200px; object-fit: cover; display: block; border-radius: 12px; }

    .gw-tut-proceed-btn {
        display: flex; align-items: center; justify-content: center; gap: 10px;
        width: 100%; padding: 15px 24px;
        background: linear-gradient(135deg, #303823, #648E37);
        border-radius: 50px; color: #fff;
        font-size: 0.95rem; font-weight: 700; text-decoration: none;
        transition: opacity 0.25s, transform 0.25s;
        box-shadow: 0 4px 20px rgba(48,56,35,0.5);
        border: 1px solid rgba(143,186,82,0.3); letter-spacing: 0.3px;
    }
    .gw-tut-proceed-btn:hover { opacity: 0.9; transform: translateY(-2px); color: #fff; }
    .gw-tut-proceed-btn svg { width: 18px; height: 18px; }

    @media (max-width: 480px) {
        .gw-tut-container { padding: 22px 18px; max-height: 95vh; border-radius: 18px; }
        .gw-tut-copied-banner { padding: 8px 12px; font-size: 0.78rem; margin-bottom: 14px; }
        .gw-tut-title { font-size: 1rem; margin-bottom: 14px; }
        .gw-tut-steps { gap: 8px; margin-bottom: 16px; }
        .gw-tut-step-num { width: 22px; height: 22px; font-size: 0.7rem; }
        .gw-tut-step-text { font-size: 0.78rem; }
        .gw-tut-gifs { gap: 8px; margin-bottom: 16px; }
        .gw-tut-gif-box { max-height: 130px; min-height: 60px; }
        .gw-tut-gif { max-height: 130px; }
        .gw-tut-proceed-btn { padding: 13px 18px; font-size: 0.87rem; }
    }
    </style>

</body>
</html>