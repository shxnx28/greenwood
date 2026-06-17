<?php
/**
 * pixel.php — Greenwood Philippines
 * Single file: Meta Pixel base code + dynamic events per page + branch tracking.
 *
 * HOW TO USE:
 *   Add this ONE line inside <head> of every page, before </head>:
 *     <?php include 'pixel.php'; ?>
 *
 * PAGES & EVENTS:
 *   index.php            → PageView + branch card clicks tracked as FindLocation
 *   catalog.php          → PageView + ViewContent (category) or Search
 *   product-detail.php   → PageView + ViewContent (product name/ID)
 *   room-simulator.php   → PageView + CustomizeProduct
 *   projects.php         → PageView
 *   Any other page       → PageView only
 */

$current_script = basename($_SERVER['PHP_SELF']);
$query          = $_GET;

$extra_js = '';

switch ($current_script) {

    // ── Catalog / category listing ────────────────────────────────────────────
    case 'catalog.php':
        $category = isset($query['category']) ? htmlspecialchars($query['category'], ENT_QUOTES) : '';
        $search   = isset($query['search'])   ? htmlspecialchars($query['search'],   ENT_QUOTES) : '';

        if ($search !== '') {
            $extra_js = "fbq('track', 'Search', { search_string: '{$search}' });";
        } else {
            $content_category = $category !== '' ? $category : 'all';
            $extra_js = "fbq('track', 'ViewContent', { content_type: 'product_group', content_category: '{$content_category}' });";
        }
        break;

    // ── Single product detail page ────────────────────────────────────────────
    case 'product-detail.php':
        $pname = isset($product_name) ? addslashes($product_name) : '';
        $pid   = isset($product_id)   ? (int)$product_id          : 0;
        $extra_js = "fbq('track', 'ViewContent', { content_type: 'product', content_name: '{$pname}', content_ids: ['{$pid}'], content_category: 'home-finishing-materials' });";
        break;

    // ── Room Simulator ────────────────────────────────────────────────────────
    case 'room-simulator.php':
        $extra_js = "fbq('track', 'CustomizeProduct');";
        break;

    // ── Homepage: inject branch-click tracking JS ─────────────────────────────
    case 'index.php':
        // Branch card clicks (location cards) → FindLocation event
        // Contact modal "View Contacts" button clicks → also tracked
        // The JS below runs after DOM is ready and attaches listeners
        // to every .location-link and .btn-location-contact element.
        $extra_js = <<<'JS'
document.addEventListener('DOMContentLoaded', function () {

    // Track clicks on branch cards (the <a class="location-link"> wrapping each card)
    document.querySelectorAll('a.location-link[href*="facebook"]').forEach(function (el) {
        el.addEventListener('click', function () {
            var nameEl = el.querySelector('h4');
            var branchName = nameEl ? nameEl.innerText.trim() : 'Unknown Branch';
            fbq('track', 'FindLocation', {
                content_name: branchName,
                content_type: 'branch'
            });
        });
    });

    // Track "View Contacts" button clicks inside branch cards
    document.querySelectorAll('.btn-location-contact').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var card = btn.closest('.col-lg-6');
            var nameEl = card ? card.querySelector('h4') : null;
            var branchName = nameEl ? nameEl.innerText.trim() : 'Unknown Branch';
            fbq('trackCustom', 'ViewBranchContacts', {
                content_name: branchName,
                content_type: 'branch_contact'
            });
        });
    });

});
JS;
        break;

    default:
        $extra_js = '';
        break;
}
?>
<!-- ═══════════════════════════════════════════════
     Meta Pixel — Greenwood Philippines
     Pixel ID: 1501970074846574
     Managed via: pixel.php (single shared file)
     ═══════════════════════════════════════════════ -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');

    fbq('init', '1501970074846574');
    fbq('track', 'PageView');

    <?php if (!empty($extra_js)) echo $extra_js; ?>

</script>
<noscript>
    <img height="1" width="1" style="display:none"
         src="https://www.facebook.com/tr?id=1501970074846574&ev=PageView&noscript=1"/>
</noscript>
<!-- End Meta Pixel -->
