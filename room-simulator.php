<?php
require_once 'admin/db.php';

$sql = "
    SELECT
        pv.variant_id, pv.image_path, pv.sku,
        p.product_name, p.product_id,
        c.color_name, c.hex_code,
        s.size_name,
        pt.product_type_name, cat.category_name,
        rsa.asset_id, rsa.real_width_cm, rsa.real_height_cm,
        rsa.crop_left_pct, rsa.crop_right_pct, rsa.crop_top_pct, rsa.crop_bottom_pct,
        rsa.is_tileable, rsa.surface_type AS preset_surface
    FROM product_variant pv
    INNER JOIN product p ON pv.product_id = p.product_id
    INNER JOIN color c ON pv.color_id = c.color_id
    INNER JOIN size s ON pv.size_id = s.size_id
    LEFT JOIN product_type pt ON p.product_type_id = pt.product_type_id
    LEFT JOIN category cat ON p.category_id = cat.category_id
    LEFT JOIN room_simulator_asset rsa ON rsa.variant_id = pv.variant_id
    WHERE pv.image_path IS NOT NULL AND pv.image_path != ''
    ORDER BY p.product_name ASC, c.color_name ASC
";

$result = $conn->query($sql);
$variants = [];
$products_map = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $variants[] = $row;
        $pid = $row['product_id'];
        if (!isset($products_map[$pid])) {
            $products_map[$pid] = [
                'product_id'        => $pid,
                'product_name'      => $row['product_name'],
                'product_type_name' => $row['product_type_name'],
                'category_name'     => $row['category_name'],
                'variants'          => []
            ];
        }
        $img = $row['image_path'];
        if (strpos($img, 'uploads/') === 0) $img = '/admin/' . $img;
        $row['image_url'] = $img;
        $row['rsa_width_cm']  = $row['real_width_cm']  ?? null;
        $row['rsa_height_cm'] = $row['real_height_cm'] ?? null;
        $row['rsa_crop'] = $row['asset_id'] ? [
            'left'   => (int)($row['crop_left_pct']   ?? 0),
            'right'  => (int)($row['crop_right_pct']  ?? 0),
            'top'    => (int)($row['crop_top_pct']     ?? 0),
            'bottom' => (int)($row['crop_bottom_pct'] ?? 0),
        ] : null;
        $row['rsa_tileable']       = (int)($row['is_tileable'] ?? 1);
        $row['rsa_preset_surface'] = $row['preset_surface'] ?? 'floor';
        $products_map[$pid]['variants'][] = $row;
    }
}

$products_list = array_values($products_map);
$types = array_unique(array_filter(array_column($products_list, 'product_type_name')));
sort($types);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
<title>Room Simulator | Greenwood Philippines</title>

<!-- Primary SEO Meta Tags -->
<meta name="description" content="Try Greenwood Philippines' free Room Simulator. Visualize how our wall panels, flooring, and ceiling materials will look in your space before you buy.">
<meta name="robots" content="index, follow">
<meta name="author" content="Greenwood Philippines">
<link rel="canonical" href="https://greenwoodphilippines.com/room-simulator">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="https://greenwoodphilippines.com/room-simulator">
<meta property="og:title" content="Room Simulator | Greenwood Philippines">
<meta property="og:description" content="Visualize Greenwood Philippines materials in your room before purchasing. Try our free interactive room simulator.">
<meta property="og:image" content="https://greenwoodphilippines.com/assets/images/nobg.webp">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Greenwood Philippines Room Simulator – Visualize Materials">
<meta property="og:site_name" content="Greenwood Philippines">
<meta property="og:locale" content="en_PH">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Room Simulator | Greenwood Philippines">
<meta name="twitter:description" content="Visualize wall, floor, and ceiling materials in your room with the Greenwood Philippines Room Simulator.">
<meta name="twitter:image" content="https://greenwoodphilippines.com/assets/images/nobg.webp">
<meta name="twitter:image:alt" content="Greenwood Philippines Room Simulator – Visualize Materials">

<!-- Schema.org Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebApplication",
  "name": "Greenwood Philippines Room Simulator",
  "description": "An interactive room simulator to visualize wall, floor, and ceiling materials from Greenwood Philippines.",
  "url": "https://greenwoodphilippines.com/room-simulator",
  "applicationCategory": "DesignApplication",
  "operatingSystem": "Web",
  "isPartOf": {
    "@type": "WebSite",
    "name": "Greenwood Philippines",
    "url": "https://greenwoodphilippines.com"
  }
}
</script>

<link href="/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/css/fontawesome.css">
<link rel="stylesheet" href="/css/style.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;overflow:hidden;}
body{background:#1a1f14;font-family:'Segoe UI',system-ui,sans-serif;color:#2d3528;}

/* ── TOPBAR ── */
.sim-topbar{
  background:#1b3d1c;
  color:#fff;padding:0 12px;height:44px;
  display:flex;align-items:center;gap:8px;
  box-shadow:0 2px 10px rgba(0,0,0,.45);
  position:relative;z-index:200;flex-shrink:0;
}
.sim-topbar .sim-logo{height:22px;flex-shrink:0;}
.topbar-brand{display:flex;flex-direction:column;line-height:1.1;flex-shrink:0;flex:1;}
.topbar-brand .brand-name{font-size:.52rem;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.9px;}
.topbar-brand .brand-sub{font-size:.76rem;font-weight:700;color:#fff;letter-spacing:.1px;}
.topbar-surf{display:none;}
.sim-topbar h1{font-size:.85rem;font-weight:700;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.topbar-pills{display:flex;gap:5px;align-items:center;flex-shrink:0;}
.pill{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.16);color:rgba(255,255,255,.9);
  height:28px;padding:0 10px;border-radius:7px;font-size:.7rem;font-weight:600;cursor:pointer;
  text-decoration:none;white-space:nowrap;transition:background .15s;
  display:inline-flex;align-items:center;justify-content:center;gap:5px;flex-shrink:0;}
.pill:hover{background:rgba(255,255,255,.2);color:#fff;}
.pill .pill-text{display:inline;}

/* ── MOBILE SURFACE BAR (below topbar, centered) ── */
.mob-surface-bar{
  display:none;background:#16301a;border-bottom:1px solid rgba(255,255,255,.08);
  padding:0 10px;height:38px;align-items:center;justify-content:center;gap:6px;
  overflow-x:auto;flex-shrink:0;position:relative;z-index:190;
}
.mob-surface-bar::-webkit-scrollbar{display:none;}
.mob-surface-bar .surf-tab{
  font-size:.67rem;font-weight:600;padding:4px 12px;
  border:1px solid rgba(255,255,255,.15);border-radius:6px;
  background:rgba(255,255,255,.06);color:rgba(255,255,255,.75);
  white-space:nowrap;flex-shrink:0;letter-spacing:.15px;transition:all .15s;
}
.mob-surface-bar .surf-tab.active{background:#fff;color:#1b3d1c;border-color:#fff;font-weight:700;}
.mob-surface-bar .surf-tab:not(.active):hover{background:rgba(255,255,255,.14);}
.mob-surface-bar-label{display:none;}

/* ── LAYOUT ──
   Mobile: subtract topbar(44px) + mob-surface-bar(36px) + mob-toolbar(44px) */
.sim-wrap{display:flex;height:calc(100vh - 44px);overflow:hidden;position:relative;touch-action:none;}

/* ── SIDEBAR ── */
.sim-sidebar{
  width:256px;min-width:256px;background:#fff;border-right:1px solid #d4e0c8;
  display:flex;flex-direction:column;overflow:hidden;z-index:110;flex-shrink:0;
  transition:transform .28s cubic-bezier(.4,0,.2,1);
  touch-action:pan-y;
}
.sidebar-tabs{display:flex;background:#f0f7e8;border-bottom:2px solid #d4e0c8;}
.stab{flex:1;padding:8px 4px;font-size:.68rem;font-weight:700;text-align:center;
  cursor:pointer;color:#648E37;background:none;border:none;text-transform:uppercase;
  letter-spacing:.6px;border-bottom:3px solid transparent;margin-bottom:-2px;transition:all .18s;}
.stab.active{color:#2c5f2d;border-bottom-color:#2c5f2d;background:#fff;}
.stab:hover:not(.active){background:#e4f0d8;}
.stab-panel{display:none;flex:1;flex-direction:column;overflow:hidden;}
.stab-panel.active{display:flex;}

.sidebar-head{padding:10px 12px 8px;background:#f6fbf0;border-bottom:1px solid #d4e0c8;}
.sidebar-head h6{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#2c5f2d;margin-bottom:8px;}
.sim-search{width:100%;border:1px solid #c8ddb0;border-radius:7px;padding:6px 10px;font-size:.8rem;outline:none;background:#fff;}
.sim-search:focus{border-color:#648E37;box-shadow:0 0 0 3px rgba(100,142,55,.12);}
.sim-type-filter{width:100%;border:1px solid #c8ddb0;border-radius:7px;padding:5px 8px;font-size:.76rem;margin-top:6px;outline:none;background:#fff;}
.products-list{flex:1;overflow-y:auto;padding:8px 10px;}
.products-list::-webkit-scrollbar{width:4px;}
.products-list::-webkit-scrollbar-thumb{background:#c8ddb0;border-radius:3px;}
.prod-group{margin-bottom:10px;}
.prod-group-title{font-size:.65rem;font-weight:700;color:#648E37;text-transform:uppercase;
  letter-spacing:.5px;padding:3px 0 5px;border-bottom:1px solid #e8f0de;margin-bottom:6px;}
.variant-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:4px;}
.variant-thumb{border-radius:7px;overflow:hidden;border:2px solid transparent;
  cursor:grab;transition:all .15s;position:relative;background:#f0f0f0;aspect-ratio:1;}
.variant-thumb:hover{border-color:#648E37;transform:scale(1.06);box-shadow:0 4px 12px rgba(100,142,55,.3);}
.variant-thumb img{width:100%;height:100%;object-fit:cover;display:block;pointer-events:none;}
.thumb-label{position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.55);
  color:#fff;font-size:.54rem;padding:2px 3px;text-align:center;overflow:hidden;
  text-overflow:ellipsis;white-space:nowrap;}

/* ── FURNITURE TAB ── */
.furn-head{padding:10px 12px 8px;background:#f6fbf0;border-bottom:1px solid #d4e0c8;}
.furn-head h6{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#2c5f2d;margin-bottom:7px;}
.furn-cats{display:flex;gap:4px;flex-wrap:wrap;}
.fcat{padding:3px 8px;border-radius:14px;font-size:.66rem;font-weight:600;
  border:1px solid #c8ddb0;background:#f0f7e8;cursor:pointer;color:#555;transition:all .15s;}
.fcat.active{background:#2c5f2d;color:#fff;border-color:#2c5f2d;}
.furn-list{flex:1;overflow-y:auto;padding:8px 10px;}
.furn-list::-webkit-scrollbar{width:4px;}
.furn-list::-webkit-scrollbar-thumb{background:#c8ddb0;border-radius:3px;}
.furn-cat-group{margin-bottom:12px;}
.furn-cat-title{font-size:.65rem;font-weight:700;color:#648E37;text-transform:uppercase;
  letter-spacing:.5px;padding:3px 0 5px;border-bottom:1px solid #e8f0de;margin-bottom:7px;}
.furn-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:6px;}
.furn-thumb{
  border-radius:9px;border:2px solid #e8f0de;cursor:grab;transition:all .16s;
  background:#fff;aspect-ratio:1;display:flex;flex-direction:column;
  align-items:stretch;box-shadow:0 1px 4px rgba(0,0,0,.07);overflow:hidden;
}
.furn-thumb:hover{border-color:#648E37;transform:scale(1.05) translateY(-2px);box-shadow:0 6px 18px rgba(100,142,55,.25);}
.furn-thumb:active{cursor:grabbing;transform:scale(.97);}
.furn-thumb canvas{flex:1;width:100%;display:block;pointer-events:none;}
.furn-label{font-size:.58rem;font-weight:700;color:#2c5f2d;text-align:center;
  padding:2px 4px 4px;background:#f6fbf0;border-top:1px solid #e8f0de;}
.furn-size-row{padding:7px 12px;background:#f6fbf0;border-top:1px solid #e4eedd;
  display:flex;align-items:center;gap:7px;flex-shrink:0;}
.furn-size-row label{font-size:.68rem;font-weight:600;color:#555;white-space:nowrap;}
.furn-size-row input{flex:1;accent-color:#2c5f2d;}
.furn-size-row span{font-size:.68rem;color:#2c5f2d;font-weight:700;min-width:30px;}

/* ── CANVAS AREA ── */
.sim-canvas-area{flex:1;display:flex;flex-direction:column;overflow:hidden;background:#20261a;min-width:0;touch-action:none;}
.sim-toolbar{
  background:#fff;border-bottom:1px solid #d4e0c8;padding:5px 10px;
  display:flex;align-items:center;gap:5px;flex-wrap:wrap;flex-shrink:0;
  overflow-x:auto;
}
.sim-toolbar::-webkit-scrollbar{height:3px;}
.sim-toolbar::-webkit-scrollbar-thumb{background:#c8ddb0;}
.sim-toolbar label{font-size:.72rem;font-weight:600;color:#555;white-space:nowrap;}
.sim-toolbar select{font-size:.72rem;border:1px solid #c8ddb0;border-radius:6px;padding:3px 6px;outline:none;}
.sim-toolbar select:focus{border-color:#648E37;}
.tsep{width:1px;height:18px;background:#dde;flex-shrink:0;}
.btool{padding:3px 8px!important;font-size:.7rem!important;border-radius:6px!important;white-space:nowrap;}
.sval{font-size:.7rem;color:#555;min-width:28px;}
.surf-tabs{display:flex;gap:3px;flex-wrap:wrap;}
.surf-tab{padding:3px 8px;border-radius:16px;font-size:.68rem;border:1px solid #c8ddb0;
  background:#f6fbf0;cursor:pointer;color:#555;transition:all .15s;white-space:nowrap;}
.surf-tab.active{background:#2c5f2d;color:#fff;border-color:#2c5f2d;}
.fill-box{background:#f0f9e8;border:1px solid #c8ddb0;border-radius:8px;
  padding:4px 9px;display:flex;align-items:center;gap:7px;flex-wrap:wrap;}
.fill-box label{font-size:.68rem;font-weight:600;color:#2c5f2d;}
.fill-box select{font-size:.68rem;border:1px solid #c8ddb0;border-radius:5px;padding:2px 5px;outline:none;}
.fill-box input[type=checkbox]{accent-color:#2c5f2d;}
.kbd-hint{font-size:.62rem;color:#aaa;padding:2px 8px;background:#f6fbf0;
  border-bottom:1px solid #e4eedd;text-align:center;flex-shrink:0;white-space:nowrap;
  overflow:hidden;text-overflow:ellipsis;}

.canvas-wrap{
  flex:1;overflow:hidden;display:flex;align-items:center;justify-content:center;
  position:relative;background:#20261a;touch-action:none;
}
/* The canvas-container scales via JS transform-origin center */
.canvas-container{
  position:relative;display:inline-block;
  transform-origin:center center;
  touch-action:none;
}
#roomCanvas{display:block;box-shadow:0 12px 48px rgba(0,0,0,.5);border-radius:3px;cursor:default;touch-action:none;}
#roomCanvas.drag-over{outline:3px dashed #7fc04d;}
#snapOverlay{position:absolute;top:0;left:0;pointer-events:none;z-index:6;}
#gridOverlay{position:absolute;top:0;left:0;pointer-events:none;z-index:4;display:none;}
.fill-hint{
  position:absolute;background:rgba(44,95,45,.92);color:#fff;
  padding:6px 14px;border-radius:20px;font-size:.74rem;pointer-events:none;
  opacity:0;transition:opacity .3s;top:10px;left:50%;transform:translateX(-50%);
  white-space:nowrap;z-index:5;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.3);
}

/* Zoom controls overlay */
.zoom-controls{
  position:absolute;bottom:10px;right:10px;z-index:50;
  display:flex;flex-direction:column;gap:4px;
}
.zoom-btn{
  width:34px;height:34px;border-radius:8px;border:none;
  background:rgba(44,95,45,.88);color:#fff;font-size:16px;font-weight:700;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  box-shadow:0 2px 8px rgba(0,0,0,.3);backdrop-filter:blur(4px);
  transition:background .15s;
}
.zoom-btn:active{background:rgba(44,95,45,1);}
.zoom-level{
  background:rgba(0,0,0,.5);color:#fff;font-size:.6rem;font-weight:700;
  text-align:center;border-radius:5px;padding:2px 4px;
  letter-spacing:.5px;
}
.fill-hint.show{opacity:1;}

/* ── RIGHT PANEL ── */
.sim-right{width:215px;min-width:215px;background:#fff;border-left:1px solid #d4e0c8;
  display:flex;flex-direction:column;overflow:hidden;flex-shrink:0;}
.rhead{padding:10px 12px 8px;background:#f6fbf0;border-bottom:1px solid #d4e0c8;}
.rhead h6{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#2c5f2d;}
.rhead p{font-size:.6rem;color:#888;margin-top:2px;}
.layers-list{flex:1;overflow-y:auto;padding:6px;}
.layers-list::-webkit-scrollbar{width:4px;}
.layers-list::-webkit-scrollbar-thumb{background:#c8ddb0;border-radius:3px;}
.layer-item{
  display:flex;align-items:center;gap:6px;padding:6px 5px;
  border-radius:8px;border:1px solid #e4eedd;margin-bottom:4px;
  cursor:pointer;transition:all .15s;background:#fafff6;position:relative;
}
.layer-item:hover{border-color:#648E37;background:#f0f9e8;}
.layer-item.sel{border-color:#2c5f2d;background:#e4f5d8;box-shadow:0 0 0 2px rgba(44,95,45,.15);}
.layer-item.ldrag-top{border-top:3px solid #2c5f2d;}
.layer-item.ldrag-bot{border-bottom:3px solid #2c5f2d;}
.layer-item.ldragging{opacity:.35;}
.lhandle{color:#bbb;font-size:11px;cursor:grab;padding:2px 3px;flex-shrink:0;
  display:flex;flex-direction:column;gap:2px;}
.lhandle span{display:block;width:11px;height:1.5px;background:currentColor;border-radius:1px;}
.lhandle:active{cursor:grabbing;}
.layer-item img,.layer-item .lthumb{width:30px;height:30px;object-fit:cover;border-radius:5px;flex-shrink:0;background:#f0f7e8;}
.linfo{flex:1;min-width:0;}
.lname{font-size:.66rem;font-weight:600;color:#333;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.lsub{font-size:.58rem;color:#888;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.ldel{background:none;border:none;color:#dc3545;font-size:11px;cursor:pointer;padding:2px 3px;border-radius:4px;flex-shrink:0;}
.ldel:hover{background:#fdecea;}
.ractions{padding:8px;border-top:1px solid #e4eedd;display:flex;flex-direction:column;gap:6px;}

/* ── CROP MODAL ── */
.modal-ov{position:fixed;inset:0;background:rgba(0,0,0,.72);z-index:2000;
  display:flex;align-items:center;justify-content:center;padding:10px;}
.modal-ov.hidden{display:none;}
.crop-box{background:#fff;border-radius:14px;padding:20px;max-width:660px;width:100%;
  box-shadow:0 20px 60px rgba(0,0,0,.4);max-height:90vh;overflow-y:auto;}
.crop-box h5{font-size:.95rem;font-weight:700;color:#2c5f2d;margin-bottom:14px;}
#cropCanvas{border:2px dashed #c8ddb0;border-radius:6px;display:block;margin:0 auto;max-width:100%;}
.crop-controls{margin-top:12px;display:flex;flex-direction:column;gap:9px;}
.crop-row{display:flex;align-items:center;gap:8px;}
.crop-row label{font-size:.76rem;font-weight:600;color:#555;min-width:90px;}
.crop-row input[type=range]{flex:1;}
.crop-row span{font-size:.72rem;color:#888;min-width:34px;text-align:right;}
.crop-acts{display:flex;gap:8px;justify-content:flex-end;margin-top:12px;}

/* ── MOBILE / TABLET ── */
.backdrop{
  display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);
  z-index:105;opacity:0;transition:opacity .25s;pointer-events:none;
}
.backdrop.show{opacity:1;pointer-events:auto;}

/* Bottom toolbar — 48px tall, sits at bottom, canvas-wrap accounts for it */
.mob-toolbar{
  display:none;position:fixed;bottom:0;left:0;right:0;z-index:300;
  height:48px;
  background:#fff;border-top:1px solid #dde8d0;
  padding:0 10px;gap:4px;overflow-x:auto;flex-shrink:0;
  align-items:center;
  backdrop-filter:blur(10px);
  -webkit-backdrop-filter:blur(10px);
}
.mob-toolbar::-webkit-scrollbar{display:none;}
.mob-toolbar .btool{flex-shrink:0;}

/* Unified mobile tool button style */
.mob-btn{
  display:inline-flex;align-items:center;justify-content:center;gap:4px;
  height:32px;padding:0 10px;border-radius:7px;border:1px solid #d4e0c8;
  background:#f6fbf0;color:#2c5f2d;font-size:.72rem;font-weight:600;
  cursor:pointer;white-space:nowrap;flex-shrink:0;transition:all .15s;
  text-decoration:none;
}
.mob-btn:hover{background:#e4f0d8;border-color:#648E37;}
.mob-btn:active{transform:scale(.95);background:#d6ecc4;}
.mob-btn.danger{border-color:#f5c6cb;background:#fff5f5;color:#c0392b;}
.mob-btn.danger:hover{background:#fdecea;border-color:#e74c3c;}
.mob-btn.primary{background:#2c5f2d;border-color:#2c5f2d;color:#fff;}
.mob-btn.primary:hover{background:#3a7a3b;border-color:#3a7a3b;}
.mob-btn.icon-only{width:32px;padding:0;}
.mob-divider{width:1px;height:22px;background:#e0e8d4;flex-shrink:0;margin:0 2px;}
.mob-zoom-display{
  font-size:.68rem;font-weight:700;color:#2c5f2d;min-width:36px;
  text-align:center;flex-shrink:0;letter-spacing:.3px;
}

/* Active-surface indicator chip in the toolbar */
.mob-surf-chip{
  display:flex;align-items:center;gap:4px;padding:4px 10px;
  background:#e4f5d8;border:1.5px solid #2c5f2d;border-radius:20px;
  font-size:.68rem;font-weight:700;color:#2c5f2d;cursor:pointer;
  white-space:nowrap;flex-shrink:0;
}
.mob-surf-chip i{font-size:.6rem;}

/* Surface picker sheet */
.mob-surface{
  display:none;position:fixed;bottom:0;left:0;right:0;z-index:600;
  background:#fff;border-radius:16px 16px 0 0;
  border-top:2px solid #d4e0c8;padding:16px 16px 28px;
  flex-direction:column;gap:12px;
  box-shadow:0 -6px 24px rgba(0,0,0,.18);
  transform:translateY(100%);transition:transform .3s ease;
}
.mob-surface.show{transform:translateY(0);}
.mob-surface h6{
  font-size:.75rem;font-weight:700;color:#1a3a1b;text-align:center;
  text-transform:uppercase;letter-spacing:.8px;
}
.mob-surface .surf-instruction{
  font-size:.7rem;color:#777;text-align:center;margin-top:-6px;
}
.mob-surface .surf-row{display:flex;gap:7px;flex-wrap:wrap;justify-content:center;}
.mob-surface .surf-tab{font-size:.74rem;padding:7px 16px;border-radius:8px;}
.mob-surface-close{
  background:#f6fbf0;border:1px solid #d4e0c8;border-radius:8px;
  padding:8px;font-size:.72rem;color:#444;cursor:pointer;margin-top:2px;
  font-weight:600;letter-spacing:.2px;
}

/* FABs — sit above the bottom toolbar, no overlap */
.fab-group{
  display:none;position:fixed;right:12px;bottom:52px;z-index:400;
  flex-direction:column;gap:8px;align-items:flex-end;
}
.fab{
  width:44px;height:44px;border-radius:12px;border:none;color:#fff;font-size:15px;
  cursor:pointer;box-shadow:0 3px 12px rgba(0,0,0,.28);display:flex;align-items:center;
  justify-content:center;transition:transform .15s,box-shadow .15s;
}
.fab:active{transform:scale(.9);}
.fab-sb{background:#2c5f2d;}
.fab-lay{background:#4a7030;}

/* FAB labels */
.fab-wrap{display:flex;align-items:center;gap:7px;}
.fab-lbl{
  background:rgba(20,30,20,.82);color:#fff;font-size:.65rem;font-weight:700;
  padding:3px 8px;border-radius:10px;white-space:nowrap;pointer-events:none;
  opacity:0;transition:opacity .2s;
}
.fab-wrap:hover .fab-lbl,.fab-wrap:focus-within .fab-lbl{opacity:1;}

/* Layers drawer — slides in from LEFT so it never covers bottom toolbar */
.mob-drawer{
  display:none;position:fixed;
  top:82px;    /* below topbar(44) + surface-bar(38) */
  bottom:48px; /* above mob-toolbar */
  left:0;
  width:75vw;max-width:280px;
  background:#fff;border-radius:0 16px 16px 0;
  box-shadow:4px 0 24px rgba(0,0,0,.3);z-index:500;
  flex-direction:column;
  transform:translateX(-100%);transition:transform .3s ease;
}
.mob-drawer.show{transform:translateX(0);}
.drawer-handle{width:36px;height:4px;background:#ccc;border-radius:2px;margin:10px auto 6px;flex-shrink:0;}
.drawer-title{
  text-align:center;font-size:.76rem;font-weight:700;color:#2c5f2d;
  padding:0 10px 8px;border-bottom:1px solid #e4eedd;flex-shrink:0;
}
.drawer-body{overflow-y:auto;flex:1;padding:6px;touch-action:pan-y;overscroll-behavior:contain;}

/* ── BREAKPOINTS ── */
@media(max-width:1024px){
  .sim-right{display:none;}
  .sim-toolbar{display:none;}
  .kbd-hint{display:none;}
  .mob-toolbar{display:flex;}
  .fab-group{display:flex;}
  .mob-drawer{display:flex;}
  .backdrop{display:block;}
  /* Show surface bar at top */
  .mob-surface-bar{display:flex;}
  /* Account for topbar(44) + surface-bar(38) + mob-toolbar(48) */
  .sim-wrap{height:calc(100vh - 44px - 38px - 48px);}
  .mob-surface-bar{display:flex;}
  /* Floating zoom overlay hidden — zoom buttons are in toolbar */
  .zoom-controls{display:none !important;}
  .sim-sidebar{
    position:fixed;top:82px;left:0;bottom:48px;z-index:110;
    width:80vw;max-width:300px;min-width:220px;
    transform:translateX(-100%);
    box-shadow:4px 0 24px rgba(0,0,0,.35);
    transition:transform .28s cubic-bezier(.4,0,.2,1);
    touch-action:pan-y;
  }
  .sim-sidebar.open{transform:translateX(0);}
  /* FABs sit above toolbar, z-index above drawer */
  .fab-group{bottom:60px;z-index:510;}
}
@media(max-width:380px){
  .topbar-brand .brand-name{display:none;}
  .topbar-brand .brand-sub{font-size:.7rem;}
  .topbar-surf .surf-tab{font-size:.6rem;padding:3px 8px;}
}

/* ── ROTATE OVERLAY ── */
#rotateOverlay{
  display:none;position:fixed;inset:0;z-index:9999;
  background:linear-gradient(135deg,#1a3a1b 0%,#2c5f2d 60%,#3a7a3b 100%);
  flex-direction:column;align-items:center;justify-content:center;gap:20px;
  color:#fff;text-align:center;padding:30px;
}
#rotateOverlay.show{display:flex;}
.rotate-icon{font-size:3.5rem;animation:rotateAnim 2s ease-in-out infinite;}
@keyframes rotateAnim{
  0%,100%{transform:rotate(0deg);}
  40%{transform:rotate(90deg);}
  60%{transform:rotate(90deg);}
}
.rotate-title{
  font-family:'Segoe UI',system-ui,sans-serif;
  font-size:1.35rem;font-weight:800;letter-spacing:.5px;
  text-transform:uppercase;color:#fff;
}
.rotate-sub{
  font-family:'Segoe UI',system-ui,sans-serif;
  font-size:.85rem;color:rgba(255,255,255,.78);
  max-width:260px;line-height:1.55;
}
.rotate-badge{
  display:inline-flex;align-items:center;gap:7px;
  background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.28);
  border-radius:24px;padding:7px 18px;
  font-size:.8rem;font-weight:700;color:#fff;letter-spacing:.4px;
  cursor:pointer;transition:background .2s;margin-top:4px;
}
.rotate-badge:hover{background:rgba(255,255,255,.26);}

/* ── SURFACE BUTTON HIGHLIGHT ── */
.surf-tabs{display:flex;gap:3px;flex-wrap:wrap;}
.surf-tab{
  padding:4px 10px;border-radius:16px;font-size:.68rem;font-weight:700;
  border:2px solid #c8ddb0;background:#f6fbf0;cursor:pointer;color:#648E37;
  transition:all .18s;white-space:nowrap;letter-spacing:.3px;
  position:relative;
}
.surf-tab:not(.active):hover{background:#e4f0d8;border-color:#648E37;color:#2c5f2d;}
.surf-tab.active{
  background:#2c5f2d;
  color:#fff;border-color:#2c5f2d;
  box-shadow:0 1px 4px rgba(44,95,45,.25);
}
/* Pulsing "use me" highlight for surface buttons — shown initially */
.surf-tab.needs-attention{
  border-color:#ff8c00 !important;
  background:#fff8ee !important;
  color:#cc5500 !important;
  animation:surfPulse 1.8s ease-in-out 3;
}
@keyframes surfPulse{
  0%,100%{box-shadow:0 0 0 0 rgba(255,140,0,.45);}
  50%{box-shadow:0 0 0 5px rgba(255,140,0,.0);}
}
/* Surface instruction banner */
.surface-hint-bar{
  background:linear-gradient(90deg,#fff4e0,#fff9ee);
  border:1.5px solid #ffc04d;border-radius:8px;
  padding:5px 12px;display:flex;align-items:center;gap:8px;
  font-size:.72rem;color:#8a5000;font-weight:600;flex-shrink:0;
  margin:0 6px;
}
.surface-hint-bar i{color:#e07c00;font-size:.8rem;}
.surface-hint-bar.hidden{display:none;}

/* ── IMPROVED TOOLBAR ORGANIZATION ── */
.toolbar-section{display:flex;align-items:center;gap:5px;flex-shrink:0;}
.toolbar-section-label{
  font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.8px;
  color:#aaa;white-space:nowrap;padding:0 2px;
}
/* ── DESKTOP OVERRIDES ── */
@media(min-width:1025px){
  /* Pills show text on desktop */
  .pill{width:auto;padding:0 10px;gap:5px;border-radius:20px;}
  .pill .pill-text{display:inline;}
  /* Topbar brand shows Greenwood on desktop */
  .topbar-brand .brand-name{font-size:.56rem;}
  /* Surface tabs in topbar hidden on desktop (desktop toolbar has its own) */
  .topbar-surf{display:none !important;}
}
</style>
</head>
<body>


<h1 class="visually-hidden">Room Simulator — Visualize Wall, Floor &amp; Ceiling Materials | Greenwood Philippines</h1>
<div class="sim-topbar">
  <img src="/assets/images/whitenobg.webp" alt="Greenwood Philippines" class="sim-logo">
  <div class="topbar-brand">
    <span class="brand-name">Greenwood</span>
    <span class="brand-sub">Room Simulator</span>
  </div>
  <!-- Surface tabs sit here on mobile, flex:1 fills remaining space -->
  <div class="topbar-surf" id="topbarSurfTabs">
    <div class="surf-tab active" data-surface="floor" onclick="setSurface('floor');syncMobSurfBar(this)">Floor</div>
    <div class="surf-tab" data-surface="back_wall" onclick="setSurface('back_wall');syncMobSurfBar(this)">Back Wall</div>
    <div class="surf-tab" data-surface="left_wall" onclick="setSurface('left_wall');syncMobSurfBar(this)">Left Wall</div>
    <div class="surf-tab" data-surface="right_wall" onclick="setSurface('right_wall');syncMobSurfBar(this)">Right Wall</div>
    <div class="surf-tab" data-surface="ceiling" onclick="setSurface('ceiling');syncMobSurfBar(this)">Ceiling</div>
  </div>
  <div class="topbar-pills">
    <a href="/catalog" class="pill" title="Back to Catalog"><i class="fas fa-arrow-left"></i><span class="pill-text"> Catalog</span></a>
    <button class="pill" onclick="downloadCanvas()" title="Save Image"><i class="fas fa-download"></i><span class="pill-text"> Save</span></button>
    <button class="pill" onclick="clearCanvas()" title="Clear All"><i class="fas fa-trash-alt"></i><span class="pill-text"> Clear</span></button>
  </div>
</div>

<!-- MOBILE SURFACE BAR — shown only on mobile, sits below topbar -->
<div class="mob-surface-bar" id="mobSurfaceBar">
  <div class="surf-tab active" data-surface="floor" onclick="setSurface('floor');syncMobSurfBar(this)">Floor</div>
  <div class="surf-tab" data-surface="back_wall" onclick="setSurface('back_wall');syncMobSurfBar(this)">Back Wall</div>
  <div class="surf-tab" data-surface="left_wall" onclick="setSurface('left_wall');syncMobSurfBar(this)">Left Wall</div>
  <div class="surf-tab" data-surface="right_wall" onclick="setSurface('right_wall');syncMobSurfBar(this)">Right Wall</div>
  <div class="surf-tab" data-surface="ceiling" onclick="setSurface('ceiling');syncMobSurfBar(this)">Ceiling</div>
</div>

<div class="backdrop" id="backdrop" onclick="closeMobileSidebar()"></div>

<div class="sim-wrap">

  <!-- SIDEBAR -->
  <div class="sim-sidebar" id="simSidebar">
    <div class="sidebar-tabs">
      <button class="stab active" onclick="switchTab('products')" id="stab-products"><i class="fas fa-swatchbook me-1"></i>Products</button>
      <button class="stab" onclick="switchTab('furniture')" id="stab-furniture"><i class="fas fa-couch me-1"></i>Furniture</button>
    </div>

    <!-- Products -->
    <div class="stab-panel active" id="panel-products">
      <div class="sidebar-head">
        <h6><i class="fas fa-swatchbook me-1"></i>Products</h6>
        <input type="text" class="sim-search" id="sidebarSearch" placeholder="Search products…">
        <select class="sim-type-filter" id="sidebarTypeFilter">
          <option value="">All Types</option>
          <?php foreach ($types as $t): ?>
          <option value="<?= htmlspecialchars(strtolower($t)) ?>"><?= htmlspecialchars($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="products-list" id="productsList">
        <?php foreach ($products_list as $prod): ?>
        <div class="prod-group"
             data-product="<?= strtolower(htmlspecialchars($prod['product_name'])) ?>"
             data-type="<?= strtolower(htmlspecialchars($prod['product_type_name'] ?? '')) ?>">
          <div class="prod-group-title"><?= htmlspecialchars($prod['product_name']) ?></div>
          <div class="variant-grid">
            <?php foreach ($prod['variants'] as $v):
              $imgUrl = htmlspecialchars($v['image_url'] ?? ''); ?>
            <div class="variant-thumb" draggable="true"
                 data-img="<?= $imgUrl ?>"
                 data-name="<?= htmlspecialchars($prod['product_name']) ?>"
                 data-color="<?= htmlspecialchars($v['color_name']) ?>"
                 data-sku="<?= htmlspecialchars($v['sku']) ?>"
                 data-size="<?= htmlspecialchars($v['size_name'] ?? '') ?>"
                 data-rsa-width="<?= htmlspecialchars($v['rsa_width_cm'] ?? '') ?>"
                 data-rsa-height="<?= htmlspecialchars($v['rsa_height_cm'] ?? '') ?>"
                 data-rsa-crop="<?= htmlspecialchars($v['rsa_crop'] ? json_encode($v['rsa_crop']) : '') ?>"
                 data-rsa-surface="<?= htmlspecialchars($v['rsa_preset_surface']) ?>"
                 title="<?= htmlspecialchars($prod['product_name'].' · '.$v['color_name'].' · '.($v['size_name']??'')) ?>">
              <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($v['color_name']) ?>" loading="lazy"
                   onerror="this.onerror=null;this.src='/assets/images/nobg.webp';">
              <span class="thumb-label"><?= htmlspecialchars($v['color_name']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Furniture -->
    <div class="stab-panel" id="panel-furniture">
      <div class="furn-head">
        <h6><i class="fas fa-couch me-1"></i>Furniture &amp; Appliances</h6>
        <div class="furn-cats" id="furnCats">
          <button class="fcat active" data-cat="all">All</button>
          <button class="fcat" data-cat="seating">Seating</button>
          <button class="fcat" data-cat="tables">Tables</button>
          <button class="fcat" data-cat="storage">Storage</button>
          <button class="fcat" data-cat="beds">Beds</button>
          <button class="fcat" data-cat="appliances">Appliances</button>
          <button class="fcat" data-cat="lighting">Lighting</button>
          <button class="fcat" data-cat="decor">Decor</button>
        </div>
      </div>
      <div class="furn-list" id="furnList"></div>
      <div class="furn-size-row">
        <label><i class="fas fa-expand-alt me-1"></i>Size:</label>
        <input type="range" id="furnSizeSlider" min="40" max="200" value="100" oninput="updateFurnSize(this.value)">
        <span id="furnSizeVal">100%</span>
      </div>
    </div>
  </div>

  <!-- CANVAS -->
  <div class="sim-canvas-area">
    <div class="sim-toolbar" id="desktopToolbar">
      <!-- Section: Room -->
      <div class="toolbar-section">
        <span class="toolbar-section-label">Room</span>
        <select id="roomPreset" onchange="loadRoomPreset()" style="font-size:.72rem;border:1px solid #c8ddb0;border-radius:6px;padding:3px 6px;outline:none;">
          <option value="living">Living Room</option>
          <option value="bedroom">Bedroom</option>
          <option value="bathroom">Bathroom</option>
          <option value="kitchen">Kitchen</option>
          <option value="blank">Blank</option>
        </select>
      </div>
      <div class="tsep"></div>
      <!-- Section: Surface (highlighted) -->
      <div class="toolbar-section" style="flex-direction:column;align-items:flex-start;gap:2px;">
        <span class="toolbar-section-label" style="color:#cc5500;">Step 1 — Pick a surface first</span>
        <div class="surf-tabs" id="surfTabs">
          <div class="surf-tab needs-attention active" data-surface="floor" onclick="setSurface('floor')"><i class="fas fa-layer-group me-1" style="font-size:.6rem"></i>Floor</div>
          <div class="surf-tab needs-attention" data-surface="back_wall" onclick="setSurface('back_wall')"><i class="fas fa-square me-1" style="font-size:.6rem"></i>Back Wall</div>
          <div class="surf-tab needs-attention" data-surface="left_wall" onclick="setSurface('left_wall')"><i class="fas fa-arrow-left me-1" style="font-size:.6rem"></i>Left Wall</div>
          <div class="surf-tab needs-attention" data-surface="right_wall" onclick="setSurface('right_wall')"><i class="fas fa-arrow-right me-1" style="font-size:.6rem"></i>Right Wall</div>
          <div class="surf-tab needs-attention" data-surface="ceiling" onclick="setSurface('ceiling')"><i class="fas fa-chevron-up me-1" style="font-size:.6rem"></i>Ceiling</div>
        </div>
      </div>
      <div class="tsep"></div>
      <!-- Section: Fill -->
      <div class="toolbar-section" style="flex-direction:column;align-items:flex-start;gap:2px;">
        <span class="toolbar-section-label" style="color:#2c5f2d;">Step 2 — Drag product, then Fill</span>
        <div class="fill-box">
          <select id="fillPattern" style="font-size:.68rem;border:1px solid #c8ddb0;border-radius:5px;padding:2px 5px;outline:none;">
            <option value="straight">Straight</option>
            <option value="brick">Brick</option>
            <option value="herringbone">Herringbone</option>
          </select>
          <label style="font-size:.68rem;font-weight:600;color:#2c5f2d;"><input type="checkbox" id="fillGuides" checked style="accent-color:#2c5f2d;"> Guides</label>
          <button class="btn btn-sm btn-success btool" onclick="fillSurface()"><i class="fas fa-fill-drip me-1"></i>Fill Surface</button>
        </div>
      </div>
      <div class="tsep"></div>
      <!-- Section: Adjust -->
      <div class="toolbar-section" style="flex-direction:column;align-items:flex-start;gap:2px;">
        <span class="toolbar-section-label">Adjust Selected</span>
        <div style="display:flex;align-items:center;gap:5px;">
          <span style="font-size:.68rem;color:#555;white-space:nowrap;">Opacity</span>
          <input type="range" id="opacitySlider" min="10" max="100" value="90" style="width:55px;accent-color:#2c5f2d;" oninput="updateOpacity(this.value)">
          <span class="sval" id="opacityVal">100%</span>
          <span style="font-size:.68rem;color:#555;white-space:nowrap;">Scale</span>
          <input type="range" id="scaleSlider" min="20" max="300" value="100" style="width:55px;accent-color:#2c5f2d;" oninput="updateScale(this.value)">
          <span class="sval" id="scaleVal">100%</span>
        </div>
      </div>
      <div class="tsep"></div>
      <!-- Section: Edit Tools -->
      <div class="toolbar-section" style="flex-direction:column;align-items:flex-start;gap:2px;">
        <span class="toolbar-section-label">Edit</span>
        <div style="display:flex;gap:3px;">
          <button class="btn btn-sm btn-outline-primary btool" onclick="openCropModal()" title="Crop"><i class="fas fa-crop-alt"></i></button>
          <button class="btn btn-sm btn-outline-secondary btool" onclick="copySelected()" title="Copy"><i class="fas fa-copy"></i></button>
          <button class="btn btn-sm btn-outline-warning btool" onclick="pasteItem()" title="Paste"><i class="fas fa-paste"></i></button>
          <button class="btn btn-sm btn-outline-info btool" onclick="flipSelected('x')" title="Flip H"><i class="fas fa-arrows-alt-h"></i></button>
          <button class="btn btn-sm btn-outline-info btool" onclick="flipSelected('y')" title="Flip V"><i class="fas fa-arrows-alt-v"></i></button>
          <button class="btn btn-sm btn-outline-secondary btool" onclick="rotateSelected(90)" title="Rotate 90°"><i class="fas fa-redo"></i></button>
          <button class="btn btn-sm btn-outline-secondary btool" onclick="rotateSelected(-90)" title="Rotate -90°"><i class="fas fa-undo"></i></button>
          <button class="btn btn-sm btn-outline-danger btool" onclick="deleteSelected()" title="Delete"><i class="fas fa-trash"></i></button>
        </div>
      </div>
    </div>
    <div class="kbd-hint">① Pick a surface → ② Drag product from sidebar → ③ Click <strong>Fill Surface</strong> or drag to position · <kbd>Ctrl+C/V</kbd> Copy/Paste · <kbd>Del</kbd> Delete · <kbd>R</kbd> Rotate · <kbd>H/V</kbd> Flip</div>
    <div class="canvas-wrap" id="canvasWrap">
      <div class="canvas-container" id="canvasContainer">
        <canvas id="roomCanvas" width="1100" height="680"></canvas>
        <svg id="snapOverlay" width="1100" height="680"></svg>
        <svg id="gridOverlay" width="1100" height="680"></svg>
        <div class="fill-hint" id="fillHint"></div>
      </div>
      <div class="zoom-controls">
        <button class="zoom-btn" onclick="zoomIn()" title="Zoom In">+</button>
        <div class="zoom-level" id="zoomLevel">100%</div>
        <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out">−</button>
        <button class="zoom-btn" onclick="zoomFit()" title="Fit to Screen" style="font-size:10px;font-weight:800;">FIT</button>
      </div>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="sim-right">
    <div class="rhead">
      <h6><i class="fas fa-layer-group me-1"></i>Layers</h6>
      <p>Drag <i class="fas fa-grip-lines" style="font-size:.6rem"></i> to reorder depth</p>
    </div>
    <div class="layers-list" id="layersList">
      <p class="text-muted text-center" style="font-size:.72rem;padding:14px 8px;line-height:1.5">Drag products onto canvas.</p>
    </div>
    <div class="ractions">
      <button class="btn btn-sm btn-outline-danger w-100" onclick="clearCanvas()"><i class="fas fa-trash me-1"></i>Clear All</button>
      <button class="btn btn-sm btn-success w-100" onclick="downloadCanvas()"><i class="fas fa-download me-1"></i>Save Image</button>
    </div>
  </div>
</div>

<!-- MOBILE BOTTOM TOOLBAR -->
<div class="mob-toolbar" id="mobToolbar">
  <!-- Zoom controls -->
  <button class="mob-btn icon-only" onclick="zoomOut()" title="Zoom Out" aria-label="Zoom Out">
    <i class="fas fa-search-minus" style="font-size:.7rem"></i>
  </button>
  <span class="mob-zoom-display" id="zoomLevelMob">100%</span>
  <button class="mob-btn icon-only" onclick="zoomIn()" title="Zoom In" aria-label="Zoom In">
    <i class="fas fa-search-plus" style="font-size:.7rem"></i>
  </button>
  <button class="mob-btn" onclick="zoomFit()" title="Fit to screen" style="font-size:.62rem;font-weight:700;letter-spacing:.4px;">FIT</button>

  <div class="mob-divider"></div>

  <select id="roomPresetMob" onchange="document.getElementById('roomPreset').value=this.value;loadRoomPreset()"
    style="height:32px;font-size:.7rem;border:1px solid #d4e0c8;border-radius:7px;padding:0 8px;
           background:#f6fbf0;color:#2c5f2d;font-weight:600;outline:none;flex-shrink:0;cursor:pointer;">
    <option value="living">Living</option>
    <option value="bedroom">Bedroom</option>
    <option value="bathroom">Bathroom</option>
    <option value="kitchen">Kitchen</option>
    <option value="blank">Blank</option>
  </select>

  <div class="mob-divider"></div>

  <button class="mob-btn primary" onclick="fillSurface()" title="Fill surface with selected product">
    <i class="fas fa-fill-drip" style="font-size:.7rem"></i>
    Fill
  </button>

  <div class="mob-divider"></div>

  <button class="mob-btn icon-only" onclick="rotateSelected(90)" title="Rotate 90°" aria-label="Rotate">
    <i class="fas fa-rotate-right" style="font-size:.7rem"></i>
  </button>
  <button class="mob-btn icon-only" onclick="flipSelected('x')" title="Flip horizontal" aria-label="Flip H">
    <i class="fas fa-arrows-left-right" style="font-size:.7rem"></i>
  </button>
  <button class="mob-btn icon-only" onclick="copySelected()" title="Copy" aria-label="Copy">
    <i class="fas fa-copy" style="font-size:.7rem"></i>
  </button>
  <button class="mob-btn icon-only" onclick="pasteItem()" title="Paste" aria-label="Paste">
    <i class="fas fa-paste" style="font-size:.7rem"></i>
  </button>
  <button class="mob-btn icon-only danger" onclick="deleteSelected()" title="Delete" aria-label="Delete">
    <i class="fas fa-trash" style="font-size:.7rem"></i>
  </button>

  <div class="mob-divider"></div>

  <div style="display:flex;flex-direction:column;gap:1px;flex-shrink:0;align-items:center;">
    <span style="font-size:.5rem;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.5px;">Scale</span>
    <input type="range" id="scaleSliderMob" min="20" max="300" value="100"
      style="width:62px;accent-color:#2c5f2d"
      oninput="updateScale(this.value);document.getElementById('scaleSlider').value=this.value" title="Scale">
  </div>
  <div style="display:flex;flex-direction:column;gap:1px;flex-shrink:0;align-items:center;">
    <span style="font-size:.5rem;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:.5px;">Opacity</span>
    <input type="range" id="opacitySliderMob" min="10" max="100" value="90"
      style="width:62px;accent-color:#2c5f2d"
      oninput="updateOpacity(this.value);document.getElementById('opacitySlider').value=this.value" title="Opacity">
  </div>
</div>

<!-- MOBILE SURFACE PANEL (slide-up sheet) -->
<div class="mob-surface" id="mobSurface">
  <div class="drawer-handle"></div>
  <h6>Pick a Surface</h6>
  <div class="surf-instruction">Choose where your product will appear, then tap <strong>Fill</strong></div>
  <div class="surf-row" id="mobSurfRow">
    <div class="surf-tab needs-attention" data-surface="floor" onclick="setSurface('floor');hideMobSurface()">Floor</div>
    <div class="surf-tab needs-attention" data-surface="back_wall" onclick="setSurface('back_wall');hideMobSurface()">Back Wall</div>
    <div class="surf-tab needs-attention" data-surface="left_wall" onclick="setSurface('left_wall');hideMobSurface()">Left Wall</div>
    <div class="surf-tab needs-attention" data-surface="right_wall" onclick="setSurface('right_wall');hideMobSurface()">Right Wall</div>
    <div class="surf-tab needs-attention" data-surface="ceiling" onclick="setSurface('ceiling');hideMobSurface()">Ceiling</div>
  </div>
  <button class="mob-surface-close" onclick="hideMobSurface()"><i class="fas fa-times me-1"></i>Close</button>
</div>
<!-- Surface sheet backdrop -->
<div id="surfBackdrop" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:599;" onclick="hideMobSurface()"></div>

<!-- FABs -->
<div class="fab-group" id="fabGroup">
  <div class="fab-wrap">
    <span class="fab-lbl">Layers</span>
    <button class="fab fab-lay" onclick="toggleMobLayers()" title="Layers"><i class="fas fa-layer-group"></i></button>
  </div>
  <div class="fab-wrap">
    <span class="fab-lbl">Products</span>
    <button class="fab fab-sb" onclick="toggleMobileSidebar()" title="Products"><i class="fas fa-swatchbook"></i></button>
  </div>
</div>

<!-- MOBILE LAYERS DRAWER -->
<div class="mob-drawer" id="mobDrawer">
  <div class="drawer-handle" onclick="document.getElementById('mobDrawer').classList.remove('show')"></div>
  <div class="drawer-title"><i class="fas fa-layer-group me-1"></i>Layers — tap to select &nbsp;<button onclick="document.getElementById('mobDrawer').classList.remove('show')" style="float:right;background:none;border:none;font-size:.8rem;color:#888;cursor:pointer;padding:0 4px">✕</button></div>
  <div class="drawer-body" id="mobLayersBody"></div>
</div>

<!-- CROP MODAL -->
<div class="modal-ov hidden" id="cropModal">
  <div class="crop-box">
    <h5><i class="fas fa-crop-alt me-2"></i>Crop Image</h5>
    <canvas id="cropCanvas" width="580" height="340"></canvas>
    <div class="crop-controls">
      <div class="crop-row">
        <label>Horizontal cut</label>
        <input type="range" id="cropLeft" min="0" max="50" value="0" oninput="updateCrop()">
        <span id="cropLeftVal">0%</span>
        <input type="range" id="cropRight" min="0" max="50" value="0" oninput="updateCrop()">
        <span id="cropRightVal">0%</span>
      </div>
      <div class="crop-row">
        <label>Vertical cut</label>
        <input type="range" id="cropTop" min="0" max="50" value="0" oninput="updateCrop()">
        <span id="cropTopVal">0%</span>
        <input type="range" id="cropBottom" min="0" max="50" value="0" oninput="updateCrop()">
        <span id="cropBottomVal">0%</span>
      </div>
    </div>
    <div class="crop-acts">
      <button class="btn btn-sm btn-outline-secondary" onclick="closeCropModal()">Cancel</button>
      <button class="btn btn-sm btn-success" onclick="applyCrop()"><i class="fas fa-check me-1"></i>Apply</button>
    </div>
  </div>
</div>

<script>
/* ═══════════════════════════════════════════════════
   GREENWOOD ROOM SIMULATOR
   • Original room scale/layout preserved exactly
   • Furniture redrawn as front-elevation (3D perspective view)
   • All original functionality unchanged
═══════════════════════════════════════════════════ */

// ── SHARED DRAWING HELPERS ──
const C = {
  wood_light:'#D4B896', wood_mid:'#B8936A', wood_dark:'#8B6340', wood_stroke:'#6B4B2A',
  fabric_warm:'#C9A882', fabric_cool:'#9BAAB8', fabric_neutral:'#BDB5A4',
  fabric_stroke:'#8A7A6A', arm_col:'#A08060',
  white:'#F5F3F0', off_white:'#EAE6E0', light_grey:'#D5D0C8', mid_grey:'#A8A29A',
  dark_grey:'#6A6560', charcoal:'#3A3530',
  metal:'#C8C4BE', metal_dark:'#8A8680',
  water:'rgba(140,195,230,0.55)',
  shadow:'rgba(0,0,0,0.18)',
};
function rr(ctx,x,y,w,h,r){
  r=Math.min(r,w/2,h/2,6);
  ctx.beginPath();ctx.moveTo(x+r,y);ctx.arcTo(x+w,y,x+w,y+h,r);ctx.arcTo(x+w,y+h,x,y+h,r);ctx.arcTo(x,y+h,x,y,r);ctx.arcTo(x,y,x+w,y,r);ctx.closePath();
}
function fsr(ctx,x,y,w,h,r,fill,stroke,lw){
  rr(ctx,x,y,w,h,r);
  if(fill){ctx.fillStyle=fill;ctx.fill();}
  if(stroke){ctx.strokeStyle=stroke;ctx.lineWidth=lw||1.2;ctx.stroke();}
}
function sh(ctx,b,ox,oy,col){ctx.shadowBlur=b;ctx.shadowOffsetX=ox;ctx.shadowOffsetY=oy;ctx.shadowColor=col||C.shadow;}
function nsh(ctx){ctx.shadowBlur=0;ctx.shadowOffsetX=0;ctx.shadowOffsetY=0;}

/* ═══════════════════════════════════════════════════
   FRONT-ELEVATION FURNITURE
   All items drawn as seen standing in the room looking at them.
   Width = item width, Height = item visible height from front.
═══════════════════════════════════════════════════ */

// ── SEATING ──
function drawSofa3(ctx,W,H){
  // legs
  ctx.fillStyle=C.wood_dark;
  [[W*.06,H*.82],[W*.24,H*.82],[W*.72,H*.82],[W*.9,H*.82]].forEach(([x,y])=>ctx.fillRect(x,y,W*.04,H*.12));
  ctx.save();sh(ctx,8,0,3);
  // main body shadow
  fsr(ctx,0,H*.1,W,H*.72,6,C.fabric_warm,null);
  nsh(ctx);ctx.restore();
  // back rest
  fsr(ctx,0,H*.1,W,H*.36,6,C.fabric_warm,C.fabric_stroke,1.5);
  // back cushion highlight
  ctx.fillStyle='rgba(255,255,255,.1)';rr(ctx,W*.04,H*.12,W*.88,H*.12,4);ctx.fill();
  // back cushion seam lines (3 cushions)
  ctx.strokeStyle='rgba(0,0,0,.1)';ctx.lineWidth=1.5;
  ctx.beginPath();ctx.moveTo(W*.33,H*.12);ctx.lineTo(W*.33,H*.44);ctx.stroke();
  ctx.beginPath();ctx.moveTo(W*.66,H*.12);ctx.lineTo(W*.66,H*.44);ctx.stroke();
  // seat base
  fsr(ctx,W*.02,H*.44,W*.96,H*.26,4,C.arm_col,C.fabric_stroke,1.2);
  // 3 seat cushions
  const cw=(W*.92)/3;
  for(let i=0;i<3;i++){
    fsr(ctx,W*.04+cw*i,H*.46,cw-W*.02,H*.22,4,C.off_white,C.fabric_stroke,1);
    ctx.strokeStyle='rgba(0,0,0,.07)';ctx.lineWidth=1;
    ctx.beginPath();ctx.moveTo(W*.04+cw*i+cw*.5,H*.48);ctx.lineTo(W*.04+cw*i+cw*.5,H*.66);ctx.stroke();
  }
  // arms
  fsr(ctx,0,H*.1,W*.06,H*.72,4,C.arm_col,C.fabric_stroke,1.2);
  fsr(ctx,W*.94,H*.1,W*.06,H*.72,4,C.arm_col,C.fabric_stroke,1.2);
  // arm top highlight
  ctx.fillStyle='rgba(255,255,255,.15)';rr(ctx,W*.005,H*.1,W*.05,H*.08,2);ctx.fill();
  ctx.fillStyle='rgba(255,255,255,.15)';rr(ctx,W*.945,H*.1,W*.05,H*.08,2);ctx.fill();
}

function drawSofa2(ctx,W,H){
  ctx.fillStyle=C.wood_dark;
  [[W*.06,H*.82],[W*.24,H*.82],[W*.72,H*.82],[W*.9,H*.82]].forEach(([x,y])=>ctx.fillRect(x,y,W*.04,H*.12));
  ctx.save();sh(ctx,6,0,2);fsr(ctx,0,H*.1,W,H*.72,6,C.fabric_cool,null);nsh(ctx);ctx.restore();
  fsr(ctx,0,H*.1,W,H*.36,6,C.fabric_cool,C.fabric_stroke,1.5);
  ctx.fillStyle='rgba(255,255,255,.09)';rr(ctx,W*.04,H*.12,W*.88,H*.12,4);ctx.fill();
  ctx.strokeStyle='rgba(0,0,0,.1)';ctx.lineWidth=1.5;
  ctx.beginPath();ctx.moveTo(W*.5,H*.12);ctx.lineTo(W*.5,H*.44);ctx.stroke();
  fsr(ctx,W*.02,H*.44,W*.96,H*.26,4,'#8A9DAE',C.fabric_stroke,1.2);
  const cw=(W*.92)/2;
  for(let i=0;i<2;i++){fsr(ctx,W*.04+cw*i,H*.46,cw-W*.02,H*.22,4,C.off_white,C.fabric_stroke,1);}
  fsr(ctx,0,H*.1,W*.06,H*.72,4,'#8A9DAE',C.fabric_stroke,1.2);
  fsr(ctx,W*.94,H*.1,W*.06,H*.72,4,'#8A9DAE',C.fabric_stroke,1.2);
}

function drawArmchair(ctx,W,H){
  ctx.fillStyle=C.wood_dark;
  [[W*.08,H*.82],[W*.86,H*.82]].forEach(([x,y])=>ctx.fillRect(x,y,W*.06,H*.12));
  ctx.save();sh(ctx,6,0,2);fsr(ctx,0,H*.12,W,H*.68,7,C.fabric_warm,null);nsh(ctx);ctx.restore();
  fsr(ctx,0,H*.12,W,H*.34,7,C.fabric_warm,C.fabric_stroke,1.5);
  ctx.fillStyle='rgba(255,255,255,.09)';rr(ctx,W*.05,H*.14,W*.9,H*.14,4);ctx.fill();
  fsr(ctx,W*.04,H*.44,W*.92,H*.26,4,C.arm_col,C.fabric_stroke,1.2);
  fsr(ctx,W*.06,H*.46,W*.88,H*.22,5,C.off_white,C.fabric_stroke,1);
  fsr(ctx,0,H*.12,W*.08,H*.68,4,C.arm_col,C.fabric_stroke,1.2);
  fsr(ctx,W*.92,H*.12,W*.08,H*.68,4,C.arm_col,C.fabric_stroke,1.2);
}

function drawOfficeChair(ctx,W,H){
  const cx=W/2;
  // back
  fsr(ctx,cx-W*.28,H*.06,W*.56,H*.38,7,'#A8A098','#7A7268',1.5);
  ctx.fillStyle='rgba(255,255,255,.1)';rr(ctx,cx-W*.2,H*.09,W*.4,H*.14,4);ctx.fill();
  // seat
  fsr(ctx,cx-W*.36,H*.45,W*.72,H*.18,5,'#B0A898','#7A7268',1.5);
  ctx.fillStyle='rgba(255,255,255,.08)';rr(ctx,cx-W*.28,H*.47,W*.26,H*.1,3);ctx.fill();
  // armrests
  fsr(ctx,cx-W*.44,H*.46,W*.1,H*.14,3,'#9A9488','#7A7268',1);
  fsr(ctx,cx+W*.34,H*.46,W*.1,H*.14,3,'#9A9488','#7A7268',1);
  // gas cylinder
  ctx.fillStyle='#5A5550';ctx.fillRect(cx-W*.04,H*.63,W*.08,H*.2);
  // base disc
  fsr(ctx,cx-W*.38,H*.83,W*.76,H*.1,4,'#4A4540','#3A3530',1.2);
  // 5 caster lines
  ctx.strokeStyle='#5A5550';ctx.lineWidth=3;ctx.lineCap='round';
  for(let i=0;i<5;i++){const a=i*Math.PI*2/5-Math.PI/2;ctx.beginPath();ctx.moveTo(cx,H*.88);ctx.lineTo(cx+Math.cos(a)*W*.35,H*.88+Math.sin(a)*H*.05);ctx.stroke();}
}

function drawBarStool(ctx,W,H){
  const cx=W/2;
  ctx.save();sh(ctx,4,0,2);fsr(ctx,cx-W*.38,H*.08,W*.76,H*.16,5,C.wood_mid,C.wood_stroke,1.5);nsh(ctx);ctx.restore();
  ctx.fillStyle='rgba(255,255,255,.12)';rr(ctx,cx-W*.3,H*.1,W*.28,H*.08,2);ctx.fill();
  ctx.strokeStyle=C.wood_dark;ctx.lineWidth=5;ctx.lineCap='round';
  ctx.beginPath();ctx.moveTo(cx-W*.2,H*.24);ctx.lineTo(cx-W*.26,H*.88);ctx.stroke();
  ctx.beginPath();ctx.moveTo(cx+W*.2,H*.24);ctx.lineTo(cx+W*.26,H*.88);ctx.stroke();
  ctx.strokeStyle=C.wood_mid;ctx.lineWidth=3;
  ctx.beginPath();ctx.moveTo(cx-W*.22,H*.58);ctx.lineTo(cx+W*.22,H*.58);ctx.stroke();
  ctx.fillStyle=C.wood_dark;ctx.fillRect(cx-W*.3,H*.88,W*.1,H*.08);ctx.fillRect(cx+W*.2,H*.88,W*.1,H*.08);
}

function drawBench(ctx,W,H){
  ctx.save();sh(ctx,5,0,2);fsr(ctx,W*.02,H*.12,W*.96,H*.3,5,C.wood_light,C.wood_stroke,1.5);nsh(ctx);ctx.restore();
  ctx.strokeStyle='rgba(0,0,0,.07)';ctx.lineWidth=1;
  [.33,.66].forEach(f=>{ctx.beginPath();ctx.moveTo(W*.02+f*W*.96,H*.12);ctx.lineTo(W*.02+f*W*.96,H*.42);ctx.stroke();});
  ctx.fillStyle='rgba(255,255,255,.1)';rr(ctx,W*.05,H*.14,W*.3,H*.1,2);ctx.fill();
  ctx.fillStyle=C.wood_dark;
  [[W*.06,H*.42],[W*.28,H*.42],[W*.66,H*.42],[W*.88,H*.42]].forEach(([x,y])=>ctx.fillRect(x,y,W*.06,H*.46));
  ctx.strokeStyle=C.wood_mid;ctx.lineWidth=3;ctx.beginPath();ctx.moveTo(W*.09,H*.68);ctx.lineTo(W*.91,H*.68);ctx.stroke();
}

// ── TABLES (front elevation) ──
function drawDiningTable(ctx,W,H){
  ctx.save();sh(ctx,6,0,3);fsr(ctx,W*.02,H*.14,W*.96,H*.14,4,C.wood_light,C.wood_stroke,1.8);nsh(ctx);ctx.restore();
  ctx.strokeStyle='rgba(0,0,0,.05)';ctx.lineWidth=1;
  for(let i=1;i<5;i++){const y=H*.14+H*.14/5*i;ctx.beginPath();ctx.moveTo(W*.04,y);ctx.lineTo(W*.96,y);ctx.stroke();}
  ctx.fillStyle='rgba(255,255,255,.1)';rr(ctx,W*.05,H*.16,W*.3,H*.07,2);ctx.fill();
  ctx.fillStyle=C.wood_dark;ctx.fillRect(W*.06,H*.28,W*.1,H*.62);ctx.fillRect(W*.84,H*.28,W*.1,H*.62);
  ctx.strokeStyle=C.wood_dark;ctx.lineWidth=3;ctx.beginPath();ctx.moveTo(W*.11,H*.7);ctx.lineTo(W*.89,H*.7);ctx.stroke();
}

function drawCoffeeTable(ctx,W,H){
  ctx.save();sh(ctx,5,0,2);fsr(ctx,W*.03,H*.2,W*.94,H*.18,6,C.wood_light,C.wood_stroke,1.5);nsh(ctx);ctx.restore();
  ctx.fillStyle='rgba(255,255,255,.09)';rr(ctx,W*.06,H*.22,W*.32,H*.1,3);ctx.fill();
  // shelf
  fsr(ctx,W*.05,H*.62,W*.9,H*.12,3,C.wood_mid,'rgba(0,0,0,.08)',1);
  ctx.fillStyle=C.wood_dark;ctx.fillRect(W*.08,H*.38,W*.08,H*.26);ctx.fillRect(W*.84,H*.38,W*.08,H*.26);
}

function drawRoundTable(ctx,W,H){
  const cx=W/2;
  ctx.save();sh(ctx,6,0,3);
  ctx.fillStyle=C.wood_mid;ctx.beginPath();ctx.ellipse(cx,H*.22,W*.44,H*.1,0,0,Math.PI*2);ctx.fill();
  nsh(ctx);ctx.restore();
  ctx.strokeStyle=C.wood_stroke;ctx.lineWidth=1.8;ctx.beginPath();ctx.ellipse(cx,H*.22,W*.44,H*.1,0,0,Math.PI*2);ctx.stroke();
  ctx.fillStyle=C.wood_dark;ctx.fillRect(cx-W*.04,H*.22,W*.08,H*.46);
  fsr(ctx,cx-W*.34,H*.68,W*.68,H*.12,5,C.wood_dark,C.wood_stroke,1.2);
  ctx.fillStyle='rgba(255,255,255,.1)';rr(ctx,cx-W*.2,H*.2,W*.22,H*.06,2);ctx.fill();
}

function drawDesk(ctx,W,H){
  ctx.save();sh(ctx,5,0,2);fsr(ctx,W*.01,H*.14,W*.98,H*.12,4,C.off_white,C.mid_grey,1.5);nsh(ctx);ctx.restore();
  // drawer pedestal right
  fsr(ctx,W*.62,H*.26,W*.36,H*.62,4,C.light_grey,C.mid_grey,1);
  const dh=H*.62/3;
  for(let i=0;i<3;i++){
    ctx.strokeStyle='rgba(0,0,0,.09)';ctx.lineWidth=1;rr(ctx,W*.64,H*.26+dh*i+3,W*.32,dh-6,2);ctx.stroke();
    ctx.fillStyle=C.metal_dark;ctx.beginPath();ctx.arc(W*.78,H*.26+dh*(i+.5),4,0,Math.PI*2);ctx.fill();
  }
  ctx.fillStyle=C.mid_grey;ctx.fillRect(W*.02,H*.26,W*.08,H*.62);
  ctx.fillStyle='rgba(255,255,255,.07)';rr(ctx,W*.04,H*.16,W*.28,H*.07,2);ctx.fill();
}

function drawSideTable(ctx,W,H){
  ctx.save();sh(ctx,4,0,2);fsr(ctx,W*.04,H*.14,W*.92,H*.14,5,C.wood_mid,C.wood_stroke,1.5);nsh(ctx);ctx.restore();
  ctx.fillStyle='rgba(255,255,255,.09)';rr(ctx,W*.07,H*.16,W*.3,H*.07,2);ctx.fill();
  ctx.fillStyle=C.wood_dark;ctx.fillRect(W*.1,H*.28,W*.08,H*.6);ctx.fillRect(W*.82,H*.28,W*.08,H*.6);
  ctx.strokeStyle=C.wood_mid;ctx.lineWidth=3;ctx.beginPath();ctx.moveTo(W*.14,H*.6);ctx.lineTo(W*.86,H*.6);ctx.stroke();
}

// ── STORAGE ──
function drawWardrobe(ctx,W,H){
  const p=5;
  ctx.save();sh(ctx,8,0,4);fsr(ctx,p,p,W-p*2,H-p*2,5,C.off_white,C.mid_grey,2);nsh(ctx);ctx.restore();
  const mx=W/2;
  ctx.strokeStyle=C.mid_grey;ctx.lineWidth=1.5;ctx.beginPath();ctx.moveTo(mx,p);ctx.lineTo(mx,H-p);ctx.stroke();
  const ins=8,pw=(W-p*2-ins*2)/2;
  ctx.strokeStyle='rgba(0,0,0,.09)';ctx.lineWidth=1;
  rr(ctx,p+ins,p+ins,pw,H-p*2-ins*2,4);ctx.stroke();
  rr(ctx,mx+ins/2,p+ins,pw,H-p*2-ins*2,4);ctx.stroke();
  ctx.fillStyle=C.metal_dark;ctx.fillRect(mx-14,H/2-10,8,20);ctx.fillRect(mx+6,H/2-10,8,20);
  ctx.fillStyle='rgba(255,255,255,.1)';rr(ctx,p+10,p+10,W*.18,H*.1,2);ctx.fill();
  ctx.fillStyle=C.mid_grey;ctx.fillRect(p+4,H-p-6,W*.12,6);ctx.fillRect(W-p-W*.12-4,H-p-6,W*.12,6);
}

function drawBookshelf(ctx,W,H){
  const p=5;
  ctx.save();sh(ctx,6,0,3);fsr(ctx,p,p,W-p*2,H-p*2,4,C.wood_mid,C.wood_stroke,1.5);nsh(ctx);ctx.restore();
  const shelves=5,sw=W-p*2,ih=H-p*2;
  ctx.fillStyle=C.wood_dark;
  for(let i=1;i<=shelves;i++)ctx.fillRect(p,p+ih/shelves*i-2,sw,4);
  const bc=['#C44','#48A','#8A4','#A64','#4AA','#AA4','#E74','#84C'];
  for(let s=0;s<shelves;s++){
    let bx=p+3;const by=p+ih/shelves*s+4,bh=ih/shelves-12;let c=0;
    while(bx<W-p-4){const bw=Math.min(10+Math.random()*8,W-p-4-bx);ctx.fillStyle=bc[(c+s)%bc.length];ctx.fillRect(bx,by,bw,bh);ctx.fillStyle='rgba(255,255,255,.07)';ctx.fillRect(bx+1,by+1,bw*.4,bh*.25);bx+=bw+1;c++;}
  }
  ctx.fillStyle=C.wood_dark;ctx.fillRect(p,p,W*.07,H-p*2);ctx.fillRect(W-p-W*.07,p,W*.07,H-p*2);
}

function drawDresser(ctx,W,H){
  const p=5;
  ctx.save();sh(ctx,6,0,3);fsr(ctx,p,p,W-p*2,H-p*2,5,C.off_white,C.mid_grey,1.5);nsh(ctx);ctx.restore();
  const nd=4,dh=(H-p*2)/nd;
  for(let i=0;i<nd;i++){
    const dy=p+dh*i;
    rr(ctx,p+6,dy+3,W-p*2-12,dh-6,3);ctx.strokeStyle='rgba(0,0,0,.09)';ctx.lineWidth=1;ctx.stroke();
    ctx.fillStyle=C.metal_dark;ctx.fillRect(W/2-14,dy+dh*.5-4,28,8);
    ctx.fillStyle=C.metal;ctx.fillRect(W/2-12,dy+dh*.5-2,24,4);
    ctx.fillStyle='rgba(255,255,255,.08)';rr(ctx,p+10,dy+4,W*.3,dh*.28,2);ctx.fill();
  }
  ctx.fillStyle=C.mid_grey;ctx.fillRect(p+4,H-p-5,W*.1,5);ctx.fillRect(W-p-W*.1-4,H-p-5,W*.1,5);
}

function drawTVCabinet(ctx,W,H){
  const p=5;
  ctx.save();sh(ctx,5,0,2);fsr(ctx,p,p*1.5,W-p*2,H-p*2.5,5,C.light_grey,C.mid_grey,1.5);nsh(ctx);ctx.restore();
  ctx.strokeStyle=C.mid_grey;ctx.lineWidth=1.2;ctx.beginPath();ctx.moveTo(W/2,p*1.5);ctx.lineTo(W/2,H-p);ctx.stroke();
  const ins=10,ph=(W-p*2-ins*2)/2;
  ctx.strokeStyle='rgba(0,0,0,.09)';ctx.lineWidth=1;
  rr(ctx,p+ins,p*1.5+ins,ph,H-p*2.5-ins*2,3);ctx.stroke();
  rr(ctx,W/2+ins/2,p*1.5+ins,ph,H-p*2.5-ins*2,3);ctx.stroke();
  ctx.fillStyle=C.metal_dark;ctx.fillRect(W/2-14,H*.5-5,10,10);ctx.fillRect(W/2+4,H*.5-5,10,10);
  ctx.fillStyle=C.mid_grey;ctx.fillRect(p+4,H-p-5,W*.12,5);ctx.fillRect(W-p-W*.12-4,H-p-5,W*.12,5);
}

function drawShoeRack(ctx,W,H){
  const p=5;
  ctx.save();sh(ctx,4,0,2);fsr(ctx,p,p,W-p*2,H-p*2,4,C.wood_mid,C.wood_stroke,1.5);nsh(ctx);ctx.restore();
  const nr=4,rh=(H-p*2)/nr;
  ctx.fillStyle=C.wood_dark;for(let i=1;i<=nr;i++)ctx.fillRect(p,p+rh*i-2,W-p*2,4);
  ctx.strokeStyle='rgba(0,0,0,.1)';ctx.lineWidth=1;
  for(let s=0;s<nr;s++){const sy=p+rh*s+rh*.3;let sx=p+4;while(sx<W-p-10){const ew=Math.min(16,W-p-10-sx);ctx.beginPath();ctx.ellipse(sx+ew/2,sy,ew/2,rh*.15,-.2,0,Math.PI*2);ctx.stroke();sx+=20;}}
  ctx.fillStyle=C.wood_dark;ctx.fillRect(p,p,W*.07,H-p*2);ctx.fillRect(W-p-W*.07,p,W*.07,H-p*2);
}

// ── BEDS (front/headboard elevation) ──
function drawBed(ctx,W,H,mattress,frame,wCm){
  const p=5;
  ctx.save();sh(ctx,8,0,4);fsr(ctx,p,p,W-p*2,H*.46,8,frame,'#7A6A5A',2);nsh(ctx);ctx.restore();
  // headboard detail
  fsr(ctx,p+8,p+8,W-p*2-16,H*.32,5,'rgba(255,255,255,.05)','rgba(255,255,255,.07)',1);
  // mattress + base
  fsr(ctx,p-2,H*.46,W-p*2+4,H*.36,4,mattress,'rgba(0,0,0,.1)',1.2);
  // bedding / sheet fold
  fsr(ctx,p+4,H*.48,W-p*2-8,H*.14,3,C.white,'rgba(0,0,0,.08)',1);
  // pillows
  const cnt=wCm>130?2:1,pw=cnt===2?(W-p*2-26)/2:(W-p*2-32)*.68;
  ctx.save();sh(ctx,3,0,1,'rgba(0,0,0,.1)');
  if(cnt===2){fsr(ctx,p+10,H*.49,pw,H*.12,4,C.white,'rgba(0,0,0,.1)',1);fsr(ctx,W-p-10-pw,H*.49,pw,H*.12,4,C.white,'rgba(0,0,0,.1)',1);}
  else fsr(ctx,W/2-pw/2,H*.49,pw,H*.12,4,C.white,'rgba(0,0,0,.1)',1);
  nsh(ctx);ctx.restore();
  // blanket fold line
  ctx.strokeStyle='rgba(0,0,0,.09)';ctx.lineWidth=1.5;
  ctx.beginPath();ctx.moveTo(p+8,H*.62);ctx.lineTo(W-p-8,H*.62);ctx.stroke();
  // footboard
  fsr(ctx,p-2,H*.82,W-p*2+4,H*.08,3,'#8A7A6A','#6A5A4A',1.2);
  // legs
  ctx.fillStyle='#7A6A5A';ctx.fillRect(p+2,H*.9,W*.1,H*.08);ctx.fillRect(W-p-W*.1-2,H*.9,W*.1,H*.08);
}

// ── APPLIANCES ──
function drawTV(ctx,W,H){
  const p=5;
  ctx.save();sh(ctx,6,0,3);fsr(ctx,p,p,W-p*2,H*.72,5,C.charcoal,C.dark_grey,1.5);nsh(ctx);ctx.restore();
  const sg=ctx.createLinearGradient(p+6,p+5,W-p-6,H*.67);sg.addColorStop(0,'#1E3A5A');sg.addColorStop(.5,'#2A5E8A');sg.addColorStop(1,'#102840');
  fsr(ctx,p+6,p+5,W-p*2-12,H*.62,3,sg,'#0A1828',1);
  ctx.fillStyle='rgba(255,255,255,.06)';rr(ctx,p+10,p+8,W*.28,H*.16,2);ctx.fill();
  ctx.fillStyle='#2A2520';ctx.fillRect(W/2-4,H*.72,8,H*.08);
  fsr(ctx,W/2-W*.18,H*.8,W*.36,H*.1,3,C.charcoal,C.dark_grey,1);
  ctx.fillStyle='#00FF88';ctx.shadowColor='#00FF88';ctx.shadowBlur=5;ctx.beginPath();ctx.arc(W-p-10,H*.68,3,0,Math.PI*2);ctx.fill();ctx.shadowBlur=0;
}

function drawFridge(ctx,W,H){
  const p=6;
  ctx.save();sh(ctx,8,0,4);fsr(ctx,p,p,W-p*2,H-p*2,8,C.light_grey,C.mid_grey,1.8);nsh(ctx);ctx.restore();
  ctx.fillStyle='rgba(255,255,255,.16)';rr(ctx,p+5,p+5,W*.22,H*.36,3);ctx.fill();
  const fzy=H*.36;ctx.fillStyle=C.mid_grey;ctx.fillRect(p,fzy,W-p*2,3);
  fsr(ctx,p+5,p+4,W-p*2-10,fzy-10,5,'rgba(0,0,0,.02)','rgba(0,0,0,.07)',1);
  fsr(ctx,p+5,fzy+7,W-p*2-10,H-p*2-fzy-12,5,'rgba(0,0,0,.02)','rgba(0,0,0,.07)',1);
  ctx.strokeStyle=C.metal_dark;ctx.lineWidth=5;ctx.lineCap='round';
  ctx.beginPath();ctx.moveTo(p+W*.16,H*.08);ctx.lineTo(p+W*.16,H*.28);ctx.stroke();
  ctx.beginPath();ctx.moveTo(p+W*.16,fzy+H*.05);ctx.lineTo(p+W*.16,fzy+H*.16);ctx.stroke();
  ctx.fillStyle=C.mid_grey;ctx.fillRect(p+4,H-p-5,W*.14,5);ctx.fillRect(W-p-W*.14-4,H-p-5,W*.14,5);
}

function drawWashingMachine(ctx,W,H){
  const p=6;
  ctx.save();sh(ctx,8,0,4);fsr(ctx,p,p,W-p*2,H-p*2,10,C.light_grey,C.mid_grey,1.8);nsh(ctx);ctx.restore();
  fsr(ctx,p,p,W-p*2,H*.14,8,C.off_white,C.mid_grey,1);
  [.3,.55,.76].forEach(fx=>{ctx.fillStyle=C.dark_grey;ctx.beginPath();ctx.arc(W*fx,p+H*.07,5,0,Math.PI*2);ctx.fill();ctx.fillStyle=C.mid_grey;ctx.beginPath();ctx.arc(W*fx,p+H*.07,3,0,Math.PI*2);ctx.fill();});
  const dcx=W/2,dcy=H*.56,dr=W*.32;
  ctx.save();sh(ctx,5,0,2);ctx.fillStyle='#353040';ctx.beginPath();ctx.arc(dcx,dcy,dr,0,Math.PI*2);ctx.fill();nsh(ctx);ctx.restore();
  ctx.fillStyle='rgba(100,150,210,.28)';ctx.beginPath();ctx.arc(dcx,dcy,dr-4,0,Math.PI*2);ctx.fill();
  ctx.strokeStyle='#5A5068';ctx.lineWidth=3;ctx.beginPath();ctx.arc(dcx,dcy,dr-4,0,Math.PI*2);ctx.stroke();
  for(let i=0;i<8;i++){const a=i*Math.PI*2/8;ctx.fillStyle='rgba(0,0,0,.25)';ctx.beginPath();ctx.arc(dcx+Math.cos(a)*dr*.6,dcy+Math.sin(a)*dr*.6,4,0,Math.PI*2);ctx.fill();}
  ctx.strokeStyle='#8A8490';ctx.lineWidth=5;ctx.beginPath();ctx.arc(dcx,dcy,dr+2,0,Math.PI*2);ctx.stroke();
  ctx.fillStyle=C.mid_grey;ctx.fillRect(p+4,H-p-5,W*.14,5);ctx.fillRect(W-p-W*.14-4,H-p-5,W*.14,5);
}

function drawACUnit(ctx,W,H){
  const p=6;
  ctx.save();sh(ctx,4,0,2);fsr(ctx,p,H*.12,W-p*2,H*.62,8,C.off_white,C.light_grey,1.5);nsh(ctx);ctx.restore();
  const nv=6,vw=(W-p*2-14)/nv;
  for(let i=0;i<nv;i++)fsr(ctx,p+7+vw*i,H*.32,vw-3,H*.28,2,'rgba(0,0,0,.04)','rgba(0,0,0,.09)',1);
  ctx.strokeStyle='rgba(0,0,0,.07)';ctx.lineWidth=1;ctx.beginPath();ctx.moveTo(p+5,H*.29);ctx.lineTo(W-p-5,H*.29);ctx.stroke();
  ctx.fillStyle='rgba(0,220,255,.85)';ctx.shadowColor='#00DCFF';ctx.shadowBlur=5;
  ctx.font=`bold ${Math.min(W*.1,12)}px monospace`;ctx.textAlign='center';ctx.fillText('24°',W*.75,H*.24);ctx.shadowBlur=0;ctx.textAlign='left';
  ctx.fillStyle='#00E5FF';ctx.beginPath();ctx.arc(W-p-9,H*.21,3,0,Math.PI*2);ctx.fill();
  ctx.fillStyle=C.mid_grey;ctx.fillRect(p,H*.05,W*.1,H*.08);ctx.fillRect(W-p-W*.1,H*.05,W*.1,H*.08);
}

function drawToilet(ctx,W,H){
  const cx=W/2;
  ctx.save();sh(ctx,4,0,2);fsr(ctx,cx-W*.28,H*.02,W*.56,H*.26,6,C.off_white,C.light_grey,1.5);nsh(ctx);ctx.restore();
  ctx.fillStyle='rgba(255,255,255,.18)';rr(ctx,cx-W*.2,H*.04,W*.24,H*.08,2);ctx.fill();
  ctx.fillStyle=C.light_grey;ctx.beginPath();ctx.arc(cx,H*.15,6,0,Math.PI*2);ctx.fill();ctx.strokeStyle=C.mid_grey;ctx.lineWidth=1;ctx.stroke();
  ctx.save();sh(ctx,5,0,2);ctx.fillStyle=C.off_white;ctx.beginPath();ctx.ellipse(cx,H*.6,W*.4,H*.3,0,0,Math.PI*2);ctx.fill();nsh(ctx);ctx.restore();
  ctx.strokeStyle=C.light_grey;ctx.lineWidth=1.5;ctx.beginPath();ctx.ellipse(cx,H*.6,W*.4,H*.3,0,0,Math.PI*2);ctx.stroke();
  ctx.strokeStyle=C.mid_grey;ctx.lineWidth=4;ctx.beginPath();ctx.ellipse(cx,H*.6,W*.35,H*.26,0,0,Math.PI*2);ctx.stroke();
  ctx.fillStyle=C.water;ctx.beginPath();ctx.ellipse(cx,H*.61,W*.27,H*.2,0,0,Math.PI*2);ctx.fill();
  fsr(ctx,cx-W*.3,H*.88,W*.6,H*.1,4,C.light_grey,C.mid_grey,1);
}

function drawBathtub(ctx,W,H){
  const p=6;
  ctx.save();sh(ctx,8,0,4);fsr(ctx,p,H*.16,W-p*2,H*.72,12,C.off_white,C.light_grey,2);nsh(ctx);ctx.restore();
  fsr(ctx,p+9,H*.26,W-p*2-18,H*.55,9,C.water,C.light_grey,1);
  ctx.fillStyle='rgba(255,255,255,.2)';for(let i=0;i<3;i++){rr(ctx,p+14+i*28,H*.3,22,5,2);ctx.fill();}
  ctx.strokeStyle=C.metal;ctx.lineWidth=5;ctx.lineCap='round';
  ctx.beginPath();ctx.moveTo(p+16,H*.22);ctx.lineTo(p+16,H*.16);ctx.stroke();
  ctx.beginPath();ctx.moveTo(p+8,H*.16);ctx.lineTo(p+24,H*.16);ctx.stroke();
  ctx.fillStyle=C.metal;ctx.beginPath();ctx.arc(p+16,H*.22,4,0,Math.PI*2);ctx.fill();
  ctx.fillStyle=C.mid_grey;ctx.beginPath();ctx.arc(W-p-16,H-.1*H,5,0,Math.PI*2);ctx.fill();
  // feet
  ctx.fillStyle=C.mid_grey;ctx.beginPath();ctx.arc(p+12,H*.88,6,0,Math.PI*.7,true);ctx.fill();ctx.beginPath();ctx.arc(W-p-12,H*.88,6,0,Math.PI*.7,true);ctx.fill();
}

function drawKitchenSink(ctx,W,H){
  const p=6;
  ctx.save();sh(ctx,5,0,2);fsr(ctx,p,p,W-p*2,H*.6,5,C.off_white,C.mid_grey,1.5);nsh(ctx);ctx.restore();
  ctx.fillStyle='rgba(255,255,255,.07)';rr(ctx,p+4,p+2,W*.28,H*.14,2);ctx.fill();
  fsr(ctx,p+9,H*.18,W-p*2-18,H*.36,7,C.water,C.light_grey,1.2);
  ctx.fillStyle='rgba(255,255,255,.18)';for(let i=0;i<3;i++){rr(ctx,p+12+i*W*.2,H*.22,W*.14,4,2);ctx.fill();}
  ctx.strokeStyle=C.metal;ctx.lineWidth=5;ctx.lineCap='round';
  ctx.beginPath();ctx.moveTo(W/2,H*.12);ctx.lineTo(W/2,H*.08);ctx.stroke();
  ctx.beginPath();ctx.moveTo(W/2-10,H*.08);ctx.lineTo(W/2+10,H*.08);ctx.stroke();
  ctx.fillStyle=C.metal;ctx.beginPath();ctx.arc(W/2,H*.12,4,0,Math.PI*2);ctx.fill();
  // cabinet base
  fsr(ctx,p,H*.6,W-p*2,H*.32,4,C.light_grey,'rgba(0,0,0,.08)',1);
  ctx.fillStyle=C.metal_dark;ctx.fillRect(W/2-10,H*.78,9,9);ctx.fillRect(W/2+1,H*.78,9,9);
  ctx.fillStyle=C.mid_grey;ctx.fillRect(p+4,H*.92,W*.12,H*.06);ctx.fillRect(W-p-W*.12-4,H*.92,W*.12,H*.06);
}

// ── LIGHTING ──
function drawFloorLamp(ctx,W,H){
  const cx=W/2;
  ctx.save();sh(ctx,4,0,1);fsr(ctx,cx-W*.26,H*.78,W*.52,H*.14,4,C.charcoal,C.dark_grey,1.2);nsh(ctx);ctx.restore();
  ctx.strokeStyle='#B0A870';ctx.lineWidth=5;ctx.lineCap='round';ctx.beginPath();ctx.moveTo(cx,H*.78);ctx.lineTo(cx,H*.2);ctx.stroke();
  const sg=ctx.createRadialGradient(cx-3,H*.14,2,cx,H*.18,W*.3);sg.addColorStop(0,'#FFF0A0');sg.addColorStop(.55,'#C8A028');sg.addColorStop(1,'#8A6810');
  ctx.save();sh(ctx,5,0,1);ctx.fillStyle=sg;ctx.beginPath();ctx.ellipse(cx,H*.18,W*.3,H*.09,0,0,Math.PI*2);ctx.fill();nsh(ctx);ctx.restore();
  ctx.strokeStyle='rgba(0,0,0,.2)';ctx.lineWidth=1.5;ctx.beginPath();ctx.ellipse(cx,H*.18,W*.3,H*.09,0,0,Math.PI*2);ctx.stroke();
  ctx.strokeStyle='rgba(0,0,0,.28)';ctx.lineWidth=2;ctx.beginPath();ctx.arc(cx,H*.18,W*.1,0,Math.PI*2);ctx.stroke();
  ctx.fillStyle='rgba(255,240,120,.14)';ctx.beginPath();ctx.ellipse(cx,H*.18,W*.4,H*.13,0,0,Math.PI*2);ctx.fill();
  ctx.fillStyle='rgba(255,255,200,.8)';ctx.beginPath();ctx.arc(cx,H*.18,4,0,Math.PI*2);ctx.fill();
}

function drawPendantLight(ctx,W,H){
  const cx=W/2;
  ctx.strokeStyle='#555';ctx.lineWidth=2;ctx.beginPath();ctx.moveTo(cx,H*.04);ctx.lineTo(cx,H*.34);ctx.stroke();
  const sg=ctx.createRadialGradient(cx-3,H*.42,2,cx,H*.48,W*.32);sg.addColorStop(0,'#FFE898');sg.addColorStop(.6,'#B87828');sg.addColorStop(1,'#7A4C10');
  ctx.save();sh(ctx,6,0,2);ctx.fillStyle=sg;ctx.beginPath();ctx.ellipse(cx,H*.5,W*.32,H*.18,0,0,Math.PI*2);ctx.fill();nsh(ctx);ctx.restore();
  ctx.strokeStyle='rgba(0,0,0,.2)';ctx.lineWidth=1.5;ctx.beginPath();ctx.ellipse(cx,H*.5,W*.32,H*.18,0,0,Math.PI*2);ctx.stroke();
  ctx.strokeStyle='rgba(0,0,0,.28)';ctx.lineWidth=2;ctx.beginPath();ctx.arc(cx,H*.34,W*.09,0,Math.PI*2);ctx.stroke();
  ctx.fillStyle='rgba(255,240,100,.14)';ctx.beginPath();ctx.ellipse(cx,H*.5,W*.4,H*.24,0,0,Math.PI*2);ctx.fill();
  ctx.fillStyle='rgba(255,255,180,.7)';ctx.beginPath();ctx.arc(cx,H*.34,4,0,Math.PI*2);ctx.fill();
}

function drawTableLamp(ctx,W,H){
  const cx=W/2;
  fsr(ctx,cx-W*.16,H*.76,W*.32,H*.16,4,C.wood_mid,C.wood_stroke,1.2);
  ctx.strokeStyle='#B09050';ctx.lineWidth=4;ctx.beginPath();ctx.moveTo(cx,H*.76);ctx.lineTo(cx,H*.38);ctx.stroke();
  const sg=ctx.createRadialGradient(cx-2,H*.3,2,cx,H*.34,W*.26);sg.addColorStop(0,'#FFE890');sg.addColorStop(.5,'#DDBE58');sg.addColorStop(1,'#A88830');
  ctx.save();sh(ctx,4,0,1);ctx.fillStyle=sg;ctx.beginPath();ctx.ellipse(cx,H*.34,W*.26,H*.12,0,0,Math.PI*2);ctx.fill();nsh(ctx);ctx.restore();
  ctx.strokeStyle='rgba(0,0,0,.16)';ctx.lineWidth=1.2;ctx.beginPath();ctx.ellipse(cx,H*.34,W*.26,H*.12,0,0,Math.PI*2);ctx.stroke();
  ctx.strokeStyle='rgba(0,0,0,.22)';ctx.lineWidth=2;ctx.beginPath();ctx.arc(cx,H*.38,W*.07,0,Math.PI*2);ctx.stroke();
  ctx.fillStyle='rgba(255,255,180,.7)';ctx.beginPath();ctx.arc(cx,H*.38,4,0,Math.PI*2);ctx.fill();
}

// ── DECOR ──
function drawPlant(ctx,W,H,size){
  const cx=W/2,large=size==='large';
  const potY=H*(large?.56:.62),potW=W*(large?.4:.34),potH=H*(large?.28:.24);
  ctx.save();sh(ctx,4,0,2);ctx.fillStyle='#D46830';ctx.beginPath();ctx.moveTo(cx-potW/2,potY);ctx.lineTo(cx+potW/2,potY);ctx.lineTo(cx+potW*.42,potY+potH);ctx.lineTo(cx-potW*.42,potY+potH);ctx.closePath();ctx.fill();nsh(ctx);ctx.restore();
  ctx.fillStyle='#3A2010';ctx.fillRect(cx-potW*.2,potY,potW*.4,7);
  ctx.strokeStyle='#3A6020';ctx.lineWidth=3;ctx.lineCap='round';ctx.beginPath();ctx.moveTo(cx,potY);ctx.lineTo(cx,potY-potH*(large?1.2:.95));ctx.stroke();
  const lr=W*(large?.34:.26);const lc=['#4A8A34','#5A9E3C','#3A7228','#68A844','#2E6020'];const nL=large?8:5;
  for(let i=0;i<nL;i++){
    const a=i*Math.PI*2/nL+.3;const side=Math.cos(a)>0?1:-1;
    const lx=cx+side*lr*.38,ly=potY-potH*(large?.28:.22)-i*(potH*(large?.2:.16));
    ctx.fillStyle=lc[i%lc.length];ctx.save();ctx.translate(lx,ly);ctx.rotate(side>0?-.45:.45);
    ctx.beginPath();ctx.ellipse(0,0,lr*.28,lr*.1,0,0,Math.PI*2);ctx.fill();
    ctx.strokeStyle='rgba(0,0,0,.08)';ctx.lineWidth=1;ctx.beginPath();ctx.moveTo(-lr*.24,0);ctx.lineTo(lr*.24,0);ctx.stroke();
    ctx.restore();
  }
  ctx.fillStyle='#3A8A28';ctx.beginPath();ctx.ellipse(cx,potY-potH*(large?.5:.4),lr*(large?.7:.6),lr*(large?.35:.28),0,0,Math.PI*2);ctx.fill();
}

function drawRug(ctx,W,H){
  const p=8;ctx.save();sh(ctx,6,0,3);fsr(ctx,p,p,W-p*2,H-p*2,8,'#7A3E12',null);nsh(ctx);ctx.restore();
  [{c:'#C05028',in:8},{c:'#D4922A',in:16},{c:'#3A78A0',in:24}].forEach(({c,in:ins})=>{ctx.strokeStyle=c;ctx.lineWidth=3;rr(ctx,p+ins,p+ins,W-p*2-ins*2,H-p*2-ins*2,5);ctx.stroke();});
  const cx=W/2,cy=H/2;ctx.fillStyle='#C05028';ctx.beginPath();ctx.arc(cx,cy,W*.12,0,Math.PI*2);ctx.fill();ctx.fillStyle='#D4922A';ctx.beginPath();ctx.arc(cx,cy,W*.08,0,Math.PI*2);ctx.fill();ctx.fillStyle='#3A78A0';ctx.beginPath();ctx.arc(cx,cy,W*.04,0,Math.PI*2);ctx.fill();
  ctx.strokeStyle='#C8A860';ctx.lineWidth=1.5;for(let i=0;i<8;i++){const fx=p+4+(W-p*2-8)/7*i;ctx.beginPath();ctx.moveTo(fx,p+4);ctx.lineTo(fx-2,H*.02);ctx.stroke();ctx.beginPath();ctx.moveTo(fx,H-p-4);ctx.lineTo(fx-2,H*.98);ctx.stroke();}
}

function drawMirror(ctx,W,H){
  const p=8;ctx.save();sh(ctx,8,0,4);fsr(ctx,p,p,W-p*2,H-p*2,10,'#C8A030','#9A7818',2);nsh(ctx);ctx.restore();
  const mg=ctx.createLinearGradient(p+10,p+10,W-p-10,H-p-10);mg.addColorStop(0,'rgba(185,225,255,.78)');mg.addColorStop(.4,'rgba(245,252,255,.9)');mg.addColorStop(1,'rgba(165,205,240,.68)');
  fsr(ctx,p+10,p+10,W-p*2-20,H-p*2-20,7,mg,'rgba(255,255,255,.5)',1);
  ctx.fillStyle='rgba(255,255,255,.32)';rr(ctx,p+16,p+16,W*.26,H*.36,4);ctx.fill();
  ctx.fillStyle='#A07820';[[p+2,p+2],[W-p-10,p+2],[p+2,H-p-10],[W-p-10,H-p-10]].forEach(([fx,fy])=>{ctx.beginPath();ctx.arc(fx+4,fy+4,5,0,Math.PI*2);ctx.fill();});
}

function drawCurtain(ctx,W,H){
  ctx.fillStyle=C.metal;ctx.fillRect(4,H*.04,W-8,6);ctx.fillStyle=C.metal_dark;ctx.beginPath();ctx.arc(4,H*.07,7,0,Math.PI*2);ctx.fill();ctx.beginPath();ctx.arc(W-4,H*.07,7,0,Math.PI*2);ctx.fill();
  ctx.strokeStyle=C.metal_dark;ctx.lineWidth=1.5;for(let i=0;i<8;i++){const rx=8+i*(W-16)/7;ctx.beginPath();ctx.arc(rx,H*.06,4,0,Math.PI*2);ctx.stroke();}
  const panW=W*.47,nf=5;
  const lg=ctx.createLinearGradient(0,0,panW,0);lg.addColorStop(0,'#7A1818');lg.addColorStop(.55,'#B02828');lg.addColorStop(1,'#680E0E');
  ctx.fillStyle=lg;ctx.beginPath();ctx.moveTo(4,H*.1);for(let i=0;i<=nf;i++){const fx=4+panW/nf*i;ctx.lineTo(fx+(i%2===0?-panW*.05:panW*.05),H*.1+H*.86*i/nf);}ctx.lineTo(4+panW,H*.96);ctx.lineTo(4+panW,H*.1);ctx.closePath();ctx.fill();
  const rg=ctx.createLinearGradient(W-panW,0,W,0);rg.addColorStop(0,'#680E0E');rg.addColorStop(.45,'#B02828');rg.addColorStop(1,'#7A1818');
  ctx.fillStyle=rg;ctx.beginPath();ctx.moveTo(W-panW-4,H*.1);for(let i=0;i<=nf;i++){const fx=W-panW-4+panW/nf*i;ctx.lineTo(fx+(i%2===0?panW*.05:-panW*.05),H*.1+H*.86*i/nf);}ctx.lineTo(W-4,H*.96);ctx.lineTo(W-4,H*.1);ctx.closePath();ctx.fill();
  ctx.strokeStyle='rgba(0,0,0,.1)';ctx.lineWidth=1.5;for(let i=1;i<nf;i++){const fy=H*.1+H*.86*i/nf;ctx.beginPath();ctx.moveTo(4,fy-3);ctx.lineTo(4+panW,fy-3);ctx.stroke();ctx.beginPath();ctx.moveTo(W-panW-4,fy-3);ctx.lineTo(W-4,fy-3);ctx.stroke();}
}

function drawPainting(ctx,W,H){
  const p=8;ctx.save();sh(ctx,6,0,3);fsr(ctx,p,p,W-p*2,H-p*2,5,'#B88C28','#8A6010',2);nsh(ctx);ctx.restore();
  const cg=ctx.createLinearGradient(p+10,p+10,W-p-10,H-p-10);cg.addColorStop(0,'#E8CE80');cg.addColorStop(.28,'#5A8AC0');cg.addColorStop(.62,'#78B050');cg.addColorStop(1,'#C07040');
  fsr(ctx,p+10,p+10,W-p*2-20,H-p*2-20,2,cg,'rgba(0,0,0,.1)',1);
  ctx.fillStyle='rgba(255,255,255,.22)';ctx.beginPath();ctx.ellipse(W*.35,H*.42,W*.15,H*.24,.6,0,Math.PI*2);ctx.fill();
  ctx.fillStyle='rgba(0,0,0,.1)';ctx.beginPath();ctx.arc(W*.68,H*.52,W*.1,0,Math.PI*2);ctx.fill();
}

/* ═══════════════════════════════════════════════════
   FURNITURE CATALOG — same IDs/sizes as original
═══════════════════════════════════════════════════ */
const FURNITURE_CATALOG=[
  {id:'sofa3',name:'3-Seat Sofa',cat:'seating',w_cm:210,h_cm:90,depth_cm:85,draw:drawSofa3},
  {id:'sofa2',name:'Loveseat',cat:'seating',w_cm:145,h_cm:85,depth_cm:80,draw:drawSofa2},
  {id:'armchair',name:'Armchair',cat:'seating',w_cm:80,h_cm:85,depth_cm:80,draw:drawArmchair},
  {id:'office_chair',name:'Office Chair',cat:'seating',w_cm:65,h_cm:115,depth_cm:65,draw:drawOfficeChair},
  {id:'bar_stool',name:'Bar Stool',cat:'seating',w_cm:40,h_cm:100,depth_cm:40,draw:drawBarStool},
  {id:'bench',name:'Bench',cat:'seating',w_cm:120,h_cm:48,depth_cm:38,draw:drawBench},
  {id:'dining_table',name:'Dining Table',cat:'tables',w_cm:160,h_cm:75,depth_cm:80,draw:drawDiningTable},
  {id:'coffee_table',name:'Coffee Table',cat:'tables',w_cm:120,h_cm:40,depth_cm:60,draw:drawCoffeeTable},
  {id:'round_table',name:'Round Table',cat:'tables',w_cm:90,h_cm:75,depth_cm:90,draw:drawRoundTable},
  {id:'desk',name:'Study Desk',cat:'tables',w_cm:140,h_cm:75,depth_cm:60,draw:drawDesk},
  {id:'side_table',name:'Side Table',cat:'tables',w_cm:50,h_cm:55,depth_cm:50,draw:drawSideTable},
  {id:'wardrobe',name:'Wardrobe',cat:'storage',w_cm:160,h_cm:200,depth_cm:55,draw:drawWardrobe},
  {id:'bookshelf',name:'Bookshelf',cat:'storage',w_cm:90,h_cm:180,depth_cm:30,draw:drawBookshelf},
  {id:'dresser',name:'Dresser',cat:'storage',w_cm:100,h_cm:80,depth_cm:45,draw:drawDresser},
  {id:'cabinet',name:'TV Cabinet',cat:'storage',w_cm:150,h_cm:55,depth_cm:40,draw:drawTVCabinet},
  {id:'shoe_rack',name:'Shoe Rack',cat:'storage',w_cm:90,h_cm:120,depth_cm:30,draw:drawShoeRack},
  {id:'queen_bed',name:'Queen Bed',cat:'beds',w_cm:160,h_cm:55,depth_cm:200,draw:(c,W,H)=>drawBed(c,W,H,'#EAE2D6','#7A6A5A',160)},
  {id:'single_bed',name:'Single Bed',cat:'beds',w_cm:100,h_cm:55,depth_cm:200,draw:(c,W,H)=>drawBed(c,W,H,'#C8D8E8','#6A8090',100)},
  {id:'king_bed',name:'King Bed',cat:'beds',w_cm:190,h_cm:55,depth_cm:200,draw:(c,W,H)=>drawBed(c,W,H,'#D8E8D0','#6A8870',190)},
  {id:'tv',name:'Flat Screen TV',cat:'appliances',w_cm:140,h_cm:85,depth_cm:10,draw:drawTV},
  {id:'fridge',name:'Refrigerator',cat:'appliances',w_cm:70,h_cm:170,depth_cm:65,draw:drawFridge},
  {id:'washer',name:'Washer',cat:'appliances',w_cm:60,h_cm:85,depth_cm:55,draw:drawWashingMachine},
  {id:'ac',name:'Air Conditioner',cat:'appliances',w_cm:80,h_cm:25,depth_cm:20,draw:drawACUnit},
  {id:'toilet',name:'Toilet',cat:'appliances',w_cm:40,h_cm:80,depth_cm:65,draw:drawToilet},
  {id:'bathtub',name:'Bathtub',cat:'appliances',w_cm:75,h_cm:55,depth_cm:160,draw:drawBathtub},
  {id:'sink',name:'Kitchen Sink',cat:'appliances',w_cm:80,h_cm:85,depth_cm:55,draw:drawKitchenSink},
  {id:'floor_lamp',name:'Floor Lamp',cat:'lighting',w_cm:40,h_cm:160,depth_cm:40,draw:drawFloorLamp},
  {id:'pendant',name:'Pendant Light',cat:'lighting',w_cm:40,h_cm:50,depth_cm:40,draw:drawPendantLight},
  {id:'table_lamp',name:'Table Lamp',cat:'lighting',w_cm:30,h_cm:50,depth_cm:30,draw:drawTableLamp},
  {id:'plant_lg',name:'Large Plant',cat:'decor',w_cm:50,h_cm:140,depth_cm:50,draw:(c,W,H)=>drawPlant(c,W,H,'large')},
  {id:'plant_sm',name:'Small Plant',cat:'decor',w_cm:25,h_cm:40,depth_cm:25,draw:(c,W,H)=>drawPlant(c,W,H,'small')},
  {id:'rug',name:'Area Rug',cat:'decor',w_cm:200,h_cm:5,depth_cm:140,draw:drawRug},
  {id:'mirror',name:'Wall Mirror',cat:'decor',w_cm:80,h_cm:120,depth_cm:5,draw:drawMirror},
  {id:'curtain',name:'Curtain',cat:'decor',w_cm:120,h_cm:220,depth_cm:10,draw:drawCurtain},
  {id:'painting',name:'Wall Art',cat:'decor',w_cm:80,h_cm:60,depth_cm:3,draw:drawPainting},
];

/* ═══════════════════════════════════════════════════
   ALL CODE BELOW IS IDENTICAL TO ORIGINAL (doc 2)
   Nothing changed except draw functions above.
═══════════════════════════════════════════════════ */
let furnSizeScale=1.0,activeFurnDrag=null,activeProductDrag=null;
function updateFurnSize(v){furnSizeScale=v/100;document.getElementById('furnSizeVal').textContent=v+'%';}

function buildFurnList(filterCat='all'){
  const container=document.getElementById('furnList');
  const cats={};
  FURNITURE_CATALOG.forEach(it=>{if(filterCat!=='all'&&it.cat!==filterCat)return;if(!cats[it.cat])cats[it.cat]=[];cats[it.cat].push(it);});
  const catLabels={seating:'Seating',tables:'Tables',storage:'Storage',beds:'Beds',appliances:'Appliances',lighting:'Lighting',decor:'Décor'};
  let html='';
  for(const[cat,items]of Object.entries(cats)){
    html+=`<div class="furn-cat-group">`;
    if(filterCat==='all')html+=`<div class="furn-cat-title">${catLabels[cat]||cat}</div>`;
    html+=`<div class="furn-grid">`;
    items.forEach(it=>{html+=`<div class="furn-thumb" draggable="true" data-fid="${it.id}" title="${it.name}"><canvas width="110" height="110" data-fid="${it.id}"></canvas><div class="furn-label">${it.name}</div></div>`;});
    html+=`</div></div>`;
  }
  container.innerHTML=html||'<p style="text-align:center;color:#999;font-size:.72rem;padding:16px">No items.</p>';
  container.querySelectorAll('canvas[data-fid]').forEach(c=>{
    const def=FURNITURE_CATALOG.find(f=>f.id===c.dataset.fid);if(!def)return;
    const c2=c.getContext('2d');c2.clearRect(0,0,110,110);
    try{def.draw(c2,110,110);}catch(e){console.warn(def.id,e);}
  });
  container.querySelectorAll('.furn-thumb').forEach(th=>{
    th.addEventListener('dragstart',e=>{const def=FURNITURE_CATALOG.find(f=>f.id===th.dataset.fid);if(!def)return;activeFurnDrag={furnDef:def};activeProductDrag=null;e.dataTransfer.effectAllowed='copy';});
    th.addEventListener('click',()=>{const def=FURNITURE_CATALOG.find(f=>f.id===th.dataset.fid);if(!def)return;addFurniture(def,CW/2,CH/2);closeMobileSidebar();showHint(`Added ${def.name}`);});
  });
}

document.getElementById('furnCats').addEventListener('click',e=>{
  const b=e.target.closest('.fcat');if(!b)return;
  document.querySelectorAll('.fcat').forEach(x=>x.classList.toggle('active',x===b));
  buildFurnList(b.dataset.cat);
});
function switchTab(t){
  document.querySelectorAll('.stab').forEach(b=>b.classList.toggle('active',b.id===`stab-${t}`));
  document.querySelectorAll('.stab-panel').forEach(p=>p.classList.toggle('active',p.id===`panel-${t}`));
}

function toggleMobileSidebar(){const sb=document.getElementById('simSidebar'),bd=document.getElementById('backdrop');sb.classList.toggle('open');bd.classList.toggle('show',sb.classList.contains('open'));}
function closeMobileSidebar(){document.getElementById('simSidebar').classList.remove('open');document.getElementById('backdrop').classList.remove('show');}
function toggleMobLayers(){const d=document.getElementById('mobDrawer');d.classList.toggle('show');if(d.classList.contains('show'))syncMobLayers();}
function syncMobLayers(){
  const dest=document.getElementById('mobLayersBody');
  dest.innerHTML=document.getElementById('layersList').innerHTML;
  dest.querySelectorAll('.layer-item').forEach(r=>{
    r.addEventListener('click',()=>{selectItem(+r.dataset.id);document.getElementById('mobDrawer').classList.remove('show');});
  });
  dest.querySelectorAll('.ldel').forEach(b=>{
    b.addEventListener('click',e=>{e.stopPropagation();deleteItem(+(b.closest('.layer-item').dataset.id));syncMobLayers();});
  });
  // ── Mobile drag-to-reorder for layers ──
  let mpDragId=null,mpGhost=null,mpOffY=0;
  dest.querySelectorAll('.lhandle[data-lh]').forEach(handle=>{
    const row=handle.closest('.layer-item');
    handle.style.touchAction='none';
    handle.addEventListener('pointerdown',e=>{
      e.preventDefault();e.stopPropagation();
      handle.setPointerCapture(e.pointerId);
      // Lock drawer body scroll while dragging
      dest.style.overflowY='hidden';
      mpDragId=+row.dataset.id;mpOffY=e.clientY-row.getBoundingClientRect().top;
      mpGhost=row.cloneNode(true);const rb=row.getBoundingClientRect();
      Object.assign(mpGhost.style,{position:'fixed',left:rb.left+'px',top:rb.top+'px',width:rb.width+'px',
        opacity:'.8',pointerEvents:'none',zIndex:'9999',background:'#e4f5d8',
        border:'2px solid #2c5f2d',borderRadius:'8px',boxShadow:'0 4px 16px rgba(0,0,0,.3)'});
      document.body.appendChild(mpGhost);row.classList.add('ldragging');
    });
    handle.addEventListener('pointermove',e=>{
      if(!mpDragId)return;e.preventDefault();e.stopPropagation();
      mpGhost.style.top=(e.clientY-mpOffY)+'px';
      dest.querySelectorAll('.layer-item').forEach(r=>r.classList.remove('ldrag-top','ldrag-bot'));
      const rows=[...dest.querySelectorAll('.layer-item:not(.ldragging)')];
      const over=rows.find(r=>{const b=r.getBoundingClientRect();return e.clientY>=b.top&&e.clientY<=b.bottom;});
      if(over){const mid=over.getBoundingClientRect().top+over.offsetHeight/2;over.classList.add(e.clientY<mid?'ldrag-top':'ldrag-bot');}
    });
    const finishDrag=e=>{
      if(!mpDragId)return;
      const rows=[...dest.querySelectorAll('.layer-item:not(.ldragging)')];
      const over=rows.find(r=>{const b=r.getBoundingClientRect();return e.clientY>=b.top&&e.clientY<=b.bottom;});
      if(over){const tid=+over.dataset.id;if(tid!==mpDragId){const mid=over.getBoundingClientRect().top+over.offsetHeight/2;const before=e.clientY<mid;const fi=placedItems.findIndex(i=>i.id===mpDragId);const[moved]=placedItems.splice(fi,1);const ins=placedItems.findIndex(i=>i.id===tid);placedItems.splice(before?ins+1:ins,0,moved);redraw();}}
      if(mpGhost){mpGhost.remove();mpGhost=null;}
      dest.querySelectorAll('.layer-item').forEach(r=>r.classList.remove('ldrag-top','ldrag-bot','ldragging'));
      dest.style.overflowY='auto';
      mpDragId=null;updateLayers();syncMobLayers();
    };
    handle.addEventListener('pointerup',finishDrag);
    handle.addEventListener('pointercancel',()=>{if(mpGhost){mpGhost.remove();mpGhost=null;}dest.querySelectorAll('.layer-item').forEach(r=>r.classList.remove('ldrag-top','ldrag-bot','ldragging'));dest.style.overflowY='auto';mpDragId=null;});
  });
}
function showMobSurface(){const el=document.getElementById('mobSurface'),bd=document.getElementById('surfBackdrop');el.style.display='flex';requestAnimationFrame(()=>el.classList.add('show'));if(bd)bd.style.display='block';}
function hideMobSurface(){const el=document.getElementById('mobSurface'),bd=document.getElementById('surfBackdrop');el.classList.remove('show');if(bd)bd.style.display='none';}

/* ── ORIGINAL CANVAS/ROOM CODE ── */
const canvas=document.getElementById('roomCanvas');
const ctx=canvas.getContext('2d');
const CW=canvas.width,CH=canvas.height;

let placedItems=[],selectedId=null,clipboard=null;
let isDragging=false,isResizing=false,resizeHandle=null;
let dragOX=0,dragOY=0,resStartX=0,resStartY=0,resStartW=0,resStartH=0,resItemX=0,resItemY=0;
let itemIdCnt=0,currentPreset='living',activeSurface='floor';
let fillGuides=[];
const SNAP_T=8,HS=8;
const CLR_CENTER='rgba(255,20,147,.85)',CLR_EDGE='rgba(0,180,255,.85)';

/* ORIGINAL exact corners from doc 2 */
const BW={tl:{x:CW*.22,y:CH*.08},tr:{x:CW*.78,y:CH*.08},bl:{x:CW*.22,y:CH*.85},br:{x:CW*.78,y:CH*.85}};
const OC={tl:{x:0,y:0},tr:{x:CW,y:0},bl:{x:0,y:CH},br:{x:CW,y:CH}};

const SURFACES={
  back_wall:{label:'Back Wall',poly:[BW.tl,BW.tr,BW.br,BW.bl],bbox:()=>({x:BW.tl.x,y:BW.tl.y,w:BW.tr.x-BW.tl.x,h:BW.bl.y-BW.tl.y})},
  left_wall:{label:'Left Wall',poly:[OC.tl,BW.tl,BW.bl,OC.bl],bbox:()=>({x:OC.tl.x,y:OC.tl.y,w:BW.tl.x,h:OC.bl.y})},
  right_wall:{label:'Right Wall',poly:[BW.tr,OC.tr,OC.br,BW.br],bbox:()=>({x:BW.tr.x,y:OC.tr.y,w:OC.tr.x-BW.tr.x,h:OC.br.y})},
  ceiling:{label:'Ceiling',poly:[OC.tl,OC.tr,BW.tr,BW.tl],bbox:()=>({x:0,y:0,w:CW,h:BW.tl.y})},
  floor:{label:'Floor',poly:[BW.bl,BW.br,OC.br,OC.bl],bbox:()=>({x:0,y:BW.bl.y,w:CW,h:CH-BW.bl.y})},
};
const ROOM_DIMS={back_wall:{wM:5,hM:2.8},left_wall:{wM:4,hM:2.8},right_wall:{wM:4,hM:2.8},floor:{wM:5,hM:4},ceiling:{wM:5,hM:4}};

function bilerp(corners,u,v){const[tl,tr,br,bl]=corners;return{x:(1-v)*((1-u)*tl.x+u*tr.x)+v*((1-u)*bl.x+u*br.x),y:(1-v)*((1-u)*tl.y+u*tr.y)+v*((1-u)*bl.y+u*br.y)};}
function pxPerCm(){return(BW.tr.x-BW.tl.x)/(ROOM_DIMS.back_wall.wM*100);}
function parseSizeName(s){if(!s||/sqm/i.test(s))return{w:null,h:null};const nums=[...s.matchAll(/(\d+(?:\.\d+)?)/g)].map(m=>+m[1]);if(!nums.length)return{w:null,h:null};const ft=/\bft\b/i.test(s);if(ft&&nums.length>=2)return{w:Math.round(nums[0]*30.48),h:Math.round(nums[1]*30.48)};if(ft&&nums.length===1){const v=Math.round(nums[0]*30.48);return{w:v,h:v};}if(nums.length===1){let v=nums[0];if(/mm/i.test(s))v/=10;else if(/m\b/i.test(s)&&v<20)v*=100;return{w:v,h:v};}const isMM=/mm/i.test(s),isCM=/cm/i.test(s);let conv=nums.map(n=>isMM?n/10:isCM?n:n>500?n/10:n);if(conv.length>=3){const mi=Math.min(...conv),idx=conv.indexOf(mi);conv.splice(idx,1);}const[d1,d2]=conv,longer=Math.max(d1,d2),shorter=Math.min(d1,d2),ratio=longer/Math.max(shorter,.01);if(ratio>=1.8&&ratio<=2.2&&longer>=100)return{w:shorter,h:longer};if(ratio<3)return{w:d1,h:d2};return{w:shorter,h:longer};}

const PRESETS={
  living:()=>drawRoom('#F0EBE0','#D4C4A8','#E8E0D0','#C8B89A'),
  bedroom:()=>drawRoom('#E8E0F0','#C8C0A8','#D8D0E8','#B8B0A0'),
  bathroom:()=>drawRoom('#D0E8F0','#B8D0D8','#C0D8E8','#A0B8C0',true),
  kitchen:()=>drawRoom('#F5F0E0','#D4C8A8','#E8E4D0','#B8A888'),
  blank:()=>drawRoom('#F4F4F2','#E0DDD8','#ECECEA','#D0CDC8'),
};
function scl(hex,a){const n=parseInt(hex.replace('#',''),16),r=Math.min(255,Math.max(0,(n>>16)+a)),g=Math.min(255,Math.max(0,((n>>8)&0xff)+a)),b=Math.min(255,Math.max(0,(n&0xff)+a));return'#'+[r,g,b].map(v=>v.toString(16).padStart(2,'0')).join('');}
function poly(pts){ctx.moveTo(pts[0].x,pts[0].y);for(let i=1;i<pts.length;i++)ctx.lineTo(pts[i].x,pts[i].y);ctx.closePath();}
function drawRoom(wall,floor,ceil,base,tiles){
  ctx.clearRect(0,0,CW,CH);
  ctx.fillStyle=wall;ctx.beginPath();poly([BW.tl,BW.tr,BW.br,BW.bl]);ctx.fill();
  const lg=ctx.createLinearGradient(0,0,BW.tl.x,0);lg.addColorStop(0,scl(wall,-20));lg.addColorStop(1,wall);ctx.fillStyle=lg;ctx.beginPath();poly([OC.tl,BW.tl,BW.bl,OC.bl]);ctx.fill();
  const rg=ctx.createLinearGradient(BW.tr.x,0,CW,0);rg.addColorStop(0,wall);rg.addColorStop(1,scl(wall,-24));ctx.fillStyle=rg;ctx.beginPath();poly([BW.tr,OC.tr,OC.br,BW.br]);ctx.fill();
  const cg=ctx.createLinearGradient(0,0,0,BW.tl.y);cg.addColorStop(0,ceil);cg.addColorStop(1,scl(ceil,-8));ctx.fillStyle=cg;ctx.beginPath();poly([OC.tl,OC.tr,BW.tr,BW.tl]);ctx.fill();
  const fg=ctx.createLinearGradient(0,BW.bl.y,0,CH);fg.addColorStop(0,floor);fg.addColorStop(1,scl(floor,-22));ctx.fillStyle=fg;ctx.beginPath();poly([BW.bl,BW.br,OC.br,OC.bl]);ctx.fill();
  if(tiles){ctx.save();ctx.beginPath();poly([BW.tl,BW.tr,BW.br,BW.bl]);ctx.clip();ctx.strokeStyle='rgba(255,255,255,0.3)';ctx.lineWidth=1;for(let x=BW.tl.x;x<BW.tr.x;x+=60){ctx.beginPath();ctx.moveTo(x,BW.tl.y);ctx.lineTo(x,BW.bl.y);ctx.stroke();}for(let y=BW.tl.y;y<BW.bl.y;y+=60){ctx.beginPath();ctx.moveTo(BW.tl.x,y);ctx.lineTo(BW.tr.x,y);ctx.stroke();}ctx.restore();}
  ctx.strokeStyle=base;ctx.lineWidth=3;[[OC.tl,BW.tl],[OC.tr,BW.tr],[OC.bl,BW.bl],[OC.br,BW.br],[BW.tl,BW.tr],[BW.tr,BW.br],[BW.br,BW.bl],[BW.bl,BW.tl]].forEach(([a,b])=>{ctx.beginPath();ctx.moveTo(a.x,a.y);ctx.lineTo(b.x,b.y);ctx.stroke();});
  ctx.fillStyle='rgba(100,142,55,0.22)';ctx.font='bold 11px Segoe UI';ctx.textAlign='center';ctx.fillText('BACK WALL',(BW.tl.x+BW.tr.x)/2,(BW.tl.y+BW.bl.y)/2);ctx.fillText('LEFT WALL',(OC.tl.x+BW.tl.x)/2,(OC.tl.y+OC.bl.y)/2);ctx.fillText('RIGHT WALL',(BW.tr.x+OC.tr.x)/2,(OC.tr.y+OC.br.y)/2);ctx.fillText('FLOOR',CW/2,(BW.bl.y+CH)/2+8);ctx.fillText('CEILING',CW/2,BW.tl.y/2);ctx.textAlign='left';
}

function triMap(ctx,img,sX,sY,sW,sH,p0,p1,p2,u0,v0,u1,v1,u2,v2){const x0=p0.x,y0=p0.y,x1=p1.x,y1=p1.y,x2=p2.x,y2=p2.y;const ix0=sX+u0*sW,iy0=sY+v0*sH,ix1=sX+u1*sW,iy1=sY+v1*sH,ix2=sX+u2*sW,iy2=sY+v2*sH;const det=ix0*(iy1-iy2)+ix1*(iy2-iy0)+ix2*(iy0-iy1);if(Math.abs(det)<1e-10)return;const a=(x0*(iy1-iy2)+x1*(iy2-iy0)+x2*(iy0-iy1))/det,c=(x0*(ix2-ix1)+x1*(ix0-ix2)+x2*(ix1-ix0))/det,e=x0-a*ix0-c*iy0;const b=(y0*(iy1-iy2)+y1*(iy2-iy0)+y2*(iy0-iy1))/det,d=(y0*(ix2-ix1)+y1*(ix0-ix2)+y2*(ix1-ix0))/det,f=y0-b*ix0-d*iy0;ctx.save();ctx.transform(a,b,c,d,e,f);ctx.drawImage(img,0,0);ctx.restore();}
function drawTile(ctx,img,poly4,cols,rows,col,row,opa,crop,rowOff){rowOff=rowOff||0;const u0=(col+rowOff)/cols,u1=(col+1+rowOff)/cols,v0=row/rows,v1=(row+1)/rows;const cu0=Math.max(0,u0),cu1=Math.min(1,u1),cv0=Math.max(0,v0),cv1=Math.min(1,v1);if(cu1<=cu0||cv1<=cv0)return;const ptl=bilerp(poly4,cu0,cv0),ptr=bilerp(poly4,cu1,cv0),pbr=bilerp(poly4,cu1,cv1),pbl=bilerp(poly4,cu0,cv1);const iw=img.naturalWidth,ih=img.naturalHeight;let sl=0,st=0,sw=iw,sh=ih;if(crop){sl=Math.round(iw*(crop.left||0)/100);st=Math.round(ih*(crop.top||0)/100);sw=Math.max(1,iw-sl-Math.round(iw*(crop.right||0)/100));sh=Math.max(1,ih-st-Math.round(ih*(crop.bottom||0)/100));}const ifl=Math.max(0,-u0)/(u1-u0),ifr=1-Math.max(0,u1-1)/(u1-u0);const ift=Math.max(0,-v0)/(v1-v0),ifb=1-Math.max(0,v1-1)/(v1-v0);const srcX=sl+sw*ifl,srcY=st+sh*ift,srcW=sw*(ifr-ifl),srcH=sh*(ifb-ift);if(srcW<1||srcH<1)return;ctx.save();ctx.globalAlpha=opa;ctx.beginPath();ctx.moveTo(ptl.x,ptl.y);ctx.lineTo(ptr.x,ptr.y);ctx.lineTo(pbr.x,pbr.y);ctx.lineTo(pbl.x,pbl.y);ctx.closePath();ctx.clip();triMap(ctx,img,srcX,srcY,srcW,srcH,ptl,ptr,pbl,0,0,1,0,0,1);triMap(ctx,img,srcX,srcY,srcW,srcH,ptr,pbr,pbl,1,0,1,1,0,1);ctx.restore();}
function drawTileHB(ctx,img,poly4,cols,rows,col,row,opa,crop,isV){const u0=col/cols,u1=(col+1)/cols,v0=row/rows,v1=(row+1)/rows;const ptl=bilerp(poly4,u0,v0),ptr=bilerp(poly4,u1,v0),pbr=bilerp(poly4,u1,v1),pbl=bilerp(poly4,u0,v1);const iw=img.naturalWidth,ih=img.naturalHeight;let sl=0,st=0,sw=iw,sh=ih;if(crop){sl=Math.round(iw*(crop.left||0)/100);st=Math.round(ih*(crop.top||0)/100);sw=Math.max(1,iw-sl-Math.round(iw*(crop.right||0)/100));sh=Math.max(1,ih-st-Math.round(ih*(crop.bottom||0)/100));}ctx.save();ctx.globalAlpha=opa;ctx.beginPath();ctx.moveTo(ptl.x,ptl.y);ctx.lineTo(ptr.x,ptr.y);ctx.lineTo(pbr.x,pbr.y);ctx.lineTo(pbl.x,pbl.y);ctx.closePath();ctx.clip();if(isV){triMap(ctx,img,sl,st,sw,sh,ptl,ptr,pbl,0,0,1,0,0,1);triMap(ctx,img,sl,st,sw,sh,ptr,pbr,pbl,1,0,1,1,0,1);}else{triMap(ctx,img,sl,st,sw,sh,ptl,ptr,pbl,0,1,0,0,1,1);triMap(ctx,img,sl,st,sw,sh,ptr,pbr,pbl,0,0,1,0,1,1);}ctx.restore();}

function bakeFurn(item){const def=item.furnDef;if(!def)return;const rot=(item.rotation||0)%360,swapped=rot===90||rot===270;const bW=item._bW||item.w,bH=item._bH||item.h;const oW=swapped?bH:bW,oH=swapped?bW:bH;const off=document.createElement('canvas');off.width=Math.round(oW);off.height=Math.round(oH);const oc=off.getContext('2d');oc.save();oc.translate(off.width/2,off.height/2);oc.rotate(rot*Math.PI/180);if(item.flipX)oc.scale(-1,1);if(item.flipY)oc.scale(1,-1);oc.translate(-(swapped?bH:bW)/2,-(swapped?bW:bH)/2);try{def.draw(oc,swapped?bH:bW,swapped?bW:bH);}catch(e){}oc.restore();const cx=item.x+item.w/2,cy=item.y+item.h/2;item.w=off.width;item.h=off.height;item.x=cx-off.width/2;item.y=cy-off.height/2;item.imgSrc=off.toDataURL();const ni=new Image();ni.src=item.imgSrc;item.imgEl=ni;return ni;}
function bakeProduct(item){const rot=(item.rotation||0)%360,swapped=rot===90||rot===270;const bW=item._bW||item.w,bH=item._bH||item.h;const oW=swapped?bH:bW,oH=swapped?bW:bH;const off=document.createElement('canvas');off.width=Math.round(oW);off.height=Math.round(oH);const oc=off.getContext('2d');const img=item._srcImg||item.imgEl;const iw=img.naturalWidth,ih=img.naturalHeight;let sl=0,st=0,sw=iw,sh=ih;if(item.crop){sl=Math.round(iw*(item.crop.left||0)/100);st=Math.round(ih*(item.crop.top||0)/100);sw=Math.max(1,iw-sl-Math.round(iw*(item.crop.right||0)/100));sh=Math.max(1,ih-st-Math.round(ih*(item.crop.bottom||0)/100));}oc.save();oc.translate(off.width/2,off.height/2);if(rot)oc.rotate(rot*Math.PI/180);if(item.flipX)oc.scale(-1,1);if(item.flipY)oc.scale(1,-1);const dW=swapped?oH:oW,dH=swapped?oW:oH;oc.drawImage(img,sl,st,sw,sh,-dW/2,-dH/2,dW,dH);oc.restore();return off;}
function rotateSelected(deg){if(!selectedId){showHint('Select an item first');return;}const item=placedItems.find(i=>i.id===selectedId);if(!item||item.isFillGroup)return;item.rotation=((item.rotation||0)+deg+360)%360;if(!item._bW){item._bW=item.w;item._bH=item.h;}if(item.isFurniture){const ni=bakeFurn(item);if(ni)ni.onload=()=>{redraw();updateLayers();};return;}if(!item._srcImg)item._srcImg=item.imgEl;const swapped=(item.rotation===90||item.rotation===270),cx=item.x+item.w/2,cy=item.y+item.h/2;item.w=swapped?item._bH:item._bW;item.h=swapped?item._bW:item._bH;item.x=cx-item.w/2;item.y=cy-item.h/2;const off=bakeProduct(item);const ni=new Image();ni.src=off.toDataURL();item.imgEl=ni;item.imgSrc=off.toDataURL();ni.onload=()=>{redraw();updateLayers();};showHint(`Rotated ${deg>0?deg+'°':Math.abs(deg)+'° CCW'}`);}
function flipSelected(axis){if(!selectedId){showHint('Select an item first');return;}const item=placedItems.find(i=>i.id===selectedId);if(!item)return;if(axis==='x')item.flipX=!item.flipX;else item.flipY=!item.flipY;if(!item._bW){item._bW=item.w;item._bH=item.h;}if(item.isFurniture&&item.furnDef){const ni=bakeFurn(item);if(ni)ni.onload=()=>{redraw();updateLayers();};return;}if(!item._srcImg)item._srcImg=item.imgEl;const off=bakeProduct(item);const ni=new Image();ni.src=off.toDataURL();item.imgEl=ni;item.imgSrc=off.toDataURL();ni.onload=()=>{redraw();updateLayers();};showHint(`Flipped ${axis==='x'?'H':'V'}`);}

function getHandles(item){return{nw:{x:item.x,y:item.y},ne:{x:item.x+item.w,y:item.y},sw:{x:item.x,y:item.y+item.h},se:{x:item.x+item.w,y:item.y+item.h}};}
function redraw(){
  ctx.clearRect(0,0,CW,CH);PRESETS[currentPreset]();
  placedItems.forEach(item=>{
    if(!item.imgEl?.complete||!item.imgEl.naturalWidth)return;
    ctx.save();
    const p=SURFACES[item.surface]?.poly;
    if(p&&!item.isFurniture){ctx.beginPath();ctx.moveTo(p[0].x,p[0].y);for(let i=1;i<p.length;i++)ctx.lineTo(p[i].x,p[i].y);ctx.closePath();ctx.clip();}
    if(item.isFillGroup){const{fillCols:fc,fillRows:fr,fillPattern:fp,surfPoly:sp,imgEl:ig,opacity:op,crop}=item;for(let row=0;row<fr;row++){if(fp==='herringbone')for(let col=0;col<fc;col++)drawTileHB(ctx,ig,sp,fc,fr,col,row,op,crop,(col+row)%2===0);else{const ro=(fp==='brick'&&row%2===1)?.5/fc:0;for(let col=0;col<fc;col++)drawTile(ctx,ig,sp,fc,fr,col,row,op,crop,ro);}}}
    else{ctx.globalAlpha=item.opacity;ctx.drawImage(item.imgEl,item.x,item.y,item.w,item.h);}
    ctx.restore();
    if(item.id===selectedId){ctx.save();ctx.strokeStyle='#648E37';ctx.lineWidth=2;ctx.setLineDash([5,3]);ctx.strokeRect(item.x-1,item.y-1,item.w+2,item.h+2);ctx.setLineDash([]);ctx.fillStyle='#dc3545';ctx.beginPath();ctx.arc(item.x+item.w,item.y,11,0,Math.PI*2);ctx.fill();ctx.fillStyle='#fff';ctx.font='bold 13px Arial';ctx.textAlign='center';ctx.textBaseline='middle';ctx.fillText('×',item.x+item.w,item.y);const lbl=item.isFurniture?item.name:(SURFACES[item.surface]?.label||item.surface);const bw=ctx.measureText(lbl).width+14,bx=item.x+item.w/2-bw/2,by=item.y-20;ctx.fillStyle='rgba(44,95,45,.85)';ctx.fillRect(bx,by,bw,16);ctx.fillStyle='#fff';ctx.font='bold 10px Segoe UI';ctx.fillText(lbl,item.x+item.w/2,by+8);ctx.textAlign='left';ctx.textBaseline='alphabetic';ctx.fillStyle='#2c5f2d';ctx.strokeStyle='#fff';ctx.lineWidth=1.5;Object.values(getHandles(item)).forEach(h=>{ctx.beginPath();ctx.rect(h.x-HS/2,h.y-HS/2,HS,HS);ctx.fill();ctx.stroke();});if(item.rotation){ctx.fillStyle='rgba(100,142,55,.85)';ctx.font='bold 10px Segoe UI';ctx.textAlign='center';const rt=`↻ ${item.rotation}°`,rtw=ctx.measureText(rt).width+10;ctx.fillRect(item.x+item.w/2-rtw/2,item.y+item.h+4,rtw,15);ctx.fillStyle='#fff';ctx.fillText(rt,item.x+item.w/2,item.y+item.h+13);ctx.textAlign='left';}ctx.restore();}
  });
}

function addFurniture(def,dropX,dropY){const id=++itemIdCnt,pc=pxPerCm();const bW=Math.max(100,Math.round(pc*Math.max(def.w_cm,def.depth_cm)*furnSizeScale*1.4));const bH=Math.max(100,Math.round(pc*def.h_cm*furnSizeScale*1.2));const off=document.createElement('canvas');off.width=bW;off.height=bH;try{def.draw(off.getContext('2d'),bW,bH);}catch(e){}const img=new Image();img.src=off.toDataURL();const item={id,imgEl:img,name:def.name,color:def.cat,sku:def.id,size:`${def.w_cm}×${def.depth_cm}cm`,imgSrc:off.toDataURL(),x:dropX-bW/2,y:dropY-bH/2,w:bW,h:bH,_bW:bW,_bH:bH,opacity:+document.getElementById('opacitySlider').value/100,surface:activeSurface,crop:null,realW:def.w_cm*furnSizeScale,realH:def.h_cm*furnSizeScale,scaleVal:Math.round(furnSizeScale*100),rotation:0,flipX:false,flipY:false,isFurniture:true,furnId:def.id,furnDef:def};img.onload=()=>{placedItems.push(item);selectedId=id;redraw();updateLayers();syncSliders(item);};}
function addProduct(data,dropX,dropY){const img=new Image();img.crossOrigin='anonymous';img.src=data.img;const id=++itemIdCnt;let realW=null,realH=null;if(data.rsaWidth&&data.rsaHeight){realW=+data.rsaWidth;realH=+data.rsaHeight;}else{const p=parseSizeName(data.size||'');realW=p.w;realH=p.h;}const surf=activeSurface;const item={id,imgEl:img,name:data.name,color:data.color,sku:data.sku,size:data.size||'',imgSrc:data.img,x:dropX-60,y:dropY-120,w:40,h:200,_bW:null,_bH:null,_srcImg:null,opacity:+document.getElementById('opacitySlider').value/100,surface:surf,crop:data.rsaCrop||null,realW,realH,scaleVal:100,rotation:0,flipX:false,flipY:false};img.onload=()=>{const pc=pxPerCm();if(realW&&realH){item.w=Math.max(4,Math.round(pc*realW));item.h=Math.max(4,Math.round(pc*realH));}else{const ar=img.naturalWidth/Math.max(1,img.naturalHeight),base=ar>=1.5?120:20;item.w=Math.round(pc*base);item.h=Math.round(item.w*(img.naturalHeight/Math.max(1,img.naturalWidth)));}item.x=dropX-item.w/2;item.y=dropY-item.h/2;item._bW=item.w;item._bH=item.h;item._srcImg=img;placedItems.push(item);selectedId=id;redraw();updateLayers();syncSliders(item);};img.onerror=()=>{if(!img.src.includes('nobg.webp'))img.src='/assets/images/nobg.webp';};}

function copySelected(){if(!selectedId)return;const item=placedItems.find(i=>i.id===selectedId);if(!item)return;clipboard={...JSON.parse(JSON.stringify({...item,imgEl:undefined,furnDef:undefined,_srcImg:undefined})),_imgSrc:item.imgSrc,_isFurn:item.isFurniture,_furnId:item.furnId};showHint(`Copied: ${item.name}`);}
function pasteItem(){if(!clipboard)return;const id=++itemIdCnt,img=new Image();img.crossOrigin='anonymous';img.src=clipboard._imgSrc;const ni={...clipboard,id,imgEl:img,x:clipboard.x+22,y:clipboard.y+22,isFillGroup:false,isFillTile:false,flipX:!!clipboard.flipX,flipY:!!clipboard.flipY,rotation:clipboard.rotation||0};if(clipboard._isFurn){ni.isFurniture=true;ni.furnId=clipboard._furnId;ni.furnDef=FURNITURE_CATALOG.find(f=>f.id===clipboard._furnId);}img.onload=()=>redraw();placedItems.push(ni);selectedId=id;redraw();updateLayers();syncSliders(ni);showHint('Pasted!');}

let cropTarget=null;
function openCropModal(){if(!selectedId){showHint('Select an item to crop');return;}const item=placedItems.find(i=>i.id===selectedId);if(!item?.imgEl||item.isFurniture)return;cropTarget=selectedId;const c=item.crop||{left:0,right:0,top:0,bottom:0};['Left','Right','Top','Bottom'].forEach(s=>document.getElementById('crop'+s).value=c[s.toLowerCase()]||0);document.getElementById('cropModal').classList.remove('hidden');updateCrop();}
function closeCropModal(){document.getElementById('cropModal').classList.add('hidden');cropTarget=null;}
function updateCrop(){const v=['Left','Right','Top','Bottom'].map(s=>+document.getElementById('crop'+s).value);['Left','Right','Top','Bottom'].forEach((s,i)=>document.getElementById('crop'+s+'Val').textContent=v[i]+'%');if(!cropTarget)return;const item=placedItems.find(i=>i.id===cropTarget);if(!item?.imgEl)return;const[l,r,t,b]=v,iw=item.imgEl.naturalWidth,ih=item.imgEl.naturalHeight;const sl=Math.round(iw*l/100),st=Math.round(ih*t/100),sw=Math.max(1,iw-sl-Math.round(iw*r/100)),sh=Math.max(1,ih-st-Math.round(ih*b/100));const cc=document.getElementById('cropCanvas'),cc2=cc.getContext('2d');cc.width=580;cc.height=Math.min(380,Math.round(580*sh/sw));cc2.clearRect(0,0,cc.width,cc.height);cc2.drawImage(item.imgEl,sl,st,sw,sh,0,0,cc.width,cc.height);}
function applyCrop(){if(!cropTarget)return;const item=placedItems.find(i=>i.id===cropTarget);if(!item)return;item.crop={left:+document.getElementById('cropLeft').value,right:+document.getElementById('cropRight').value,top:+document.getElementById('cropTop').value,bottom:+document.getElementById('cropBottom').value};closeCropModal();redraw();}

function setSurface(s){activeSurface=s;document.querySelectorAll('.surf-tab').forEach(t=>t.classList.toggle('active',t.dataset.surface===s));if(selectedId){const item=placedItems.find(i=>i.id===selectedId);if(item&&!item.isFillGroup&&!item.isFurniture){item.surface=s;redraw();updateLayers();}}}
function syncMobSurfBar(el){/* active class already set by setSurface's querySelectorAll above */}

function fillSurface(){const src=placedItems.find(i=>i.id===selectedId);if(!src){showHint('Select a placed item first!');return;}if(!src.imgEl?.complete||!src.imgEl.naturalWidth){showHint('Image not loaded yet');return;}if(src.isFurniture){showHint('Fill is for tile products only');return;}const surf=activeSurface,sd=SURFACES[surf],bbox=sd.bbox(),pc=pxPerCm();const pat=document.getElementById('fillPattern').value;const tW=Math.max(4,src.w),tH=Math.max(4,src.h);const cols=Math.max(1,Math.floor(ROOM_DIMS[surf].wM*100/(tW/pc))),rows=Math.max(1,Math.floor(ROOM_DIMS[surf].hM*100/(tH/pc)));placedItems=placedItems.filter(i=>!(i.surface===surf&&i.isFillGroup));const id=++itemIdCnt;const fi={id,imgEl:src.imgEl,imgSrc:src.imgSrc,name:src.name,color:src.color,sku:src.sku,size:src.size,x:bbox.x,y:bbox.y,w:tW,h:tH,opacity:src.opacity,surface:surf,crop:null,flipX:false,flipY:false,isFillGroup:true,fillCols:cols,fillRows:rows,fillPattern:pat,fillBbox:{...bbox},surfPoly:sd.poly.slice(),rotation:0};placedItems.push(fi);buildFillGrid(fi);selectedId=id;redraw();updateLayers();showHint(`Filled ${sd.label} — ${cols}×${rows} tiles · ${pat}`);}
function buildFillGrid(fi){fillGuides=[];if(!document.getElementById('fillGuides').checked){renderFillGrid();return;}const{surfPoly:sp,fillCols:fc,fillRows:fr,fillPattern:fp}=fi;for(let row=0;row<=fr;row++){const v=row/fr;fillGuides.push({x1:bilerp(sp,0,v).x,y1:bilerp(sp,0,v).y,x2:bilerp(sp,1,v).x,y2:bilerp(sp,1,v).y,t:'h'});}for(let row=0;row<fr;row++){const v0=row/fr,v1=(row+1)/fr,off=(fp==='brick'||fp==='herringbone')&&row%2===1?.5/fc:0;for(let col=0;col<=fc;col++){const u=col/fc+off;if(u<0||u>1)continue;fillGuides.push({x1:bilerp(sp,u,v0).x,y1:bilerp(sp,u,v0).y,x2:bilerp(sp,u,v1).x,y2:bilerp(sp,u,v1).y,t:'v'});}}renderFillGrid();}
function renderFillGrid(){const svg=document.getElementById('gridOverlay');if(!document.getElementById('fillGuides').checked||!fillGuides.length){svg.style.display='none';svg.innerHTML='';return;}svg.style.display='block';svg.innerHTML=`<g>${fillGuides.map(g=>`<line x1="${g.x1}" y1="${g.y1}" x2="${g.x2}" y2="${g.y2}" stroke="${g.t==='h'?'rgba(100,200,80,.55)':'rgba(255,220,60,.5)'}" stroke-width="1" stroke-dasharray="4,3"/>`).join('')}</g>`;}

function smartSnap(item,cx,cy){const W=item.w,H=item.h,mL=cx,mCx=cx+W/2,mR=cx+W,mT=cy,mCy=cy+H/2,mB=cy+H;let bX=null,bDx=SNAP_T+1,bY=null,bDy=SNAP_T+1,lines=[];const xa=[CW/2],ya=[CH/2];const bb=SURFACES[activeSurface]?.bbox?.();if(bb){xa.push(bb.x,bb.x+bb.w/2,bb.x+bb.w);ya.push(bb.y,bb.y+bb.h/2,bb.y+bb.h);}placedItems.filter(i=>i.id!==item.id).forEach(it=>{xa.push(it.x,it.x+it.w/2,it.x+it.w);ya.push(it.y,it.y+it.h/2,it.y+it.h);});for(const a of xa){let d=Math.abs(mL-a);if(d<bDx){bDx=d;bX=a;}d=Math.abs(mCx-a);if(d<bDx){bDx=d;bX=a-W/2;}d=Math.abs(mR-a);if(d<bDx){bDx=d;bX=a-W;}}for(const a of ya){let d=Math.abs(mT-a);if(d<bDy){bDy=d;bY=a;}d=Math.abs(mCy-a);if(d<bDy){bDy=d;bY=a-H/2;}d=Math.abs(mB-a);if(d<bDy){bDy=d;bY=a-H;}}const sX=bDx<=SNAP_T?bX:null,sY=bDy<=SNAP_T?bY:null;const fX=sX!==null?sX:cx,fY=sY!==null?sY:cy;if(sX!==null){lines.push({x1:fX,y1:0,x2:fX,y2:CH,c:CLR_EDGE});lines.push({x1:fX+W,y1:0,x2:fX+W,y2:CH,c:CLR_EDGE});}if(sY!==null){lines.push({x1:0,y1:fY,x2:CW,y2:fY,c:CLR_EDGE});lines.push({x1:0,y1:fY+H,x2:CW,y2:fY+H,c:CLR_EDGE});}if(sX!==null&&Math.abs(fX+W/2-CW/2)<1){lines=lines.filter(l=>!l.x1||l.c!==CLR_EDGE);lines.push({x1:CW/2,y1:0,x2:CW/2,y2:CH,c:CLR_CENTER});}if(sY!==null&&Math.abs(fY+H/2-CH/2)<1){lines.push({x1:0,y1:CH/2,x2:CW,y2:CH/2,c:CLR_CENTER});}return{x:fX,y:fY,lines,snapped:sX!==null||sY!==null};}
function renderSnap(lines){const svg=document.getElementById('snapOverlay');svg.innerHTML=lines?.length?lines.map(g=>`<line x1="${g.x1}" y1="${g.y1}" x2="${g.x2}" y2="${g.y2}" stroke="${g.c}" stroke-width="${Math.ceil(1.5/canvasZoom)}" opacity=".9"/>`).join(''):'';}
function clearSnap(){document.getElementById('snapOverlay').innerHTML='';}

let layerDragId=null;
function updateLayers(){const con=document.getElementById('layersList');if(!placedItems.length){con.innerHTML='<p class="text-muted text-center" style="font-size:.72rem;padding:14px 8px;line-height:1.5">Drag products onto canvas.</p>';return;}con.innerHTML=[...placedItems].reverse().map(item=>{const th=item.isFurniture?`<canvas class="lthumb" width="30" height="30" data-tid="${item.id}"></canvas>`:`<img src="${item.imgSrc}" alt="${item.name||'Material'}" onerror="this.src='/assets/images/nobg.webp'">`;const badges=(item.isFillGroup?'<span style="color:#648E37;font-size:.55rem;font-weight:700"> FILL</span>':item.isFurniture?' 🪑':'')+(item.flipX||item.flipY?`<span style="color:#648E37;font-size:.55rem">${item.flipX?'↔':''}${item.flipY?'↕':''}</span>`:'')+( item.rotation?`<span style="color:#c08020;font-size:.55rem"> ↻${item.rotation}°</span>`:'');const sub=item.isFurniture?`Furniture · ${item.size||''}`:`${item.color} · ${SURFACES[item.surface]?.label||item.surface}${item.isFillGroup?` · ${item.fillCols}×${item.fillRows}`:''}`;return `<div class="layer-item${item.id===selectedId?' sel':''}" data-id="${item.id}"><div class="lhandle" data-lh="1"><span></span><span></span><span></span></div>${th}<div class="linfo" onclick="selectItem(${item.id})"><div class="lname">${item.name}${badges}</div><div class="lsub">${sub}</div></div><button class="ldel" onclick="event.stopPropagation();deleteItem(${item.id})"><i class="fas fa-times"></i></button></div>`;}).join('');
con.querySelectorAll('canvas[data-tid]').forEach(tc=>{const item=placedItems.find(i=>i.id===+tc.dataset.tid);if(!item?.furnDef)return;const tc2=tc.getContext('2d');tc2.clearRect(0,0,30,30);try{item.furnDef.draw(tc2,30,30);}catch(e){};});
// Pointer-event based reorder (works on touch AND mouse)
let pDragId=null,pGhost=null,pOffY=0;
con.querySelectorAll('.lhandle[data-lh]').forEach(handle=>{
  const row=handle.closest('.layer-item');
  handle.style.touchAction='none';
  handle.addEventListener('pointerdown',e=>{
    e.preventDefault();handle.setPointerCapture(e.pointerId);
    // Lock desktop layers panel scroll while dragging
    con.style.overflowY='hidden';
    pDragId=+row.dataset.id;pOffY=e.clientY-row.getBoundingClientRect().top;
    pGhost=row.cloneNode(true);const rb=row.getBoundingClientRect();
    Object.assign(pGhost.style,{position:'fixed',left:rb.left+'px',top:rb.top+'px',width:rb.width+'px',opacity:'.75',pointerEvents:'none',zIndex:'9999',background:'#e4f5d8',border:'2px solid #2c5f2d',borderRadius:'8px',boxShadow:'0 4px 16px rgba(0,0,0,.3)'});
    document.body.appendChild(pGhost);row.classList.add('ldragging');
  });
  handle.addEventListener('pointermove',e=>{
    if(!pDragId)return;e.preventDefault();
    pGhost.style.top=(e.clientY-pOffY)+'px';
    con.querySelectorAll('.layer-item').forEach(r=>r.classList.remove('ldrag-top','ldrag-bot'));
    const rows=[...con.querySelectorAll('.layer-item:not(.ldragging)')];
    const over=rows.find(r=>{const b=r.getBoundingClientRect();return e.clientY>=b.top&&e.clientY<=b.bottom;});
    if(over){const mid=over.getBoundingClientRect().top+over.offsetHeight/2;over.classList.add(e.clientY<mid?'ldrag-top':'ldrag-bot');}
  });
  handle.addEventListener('pointerup',e=>{
    if(!pDragId){return;}
    const rows=[...con.querySelectorAll('.layer-item:not(.ldragging)')];
    const over=rows.find(r=>{const b=r.getBoundingClientRect();return e.clientY>=b.top&&e.clientY<=b.bottom;});
    if(over){const tid=+over.dataset.id;if(tid!==pDragId){const mid=over.getBoundingClientRect().top+over.offsetHeight/2;const before=e.clientY<mid;const fi=placedItems.findIndex(i=>i.id===pDragId);const[moved]=placedItems.splice(fi,1);const ins=placedItems.findIndex(i=>i.id===tid);placedItems.splice(before?ins+1:ins,0,moved);redraw();}}
    if(pGhost){pGhost.remove();pGhost=null;}con.querySelectorAll('.layer-item').forEach(r=>r.classList.remove('ldrag-top','ldrag-bot','ldragging'));con.style.overflowY='auto';pDragId=null;updateLayers();
  });
  handle.addEventListener('pointercancel',()=>{if(pGhost){pGhost.remove();pGhost=null;}con.querySelectorAll('.layer-item').forEach(r=>r.classList.remove('ldrag-top','ldrag-bot','ldragging'));con.style.overflowY='auto';pDragId=null;});
});
// Sync mobile layers if drawer is open
if(typeof _autoSyncMobLayers==='function')setTimeout(_autoSyncMobLayers,0);
}  // end updateLayers

// Auto-sync mobile layers drawer if open
function _autoSyncMobLayers(){const d=document.getElementById('mobDrawer');if(d&&d.classList.contains('show'))syncMobLayers();}

function selectItem(id){selectedId=id;const item=placedItems.find(i=>i.id===id);if(item){syncSliders(item);if(!item.isFillGroup&&!item.isFurniture)setSurface(item.surface);if(item.isFillGroup)buildFillGrid(item);}redraw();updateLayers();}
function syncSliders(item){const ov=Math.round(item.opacity*100),sv=item.scaleVal||100;document.getElementById('opacitySlider').value=ov;document.getElementById('opacityVal').textContent=ov+'%';document.getElementById('scaleSlider').value=sv;document.getElementById('scaleVal').textContent=sv+'%';document.getElementById('opacitySliderMob').value=ov;document.getElementById('scaleSliderMob').value=sv;}
function deleteSelected(){if(selectedId)deleteItem(selectedId);}
function deleteItem(id){placedItems=placedItems.filter(i=>i.id!==id);if(selectedId===id){selectedId=null;fillGuides=[];renderFillGrid();clearSnap();}redraw();updateLayers();}
function clearCanvas(){if(!placedItems.length)return;if(!confirm('Clear all placed items?'))return;placedItems=[];selectedId=null;fillGuides=[];renderFillGrid();clearSnap();redraw();updateLayers();}
function updateOpacity(v){document.getElementById('opacityVal').textContent=v+'%';if(!selectedId)return;const item=placedItems.find(i=>i.id===selectedId);if(item){item.opacity=v/100;redraw();}}
function updateScale(v){document.getElementById('scaleVal').textContent=v+'%';if(!selectedId)return;const item=placedItems.find(i=>i.id===selectedId);if(!item?.imgEl)return;const pct=v/100,cx=item.x+item.w/2,cy=item.y+item.h/2;if(item.isFurniture&&item.furnDef){const pc=pxPerCm(),bW=Math.max(60,Math.round(pc*Math.max(item.furnDef.w_cm,item.furnDef.depth_cm)*1.4)),bH=Math.max(60,Math.round(pc*item.furnDef.h_cm*1.2));if(!item._origBW){item._origBW=bW;item._origBH=bH;}item._bW=Math.round(item._origBW*pct);item._bH=Math.round(item._origBH*pct);item.scaleVal=+v;item.realW=item.furnDef.w_cm*pct;item.realH=item.furnDef.h_cm*pct;const ni=bakeFurn(item);item.x=cx-item.w/2;item.y=cy-item.h/2;if(ni)ni.onload=()=>{item.x=cx-item.w/2;item.y=cy-item.h/2;redraw();updateLayers();};return;}if(!item._bW||!item._bH){const pc=pxPerCm();if(item.realW&&item.realH){item._bW=Math.max(4,Math.round(pc*item.realW));item._bH=Math.max(4,Math.round(pc*item.realH));}else{item._bW=item.w;item._bH=item.h;}}const sw=(item.rotation===90||item.rotation===270);item.w=Math.max(4,Math.round((sw?item._bH:item._bW)*pct));item.h=Math.max(4,Math.round((sw?item._bW:item._bH)*pct));item.x=cx-item.w/2;item.y=cy-item.h/2;item.scaleVal=+v;if(item.rotation&&item.rotation%360){const off=bakeProduct(item);if(off){const ni=new Image();ni.src=off.toDataURL();item.imgEl=ni;item.imgSrc=off.toDataURL();ni.onload=()=>redraw();return;}}redraw();}
function loadRoomPreset(){currentPreset=document.getElementById('roomPreset').value;redraw();}
function downloadCanvas(){const prev=selectedId;selectedId=null;fillGuides=[];renderFillGrid();clearSnap();redraw();const a=document.createElement('a');a.download='room-simulation.png';a.href=canvas.toDataURL('image/png');a.click();selectedId=prev;redraw();}
function showHint(msg){const el=document.getElementById('fillHint');el.textContent=msg;el.classList.add('show');setTimeout(()=>el.classList.remove('show'),2600);}

function getItemAt(mx,my,pad){pad=pad||0;for(let i=placedItems.length-1;i>=0;i--){const it=placedItems[i];if(mx>=it.x-pad&&mx<=it.x+it.w+pad&&my>=it.y-pad&&my<=it.y+it.h+pad)return it;}return null;}
function isOnDel(mx,my,item){return Math.hypot(mx-(item.x+item.w),my-item.y)<=13;}
function canvasXY(e){const r=canvas.getBoundingClientRect();return{mx:(e.clientX-r.left)*(canvas.width/r.width),my:(e.clientY-r.top)*(canvas.height/r.height)};}
function getHandleAt(mx,my,item,hs){if(!item)return null;hs=hs||HS;for(const[n,h]of Object.entries(getHandles(item))){if(mx>=h.x-hs&&mx<=h.x+hs&&my>=h.y-hs&&my<=h.y+hs)return n;}return null;}




document.querySelectorAll('.variant-thumb').forEach(th=>{th.addEventListener('dragstart',e=>{activeProductDrag={img:th.dataset.img,name:th.dataset.name,color:th.dataset.color,sku:th.dataset.sku,size:th.dataset.size||'',rsaWidth:th.dataset.rsaWidth||null,rsaHeight:th.dataset.rsaHeight||null,rsaCrop:th.dataset.rsaCrop?JSON.parse(th.dataset.rsaCrop):null,rsaSurface:th.dataset.rsaSurface||'floor'};activeFurnDrag=null;e.dataTransfer.effectAllowed='copy';});th.addEventListener('click',()=>{if(window.innerWidth>1024)return;addProduct({img:th.dataset.img,name:th.dataset.name,color:th.dataset.color,sku:th.dataset.sku,size:th.dataset.size||'',rsaWidth:th.dataset.rsaWidth||null,rsaHeight:th.dataset.rsaHeight||null,rsaCrop:th.dataset.rsaCrop?JSON.parse(th.dataset.rsaCrop):null,rsaSurface:th.dataset.rsaSurface||'floor'},CW/2,CH/2);closeMobileSidebar();});});
canvas.addEventListener('dragover',e=>{e.preventDefault();e.dataTransfer.dropEffect='copy';canvas.classList.add('drag-over');});
canvas.addEventListener('dragleave',()=>canvas.classList.remove('drag-over'));
canvas.addEventListener('drop',e=>{e.preventDefault();canvas.classList.remove('drag-over');const r=canvas.getBoundingClientRect(),dx=(e.clientX-r.left)*(CW/r.width),dy=(e.clientY-r.top)*(CH/r.height);if(activeFurnDrag){addFurniture(activeFurnDrag.furnDef,dx,dy);activeFurnDrag=null;}else if(activeProductDrag){addProduct(activeProductDrag,dx,dy);activeProductDrag=null;}});

// ── UNIFIED POINTER EVENTS (mouse + touch + stylus) ──
// preventDefault on touchstart/touchmove stops mobile browsers from scrolling
// the page instead of firing pointermove during a drag on the canvas.
canvas.addEventListener('touchstart', e=>e.preventDefault(), {passive:false});
canvas.addEventListener('touchmove',  e=>e.preventDefault(), {passive:false});

let activePointers={};
canvas.addEventListener('pointerdown',e=>{
  if(e.pointerType==='mouse'&&e.button!==0)return;
  activePointers[e.pointerId]=e;
  // Two-pointer = pinch — handled by canvasWrap, ignore here
  if(Object.keys(activePointers).length>1)return;
  e.preventDefault();
  canvas.setPointerCapture(e.pointerId);
  const{mx,my}=canvasXY(e);
  if(selectedId){
    const sel=placedItems.find(i=>i.id===selectedId);
    if(sel&&isOnDel(mx,my,sel)){deleteItem(sel.id);return;}
    const touchHS=e.pointerType==='mouse'?HS:Math.min(Math.round(20/canvasZoom),Math.floor(Math.min(sel.w,sel.h)*0.25));
    const h=getHandleAt(mx,my,sel,Math.max(HS,touchHS));
    if(h){isResizing=true;resizeHandle=h;resStartX=mx;resStartY=my;resStartW=sel.w;resStartH=sel.h;resItemX=sel.x;resItemY=sel.y;return;}
  }
  const pad=e.pointerType==='mouse'?0:Math.round(10/canvasZoom);
  const hit=getItemAt(mx,my,pad);
  if(hit){selectedId=hit.id;isDragging=true;dragOX=mx-hit.x;dragOY=my-hit.y;const idx=placedItems.findIndex(i=>i.id===hit.id);if(idx>-1){const item=placedItems.splice(idx,1)[0];placedItems.push(item);}syncSliders(hit);redraw();updateLayers();if(hit.isFillGroup)buildFillGrid(hit);}
  else{selectedId=null;fillGuides=[];renderFillGrid();clearSnap();redraw();updateLayers();}
},{passive:false});

canvas.addEventListener('pointermove',e=>{
  activePointers[e.pointerId]=e;
  if(Object.keys(activePointers).length>1)return; // pinch in progress
  if(!isDragging&&!isResizing){
    // cursor update (mouse only)
    if(e.pointerType==='mouse'){const{mx,my}=canvasXY(e);if(selectedId){const sel=placedItems.find(i=>i.id===selectedId);if(sel){if(isOnDel(mx,my,sel)){canvas.style.cursor='pointer';return;}const h=getHandleAt(mx,my,sel);if(h){canvas.style.cursor=(h==='se'||h==='nw')?'nwse-resize':'nesw-resize';return;}}}canvas.style.cursor=getItemAt(mx,my)?'move':'default';}
    return;
  }
  e.preventDefault();
  const{mx,my}=canvasXY(e);
  if(isResizing&&selectedId){
    const item=placedItems.find(i=>i.id===selectedId);if(!item)return;
    const dx=mx-resStartX,dy=my-resStartY,mn=20;
    if(resizeHandle==='se'){item.w=Math.max(mn,resStartW+dx);item.h=Math.max(mn,resStartH+dy);}
    else if(resizeHandle==='sw'){const nw=Math.max(mn,resStartW-dx);item.x=resItemX+(resStartW-nw);item.w=nw;item.h=Math.max(mn,resStartH+dy);}
    else if(resizeHandle==='ne'){item.w=Math.max(mn,resStartW+dx);const nh=Math.max(mn,resStartH-dy);item.y=resItemY+(resStartH-nh);item.h=nh;}
    else if(resizeHandle==='nw'){const nw=Math.max(mn,resStartW-dx),nh=Math.max(mn,resStartH-dy);item.x=resItemX+(resStartW-nw);item.y=resItemY+(resStartH-nh);item.w=nw;item.h=nh;}
    redraw();return;
  }
  if(isDragging&&selectedId){
    const item=placedItems.find(i=>i.id===selectedId);if(!item)return;
    const snap=smartSnap(item,mx-dragOX,my-dragOY);item.x=snap.x;item.y=snap.y;renderSnap(snap.lines);redraw();
  }
},{passive:false});

canvas.addEventListener('pointerup',e=>{
  delete activePointers[e.pointerId];
  if(isResizing&&selectedId){const item=placedItems.find(i=>i.id===selectedId);if(item&&item._bW&&item._bH){item.scaleVal=Math.round(item.w/item._bW*100);syncSliders(item);}}
  isDragging=false;isResizing=false;resizeHandle=null;clearSnap();
});
canvas.addEventListener('pointercancel',e=>{
  delete activePointers[e.pointerId];
  isDragging=false;isResizing=false;resizeHandle=null;clearSnap();
});

document.addEventListener('keydown',e=>{if(document.activeElement.tagName==='INPUT'||document.activeElement.tagName==='SELECT')return;if(e.key==='Delete'||e.key==='Backspace'){e.preventDefault();deleteSelected();return;}if(e.key==='Escape'){selectedId=null;fillGuides=[];renderFillGrid();clearSnap();redraw();updateLayers();return;}if((e.ctrlKey||e.metaKey)&&e.key==='c'){e.preventDefault();copySelected();return;}if((e.ctrlKey||e.metaKey)&&e.key==='v'){e.preventDefault();pasteItem();return;}if(!e.ctrlKey&&!e.metaKey&&!e.altKey){if(e.key==='h'){flipSelected('x');return;}if(e.key==='v'){flipSelected('y');return;}if(e.key==='r'){rotateSelected(e.shiftKey?-90:90);return;}}if(selectedId&&['ArrowUp','ArrowDown','ArrowLeft','ArrowRight'].includes(e.key)){e.preventDefault();const item=placedItems.find(i=>i.id===selectedId);if(!item)return;const step=e.shiftKey?10:1;if(e.key==='ArrowUp')item.y-=step;if(e.key==='ArrowDown')item.y+=step;if(e.key==='ArrowLeft')item.x-=step;if(e.key==='ArrowRight')item.x+=step;redraw();}});

function filterSidebar(){const s=document.getElementById('sidebarSearch').value.toLowerCase();const t=document.getElementById('sidebarTypeFilter').value.toLowerCase();document.querySelectorAll('.prod-group').forEach(g=>{g.style.display=(!s||(g.dataset.product||'').includes(s))&&(!t||(g.dataset.type||'')===t)?'':'none';});}
document.getElementById('sidebarSearch').addEventListener('input',filterSidebar);
document.getElementById('sidebarTypeFilter').addEventListener('change',filterSidebar);
document.getElementById('fillGuides').addEventListener('change',()=>{const fi=placedItems.find(i=>i.isFillGroup);if(fi)buildFillGrid(fi);else{fillGuides=[];renderFillGrid();}});
new ResizeObserver(()=>{const fi=placedItems.find(i=>i.id===selectedId&&i.isFillGroup);if(fi)buildFillGrid(fi);else renderFillGrid();}).observe(canvas);

// ── SURFACE ATTENTION: remove highlight once user interacts ──
function clearSurfaceAttention(){document.querySelectorAll('.surf-tab.needs-attention').forEach(t=>t.classList.remove('needs-attention'));}
document.querySelectorAll('.surf-tab').forEach(t=>t.addEventListener('click',clearSurfaceAttention,{once:true}));
document.querySelectorAll('.variant-thumb').forEach(t=>t.addEventListener('click',clearSurfaceAttention,{once:true}));
canvas.addEventListener('drop',function onFirstDrop(){clearSurfaceAttention();canvas.removeEventListener('drop',onFirstDrop);},{once:true});

// ── ROTATE / FULLSCREEN OVERLAY ──
function checkOrientation(){
  const overlay=document.getElementById('rotateOverlay');
  if(!overlay)return;
  const isMobile=window.innerWidth<=900||/Mobi|Android/i.test(navigator.userAgent);
  const isPortrait=window.innerHeight>window.innerWidth;
  if(isMobile&&isPortrait&&!overlay._dismissed){
    overlay.classList.add('show');
    clearTimeout(overlay._timer);
    overlay._timer=setTimeout(()=>{overlay.classList.remove('show');overlay._dismissed=true;},3000);
  }else{overlay.classList.remove('show');}
}
function requestFullscreenAndDismiss(){
  const overlay=document.getElementById('rotateOverlay');
  if(overlay){overlay.classList.remove('show');overlay._dismissed=true;}
  const el=document.documentElement;
  if(el.requestFullscreen)el.requestFullscreen().catch(()=>{});
  else if(el.webkitRequestFullscreen)el.webkitRequestFullscreen();
}
window.addEventListener('orientationchange',()=>setTimeout(()=>{checkOrientation();autoFitCanvas();},300));
window.addEventListener('resize',checkOrientation);
setTimeout(checkOrientation,400);

// ── ZOOM & FIT SYSTEM ──
let canvasZoom=1;
const canvasContainer=document.getElementById('canvasContainer');
const canvasWrap=document.getElementById('canvasWrap');

function applyZoom(z,animate){
  canvasZoom=Math.min(3,Math.max(0.15,z));
  canvasContainer.style.transition=animate?'transform .2s ease':'none';
  canvasContainer.style.transform=`scale(${canvasZoom})`;
  const pct=Math.round(canvasZoom*100)+'%';
  const zl=document.getElementById('zoomLevel');if(zl)zl.textContent=pct;
  const zlm=document.getElementById('zoomLevelMob');if(zlm)zlm.textContent=pct;
}
function zoomIn(){applyZoom(canvasZoom*1.2,true);}
function zoomOut(){applyZoom(canvasZoom/1.2,true);}
function zoomFit(){
  const wrap=canvasWrap.getBoundingClientRect();
  const pw=wrap.width-20,ph=wrap.height-20;
  const fit=Math.min(pw/CW,ph/CH,1);
  applyZoom(fit,true);
}

// Auto-fit on load and resize — retry so mobile layout has time to settle
function autoFitCanvas(){
  const wrap=canvasWrap.getBoundingClientRect();
  if(wrap.width<10)return;
  const pw=wrap.width-20,ph=wrap.height-20;
  const fit=Math.min(pw/CW,ph/CH,1);
  applyZoom(fit,false);
}
window.addEventListener('resize',()=>{autoFitCanvas();renderFillGrid();});
requestAnimationFrame(()=>{autoFitCanvas();setTimeout(autoFitCanvas,150);setTimeout(autoFitCanvas,500);});

// Mouse wheel zoom (desktop)
canvasWrap.addEventListener('wheel',e=>{
  e.preventDefault();
  const delta=e.deltaY>0?0.9:1.1;
  applyZoom(canvasZoom*delta,false);
},{passive:false});

// Pinch-to-zoom (touch) — only intercept 2-finger; single-finger reaches canvas touchstart above
let pinchStartDist=0,pinchStartZoom=1;
canvasWrap.addEventListener('touchstart',e=>{
  if(e.touches.length===2){
    e.preventDefault();
    pinchStartDist=Math.hypot(
      e.touches[0].clientX-e.touches[1].clientX,
      e.touches[0].clientY-e.touches[1].clientY
    );
    pinchStartZoom=canvasZoom;
  }
},{passive:false});
canvasWrap.addEventListener('touchmove',e=>{
  if(e.touches.length===2){
    e.preventDefault();
    const dist=Math.hypot(
      e.touches[0].clientX-e.touches[1].clientX,
      e.touches[0].clientY-e.touches[1].clientY
    );
    applyZoom(pinchStartZoom*(dist/pinchStartDist),false);
  }
},{passive:false});

buildFurnList('all');
redraw();
</script>
</body>
</html>