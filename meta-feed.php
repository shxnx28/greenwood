<?php
/**
 * meta-feed.php — Greenwood Philippines
 * Product feed for Meta Commerce Manager.
 *
 * URL to register in Meta: https://greenwoodphilippines.com/meta-feed.php
 * Set fetch frequency to: Daily (or Hourly for faster updates)
 *
 * Feed format: CSV (Meta standard fields)
 * Each row = one product variant (e.g. WPC Indoor Fluted - Black - 2900mm)
 */

require_once __DIR__ . '/admin/db.php';

// --- Simple rate limiting: block abusive crawlers ---
if (function_exists('apcu_fetch')) {
    $key  = 'metafeed_rl_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $hits = (int) apcu_fetch($key);
    if ($hits >= 20) {
        http_response_code(429);
        header('Retry-After: 60');
        exit('Too Many Requests');
    }
    apcu_store($key, $hits + 1, 60);
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="greenwood-meta-feed.csv"');

$base_url   = 'https://greenwoodphilippines.com';
$image_base = $base_url . '/admin/';

// --- CSV output helper ---
function csv_row(array $fields): string {
    return implode(',', array_map(function($v) {
        // Wrap in quotes, escape any internal quotes
        return '"' . str_replace('"', '""', (string) $v) . '"';
    }, $fields)) . "\n";
}

// --- Header row (Meta required + recommended fields) ---
echo csv_row([
    'id',
    'title',
    'description',
    'availability',
    'condition',
    'price',
    'sale_price',
    'link',
    'image_link',
    'brand',
    'google_product_category',
    'product_type',
    'custom_label_0',   // category (Wall / Floor / Ceiling / Fence)
    'custom_label_1',   // indoor / outdoor / indoor&outdoor
    'custom_label_2',   // color name
    'custom_label_3',   // size / dimensions
]);

// --- Main query: join all relevant tables ---
$sql = "
    SELECT
        pv.variant_id,
        pv.sku,
        pv.stock,
        pv.image_path         AS variant_image,
        p.product_id,
        p.product_name,
        p.description,
        p.image_path          AS parent_image,
        c.category_name,
        c.slug                AS category_slug,
        pt.product_type_name,
        col.color_name,
        s.size_name,
        s.sell_unit,
        s.pieces_per_box,
        -- Retail price (price_type = 'retail')
        MAX(CASE WHEN pr.price_type = 'retail'    THEN pr.price END) AS retail_price,
        MAX(CASE WHEN pr.price_type = 'retail'    THEN pr.min_quantity END) AS retail_min_qty,
        -- Wholesale price (price_type = 'wholesale')
        MAX(CASE WHEN pr.price_type = 'wholesale' THEN pr.price END) AS wholesale_price,
        MAX(CASE WHEN pr.price_type = 'wholesale' THEN pr.min_quantity END) AS wholesale_min_qty
    FROM product_variant pv
    INNER JOIN product      p   ON p.product_id       = pv.product_id
    INNER JOIN category     c   ON c.category_id      = p.category_id
    INNER JOIN product_type pt  ON pt.product_type_id = p.product_type_id
    INNER JOIN color        col ON col.color_id        = pv.color_id
    INNER JOIN size         s   ON s.size_id           = pv.size_id
    LEFT  JOIN price        pr  ON pr.variant_id       = pv.variant_id
    GROUP BY
        pv.variant_id, pv.sku, pv.stock, pv.image_path,
        p.product_id, p.product_name, p.description, p.image_path,
        c.category_name, c.slug, pt.product_type_name,
        col.color_name, s.size_name, s.sell_unit, s.pieces_per_box
    ORDER BY p.product_name, col.color_name
";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    exit('Query error: ' . $conn->error);
}

while ($row = $result->fetch_assoc()) {

    // --- ID: unique per variant ---
    $id = 'GW-' . $row['variant_id'];

    // --- Title: "Product Name - Color - Size" ---
    $title = $row['product_name'] . ' - ' . $row['color_name'] . ' - ' . $row['size_name'];

    // --- Description: fallback to product name if empty ---
    $description = !empty($row['description'])
        ? $row['description']
        : 'Premium ' . $row['product_name'] . ' by Greenwood Philippines.';

    // --- Availability ---
    $availability = ($row['stock'] > 0) ? 'in stock' : 'out of stock';

    // --- Price: Meta requires retail price in feed ---
    // Format: "1200.00 PHP"
    $retail_price = $row['retail_price'] !== null
        ? number_format((float)$row['retail_price'], 2, '.', '') . ' PHP'
        : '0.00 PHP';

    // --- Sale price: show wholesale as sale price ---
    // Meta will display this as a discounted price alongside retail
    $sale_price = '';
    if ($row['wholesale_price'] !== null && $row['wholesale_price'] < $row['retail_price']) {
        $sale_price = number_format((float)$row['wholesale_price'], 2, '.', '') . ' PHP';
    }

    // --- Product page URL ---
    $link = $base_url . gw_product_url($row['product_id'], $row['product_name']);

    // --- Image: prefer variant image, fall back to parent image ---
    $image_path = !empty($row['variant_image'])
        ? $row['variant_image']
        : $row['parent_image'];
    $image_link = $image_base . ltrim($image_path, '/');

    // --- Brand ---
    $brand = 'Greenwood Philippines';

    // --- Google product category (best fit for home finishing materials) ---
    $google_category = match(strtolower($row['category_name'])) {
        'wall'    => 'Home & Garden > Decor > Wall Decor',
        'floor'   => 'Home & Garden > Flooring',
        'ceiling' => 'Home & Garden > Decor > Ceiling Decor',
        'fence'   => 'Home & Garden > Lawn & Garden > Fencing',
        default   => 'Home & Garden > Building & Construction',
    };

    // --- Product type: breadcrumb-style for Meta catalog ---
    $product_type = 'Home Finishing > ' . $row['category_name'] . ' > ' . $row['product_name'];

    // --- Custom labels for Meta audience targeting & filtering ---
    $label_category = $row['category_name'];
    $label_type     = $row['product_type_name'];
    $label_color    = $row['color_name'];
    $label_size     = $row['size_name'];

    echo csv_row([
        $id,
        $title,
        $description,
        $availability,
        'new',
        $retail_price,
        $sale_price,
        $link,
        $image_link,
        $brand,
        $google_category,
        $product_type,
        $label_category,
        $label_type,
        $label_color,
        $label_size,
    ]);
}

$result->free();
$conn->close();
