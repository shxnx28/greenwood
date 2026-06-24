<?php
// ── Announcement Popup (queried early before HTML output) ─────────────────────
require_once 'admin/db.php';
$_ann_now = date('Y-m-d H:i:s');
$_ann_sql = "SELECT * FROM announcements
             WHERE is_active = 1
               AND (schedule_start IS NULL OR schedule_start <= '$_ann_now')
               AND (schedule_end   IS NULL OR schedule_end   >= '$_ann_now')
             ORDER BY created_at DESC";
$_ann_result = $conn->query($_ann_sql);
$_ann_rows = [];
if ($_ann_result && $_ann_result->num_rows > 0) {
    while ($r = $_ann_result->fetch_assoc()) $_ann_rows[] = $r;
}
$_ann = !empty($_ann_rows) ? $_ann_rows[0] : null;

// Hero counters
$_branch_count = 0;
$_upcoming_count = 0;
$_r = $conn->query("SELECT COUNT(*) as c FROM warehouse_location WHERE is_active = 1");
if ($_r) $_branch_count = (int)$_r->fetch_assoc()['c'];
$_r = $conn->query("SELECT COUNT(*) as c FROM upcoming_branch WHERE is_active = 1");
if ($_r) $_upcoming_count = (int)$_r->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Resource Hints for performance -->
    <!-- Inter is self-hosted (same-origin), so no Google Fonts preconnects are needed -->
    <!-- Preload critical above-fold assets -->
    <link rel="preload" href="/assets/images/nobg.webp" as="image" fetchpriority="high">
    <!-- Hero (LCP) image preloaded per-viewport: phones get a right-sized 39KB copy — the -->
    <!-- full 1980x1200/190KB file is overkill on mobile and sits under a near-opaque dark   -->
    <!-- overlay there — while desktop keeps the full-res original. Saves ~150KB on mobile LCP. -->
    <link rel="preload" href="/assets/images/livingroom-m.webp" as="image" media="(max-width: 991px)" fetchpriority="high">
    <link rel="preload" href="/assets/images/livingroom.webp" as="image" media="(min-width: 992px)" fetchpriority="high">
    <!-- AOS CSS loaded async to avoid render blocking -->
    <link rel="preload" href="/css/aos.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="/css/aos.css"></noscript>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wall, Floor & Ceiling Materials | Greenwood Philippines</title>

    <!-- Primary SEO Meta Tags -->
    <meta name="description" content="Greenwood Philippines is the #1 supplier of modern wall, floor, ceiling, and fence solutions for Filipino homes and contractors. Browse our premium quality materials today.">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Greenwood Philippines">
    <link rel="canonical" href="https://greenwoodphilippines.com/">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://greenwoodphilippines.com/">
    <meta property="og:title" content="Greenwood Philippines | #1 Supplier of Wall, Floor & Ceiling Solutions">
    <meta property="og:description" content="Greenwood Philippines is the #1 supplier of modern wall, floor, ceiling, and fence solutions for Filipino homes and contractors. Premium quality materials at the best value.">
    <meta property="og:image" content="https://greenwoodphilippines.com/assets/images/nobg.webp">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Greenwood Philippines – Wall, Floor & Ceiling Solutions">
    <meta property="og:site_name" content="Greenwood Philippines">
    <meta property="og:locale" content="en_PH">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Greenwood Philippines | #1 Supplier of Wall, Floor & Ceiling Solutions">
    <meta name="twitter:description" content="Premium wall, floor, ceiling, and fence solutions for Filipino homes and contractors. Shop Greenwood Philippines today.">
    <meta name="twitter:image" content="https://greenwoodphilippines.com/assets/images/nobg.webp">
    <meta name="twitter:image:alt" content="Greenwood Philippines – Wall, Floor & Ceiling Solutions">

    <!-- Schema.org Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "Greenwood Philippines",
      "url": "https://greenwoodphilippines.com",
      "logo": "https://greenwoodphilippines.com/assets/images/gw.png",
      "description": "The #1 supplier of modern wall, floor, ceiling, and fence solutions for Filipino homes and contractors.",
      "address": {
        "@type": "PostalAddress",
        "addressCountry": "PH"
      },
      "sameAs": [
        "https://www.facebook.com/greenwoodphilippines",
        "https://www.instagram.com/greenwoodphilippines"
      ]
    }
    </script>

    <!-- Inter self-hosted as a same-origin variable font (replaces the googleapis + gstatic -->
    <!-- requests). Preloaded so the hero heading paints in the real typeface quickly.        -->
    <link rel="preload" href="/assets/fonts/inter-variable.woff2" as="font" type="font/woff2" crossorigin>
    <style>
    @font-face{
        font-family:'Inter';
        font-style:normal;
        font-weight:100 900;
        font-display:swap;
        src:url('/assets/fonts/inter-variable.woff2') format('woff2');
    }
    </style>
    <!-- Critical CSS — Bootstrap 5.3 grid/reboot/component stubs for above-fold classes  -->
    <!-- inlined so the hero renders identically on first paint (no FOUC / layout shift)  -->
    <style>
/* ── BOOTSTRAP 5.3 REBOOT (above-fold subset) ── */
*,*::before,*::after{box-sizing:border-box}
html{font-size:16px;-webkit-text-size-adjust:100%;scroll-behavior:smooth;overflow-x:hidden}
body{margin:0;font-family:'Inter','Segoe UI',Tahoma,Geneva,Verdana,sans-serif;font-size:1rem;font-weight:400;line-height:1.5;color:#212529;overflow-x:hidden;-webkit-tap-highlight-color:transparent}
h1,h2,h3,h4,h5,h6{margin-top:0;margin-bottom:.5rem;font-weight:500;line-height:1.2}
p{margin-top:0;margin-bottom:1rem}
a{color:#0d6efd;text-decoration:underline}
img,svg{vertical-align:middle}
img{max-width:100%}
/* ── BOOTSTRAP 5.3 GRID (above-fold subset) ── */
.container{width:100%;padding-right:.75rem;padding-left:.75rem;margin-right:auto;margin-left:auto}
@media(min-width:576px){.container{max-width:540px}}
@media(min-width:768px){.container{max-width:720px}}
@media(min-width:992px){.container{max-width:960px}}
@media(min-width:1200px){.container{max-width:1140px}}
@media(min-width:1400px){.container{max-width:1320px}}
.row{--bs-gutter-x:1.5rem;--bs-gutter-y:0;display:flex;flex-wrap:wrap;margin-top:calc(-1*var(--bs-gutter-y));margin-right:calc(-.5*var(--bs-gutter-x));margin-left:calc(-.5*var(--bs-gutter-x))}
.row>*{flex-shrink:0;width:100%;max-width:100%;padding-right:calc(var(--bs-gutter-x)*.5);padding-left:calc(var(--bs-gutter-x)*.5);margin-top:var(--bs-gutter-y)}
.col-lg-6{flex:0 0 auto;width:100%}
@media(min-width:992px){.col-lg-6{width:50%}}
.align-items-center{align-items:center!important}
/* ── BOOTSTRAP 5.3 DISPLAY / TYPOGRAPHY ── */
.display-1{font-size:calc(1.625rem + 4.5vw);font-weight:300;line-height:1.2}
.display-4{font-size:calc(1.475rem + 2.7vw);font-weight:300;line-height:1.2}
@media(min-width:1200px){.display-1{font-size:5rem}.display-4{font-size:3.5rem}}
.fw-bold{font-weight:700!important}
.lead{font-size:1.25rem;font-weight:400}
.h5{font-size:1.25rem}
/* ── BOOTSTRAP 5.3 SPACING ── */
.mt-4{margin-top:1.5rem!important}
.mb-5{margin-bottom:3rem!important}
.ms-auto{margin-left:auto!important}
.text-center{text-align:center!important}
.text-muted{color:#6c757d!important}
/* ── BOOTSTRAP 5.3 NAVBAR (above-fold subset) ── */
.fixed-top{position:fixed;top:0;right:0;left:0;z-index:1030}
.shadow-sm{box-shadow:0 .125rem .25rem rgba(0,0,0,.075)!important}
.bg-white{background-color:#fff!important}
.navbar{--bs-navbar-padding-y:.5rem;position:relative;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;padding-top:var(--bs-navbar-padding-y);padding-bottom:var(--bs-navbar-padding-y)}
.navbar>.container{display:flex;flex-wrap:inherit;align-items:center;justify-content:space-between}
.navbar-brand{padding-top:.3125rem;padding-bottom:.3125rem;margin-right:1rem;font-size:1.25rem;white-space:nowrap;text-decoration:none}
.navbar-nav{display:flex;flex-direction:column;padding-left:0;margin-bottom:0;list-style:none}
.navbar-nav .nav-link{padding-right:0;padding-left:0}
.nav-link{display:block;padding:.5rem 1rem;text-decoration:none;transition:color .15s ease-in-out}
.nav-item{list-style:none}
.navbar-toggler{padding:.25rem .75rem;font-size:1.25rem;line-height:1;background-color:transparent;border:1px solid transparent;border-radius:.25rem;cursor:pointer}
.navbar-toggler-icon{display:inline-block;width:1.5em;height:1.5em;vertical-align:middle;background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2833,37,41,0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");background-repeat:no-repeat;background-position:center;background-size:100%}
.collapse:not(.show){display:none}
@media(min-width:1200px){
  .navbar-expand-xl{flex-wrap:nowrap;justify-content:flex-start}
  .navbar-expand-xl .navbar-nav{flex-direction:row}
  .navbar-expand-xl .navbar-nav .nav-link{padding-right:.5rem;padding-left:.5rem}
  .navbar-expand-xl .navbar-collapse{display:flex!important;flex-basis:auto}
  .navbar-expand-xl .navbar-toggler{display:none}
}
/* ── BOOTSTRAP 5.3 BUTTON (above-fold subset) ── */
.btn{display:inline-block;font-weight:400;line-height:1.5;color:#212529;text-align:center;text-decoration:none;vertical-align:middle;cursor:pointer;-webkit-user-select:none;user-select:none;background-color:transparent;border:1px solid transparent;padding:.375rem .75rem;font-size:1rem;border-radius:.375rem;transition:color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out}
.btn-lg{padding:.5rem 1rem;font-size:1.25rem;border-radius:.5rem}
.btn-primary{color:#fff;background-color:#0d6efd;border-color:#0d6efd}
/* ── SITE-SPECIFIC ── */
html,body{max-width:100%}
main{display:block;min-height:100vh}
.hero-text>img{width:100px;height:100px;display:block;aspect-ratio:1/1}
@media(max-width:576px){.hero-logo{display:none!important}.hero-watermark-logo{display:none!important}}
/* Navbar: transparent on hero, white when scrolled */
.navbar{transition:background .35s ease,box-shadow .35s ease,padding .35s ease;padding:1rem 0;background:transparent!important;box-shadow:none!important}
.navbar.scrolled{box-shadow:0 2px 20px rgba(0,0,0,.10)!important;background:rgba(255,255,255,.97)!important;backdrop-filter:blur(8px);padding:.6rem 0}
.navbar.navbar-transparent .navbar-brand{opacity:0;pointer-events:none;transition:opacity .35s ease}
@media(min-width:1200px){.navbar.navbar-transparent .navbar-brand{opacity:0!important;pointer-events:none!important}}
.navbar.scrolled .navbar-brand{opacity:1;pointer-events:auto}
.navbar-brand .brand-logo{display:flex;align-items:center;gap:10px}
.navbar-brand .brand-logo img{width:30px;height:30px}
.navbar-brand .brand-logo .brand-text{display:flex;flex-direction:column;line-height:1.1}
.navbar-brand .brand-logo strong{font-size:1.2rem;color:#303823;font-weight:700}
.navbar-brand .brand-logo span{font-size:.7rem;color:#5a5a5a;font-weight:400;letter-spacing:1px}
.navbar-nav .nav-link{color:#303823;font-weight:600;letter-spacing:.5px;margin:0 1rem;position:relative;transition:color .3s ease}
@media(min-width:1200px){
    .navbar.navbar-transparent .navbar-nav .nav-link{color:#fff!important}
    .navbar.navbar-transparent .navbar-brand .brand-logo strong,
    .navbar.navbar-transparent .navbar-brand .brand-logo span{color:#fff!important}
}
/* Hero: light background, sample.png via .hero-bg-image (matches style.css) */
.hero-section{position:relative;width:100%;min-height:100vh;display:flex;align-items:center;padding:100px 0 60px;background-color:#f5f2ee;overflow:hidden}
.hero-bg-image{position:absolute;inset:0;background-image:url('/assets/images/livingroom.webp');background-size:cover;background-position:center right;background-repeat:no-repeat;z-index:1}
.hero-overlay{position:absolute;inset:0;background:linear-gradient(to right,rgba(255,255,255,.99) 0%,rgba(255,255,255,.88) 30%,rgba(255,255,255,.38) 52%,rgba(255,255,255,.00) 70%);z-index:2}
.hero-content{position:relative;z-index:3;width:100%}
.hero-text{color:#303823}
.brand-tag{font-size:1rem;color:#303823;font-weight:800;letter-spacing:3px;margin-bottom:.75rem;text-transform:uppercase;display:block}
.hero-text h1,.hero-heading{font-size:5.5rem;font-weight:900;line-height:1.0;color:#303823;margin-bottom:1.2rem;letter-spacing:-1px;white-space:normal}
@media(min-width:1300px){.hero-text h1,.hero-heading{white-space:nowrap}}
.hero-text .highlight,.hero-heading .highlight{color:#303823}
.hero-tagline{font-size:1.2rem;color:#444;margin-bottom:.25rem;line-height:1.65}
.hero-cta-btn{display:inline-block;background-color:#303823;color:#fff;font-size:.82rem;font-weight:800;letter-spacing:2px;text-transform:uppercase;padding:.85rem 2.2rem;border:2px solid #303823;border-radius:4px;text-decoration:none;transition:background .25s,color .25s,border-color .25s}
.cta-button{background:#648E37;color:#fff;padding:1rem 2.5rem;border-radius:6px;font-weight:700;text-transform:uppercase;letter-spacing:1px;transition:all .3s ease;border:2px solid #648E37;box-shadow:0 4px 20px rgba(100,142,55,.45);display:inline-block;text-decoration:none}
.hero-branch-num{font-size:2rem;font-weight:900;color:#303823!important;line-height:1;letter-spacing:-.5px}
.hero-branch-label{font-size:.72rem;font-weight:400;letter-spacing:.3px;color:#303823!important;opacity:.7;margin-top:5px}
.hero-branch-divider{width:1px;height:40px;background:rgba(48,56,35,.3)!important;flex-shrink:0;align-self:center}
@media(min-width:992px){.hero-content .container{padding-left:0 !important}}
@media(max-width:991px){
    /* Mobile hero uses the right-sized LCP image (mirrors the <head> preload + style.css) */
    .hero-bg-image{background-image:url('/assets/images/livingroom-m.webp')}
    .hero-content .container { max-width: 100% !important; padding-left: 20px !important; padding-right: 20px !important; }
    .hero-content .row > * { padding-left: 0 !important; padding-right: 0 !important; }
    .navbar > .container { padding-left: 56px !important; padding-right: 20px !important; justify-content: flex-start !important; }
    .navbar > .container .navbar-toggler { margin-left: auto; }
}
/* Room tabs */
.hero-room-tabs{position:absolute;top:90px;bottom:0;left:0;width:48px;z-index:4;display:flex;flex-direction:column;justify-content:center;align-items:center;gap:12px;background:transparent}
.hero-room-tab{background:rgba(48,56,35,.45);border:none;border-radius:0 16px 16px 0;padding:16px 10px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;width:44px;min-height:120px;box-shadow:0 2px 8px rgba(0,0,0,.18);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);transition:background .25s}
.hero-room-tab .pill{writing-mode:vertical-rl;text-orientation:mixed;font-size:.85rem;letter-spacing:.5px;color:#fff;opacity:.85;font-weight:500;text-shadow:0 1px 3px rgba(0,0,0,.4);transition:opacity .25s,font-weight .25s}
.hero-room-tab.active{background:rgba(48,56,35,.92)}
.hero-room-tab.active .pill{opacity:1;font-weight:700;color:#fff;text-shadow:0 1px 3px rgba(0,0,0,.3)}
.hero-bg-image{transition:transform .6s cubic-bezier(.4,0,.2,1),opacity .35s ease}
@media(max-width:991px){.hero-room-tabs{width:36px;gap:8px}.hero-room-tab{width:32px;min-height:90px;padding:10px 6px}.hero-room-tab .pill{font-size:.68rem}}

.project-carousel-wrap .project-carousel {
    margin-top: -12px;
}
@media(max-width:576px){
    .navbar.navbar-transparent .navbar-brand{opacity:1!important;pointer-events:auto!important;}
    .navbar.navbar-transparent .navbar-toggler-icon{background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='%23ffffff' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2.5' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e")!important;}
    .hero-branch-num{color:#fff!important}
    .hero-branch-label{color:rgba(255,255,255,.5)!important;opacity:1!important}
    .hero-branch-divider{background:rgba(255,255,255,.2)!important}
}
@media(min-width:992px){.hero-content{padding-left:0}.hero-content .col-lg-6:first-child{margin-left:0;padding-left:56px;display:flex;flex-direction:column;align-items:flex-start;}}
    </style>
    <!-- Bootstrap CSS deferred (non-render-blocking) -->
    <!-- Bootstrap CSS: self-hosted (same-origin) + render-blocking. Serving it locally     -->
    <!-- removes the cross-origin jsdelivr DNS+TLS handshake from the critical path (a big   -->
    <!-- FCP win on slow mobile) while keeping it blocking so styles apply on first parse    -->
    <!-- (no post-load recalc thrash).                                                       -->
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="icon" type="image/png" href="/assets/images/gw.png">
    <!-- Bootstrap Icons: self-hosted subset of the 26 used glyphs (2.7KB woff2 + 1.4KB css) -->
    <!-- replacing the 128KB jsdelivr font + 80KB css. Same icons, same codepoints, no UI change. -->
    <link rel="preload" href="/css/bootstrap-icons.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="/css/bootstrap-icons.css"></noscript>
    <!-- Main stylesheet: preload + render-blocking (same as every other page on the site). -->
    <!-- Loading it async (preload + onload swap) made the browser first-paint the hero from -->
    <!-- the inline critical subset above, which omits the mobile hero rules (flex-end       -->
    <!-- alignment, zero padding, 2.4rem heading, dark overlay, horizontal room tabs). When  -->
    <!-- style.css then applied, the entire above-the-fold hero relaid-out — driving CLS to  -->
    <!-- ~0.9 and delaying LCP until the swap landed (~8s on Slow 4G). style.css is           -->
    <!-- same-origin, gzipped and already preloaded here, so blocking adds negligible FCP.    -->
    <link rel="preload" href="/css/style.css?v=9" as="style">
    <link rel="stylesheet" href="/css/style.css?v=9">
    <?php include 'pixel.php'; ?>
</head>
<body>
    <?php include 'navbar.php'; ?>
<main id="main-content">

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="hero-bg-image" id="heroBgImage"></div>
        <div class="hero-overlay"></div>
        <div class="hero-room-tabs" id="heroRoomTabs">
            <button class="hero-room-tab active" data-bg="/assets/images/livingroom.webp" type="button">
                <span class="pill">Living room</span>
            </button>
            <button class="hero-room-tab" data-bg="/assets/images/kitchen.webp" type="button">
                <span class="pill">Kitchen</span>
            </button>
            <button class="hero-room-tab" data-bg="/assets/images/bedroom.webp" type="button">
                <span class="pill">Bedroom</span>
            </button>
            <button class="hero-room-tab" data-bg="/assets/images/outdoor.webp" type="button">
                <span class="pill">Exterior</span>
            </button>
        </div>
        <img src="/assets/images/nobg.webp" alt="" class="hero-watermark-logo" aria-hidden="true">
        <div class="hero-content">
            <div class="hero-mobile-card">
            <div class="container">
                <div class="row align-items-center min-vh-hero">
                    <div class="col-lg-6 col-md-8" data-aos="fade-right" data-aos-duration="1000">
                        <div class="hero-text">
                            <img src="/assets/images/nobg.webp" alt="Greenwood Logo" width="100" height="100" class="hero-logo" fetchpriority="high">
                            <p class="brand-tag">GREENWOOD PHILIPPINES</p>
                            <h1 class="hero-heading">The <span class="highlight">#1 Supplier</span></h1>
                            <p class="hero-tagline">of modern wall, floor, and ceiling solutions<br class="d-none d-md-inline"> for Filipino homes and contractors.</p>
                            <div class="d-flex align-items-center gap-3 mt-4 flex-wrap">
                                <a href="/catalog" class="btn hero-cta-btn">VIEW OUR PRODUCTS</a>
                                <a href="/faq" class="hero-faq-link">FAQs <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg></a>
                            </div>
                            <div class="hero-branch-counter">
                                <div class="hero-branch-stat">
                                    <span class="hero-branch-num"><?= $_branch_count ?></span>
                                    <span class="hero-branch-label">BRANCHES</span>
                                </div>
                                <div class="hero-branch-divider"></div>
                                <div class="hero-branch-stat">
                                    <span class="hero-branch-num"><?= $_upcoming_count ?></span>
                                    <span class="hero-branch-label">UPCOMING</span>
                                </div>
                                <div class="hero-branch-divider"></div>
                                <div class="hero-branch-stat">
                                    <span class="hero-branch-num">1M+</span>
                                    <span class="hero-branch-label">PRODUCTS SOLD</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
<section id="products">
    <div class="container">
        <div class="text-center mb-4" data-aos="fade-up">
            <h2 class="display-4 fw-bold">Our Product Solutions</h2>
            <p class="lead text-muted">Premium quality materials for modern Filipino homes</p>
        </div>
        <div class="product-grid">
            <a href="/catalog/wall" class="location-link" data-aos="fade-up" data-aos-delay="100">
                <div class="product-card" data-category="wall">
                    <div class="product-image wall-img">
                        <span class="product-badge">Wall Solutions</span>
                    </div>
                    <div class="product-content">
                        <h3 class="h5 fw-bold">Wall Solutions</h3>
                        <p>Modern wall panels, cladding, and finishing materials in various textures and colors. Perfect for residential and commercial applications.</p>
                        <span class="product-cta">View Products &rarr;</span>
                    </div>
                </div>
            </a>
            <a href="/catalog/floor" class="location-link" data-aos="fade-up" data-aos-delay="200">
                <div class="product-card" data-category="floor">
                    <div class="product-image floor-img">
                        <span class="product-badge">Floor Solutions</span>
                    </div>
                    <div class="product-content">
                        <h3 class="h5 fw-bold">Floor Solutions</h3>
                        <p>High-quality flooring options including wood finishes, laminates, and durable SPC materials for every space.</p>
                        <span class="product-cta">View Products &rarr;</span>
                    </div>
                </div>
            </a>
            <a href="/catalog/ceiling" class="location-link" data-aos="fade-up" data-aos-delay="300">
                <div class="product-card" data-category="ceiling">
                    <div class="product-image ceiling-img">
                        <span class="product-badge">Ceiling Solutions</span>
                    </div>
                    <div class="product-content">
                        <h3 class="h5 fw-bold">Ceiling Solutions</h3>
                        <p>Comprehensive ceiling systems that combine aesthetics with functionality. Easy to install and maintain for contractors.</p>
                        <span class="product-cta">View Products &rarr;</span>
                    </div>
                </div>
            </a>
            <a href="/catalog/fence" class="location-link" data-aos="fade-up" data-aos-delay="400">
                <div class="product-card" data-category="fence">
                    <div class="product-image fence-img">
                        <span class="product-badge">Fence Solutions</span>
                    </div>
                    <div class="product-content">
                        <h3 class="h5 fw-bold">Fence Solutions</h3>
                        <p>Durable and stylish fencing materials for residential and commercial properties. Designed for security and modern aesthetics.</p>
                        <span class="product-cta">View Products &rarr;</span>
                    </div>
                </div>
            </a>
            <a href="/catalog/adhesive" class="location-link" data-aos="fade-up" data-aos-delay="500">
                <div class="product-card" data-category="adhesive">
                    <div class="product-image adhesive-img">
                        <span class="product-badge">Adhesive Solutions</span>
                    </div>
                    <div class="product-content">
                        <h3 class="h5 fw-bold">Adhesive Solutions</h3>
                        <p>High-performance adhesives and bonding solutions for all surface types. Fast-setting formulas for professional and residential installs.</p>
                        <span class="product-cta">View Products &rarr;</span>
                    </div>
                </div>
            </a>
        </div>

        <!-- Room Simulator Button -->
        <div class="text-center mt-5" data-aos="fade-up" data-aos-delay="200">
            <a href="/room-simulator" class="btn project-view-all-btn">Try Our Room Simulator &rarr;</a>
        </div>

    </div>
</section>

    <!-- Projects Section -->
    <section id="projects" class="bg-light">
        <div class="container">
            <div class="text-center mb-2" data-aos="fade-up">
                <h2 class="display-4 fw-bold">Finished Projects</h2>
                <p class="lead text-muted">See how our products transform spaces</p>
            </div>

            <?php
            require_once 'admin/db.php';
            $projects = [];
            $sql = "SELECT pi.* 
                    FROM project_images pi
                    INNER JOIN (
                        SELECT album, MIN(id) as first_id
                        FROM project_images
                        WHERE album IS NOT NULL AND album != ''
                        GROUP BY album
                        ORDER BY MIN(id) ASC
                        LIMIT 10
                    ) AS first_images ON pi.id = first_images.first_id
                    ORDER BY pi.id ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $projects[] = $row;
                }
            }
            $slides = array_chunk($projects, 3);
            ?>

            <div class="project-carousel-wrap">
                <div class="d-flex justify-content-end me-3 mb-0">
                    <a href="/projects" class="project-view-all-link">View All Projects &rarr;</a>
                </div>
                <div id="projectsCarousel" class="carousel slide project-carousel" data-bs-ride="carousel" data-bs-wrap="false" data-aos="fade-up" data-aos-delay="100">
                    <div class="carousel-inner">
                        <?php if (count($projects) > 0): ?>
                            <?php foreach ($slides as $slideIndex => $slide): ?>
                            <div class="carousel-item <?php echo $slideIndex === 0 ? 'active' : ''; ?>">
                                <div class="row g-4 justify-content-center">
                                    <?php foreach ($slide as $project):
                                        $title = htmlspecialchars($project['title']);
                                        $productsUsed = htmlspecialchars($project['products_used'] ?? '');
                                        $imagePathRaw = $project['image_path'] ?? '/assets/images/nobg.webp';
                                        if (strpos($imagePathRaw, 'uploads/') === 0) {
                                            $imagePath = '/admin/' . htmlspecialchars($imagePathRaw);
                                        } elseif (strpos($imagePathRaw, 'projects/') === 0) {
                                            $imagePath = '/admin/uploads/' . htmlspecialchars($imagePathRaw);
                                        } else {
                                            $imagePath = htmlspecialchars($imagePathRaw);
                                        }
                                    ?>
                                    <div class="col-md-4 d-flex product-item project-item-anim">
                                        <a href="/view_album.php?album=<?php echo urlencode($project['album'] ?? ''); ?>" class="product-catalog-card-link w-100">
                                            <div class="product-catalog-card">
                                                <div class="product-hover-overlay">
                                                    <div class="hover-text">View Album</div>
                                                </div>
                                                <div class="product-image-wrapper">
                                                    <img src="<?php echo $imagePath; ?>" alt="<?php echo $title; ?>" loading="lazy" decoding="async" class="product-image" onerror="this.onerror=null;this.src='/assets/images/nobg.webp';">
                                                </div>
                                                <div class="product-info">
                                                    <div class="product-header-section">
                                                        <h5 class="product-title"><?php echo $title; ?></h5>
                                                    </div>
                                                    <?php if (!empty($productsUsed)): ?>
                                                    <p class="product-description"><?php echo $productsUsed; ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="carousel-item active">
                                <div class="row g-4 justify-content-center">
                                    <div class="col-12 text-center py-5">
                                        <p class="text-muted">No projects available. Check our <a href="/projects">projects page</a> for all listings.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div><!-- /.carousel-inner -->
                </div><!-- /#projectsCarousel -->

                <?php if (count($slides) > 1): ?>
                <div class="text-center mt-3">
                    <div class="carousel-nav-below">
                        <button class="carousel-control-prev project-carousel-btn" type="button" data-bs-target="#projectsCarousel" data-bs-slide="prev">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <span class="carousel-nav-counter">
                            <span class="nav-current">1</span> / <?= count($slides) ?>
                        </span>
                        <button class="carousel-control-next project-carousel-btn" type="button" data-bs-target="#projectsCarousel" data-bs-slide="next">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

            </div><!-- /.project-carousel-wrap -->
        </div>
    </section>

    <!-- Influencer Reactions Section -->
    <section id="influencers">
        <div class="container">
            <div class="text-center mb-2" data-aos="fade-up">
                <h2 class="display-4 fw-bold">Influencer Reviews</h2>
                <p class="lead text-muted">Honest reviews from influencers who experienced our products firsthand</p>
            </div>

            <?php
            $influencers = [];
            $resultInf = $conn->query("SELECT * FROM influencer_reactions ORDER BY created_at DESC");
            if ($resultInf && $resultInf->num_rows > 0) {
                while ($row = $resultInf->fetch_assoc()) $influencers[] = $row;
            }
            $inf_slides = array_chunk($influencers, 3);
            ?>

            <div class="project-carousel-wrap">
                <div class="d-flex justify-content-end me-3 mb-0">
                    <a href="/influencers" class="project-view-all-link">View All Influencers &rarr;</a>
                </div>
                <div id="influencerCarousel" class="carousel slide project-carousel" data-bs-ride="carousel" data-bs-wrap="false" data-aos="fade-up" data-aos-delay="100">

                    <div class="carousel-inner">
                        <?php if (count($influencers) > 0): ?>
                            <?php foreach ($inf_slides as $slideIndex => $slide): ?>
                            <div class="carousel-item <?php echo $slideIndex === 0 ? 'active' : ''; ?>">
                                <div class="row g-4 justify-content-center">
                                    <?php foreach ($slide as $inf):
                                        $infPhoto = !empty($inf['profile_photo'])
                                            ? 'admin/' . htmlspecialchars($inf['profile_photo'])
                                            : 'assets/images/nobg.webp';
                                        $infUrl  = !empty($inf['reaction_url']) ? htmlspecialchars($inf['reaction_url']) : '#';
                                        $platformIcons = [
                                            'Facebook'  => 'bi-facebook',
                                            'Instagram' => 'bi-instagram',
                                            'TikTok'    => 'bi-tiktok',
                                            'YouTube'   => 'bi-youtube',
                                            'Twitter'   => 'bi-twitter-x',
                                        ];
                                        $infIcon = $platformIcons[$inf['platform']] ?? 'bi-globe';
                                    ?>
                                    <div class="col-md-4 d-flex product-item project-item-anim">
                                        <a href="<?php echo $infUrl; ?>"
                                           target="<?php echo $infUrl !== '#' ? '_blank' : '_self'; ?>"
                                           rel="noopener noreferrer"
                                           class="product-catalog-card-link w-100">
                                            <div class="product-catalog-card">
                                                <div class="product-hover-overlay">
                                                    <div class="hover-text">
                                                        <i class="bi <?php echo $infIcon; ?> me-2"></i>View on <?php echo htmlspecialchars($inf['platform']); ?>
                                                    </div>
                                                </div>
                                                <div class="product-image-wrapper">
                                                    <img src="<?php echo $infPhoto; ?>"
                                                         alt="<?php echo htmlspecialchars($inf['name']); ?>"
                                                         class="product-image"
                                                         loading="lazy" decoding="async"
                                                         onerror="this.onerror=null;this.src='/assets/images/nobg.webp';">
                                                </div>
                                                <div class="product-info">
                                                    <h5 class="product-title"><?php echo htmlspecialchars($inf['name']); ?></h5>
                                                    <?php if (!empty($inf['description'])): ?>
                                                    <p class="product-description">"<?php echo htmlspecialchars($inf['description']); ?>"</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="carousel-item active">
                                <div class="row g-4 justify-content-center">
                                    <div class="col-12 text-center py-5">
                                        <p class="text-muted">No influencer reactions yet.</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (count($inf_slides) > 1): ?>
                    <div class="text-center mt-3">
                        <div class="carousel-nav-below">
                            <button class="carousel-control-prev project-carousel-btn" type="button" data-bs-target="#influencerCarousel" data-bs-slide="prev">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <span class="carousel-nav-counter">
                                <span class="nav-current">1</span> / <?= count($inf_slides) ?>
                            </span>
                            <button class="carousel-control-next project-carousel-btn" type="button" data-bs-target="#influencerCarousel" data-bs-slide="next">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                <span class="visually-hidden">Next</span>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                </div><!-- /#influencerCarousel -->
            </div><!-- /.project-carousel-wrap -->
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="bg-light">
        <div class="container">
            <div class="text-center mb-4" data-aos="fade-up">
                <h2 class="display-4 fw-bold">About Greenwood Philippines</h2>
                <p class="lead text-muted">Your trusted partner in building solutions</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-12" data-aos="fade-up">
                    <div class="about-card">
                        <h3>Who We Are</h3>
                        <p>Greenwood Philippines is a manufacturer and direct supplier of warehouse-priced home finishing materials, offering high-quality solutions for residential and commercial spaces.</p>
                        <p>We specialize in modern interior and exterior materials such as WPC panels, SPC floorings, PVC ceilings, UV boards, and more—designed to elevate spaces while remaining durable, low-maintenance, and cost-efficient.</p>
                        <p class="mb-0">With multiple branches nationwide, Greenwood makes premium home finishing materials accessible across the Philippines.</p>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="about-card">
                        <h3>Our Commitment</h3>
                        <p class="mb-0">We deliver materials that balance design, durability, and value—easy to install, low maintenance, and built to last.</p>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="about-card">
                        <h3>Our Vision</h3>
                        <p class="mb-0">To be a trusted and recognized brand shaping modern and functional spaces nationwide.</p>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-right">
                    <div class="about-card">
                        <h3>Our Mission</h3>
                        <p class="mb-0">To manufacture and supply high-quality, stylish, and durable finishing materials at warehouse prices with dependable service.</p>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="about-card">
                        <h3>What We Offer</h3>
                        <p class="mb-2">We provide a wide range of premium finishing materials including:</p>
                        <p class="mb-0 about-offer-tags">WPC Fluted · WPC Half Round · WPC Column · WPC Decking · WPC Siding · WPC Fence · PVC Ceiling · SPC Flooring · UV Boards · Bamboo Charcoal Veneers · PU Stone · Solid Planks · Acoustic Panels · Honeycomb Panels · Soft Stone</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Warehouse Locations Section -->
    <section id="locations" class="py-5">
        <div class="container">
            <div class="text-center mb-4" data-aos="fade-up">
                <h2 class="display-4 fw-bold">Contact & Warehouse Locations</h2>
                <p class="lead text-muted">Visit us at any of our branches nationwide</p>
            </div>

            <?php
            $locations = [];
            $result = $conn->query("SELECT * FROM warehouse_location WHERE is_active = 1 ORDER BY display_order ASC, location_id ASC");
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $row['contacts'] = [];
                    $locations[$row['location_id']] = $row;
                }
            }
            if (!empty($locations)) {
                $ids = implode(',', array_map('intval', array_keys($locations)));
                $cResult = $conn->query("SELECT * FROM branch_contacts WHERE location_id IN ($ids) AND is_active = 1 ORDER BY location_id ASC, is_primary DESC, display_order ASC");
                if ($cResult) {
                    while ($contact = $cResult->fetch_assoc()) {
                        $locations[$contact['location_id']]['contacts'][] = $contact;
                    }
                }
                $locations = array_values($locations);
            }
            ?>

            <div class="row g-4">
                <?php
                $delay = 0;
                foreach ($locations as $location):
                    $delay += 50;
                ?>
                <div class="col-lg-4 col-md-6 col-12 d-flex">
                    <div class="location-card w-100">
                        <!-- Header -->
                        <div class="location-card-header">
                            <div class="location-icon">
                                <img src="/assets/images/pin.webp" alt="Location" class="location-pin-icon" loading="lazy" width="35" height="35">
                            </div>
                            <p class="location-header-name"><?php echo htmlspecialchars($location['location_name']); ?></p>
                            <a href="<?php echo htmlspecialchars($location['facebook_url']); ?>"
                               target="_blank"
                               class="btn-location-fb"
                               onclick="event.stopPropagation();">
                                <span class="fb-icon-wrap">
                                    <svg viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                                </span>
                                <span>Facebook</span>
                            </a>
                        </div>
                        <!-- Body -->
                        <div class="location-body">
                            <div class="location-text-content">
                                <?php if (!empty($location['address_line1'])): ?>
                                <p class="mb-0"><?php echo htmlspecialchars($location['address_line1']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($location['address_line2'])): ?>
                                <p class="mb-0"><?php echo htmlspecialchars($location['address_line2']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($location['address_line3'])): ?>
                                <p class="mb-0"><?php echo htmlspecialchars($location['address_line3']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($location['special_note'])): ?>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($location['special_note']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Footer -->
                        <?php if (!empty($location['contacts'])): ?>
                        <div class="location-contact-button">
                            <button type="button"
                                    class="btn-location-contact"
                                    data-bs-toggle="modal"
                                    data-bs-target="#contactsModal<?php echo $location['location_id']; ?>">
                                <i class="bi bi-people-fill"></i>View Contacts (<?php echo count($location['contacts']); ?>)
                            </button>
                            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($location['location_name'] . ' ' . $location['address_line1']); ?>"
                               target="_blank"
                               class="btn-location-map"
                               title="Get Directions">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C8.686 2 6 4.686 6 8c0 5.25 6 13 6 13s6-7.75 6-13c0-3.314-2.686-6-6-6z"/><circle cx="12" cy="8" r="2"/></svg>
                                Directions
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Contact Modals -->
            <?php foreach ($locations as $location): ?>
            <?php if (!empty($location['contacts'])): ?>
            <div class="modal fade"
                 id="contactsModal<?php echo $location['location_id']; ?>"
                 tabindex="-1"
                 aria-hidden="true"
                 data-bs-scroll="true"
                 data-bs-backdrop="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header border-0 pb-0">
                            <div>
                                <h5 class="modal-title fw-bold"><?php echo htmlspecialchars($location['location_name']); ?></h5>
                                <p class="text-muted small mb-0">Branch Contacts</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php foreach ($location['contacts'] as $contact): ?>
                            <div class="contact-card mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold">
                                            <?php echo htmlspecialchars($contact['contact_name']); ?>
                                            <span class="text-dark ms-2" style="font-size: 0.95rem; font-weight: 700;">
                                                <?php echo htmlspecialchars($contact['contact_number']); ?>
                                            </span>
                                        </h6>
                                        <?php if (!empty($contact['contact_role'])): ?>
                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($contact['contact_role']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($contact['is_primary'] == 1): ?>
                                    <span class="badge bg-success flex-shrink-0">Primary</span>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-2">
                                    <div class="d-flex gap-2">
                                        <a href="tel:<?php echo htmlspecialchars($contact['contact_number']); ?>" class="btn btn-success btn-sm flex-fill">
                                            <i class="bi bi-telephone-fill me-1"></i>Call
                                        </a>
                                        <button type="button" class="btn btn-outline-success btn-sm flex-fill"
                                                onclick="copyToClipboard('<?php echo htmlspecialchars($contact['contact_number']); ?>', this)">
                                            <i class="bi bi-clipboard me-1"></i>Copy Number
                                        </button>
                                    </div>
                                </div>
                                <?php if (!empty($contact['contact_email'])): ?>
                                <div class="mb-0">
                                    <div class="d-flex gap-2">
                                        <a href="mailto:<?php echo htmlspecialchars($contact['contact_email']); ?>" class="btn btn-outline-secondary btn-sm flex-fill">
                                            <i class="bi bi-envelope-fill me-1"></i>Email
                                        </a>
                                        <button type="button" class="btn btn-outline-secondary btn-sm flex-fill"
                                                onclick="copyToClipboard('<?php echo htmlspecialchars($contact['contact_email']); ?>', this)">
                                            <i class="bi bi-clipboard me-1"></i>Copy Email
                                        </button>
                                    </div>
                                    <small class="text-muted d-block mt-1 text-center"><?php echo htmlspecialchars($contact['contact_email']); ?></small>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>

            <!-- Coming Soon -->
            <?php
            $sql = "SELECT * FROM upcoming_branch WHERE is_active = 1 ORDER BY display_order ASC, upcoming_id ASC";
            $result = $conn->query($sql);
            $upcoming_branches = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $upcoming_branches[] = $row;
                }
            }
            if (count($upcoming_branches) > 0):
            ?>
            <div class="coming-soon-section" data-aos="fade-up">
                <h3 class="fw-bold">COMING SOON:</h3>
                <div class="row g-2">
                    <?php foreach ($upcoming_branches as $upcoming): ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="coming-soon-card">
                            <div class="coming-icon">
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 15"/></svg>
                            </div>
                            <h5 class="fw-bold"><?php echo htmlspecialchars($upcoming['branch_name']); ?></h5>
                            <span class="coming-soon-badge">Soon</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Operating Hours -->
            <div class="operating-hours mt-5 visit-us-parallax">
                <div class="visit-us-bg-image"></div>
                <div class="row g-4 align-items-center" style="position:relative;z-index:1;">
                    <div class="col-lg-8">
                        <div class="hours-info">
                            <p class="mb-2"><strong>OPEN FROM MONDAY - SUNDAY</strong> (PULILAN BRANCH)</p>
                            <p class="mb-0"><strong>OPEN FROM MONDAY - SATURDAY</strong> (OTHER BRANCHES)</p>
                        </div>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <a href="https://www.facebook.com/greenwoodphilippines" class="btn btn-lg btn-light visit-btn">VISIT US!</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    </main><!-- /#main-content -->
    <?php include 'footer.php'; ?>

    <script src="/js/bootstrap.bundle.min.js" defer></script>
    <script src="/js/aos.js" defer></script>
    <script src="/js/script.js" defer></script>

    <?php if (!empty($_ann_rows)): ?>
    <?php
        $ann_delay = (int)($_ann_rows[0]['display_delay'] ?? 1000);
        $ann_items_json = json_encode(array_map(function($r) {
            return [
                'id'           => (int)$r['id'],
                'title'        => htmlspecialchars($r['title']),
                'message'      => htmlspecialchars($r['message']),
                'image_url'    => htmlspecialchars($r['image_url'] ?? ''),
                'button_label' => htmlspecialchars($r['button_label'] ?? ''),
                'button_url'   => htmlspecialchars($r['button_url'] ?? ''),
                'bg_color'     => htmlspecialchars($r['bg_color'] ?? '#ffffff'),
                'show_once'    => (int)$r['show_once'],
            ];
        }, $_ann_rows));
    ?>
    <!-- ── Announcement Popup ── -->
    <style>
    #gwAnnOverlay{position:fixed;inset:0;background:rgba(0,0,0,.58);z-index:99999;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .3s ease;pointer-events:none;}
    #gwAnnOverlay.gw-visible{opacity:1;pointer-events:all;}
    #gwAnnBox{background:#fff;border-radius:20px;max-width:560px;width:calc(100% - 2rem);max-height:92vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,.28);transform:translateY(28px) scale(.96);transition:transform .32s cubic-bezier(.34,1.56,.64,1),opacity .3s ease;overflow-x:hidden;position:relative;}
    #gwAnnOverlay.gw-visible #gwAnnBox{transform:translateY(0) scale(1);}
    #gwAnnClose{position:absolute;top:10px;right:12px;background:rgba(255,255,255,.85);border:none;border-radius:50%;width:34px;height:34px;font-size:20px;line-height:34px;text-align:center;cursor:pointer;color:#333;transition:background .2s;z-index:10;}
    #gwAnnClose:hover{background:rgba(255,255,255,1);}
    #gwAnnImgWrap{position:relative;width:100%;max-height:55vh;overflow:hidden;background:#1a1a1a;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
    #gwAnnImgWrap.no-img{display:none;}
    #gwAnnImgBg{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;filter:blur(10px) brightness(1);transform:scale(1);pointer-events:none;z-index:0;}
    #gwAnnImg{position:relative;z-index:1;width:100%;max-height:55vh;height:auto;object-fit:contain;display:block;}
    #gwAnnCounter{position:absolute;bottom:10px;right:12px;background:rgba(0,0,0,.45);color:#fff;font-size:0.72rem;font-weight:600;padding:3px 9px;border-radius:20px;}
    #gwAnnBody{padding:20px 24px 14px;}
    #gwAnnTitle{font-size:1.15rem;font-weight:800;color:#1a2e0f;margin:0 0 8px;line-height:1.3;}
    #gwAnnMsg{font-size:0.9rem;color:#4b5563;line-height:1.6;margin:0;}
    #gwAnnCtaWrap{padding:0 24px 14px;display:none;}
    #gwAnnBtn{display:block;background:#648E37;color:#fff;padding:11px 24px;border-radius:10px;font-weight:700;font-size:0.9rem;text-decoration:none;text-align:center;transition:opacity .2s;}
    #gwAnnBtn:hover{opacity:.85;color:#fff;}
    #gwAnnFooter{display:flex;align-items:center;justify-content:space-between;padding:10px 24px 20px;gap:8px;}
    .gwAnnNav{display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;border:2px solid #dee2e6;background:#fff;color:#444;cursor:pointer;transition:all .2s;flex-shrink:0;}
    .gwAnnNav:hover:not(:disabled){border-color:#648E37;color:#648E37;background:#eaf3da;}
    .gwAnnNav:disabled{opacity:.3;cursor:default;}
    #gwAnnDots{display:flex;gap:6px;align-items:center;justify-content:center;flex:1;flex-wrap:wrap;}
    .gwAnnDot{width:8px;height:8px;border-radius:50%;background:#ccc;border:none;padding:0;cursor:pointer;transition:background .2s,transform .2s;}
    .gwAnnDot.active{background:#648E37;transform:scale(1.4);}
    @media(max-width:576px){
        #gwAnnImgWrap{max-height:45vh;}
        #gwAnnImg{max-height:45vh;}
        #gwAnnBody{padding:16px 18px 10px;}
        #gwAnnCtaWrap{padding:0 18px 10px;}
        #gwAnnFooter{padding:8px 18px 16px;}
    }
    #gwAnnDismissBar{display:flex;align-items:center;justify-content:space-between;padding:10px 20px 14px;gap:12px;border-top:1px solid #e4e8df;background:#fafcf7;}
    #gwAnnDismissLabel{display:flex;align-items:center;gap:7px;cursor:pointer;font-size:12.5px;color:#6b7280;font-family:'Inter','Segoe UI',sans-serif;user-select:none;}
    #gwAnnDismissLabel input[type=checkbox]{width:15px;height:15px;accent-color:#648E37;cursor:pointer;flex-shrink:0;}
    #gwAnnDismissLabel:hover span{color:#303823;}
    #gwAnnCloseBtn{background:#648E37;color:#fff;border:none;border-radius:7px;padding:7px 18px;font-size:13px;font-weight:700;cursor:pointer;font-family:'Inter','Segoe UI',sans-serif;transition:background .2s;}
    #gwAnnCloseBtn:hover{background:#527230;}
    </style>
    <div id="gwAnnOverlay" role="dialog" aria-modal="true" aria-labelledby="gwAnnTitle">
        <div id="gwAnnBox">
            <button id="gwAnnClose" onclick="gwCloseAnn()" aria-label="Close announcement">&times;</button>
            <div id="gwAnnImgWrap">
                <img id="gwAnnImgBg" src="" alt="" aria-hidden="true">
                <img id="gwAnnImg" src="" alt="">
                <span id="gwAnnCounter"></span>
            </div>
            <div id="gwAnnBody">
                <h2 id="gwAnnTitle"></h2>
                <p id="gwAnnMsg"></p>
            </div>
            <div id="gwAnnCtaWrap">
                <a id="gwAnnBtn" href="#" target="_blank" rel="noopener"></a>
            </div>
            <div id="gwAnnFooter">
                <button class="gwAnnNav" id="gwAnnPrev" aria-label="Previous">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <div id="gwAnnDots"></div>
                <button class="gwAnnNav" id="gwAnnNext" aria-label="Next">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
            <div id="gwAnnDismissBar">
                <label id="gwAnnDismissLabel">
                    <input type="checkbox" id="gwAnnDontShow">
                    <span>Don't show again</span>
                </label>
                <button id="gwAnnCloseBtn" onclick="gwCloseAnn()">Close</button>
            </div>
        </div>
    </div>
    <script>
    (function(){
        var ITEMS = <?= $ann_items_json ?>;
        var DELAY = <?= $ann_delay ?>;
        var current = 0;

        function hasCookie(n){return document.cookie.split(';').some(function(c){return c.trim().indexOf(n+'=')===0;});}
        function setCookie(n,days){var d=new Date();d.setTime(d.getTime()+days*864e5);document.cookie=n+'=1;expires='+d.toUTCString()+';path=/';}

        // Filter out items already dismissed (show_once or don't-show-again cookie)
        ITEMS = ITEMS.filter(function(a){
            return !hasCookie('gw_ann_'+a.id);
        });
        if (!ITEMS.length) return;

        var overlay  = document.getElementById('gwAnnOverlay');
        var imgWrap  = document.getElementById('gwAnnImgWrap');
        var imgEl    = document.getElementById('gwAnnImg');
        var imgBg    = document.getElementById('gwAnnImgBg');
        var counter  = document.getElementById('gwAnnCounter');
        var titleEl  = document.getElementById('gwAnnTitle');
        var msgEl    = document.getElementById('gwAnnMsg');
        var ctaWrap  = document.getElementById('gwAnnCtaWrap');
        var ctaBtn   = document.getElementById('gwAnnBtn');
        var dotsEl   = document.getElementById('gwAnnDots');
        var prevBtn  = document.getElementById('gwAnnPrev');
        var nextBtn  = document.getElementById('gwAnnNext');

        function render(idx) {
            var a = ITEMS[idx];
            // image
            if (a.image_url) {
                imgEl.src = a.image_url; imgEl.alt = a.title;
                imgBg.src = a.image_url;
                imgWrap.classList.remove('no-img');
            } else {
                imgWrap.classList.add('no-img');
            }
            // counter
            counter.textContent = ITEMS.length > 1 ? (idx+1)+' / '+ITEMS.length : '';
            // text
            titleEl.textContent = a.title;
            msgEl.textContent   = a.message;
            // cta
            if (a.button_label && a.button_url) {
                ctaBtn.textContent = a.button_label;
                ctaBtn.href = a.button_url;
                ctaWrap.style.display = '';
            } else {
                ctaWrap.style.display = 'none';
            }
            // dots
            var dots = dotsEl.querySelectorAll('.gwAnnDot');
            dots.forEach(function(d,i){ d.classList.toggle('active', i===idx); });
            // nav
            prevBtn.disabled = idx === 0;
            nextBtn.disabled = idx === ITEMS.length - 1;
            // mark seen
            if (a.show_once) setCookie('gw_ann_'+a.id, 30);
        }

        // Build dots
        if (ITEMS.length > 1) {
            ITEMS.forEach(function(_, i){
                var b = document.createElement('button');
                b.className = 'gwAnnDot' + (i===0?' active':'');
                b.setAttribute('aria-label','Announcement '+(i+1));
                b.addEventListener('click', function(){ current=i; render(current); });
                dotsEl.appendChild(b);
            });
        }

        prevBtn.addEventListener('click', function(){ if(current>0){ current--; render(current); } });
        nextBtn.addEventListener('click', function(){ if(current<ITEMS.length-1){ current++; render(current); } });

        document.addEventListener('keydown', function(e){
            if (!overlay.classList.contains('gw-visible')) return;
            if (e.key==='ArrowLeft') prevBtn.click();
            if (e.key==='ArrowRight') nextBtn.click();
            if (e.key==='Escape') gwCloseAnn();
        });

        overlay.addEventListener('click', function(e){ if(e.target===this) gwCloseAnn(); });

        window.gwCloseAnn = function(){
            overlay.classList.remove('gw-visible');
            var dontShow = document.getElementById('gwAnnDontShow');
            if (dontShow && dontShow.checked) {
                ITEMS.forEach(function(a){ setCookie('gw_ann_'+a.id, 365); });
            }
        };

        render(0);
        setTimeout(function(){ overlay.classList.add('gw-visible'); }, DELAY);
    })();
    </script>
    <?php endif; ?>

    <script>
    // ── NAV COUNTER: keeps .nav-current and total in sync with the carousel ──
    document.addEventListener('DOMContentLoaded', function () {
        function bindNavCounter(carouselId) {
            var carousel = document.getElementById(carouselId);
            if (!carousel) return;

            function getNavBelow() {
                var nb = carousel.querySelector('.carousel-nav-below');
                if (nb) return nb;
                var section = carousel.closest('section');
                if (section) nb = section.querySelector('.carousel-nav-below');
                return nb;
            }

            function getLiveTotal() {
                return carousel.querySelectorAll('.carousel-inner .carousel-item').length;
            }

            function updateDisplay(index) {
                var total = getLiveTotal();
                var nb = getNavBelow();
                if (!nb) return;
                var currentSpan = nb.querySelector('.nav-current');
                if (currentSpan) currentSpan.textContent = index + 1;
                var counter = nb.querySelector('.carousel-nav-counter');
                if (counter) {
                    counter.childNodes.forEach(function(node) {
                        if (node.nodeType === Node.TEXT_NODE && node.textContent.indexOf('/') !== -1) {
                            node.textContent = ' / ' + total;
                        }
                    });
                }
            }

            carousel.addEventListener('slide.bs.carousel', function(e) {
                updateDisplay(e.to);
            });

            carousel.addEventListener('carousel:rebuilt', function() {
                updateDisplay(0);
            });
        }

        bindNavCounter('projectsCarousel');
        bindNavCounter('influencerCarousel');
    });


    </script>

    <script>
    // ── HERO ROOM TABS: slide the hero background between rooms ──
    document.addEventListener('DOMContentLoaded', function () {
        var tabs = document.querySelectorAll('#heroRoomTabs .hero-room-tab');
        var bg = document.getElementById('heroBgImage');
        if (!tabs.length || !bg) return;

        var SLIDE_MS = 600;  // keep in sync with the .hero-bg-image transform transition
        var current = 0;     // Living room is the active tab on load
        var busy = false;

        tabs.forEach(function (tab, idx) {
            tab.addEventListener('click', function () {
                if (busy || tab.classList.contains('active')) return;
                busy = true;

                tabs.forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');

                // Later tab slides in from the right, earlier tab slides in from the left
                var dir = idx > current ? 1 : -1;

                var newBg = tab.getAttribute('data-bg');
                // On phones, swap in the right-sized copy (e.g. kitchen-m.webp)
                if (window.matchMedia('(max-width: 991px)').matches) {
                    newBg = newBg.replace(/\.webp$/, '-m.webp');
                }

                // Incoming layer: same styling as the base, parked just off-screen
                var incoming = document.createElement('div');
                incoming.className = bg.className + ' hero-bg-incoming';
                incoming.style.transition = 'none';
                incoming.style.backgroundImage = "url('" + newBg + "')";
                incoming.style.transform = 'translateX(' + (dir * 100) + '%)';
                bg.parentNode.insertBefore(incoming, bg.nextSibling);

                // Lock the start position (no animation), then enable the transition
                void incoming.offsetWidth;
                incoming.style.transition = '';

                requestAnimationFrame(function () {
                    incoming.style.transform = 'translateX(0)';
                    bg.style.transform = 'translateX(' + (dir * -100) + '%)';
                });

                setTimeout(function () {
                    // Hand the new image to the base layer with no visible jump
                    bg.style.transition = 'none';
                    bg.style.backgroundImage = "url('" + newBg + "')";
                    bg.style.transform = 'translateX(0)';
                    void bg.offsetWidth;          // flush, then restore the CSS transition
                    bg.style.transition = '';
                    if (incoming.parentNode) incoming.parentNode.removeChild(incoming);
                    current = idx;
                    busy = false;
                }, SLIDE_MS);
            });
        });
    });
    </script>
</body>
</html>