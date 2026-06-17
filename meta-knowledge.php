<?php
/**
 * meta-knowledge.php — Greenwood Philippines
 *
 * PURPOSE:
 *   This page is the live knowledge base for the Meta AI Agent (Meta Business Suite).
 *   It renders all branch locations, contacts, products, and FAQs as clean readable text
 *   pulled directly from the database — so it is always up to date.
 *
 * HOW TO CONNECT TO META AI AGENT:
 *   1. Go to Meta Business Suite → Inbox → Automations → AI Agent
 *   2. Under "Business Information" or "Knowledge Base", add a website URL source.
 *   3. Enter: https://greenwoodphilippines.com/meta-knowledge.php
 *   4. Meta will crawl this page and use it to answer customer questions.
 *
 * SECURITY NOTE:
 *   This page is public (read-only, no sensitive data exposed).
 *   It only shows what a customer would normally see on your website.
 */

require_once __DIR__ . '/admin/db.php';

// ─── Fetch all active locations with their contacts ──────────────────────────
$locations = [];
$sql = "SELECT * FROM warehouse_location WHERE is_active = 1 ORDER BY display_order ASC, location_id ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $lid = $row['location_id'];
        $cSql = "SELECT contact_name, contact_number, contact_role, is_primary
                 FROM branch_contacts
                 WHERE location_id = ? AND is_active = 1
                 ORDER BY is_primary DESC, display_order ASC";
        $cStmt = $conn->prepare($cSql);
        $cStmt->bind_param("i", $lid);
        $cStmt->execute();
        $cResult = $cStmt->get_result();
        $row['contacts'] = [];
        while ($contact = $cResult->fetch_assoc()) {
            $row['contacts'][] = $contact;
        }
        $cStmt->close();
        $locations[] = $row;
    }
}

// ─── Fetch all active products ────────────────────────────────────────────────
$products = [];
$pSql = "SELECT p.product_name, p.description, c.category_name
         FROM product p
         LEFT JOIN category c ON p.category_id = c.category_id
         WHERE p.is_active = 1
         ORDER BY c.category_name ASC, p.product_name ASC";
$pResult = $conn->query($pSql);
if ($pResult && $pResult->num_rows > 0) {
    while ($row = $pResult->fetch_assoc()) {
        $products[] = $row;
    }
}

$conn->close();

// ─── Helper: safe output ─────────────────────────────────────────────────────
function safe(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ─── Build full address string for a location ────────────────────────────────
function buildAddress(array $loc): string {
    $parts = array_filter([
        $loc['address_line1'] ?? '',
        $loc['address_line2'] ?? '',
        $loc['address_line3'] ?? '',
        $loc['city']          ?? '',
        $loc['province']      ?? '',
    ], fn($p) => trim($p) !== '');
    return implode(', ', $parts);
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="index, follow">
    <title>Greenwood Philippines — Branch Locations, Contacts & Products</title>
    <meta name="description" content="Complete list of Greenwood Philippines branch locations, contact numbers, products, and business information for customer support.">
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 24px; color: #222; line-height: 1.7; }
        h1 { color: #1a6e2e; }
        h2 { color: #1a6e2e; border-bottom: 2px solid #e0e0e0; padding-bottom: 6px; margin-top: 40px; }
        h3 { color: #333; margin-bottom: 4px; }
        .branch { background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px; }
        .branch p { margin: 4px 0; }
        .contact-list { margin: 8px 0 0 0; padding: 0; list-style: none; }
        .contact-list li { padding: 2px 0; }
        .tag { display: inline-block; background: #e6f4ea; color: #1a6e2e; border-radius: 4px; padding: 1px 8px; font-size: 0.82em; margin-left: 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #1a6e2e; color: #fff; text-align: left; padding: 8px 12px; }
        td { padding: 7px 12px; border-bottom: 1px solid #eee; }
        tr:last-child td { border-bottom: none; }
        .updated { font-size: 0.85em; color: #888; margin-top: 40px; }
    </style>
</head>
<body>

<h1>Greenwood Philippines — Business Information</h1>
<p>
    <strong>Greenwood Philippines</strong> is a manufacturer and direct supplier of warehouse-priced
    home finishing materials including wall panels, SPC flooring, PVC ceilings, fencing solutions,
    and more. We serve residential, commercial, and contractor customers nationwide.
</p>
<p>
    <strong>Website:</strong> <a href="https://greenwoodphilippines.com">greenwoodphilippines.com</a><br>
    <strong>Main Facebook Page:</strong> <a href="https://www.facebook.com/greenwoodphilippines">facebook.com/greenwoodphilippines</a><br>
    <strong>Product Catalog:</strong> <a href="https://greenwoodphilippines.com/catalog.php">greenwoodphilippines.com/catalog.php</a>
</p>

<!-- ═══════════════════════════════════════════════════════════════
     SECTION 1: BRANCH LOCATIONS & CONTACTS
     ═══════════════════════════════════════════════════════════════ -->
<h2>Branch Locations &amp; Contact Numbers</h2>
<p>We have <?= count($locations) ?> active branches nationwide. The Pulilan Bulacan main branch is open Monday to Sunday. All other branches are open Monday to Saturday.</p>

<?php foreach ($locations as $i => $loc): ?>
<div class="branch">
    <h3><?= safe($loc['location_name']) ?><?php if ($i === 0): ?> <span class="tag">Main Branch</span><?php endif; ?></h3>

    <?php $address = buildAddress($loc); if ($address): ?>
    <p><strong>Address:</strong> <?= safe($address) ?></p>
    <?php endif; ?>

    <?php if (!empty($loc['special_note'])): ?>
    <p><strong>Landmark / Note:</strong> <?= safe($loc['special_note']) ?></p>
    <?php endif; ?>

    <?php if (!empty($loc['operating_hours'])): ?>
    <p><strong>Hours:</strong> <?= safe($loc['operating_hours']) ?></p>
    <?php endif; ?>

    <?php if (!empty($loc['facebook_url'])): ?>
    <p><strong>Facebook:</strong> <a href="<?= safe($loc['facebook_url']) ?>"><?= safe($loc['facebook_url']) ?></a></p>
    <?php endif; ?>

    <?php if (!empty($loc['contacts'])): ?>
    <p><strong>Contact Numbers:</strong></p>
    <ul class="contact-list">
        <?php foreach ($loc['contacts'] as $c): ?>
        <li>
            📞 <strong><?= safe($c['contact_name']) ?></strong>
            — <?= safe($c['contact_number']) ?>
            <?php if (!empty($c['contact_role'])): ?><span class="tag"><?= safe($c['contact_role']) ?></span><?php endif; ?>
            <?php if ($c['is_primary']): ?><span class="tag">Primary</span><?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<?php endforeach; ?>


<!-- ═══════════════════════════════════════════════════════════════
     SECTION 2: PRODUCTS
     ═══════════════════════════════════════════════════════════════ -->
<h2>Products</h2>
<p>
    All products are available at warehouse prices. Retail and wholesale (bulk) pricing is available.
    For current pricing, visit: <a href="https://greenwoodphilippines.com/catalog.php">greenwoodphilippines.com/catalog.php</a>
</p>

<?php if (!empty($products)):
    // Group by category
    $grouped = [];
    foreach ($products as $p) {
        $cat = $p['category_name'] ?? 'General';
        $grouped[$cat][] = $p;
    }
?>
<?php foreach ($grouped as $catName => $catProducts): ?>
<h3><?= safe($catName) ?></h3>
<table>
    <tr><th>Product Name</th><th>Description</th></tr>
    <?php foreach ($catProducts as $p): ?>
    <tr>
        <td><?= safe($p['product_name']) ?></td>
        <td><?= safe($p['description'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endforeach; ?>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════════════════════
     SECTION 3: FREQUENTLY ASKED QUESTIONS
     ═══════════════════════════════════════════════════════════════ -->
<h2>Frequently Asked Questions</h2>

<p><strong>Q: Where are you located?</strong><br>
We have <?= count($locations) ?> branches nationwide. See the branch list above for full addresses and contact numbers.</p>

<p><strong>Q: Are you open on Sundays?</strong><br>
Yes — the Pulilan Bulacan main branch is open Monday to Sunday. All other branches are open Monday to Saturday.</p>

<p><strong>Q: Do you offer wholesale or bulk pricing?</strong><br>
Yes. Wholesale pricing is available with a minimum order quantity that varies per product. Contact your nearest branch for bulk order inquiries.</p>

<p><strong>Q: Are your products waterproof?</strong><br>
Yes. Most panels including WPC, PVC, and PU products are waterproof and designed for indoor and outdoor use depending on the variant.</p>

<p><strong>Q: Do you offer installation services?</strong><br>
For installation inquiries, please contact your nearest branch directly.</p>

<p><strong>Q: Do you ship nationwide?</strong><br>
For shipping and delivery inquiries, please contact your nearest branch or message our main Facebook page: facebook.com/greenwoodphilippines</p>

<p><strong>Q: How can I see the products and prices?</strong><br>
Visit our product catalog at <a href="https://greenwoodphilippines.com/catalog.php">greenwoodphilippines.com/catalog.php</a> or visit any branch in person.</p>

<p><strong>Q: Are there new branches opening?</strong><br>
Yes — branches are coming soon in Mandaue Cebu, Cebu City, Sta. Rosa Laguna, and Legazpi City Bicol.</p>

<p class="updated">Last updated: <?= date('F j, Y, g:i a') ?> (auto-generated from live database)</p>

</body>
</html>
