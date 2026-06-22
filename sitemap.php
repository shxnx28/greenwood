<?php
/**
 * sitemap.php — Greenwood Philippines
 * Dynamic XML sitemap pointed to from robots.txt.
 */

// Basic rate limiting — max 10 requests per IP per minute via APCu (if available)
if (function_exists('apcu_fetch')) {
    $rateKey = 'sitemap_rl_' . md5($_SERVER['REMOTE_ADDR'] ?? '');
    $hits    = (int) apcu_fetch($rateKey);
    if ($hits >= 10) {
        http_response_code(429);
        header('Retry-After: 60');
        exit('Too Many Requests');
    }
    apcu_store($rateKey, $hits + 1, 60);
}

require_once 'admin/db.php';

// Serve cached version if < 24h old (reduces DB load from bots)
$cacheFile = __DIR__ . '/sitemap_cache.xml';
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
    header('Content-Type: application/xml; charset=UTF-8');
    header('X-Sitemap-Cache: HIT');
    readfile($cacheFile);
    exit;
}

$base  = 'https://greenwoodphilippines.com';
$today = date('Y-m-d');

// Real last-modified dates from DB
$productLastMod = $today;
$albumLastMod   = $today;

$r = $conn->query("SELECT DATE(GREATEST(MAX(p.updated_at), COALESCE(MAX(pv.updated_at), MAX(p.updated_at)))) AS d
                   FROM product p LEFT JOIN product_variant pv ON pv.product_id = p.product_id");
if ($r && $row = $r->fetch_assoc()) $productLastMod = $row['d'] ?? $today;

$r = $conn->query("SELECT DATE(MAX(uploaded_at)) AS d FROM project_images");
if ($r && $row = $r->fetch_assoc()) $albumLastMod = $row['d'] ?? $today;

ob_start();
header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    <!-- ── STATIC PAGES ──────────────────────────────────────── -->

    <url>
        <loc><?php echo $base; ?>/</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>

    <url>
        <loc><?php echo $base; ?>/catalog.php</loc>
        <lastmod><?php echo $productLastMod; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>

    <url>
        <loc><?php echo $base; ?>/catalog.php?category=wall</loc>
        <lastmod><?php echo $productLastMod; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>

    <url>
        <loc><?php echo $base; ?>/catalog.php?category=floor</loc>
        <lastmod><?php echo $productLastMod; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>

    <url>
        <loc><?php echo $base; ?>/catalog.php?category=ceiling</loc>
        <lastmod><?php echo $productLastMod; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>

    <url>
        <loc><?php echo $base; ?>/catalog.php?category=fence</loc>
        <lastmod><?php echo $productLastMod; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.85</priority>
    </url>

    <url>
        <loc><?php echo $base; ?>/projects.php</loc>
        <lastmod><?php echo $albumLastMod; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.75</priority>
    </url>

    <url>
        <loc><?php echo $base; ?>/room-simulator.php</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.70</priority>
    </url>

    <url>
        <loc><?php echo $base; ?>/influencers.php</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.70</priority>
    </url>

    <url>
        <loc><?php echo $base; ?>/faq.php</loc>
        <lastmod><?php echo $today; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.60</priority>
    </url>

    <!-- ── PRODUCT PAGES ─────────────────────────────────────── -->
<?php
// Join product_variant so any variant edit also bumps the product page lastmod
$sql = "SELECT p.product_id, p.product_name, p.image_path,
               DATE(GREATEST(
                   p.updated_at,
                   COALESCE(MAX(pv.updated_at), p.updated_at)
               )) AS lastmod,
               c.category_name
        FROM product p
        LEFT JOIN category c ON p.category_id = c.category_id
        LEFT JOIN product_variant pv ON pv.product_id = p.product_id
        GROUP BY p.product_id, p.product_name, p.image_path, p.updated_at, c.category_name
        ORDER BY p.product_id ASC";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $url      = $base . '/product-detail.php?id=' . intval($row['product_id']);
        $lastmod  = $row['lastmod'] ?: $today;
        $imgUrl   = !empty($row['image_path'])
                    ? $base . '/admin/' . ltrim($row['image_path'], '/')
                    : $base . '/assets/images/nobg.webp';
        $imgTitle = htmlspecialchars($row['product_name'] . ' – Greenwood Philippines');
?>
    <url>
        <loc><?php echo $url; ?></loc>
        <lastmod><?php echo $lastmod; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.80</priority>
        <image:image>
            <image:loc><?php echo htmlspecialchars($imgUrl); ?></image:loc>
            <image:title><?php echo $imgTitle; ?></image:title>
        </image:image>
    </url>
<?php
    }
}
?>

    <!-- ── PROJECT ALBUM PAGES ───────────────────────────────── -->
<?php
$sql = "SELECT album,
               MAX(title) AS title,
               DATE(MAX(uploaded_at)) AS last_updated,
               MAX(CASE WHEN is_featured = 1 THEN image_path END) AS cover_image
        FROM project_images
        WHERE album IS NOT NULL AND album != ''
        GROUP BY album
        ORDER BY last_updated DESC";

$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $albumSlug = urlencode($row['album']);
        $url       = $base . '/view_album.php?album=' . $albumSlug;
        $lastmod   = $row['last_updated'] ?: $today;
        $imgUrl    = !empty($row['cover_image'])
                     ? $base . '/' . ltrim($row['cover_image'], '/')
                     : $base . '/assets/images/nobg.webp';
        $imgTitle  = htmlspecialchars($row['title'] . ' – Greenwood Philippines Project');
?>
    <url>
        <loc><?php echo $url; ?></loc>
        <lastmod><?php echo $lastmod; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.70</priority>
        <image:image>
            <image:loc><?php echo htmlspecialchars($imgUrl); ?></image:loc>
            <image:title><?php echo $imgTitle; ?></image:title>
        </image:image>
    </url>
<?php
    }
}
?>

</urlset>
<?php
$xml = ob_get_clean();

// Save cache for 24h
file_put_contents($cacheFile, $xml);

echo $xml;