<?php require_once 'admin/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs | Greenwood Philippines</title>
    <meta name="description" content="Frequently asked questions about Greenwood Philippines products, installation, delivery, and more.">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Greenwood Philippines">
    <link rel="canonical" href="https://greenwoodphilippines.com/faq">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://greenwoodphilippines.com/faq">
    <meta property="og:title" content="FAQs - Greenwood Philippines">
    <meta property="og:description" content="Frequently asked questions about Greenwood Philippines products, installation, delivery, payment, and warranty.">
    <meta property="og:image" content="https://greenwoodphilippines.com/assets/images/nobg.webp">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="Greenwood Philippines">
    <meta property="og:locale" content="en_PH">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="FAQs - Greenwood Philippines">
    <meta name="twitter:description" content="Frequently asked questions about Greenwood Philippines products, installation, delivery, payment, and warranty.">
    <meta name="twitter:image" content="https://greenwoodphilippines.com/assets/images/nobg.webp">

    <!-- FAQPage Structured Data (generated from the visible Q&A) -->
    <?php
    // FAQ data sourced from the visible accordion content below — keeps schema in sync with the page.
    $faqSchema = [
        ['q' => 'What types of products does Greenwood Philippines offer?', 'a' => 'Greenwood Philippines offers four main product categories: Wall Solutions — decorative wall panels and cladding for interior and exterior use Floor Solutions — durable flooring materials suitable for residential and commercial spaces Ceiling Solutions — modern ceiling panels and systems Fence Solutions — weather-resistant fencing materials You can browse our full catalog at catalog page .'],
        ['q' => 'Are your products suitable for outdoor use?', 'a' => 'Yes! Many of our products are designed to withstand the Philippine climate — including heat, humidity, and rain. Our fence solutions and select wall and ceiling panels are rated for outdoor use. Each product listing in our catalog specifies whether it is suitable for indoor, outdoor, or both.'],
        ['q' => 'Do you offer product samples before purchasing?', 'a' => 'Yes, we encourage customers to visit any of our branches to view physical samples before making a purchase. You can see the actual texture, color, and finish of our products in person. Contact your nearest branch to confirm sample availability.'],
        ['q' => 'Can I customize colors or sizes for bulk orders?', 'a' => 'For bulk and contractor orders, we may accommodate special requests for colors and sizes depending on the product line and quantity. Please reach out to us through our contact page or visit your nearest branch to discuss your project requirements.'],
        ['q' => 'Do you provide installation services?', 'a' => 'Yes, Greenwood Philippines can connect you with third-party installation contacts for most of our product lines. Please note that installation is carried out by independent installers — Greenwood Philippines does not perform the installation directly. Installation fees vary depending on the product type, quantity, and location. Please contact your nearest branch for a referral and quote.'],
        ['q' => 'Can I install the products myself (DIY)?', 'a' => 'Absolutely. Our products are designed with straightforward installation in mind. When you purchase from us, we provide installation guides and technical support. However, we still recommend professional installation for best results, especially for large areas or complex layouts.'],
        ['q' => 'What tools are needed to install your wall panels?', 'a' => 'Basic installation typically requires a measuring tape, level, saw (for cutting panels to size), drill, and appropriate adhesive or screws depending on the surface. Specific tools may vary per product — our team will advise you at the point of purchase.'],
        ['q' => 'How do I place an order?', 'a' => 'You can place an order in three ways: In-store — visit any of our branches during operating hours Facebook — message us on our official Facebook page Phone/Chat — contact the nearest branch directly through our locations page'],
        ['q' => 'Do you offer delivery services?', 'a' => 'Yes, we offer delivery services through Lalamove . Delivery availability depends on your location and the branch nearest to you. Please contact your preferred branch to confirm delivery coverage and rates for your area. Please note: The shipping fee is shouldered by the buyer.'],
        ['q' => 'What payment methods do you accept?', 'a' => 'We accept cash and bank transfer. Please confirm with your branch for the most up-to-date payment options.'],
        ['q' => 'What are your branch operating hours?', 'a' => 'Our Pulilan Branch is open Monday – Sunday . All other branches operate Monday – Saturday . For specific opening and closing times, please contact the branch directly through our locations page .'],
        ['q' => 'What should I do if I receive a damaged product?', 'a' => 'If you receive a damaged or defective product, please contact us immediately — ideally within 24–48 hours of receipt. Take photos of the damage and reach out to the branch where you made your purchase. We will assess the situation and arrange for a replacement or resolution as quickly as possible. Please note: Damage may sometimes occur during delivery via Lalamove. In such cases, the buyer must coordinate directly with Lalamove first before we can process any resolution.'],
        ['q' => 'Can I return or exchange a product?', 'a' => 'Returns and exchanges are handled on a case-by-case basis. Products must be unused, in their original condition, and accompanied by proof of purchase. Custom or special orders may not be returnable. Please contact the branch where you purchased the product to initiate the process.'],
        ['q' => 'How do I properly maintain and clean your products?', 'a' => 'Most of our products require minimal maintenance. General care tips: Wipe with a soft, damp cloth for routine cleaning Avoid harsh chemicals or abrasive scrubbers For outdoor products, periodic cleaning to remove dirt and debris is recommended Inspect and re-tighten any fasteners annually for structural pieces Product-specific maintenance guides are available from our staff at purchase.'],
    ];
    ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "FAQPage",
      "mainEntity": [
<?php foreach ($faqSchema as $_i => $_f): ?>
        {
          "@type": "Question",
          "name": <?= json_encode($_f['q'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
          "acceptedAnswer": { "@type": "Answer", "text": <?= json_encode($_f['a'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?> }
        }<?= $_i < count($faqSchema) - 1 ? ',' : '' ?>
<?php endforeach; ?>
      ]
    }
    </script>

    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"></noscript>
    <link rel="preload" href="https://unpkg.com/aos@2.3.1/dist/aos.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css"></noscript>
    <link rel="icon" type="image/png" href="/assets/images/gw.png">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">

    <style>
        /* ── Force white navbar text on transparent state for dark hero ── */
        .navbar.navbar-transparent .navbar-brand .brand-logo strong,
        .navbar.navbar-transparent .navbar-brand .brand-logo span,
        .navbar.navbar-transparent .navbar-nav .nav-link {
            color: #fff !important;
        }
        .navbar.navbar-transparent .navbar-brand {
            opacity: 0 !important;
            pointer-events: none;
        }
        .navbar.navbar-transparent .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='%23ffffff' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2.5' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
        }
        .navbar.navbar-transparent .navbar-toggler {
            border-color: rgba(255,255,255,0.6) !important;
        }
        @media (max-width: 1199px) {
            .navbar.navbar-transparent .navbar-collapse.show,
            .navbar.navbar-transparent .navbar-collapse.collapsing {
                background: rgba(30, 38, 20, 0.97) !important;
            }
            .navbar.navbar-transparent .navbar-collapse .nav-link {
                color: #fff !important;
            }
        }

        /* ── FAQ PAGE STYLES ── */
        :root {
            --green-primary:  #648E37;
            --green-hover:    #527230;
            --green-dark:     #303823;
            --green-light:    #eef4e6;
            --green-xlight:   #f6faf0;
            --green-accent:   #8fba52;
            --border-light:   #e4e8df;
        }

        /* Hero banner */
        .faq-hero {
            background: #1a1f14;
            padding: 120px 0 70px;
            position: relative;
            overflow: hidden;
        }
        .faq-hero-bg {
            position: absolute;
            inset: -20px;
            background: url('/assets/images/faq.webp') center/cover no-repeat;
            filter: blur(14px);
            opacity: 0.55;
            transform: scale(1.05);
            z-index: 0;
        }
        .faq-hero-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
            background: linear-gradient(
                160deg,
                rgba(15, 20, 10, 0.75) 0%,
                rgba(30, 38, 20, 0.60) 50%,
                rgba(15, 20, 10, 0.75) 100%
            );
        }
        .faq-hero::before {
            display: none;
        }
        .faq-hero::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--green-primary), var(--green-accent), var(--green-primary));
            z-index: 3;
        }
        .faq-hero-label {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--green-accent);
            margin-bottom: 1rem;
            display: block;
        }
        .faq-hero h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 900;
            color: #fff;
            line-height: 1.1;
            margin-bottom: 1rem;
        }
        .faq-hero h1 span {
            color: var(--green-accent);
        }
        .faq-hero p {
            color: rgba(255,255,255,0.65);
            font-size: 1.05rem;
            max-width: 500px;
            line-height: 1.7;
        }
        /* Decorative leaf shape */
        .faq-hero-deco {
            position: absolute;
            right: -60px;
            top: 50%;
            transform: translateY(-50%);
            width: 380px;
            height: 380px;
            border-radius: 60% 40% 70% 30% / 50% 60% 40% 50%;
            border: 2px solid rgba(100,142,55,0.2);
            opacity: 0.5;
        }
        .faq-hero-deco2 {
            position: absolute;
            right: 40px;
            top: 50%;
            transform: translateY(-50%);
            width: 260px;
            height: 260px;
            border-radius: 40% 60% 30% 70% / 60% 40% 60% 40%;
            border: 2px solid rgba(143,186,82,0.15);
            opacity: 0.4;
        }

        /* Search bar */
        .faq-search-wrap {
            margin-top: -28px;
            position: relative;
            z-index: 10;
        }
        .faq-search-box {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.12);
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 2px solid var(--border-light);
            transition: border-color 0.2s ease;
        }
        .faq-search-box:focus-within {
            border-color: var(--green-primary);
        }
        .faq-search-box svg {
            flex-shrink: 0;
            color: var(--green-primary);
        }
        .faq-search-input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            color: #333;
            background: transparent;
        }
        .faq-search-input::placeholder { color: #aaa; }

        /* Language Toggle */
        .lang-toggle-wrap {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 575px) {
            .lang-toggle-wrap {
                justify-content: center;
                margin-bottom: 1.25rem;
            }
        }
        .lang-toggle {
            display: inline-flex;
            background: #fff;
            border: 2px solid var(--border-light);
            border-radius: 50px;
            padding: 4px;
            gap: 2px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }
        .lang-btn {
            padding: 6px 20px;
            border-radius: 50px;
            border: none;
            background: transparent;
            font-size: 0.82rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            color: #888;
            cursor: pointer;
            transition: all 0.2s ease;
            letter-spacing: 0.5px;
        }
        .lang-btn.active {
            background: var(--green-primary);
            color: #fff;
            box-shadow: 0 2px 8px rgba(100,142,55,0.35);
        }
        .lang-btn:not(.active):hover {
            color: var(--green-primary);
        }

        /* Category tabs */
        .faq-categories {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 2.5rem;
        }
        @media (max-width: 575px) {
            .faq-categories .faq-cat-btn:first-child {
                width: 100%;
                justify-content: center;
            }
            .faq-categories .faq-cat-btn:not(:first-child) {
                flex: 1 1 calc(50% - 5px);
                justify-content: center;
            }
        }
        .faq-cat-btn {
            padding: 8px 20px;
            border-radius: 50px;
            border: 2px solid var(--border-light);
            background: #fff;
            color: #555;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .faq-cat-btn:hover {
            border-color: var(--green-primary);
            color: var(--green-primary);
        }
        .faq-cat-btn.active {
            background: var(--green-primary);
            border-color: var(--green-primary);
            color: #fff;
            box-shadow: 0 4px 14px rgba(100,142,55,0.3);
        }
        .faq-cat-btn .cat-count {
            background: rgba(255,255,255,0.3);
            border-radius: 20px;
            padding: 1px 7px;
            font-size: 0.72rem;
            font-weight: 700;
        }
        .faq-cat-btn:not(.active) .cat-count {
            background: var(--green-xlight);
            color: var(--green-primary);
        }

        /* Section heading */
        .faq-section-title {
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--green-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .faq-section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-light);
        }

        /* FAQ accordion items */
        .faq-item {
            background: #fff;
            border-radius: 14px;
            border: 1.5px solid var(--border-light);
            margin-bottom: 10px;
            overflow: hidden;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .faq-item:hover {
            border-color: #c8dba8;
        }
        .faq-item.open {
            border-color: var(--green-primary);
            box-shadow: 0 4px 20px rgba(100,142,55,0.1);
        }
        .faq-item.hidden {
            display: none;
        }

        .faq-question {
            width: 100%;
            background: none;
            border: none;
            padding: 1.2rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            text-align: left;
            font-family: 'Inter', sans-serif;
            transition: background 0.15s ease;
        }
        .faq-question:hover { background: var(--green-xlight); }
        .faq-item.open .faq-question { background: var(--green-xlight); }

        .faq-q-icon {
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            background: var(--green-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .faq-item.open .faq-q-icon {
            background: var(--green-primary);
        }
        .faq-q-icon svg {
            width: 15px;
            height: 15px;
            stroke: var(--green-primary);
        }
        .faq-item.open .faq-q-icon svg {
            stroke: #fff;
        }

        .faq-q-text {
            flex: 1;
            font-size: 0.97rem;
            font-weight: 700;
            color: var(--green-dark);
            line-height: 1.4;
        }

        .faq-toggle {
            flex-shrink: 0;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
            background: #fff;
        }
        .faq-item.open .faq-toggle {
            background: var(--green-primary);
            border-color: var(--green-primary);
            transform: rotate(45deg);
        }
        .faq-toggle svg {
            width: 14px;
            height: 14px;
            stroke: #888;
            transition: stroke 0.2s ease;
        }
        .faq-item.open .faq-toggle svg { stroke: #fff; }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                        padding 0.2s ease;
        }
        .faq-answer-inner {
            padding: 0 1.5rem 1.5rem 4.5rem;
            font-size: 0.92rem;
            color: #555;
            line-height: 1.8;
        }
        .faq-answer-inner a {
            color: var(--green-primary);
            font-weight: 600;
            text-decoration: none;
        }
        .faq-answer-inner a:hover { text-decoration: underline; }
        .faq-answer-inner ul {
            margin: 0.75rem 0;
            padding-left: 1.2rem;
        }
        .faq-answer-inner ul li { margin-bottom: 0.4rem; }

        /* No results */
        .faq-no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: #aaa;
            display: none;
        }
        .faq-no-results svg { margin-bottom: 1rem; opacity: 0.4; }
        .faq-no-results p { font-size: 1rem; }

        /* Still have questions CTA - matches index visit-us style */
        .faq-cta {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            border: 2px solid var(--border-light);
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-top: 3rem;
            overflow: hidden;
            position: relative;
            text-align: center;
        }
        .faq-cta h3 {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--green-dark);
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        .faq-cta p {
            color: #555;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }
        .faq-cta-btns {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }
        .faq-cta-btn-primary {
            background: linear-gradient(135deg, var(--green-primary), var(--green-hover));
            color: #fff;
            padding: 13px 32px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            border: 2px solid transparent;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 16px rgba(100,142,55,0.35);
        }
        .faq-cta-btn-primary:hover {
            background: transparent;
            border-color: var(--green-primary);
            color: var(--green-primary);
        }
        .faq-cta-btn-secondary {
            background: transparent;
            color: var(--green-dark);
            padding: 13px 32px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            border: 2px solid var(--border-light);
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            z-index: 1;
        }
        .faq-cta-btn-secondary:hover {
            border-color: var(--green-primary);
            color: var(--green-primary);
        }
        @media (max-width: 767px) {
            .faq-cta { padding: 2rem 1.5rem; }
            .faq-cta h3 { font-size: 1.3rem; }
        }
        .faq-cta h3 {
            font-size: 1.6rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 0.5rem;
            position: relative;
        }
        .faq-cta p {
            color: rgba(255,255,255,0.70);
            margin-bottom: 1.5rem;
            position: relative;
        }
        .faq-cta-btns {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            position: relative;
        }
        .faq-cta-btn-primary {
            background: linear-gradient(135deg, var(--green-primary), var(--green-hover));
            color: #fff;
            padding: 13px 32px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            border: 2px solid transparent;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 16px rgba(100,142,55,0.35);
        }
        .faq-cta-btn-primary:hover {
            background: transparent;
            border-color: var(--green-primary);
            color: var(--green-primary);
            box-shadow: none;
        }
        .faq-cta-btn-secondary {
            background: transparent;
            color: var(--green-primary);
            padding: 13px 32px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            border: 2px solid var(--green-primary);
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .faq-cta-btn-secondary:hover {
            background: linear-gradient(135deg, var(--green-primary), var(--green-hover));
            color: #fff;
            border-color: transparent;
            box-shadow: 0 4px 16px rgba(100,142,55,0.35);
        }
        @media (max-width: 767px) {
            .faq-cta h3 { font-size: 1.3rem; }
        }

        /* Result count badge */
        .faq-result-count {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--green-primary);
            background: var(--green-xlight);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            padding: 3px 12px;
            margin-left: auto;
            display: inline-block;
        }

        /* Breadcrumb */
        .faq-breadcrumb {
            padding: 12px 0;
            font-size: 0.82rem;
            color: rgba(255,255,255,0.5);
        }
        .faq-breadcrumb a { color: rgba(255,255,255,0.6); text-decoration: none; }
        .faq-breadcrumb a:hover { color: var(--green-accent); }
        .faq-breadcrumb span { color: rgba(255,255,255,0.35); margin: 0 6px; }

        @media (max-width: 767px) {
            .faq-hero { padding: 100px 0 60px; }
            .faq-search-box { padding: 1rem 1.25rem; gap: 0.75rem; }
            .faq-answer-inner { padding-left: 1.5rem; }
            .faq-search-wrap { margin-top: -24px; }
            section[style*="padding: 3rem"] { padding: 2rem 0 4rem !important; }
        }
    </style>
</head>
<body>

<?php include 'pixel.php'; ?>
<?php include 'navbar.php'; ?>

<main>

    <!-- Hero -->
    <section class="faq-hero">
        <div class="faq-hero-bg"></div>
        <div class="faq-hero-overlay"></div>
        <div class="faq-hero-deco"></div>
        <div class="faq-hero-deco2"></div>
        <div class="container" style="position:relative; z-index:2;">
            <nav class="faq-breadcrumb">
                <a href="/index.php">Home</a>
                <span>›</span>
                <span style="color:rgba(255,255,255,0.8)">FAQs</span>
            </nav>
            <span class="faq-hero-label">Help Center</span>
            <h1>Frequently Asked <span>Questions</span></h1>
            <p>Everything you need to know about our products, installation, delivery, and more.</p>
        </div>
    </section>

    <!-- Search -->
    <div class="faq-search-wrap">
        <div class="container">
            <div class="faq-search-box">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input class="faq-search-input" type="text" id="faqSearch" placeholder="Search questions... e.g. delivery, installation, warranty" autocomplete="off">
                <span class="faq-result-count" id="resultCount" style="display:none;"></span>
            </div>
        </div>
    </div>

    <!-- FAQ Content -->
    <section style="padding: 3rem 0 5rem; background: var(--green-xlight);">
        <div class="container">

            <!-- Language Toggle -->
            <div class="lang-toggle-wrap">
                <div class="lang-toggle" id="langToggle">
                    <button class="lang-btn active" data-lang="en">ENGLISH</button>
                    <button class="lang-btn" data-lang="tl">TAGALOG</button>
                </div>
            </div>

            <!-- Category tabs -->
            <div class="faq-categories" id="faqCategories">
                <button class="faq-cat-btn active" data-cat="all">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                    All Questions <span class="cat-count" id="countAll">0</span>
                </button>
                <button class="faq-cat-btn" data-cat="products">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="3" width="7" height="7"/><rect x="15" y="3" width="7" height="7"/><rect x="2" y="14" width="7" height="7"/><rect x="15" y="14" width="7" height="7"/></svg>
                    Products <span class="cat-count" id="countProducts">0</span>
                </button>
                <button class="faq-cat-btn" data-cat="installation">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    Installation <span class="cat-count" id="countInstallation">0</span>
                </button>
                <button class="faq-cat-btn" data-cat="ordering">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    Ordering & Delivery <span class="cat-count" id="countOrdering">0</span>
                </button>
                <button class="faq-cat-btn" data-cat="warranty">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Warranty & Support <span class="cat-count" id="countWarranty">0</span>
                </button>
            </div>

            <!-- FAQ Groups -->
            <div id="faqList">

                <!-- PRODUCTS -->
                <div class="faq-group mb-4" data-group="products">
                    <div class="faq-section-title">Products</div>

                    <div class="faq-item" data-cat="products" data-aos="fade-up">
                        <button class="faq-question" aria-expanded="false">
                            <div class="faq-q-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="7" height="7"/><rect x="15" y="3" width="7" height="7"/><rect x="2" y="14" width="7" height="7"/><rect x="15" y="14" width="7" height="7"/></svg>
                            </div>
                            <span class="faq-q-text"
                                data-en="What types of products does Greenwood Philippines offer?"
                                data-tl="Anong mga produkto ang inaalok ng Greenwood Philippines?">What types of products does Greenwood Philippines offer?</span>
                            <div class="faq-toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner"
                                data-en="Greenwood Philippines offers four main product categories:<ul><li><strong>Wall Solutions</strong> — decorative wall panels and cladding for interior and exterior use</li><li><strong>Floor Solutions</strong> — durable flooring materials suitable for residential and commercial spaces</li><li><strong>Ceiling Solutions</strong> — modern ceiling panels and systems</li><li><strong>Fence Solutions</strong> — weather-resistant fencing materials</li></ul>You can browse our full catalog at <a href='/catalog.php'>catalog page</a>."
                                data-tl="Nag-aalok ang Greenwood Philippines ng apat na pangunahing kategorya ng produkto:<ul><li><strong>Wall Solutions</strong> — dekoratibong wall panels at cladding para sa loob at labas ng bahay</li><li><strong>Floor Solutions</strong> — matibay na flooring materials para sa residential at commercial na espasyo</li><li><strong>Ceiling Solutions</strong> — modernong ceiling panels at sistema</li><li><strong>Fence Solutions</strong> — matibay na fencing materials na hindi nasisira ng panahon</li></ul>Maaari mong tingnan ang aming buong katalogo sa <a href='/catalog.php'>catalog page</a>.">
                                Greenwood Philippines offers four main product categories:
                                <ul>
                                    <li><strong>Wall Solutions</strong> — decorative wall panels and cladding for interior and exterior use</li>
                                    <li><strong>Floor Solutions</strong> — durable flooring materials suitable for residential and commercial spaces</li>
                                    <li><strong>Ceiling Solutions</strong> — modern ceiling panels and systems</li>
                                    <li><strong>Fence Solutions</strong> — weather-resistant fencing materials</li>
                                </ul>
                                You can browse our full catalog at <a href="/catalog">catalog page</a>.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item" data-cat="products" data-aos="fade-up" data-aos-delay="50">
                        <button class="faq-question" aria-expanded="false">
                            <div class="faq-q-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="7" height="7"/><rect x="15" y="3" width="7" height="7"/><rect x="2" y="14" width="7" height="7"/><rect x="15" y="14" width="7" height="7"/></svg>
                            </div>
                            <span class="faq-q-text"
                                data-en="Are your products suitable for outdoor use?"
                                data-tl="Angkop ba ang inyong mga produkto para sa labas ng bahay?">Are your products suitable for outdoor use?</span>
                            <div class="faq-toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner"
                                data-en="Yes! Many of our products are designed to withstand the Philippine climate — including heat, humidity, and rain. Our fence solutions and select wall and ceiling panels are rated for outdoor use. Each product listing in our <a href='/catalog.php'>catalog</a> specifies whether it is suitable for indoor, outdoor, or both."
                                data-tl="Oo! Marami sa aming mga produkto ay dinisenyo para tiisin ang klima ng Pilipinas — kasama na ang init, halumigmig, at ulan. Ang aming fence solutions at ilang wall at ceiling panels ay angkop para sa labas ng bahay. Bawat produkto sa aming <a href='/catalog.php'>katalogo</a> ay nagtatakda kung para sa loob, labas, o pareho.">
                                Yes! Many of our products are designed to withstand the Philippine climate — including heat, humidity, and rain. Our fence solutions and select wall and ceiling panels are rated for outdoor use. Each product listing in our <a href="/catalog">catalog</a> specifies whether it is suitable for indoor, outdoor, or both.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item" data-cat="products" data-aos="fade-up" data-aos-delay="100">
                        <button class="faq-question" aria-expanded="false">
                            <div class="faq-q-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="7" height="7"/><rect x="15" y="3" width="7" height="7"/><rect x="2" y="14" width="7" height="7"/><rect x="15" y="14" width="7" height="7"/></svg>
                            </div>
                            <span class="faq-q-text"
                                data-en="Do you offer product samples before purchasing?"
                                data-tl="Nag-aalok ba kayo ng mga sample ng produkto bago bumili?">Do you offer product samples before purchasing?</span>
                            <div class="faq-toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner"
                                data-en="Yes, we encourage customers to visit any of our branches to view physical samples before making a purchase. You can see the actual texture, color, and finish of our products in person. Contact your nearest branch to confirm sample availability."
                                data-tl="Oo, hinihikayat namin ang mga customer na bisitahin ang alinman sa aming mga branch para makita ang pisikal na mga sample bago bumili. Makikita mo ang tunay na texture, kulay, at tapusin ng aming mga produkto nang personal. Makipag-ugnayan sa pinakamalapit na branch para kumpirmahin ang availability ng mga sample.">
                                Yes, we encourage customers to visit any of our branches to view physical samples before making a purchase. You can see the actual texture, color, and finish of our products in person. Contact your nearest branch to confirm sample availability.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item" data-cat="products" data-aos="fade-up" data-aos-delay="150">
                        <button class="faq-question" aria-expanded="false">
                            <div class="faq-q-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="7" height="7"/><rect x="15" y="3" width="7" height="7"/><rect x="2" y="14" width="7" height="7"/><rect x="15" y="14" width="7" height="7"/></svg>
                            </div>
                            <span class="faq-q-text"
                                data-en="Can I customize colors or sizes for bulk orders?"
                                data-tl="Maaari bang i-customize ang kulay o sukat para sa bulk orders?">Can I customize colors or sizes for bulk orders?</span>
                            <div class="faq-toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner"
                                data-en="For bulk and contractor orders, we may accommodate special requests for colors and sizes depending on the product line and quantity. Please reach out to us through our <a href='/index.php#locations'>contact page</a> or visit your nearest branch to discuss your project requirements."
                                data-tl="Para sa bulk at contractor orders, maaari kaming tumanggap ng mga espesyal na kahilingan para sa kulay at sukat depende sa linya ng produkto at dami. Makipag-ugnayan sa amin sa pamamagitan ng aming <a href='/index.php#locations'>contact page</a> o bisitahin ang pinakamalapit na branch para talakayin ang inyong mga pangangailangan sa proyekto.">
                                For bulk and contractor orders, we may accommodate special requests for colors and sizes depending on the product line and quantity. Please reach out to us through our <a href="/index.php#locations">contact page</a> or visit your nearest branch to discuss your project requirements.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- INSTALLATION -->
                <div class="faq-group mb-4" data-group="installation">
                    <div class="faq-section-title">Installation</div>

                    <div class="faq-item" data-cat="installation" data-aos="fade-up">
                        <button class="faq-question" aria-expanded="false">
                            <div class="faq-q-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                            </div>
                            <span class="faq-q-text"
                                data-en="Do you provide installation services?"
                                data-tl="Nagbibigay ba kayo ng serbisyo sa pag-install?">Do you provide installation services?</span>
                            <div class="faq-toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner"
                                data-en="Yes, Greenwood Philippines can connect you with third-party installation contacts for most of our product lines. Please note that installation is carried out by independent installers — Greenwood Philippines does not perform the installation directly. Installation fees vary depending on the product type, quantity, and location. Please contact your nearest branch for a referral and quote."
                                data-tl="Oo, maaaring ikonekta kayo ng Greenwood Philippines sa mga third-party na installer para sa karamihan ng aming mga produkto. Pakitandaan na ang pag-install ay isinasagawa ng mga independyenteng installer — hindi direktang nagse-serbisyo ang Greenwood Philippines. Nag-iiba ang bayad sa pag-install depende sa uri ng produkto, dami, at lokasyon. Makipag-ugnayan sa pinakamalapit na branch para sa referral at quote.">
                                Yes, Greenwood Philippines can connect you with third-party installation contacts for most of our product lines. Please note that installation is carried out by independent installers — Greenwood Philippines does not perform the installation directly. Installation fees vary depending on the product type, quantity, and location. Please contact your nearest branch for a referral and quote.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item" data-cat="installation" data-aos="fade-up" data-aos-delay="50">
                        <button class="faq-question" aria-expanded="false">
                            <div class="faq-q-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                            </div>
                            <span class="faq-q-text"
                                data-en="Can I install the products myself (DIY)?"
                                data-tl="Maaari ko bang i-install ang mga produkto nang mag-isa (DIY)?">Can I install the products myself (DIY)?</span>
                            <div class="faq-toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner"
                                data-en="Absolutely. Our products are designed with straightforward installation in mind. When you purchase from us, we provide installation guides and technical support. However, we still recommend professional installation for best results, especially for large areas or complex layouts."
                                data-tl="Tiyak. Ang aming mga produkto ay dinisenyo para madaling i-install. Kapag bumili ka sa amin, nagbibigay kami ng mga gabay sa pag-install at teknikal na suporta. Gayunpaman, inirerekomenda pa rin namin ang propesyonal na pag-install para sa pinakamahusay na resulta, lalo na para sa malalaking lugar o kumplikadong layout.">
                                Absolutely. Our products are designed with straightforward installation in mind. When you purchase from us, we provide installation guides and technical support. However, we still recommend professional installation for best results, especially for large areas or complex layouts.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item" data-cat="installation" data-aos="fade-up" data-aos-delay="100">
                        <button class="faq-question" aria-expanded="false">
                            <div class="faq-q-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                            </div>
                            <span class="faq-q-text"
                                data-en="What tools are needed to install your wall panels?"
                                data-tl="Anong mga kagamitan ang kailangan para i-install ang inyong mga wall panels?">What tools are needed to install your wall panels?</span>
                            <div class="faq-toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner"
                                data-en="Basic installation typically requires a measuring tape, level, saw (for cutting panels to size), drill, and appropriate adhesive or screws depending on the surface. Specific tools may vary per product — our team will advise you at the point of purchase."
                                data-tl="Ang pangunahing pag-install ay karaniwang nangangailangan ng measuring tape, level, lagari (para putulin ang mga panel), drill, at angkop na adhesive o tornilyo depende sa ibabaw. Ang mga partikular na kagamitan ay maaaring mag-iba bawat produkto — ang aming koponan ay magbibigay ng payo sa oras ng pagbili.">
                                Basic installation typically requires a measuring tape, level, saw (for cutting panels to size), drill, and appropriate adhesive or screws depending on the surface. Specific tools may vary per product — our team will advise you at the point of purchase.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ORDERING & DELIVERY -->
                <div class="faq-group mb-4" data-group="ordering">
                    <div class="faq-section-title">Ordering & Delivery</div>

                    <div class="faq-item" data-cat="ordering" data-aos="fade-up">
                        <button class="faq-question" aria-expanded="false">
                            <div class="faq-q-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            </div>
                            <span class="faq-q-text"
                                data-en="How do I place an order?"
                                data-tl="Paano ako mag-order?">How do I place an order?</span>
                            <div class="faq-toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner"
                                data-en="You can place an order in three ways:<ul><li><strong>In-store</strong> — visit any of our branches during operating hours</li><li><strong>Facebook</strong> — message us on our <a href='https://www.facebook.com/greenwoodphilippines' target='_blank'>official Facebook page</a></li><li><strong>Phone/Chat</strong> — contact the nearest branch directly through our <a href='/index.php#locations'>locations page</a></li></ul>"
                                data-tl="Maaari kang mag-order sa tatlong paraan:<ul><li><strong>Sa branch</strong> — bisitahin ang alinman sa aming mga branch sa oras ng operasyon</li><li><strong>Facebook</strong> — mag-mensahe sa aming <a href='https://www.facebook.com/greenwoodphilippines' target='_blank'>opisyal na Facebook page</a></li><li><strong>Telepono/Chat</strong> — makipag-ugnayan sa pinakamalapit na branch sa pamamagitan ng aming <a href='/index.php#locations'>locations page</a></li></ul>">
                                You can place an order in three ways:
                                <ul>
                                    <li><strong>In-store</strong> — visit any of our branches during operating hours</li>
                                    <li><strong>Facebook</strong> — message us on our <a href="https://www.facebook.com/greenwoodphilippines" target="_blank">official Facebook page</a></li>
                                    <li><strong>Phone/Chat</strong> — contact the nearest branch directly through our <a href="/index.php#locations">locations page</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item" data-cat="ordering" data-aos="fade-up" data-aos-delay="50">
                        <button class="faq-question" aria-expanded="false">
                            <div class="faq-q-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            </div>
                            <span class="faq-q-text"
                                data-en="Do you offer delivery services?"
                                data-tl="Nag-aalok ba kayo ng serbisyo sa pagpapadala?">Do you offer delivery services?</span>
                            <div class="faq-toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner"
                                data-en="Yes, we offer delivery services through <strong>Lalamove</strong>. Delivery availability depends on your location and the branch nearest to you. Please contact your preferred branch to confirm delivery coverage and rates for your area.<br><br><div style='margin-top:0.85rem;background:#fff8e1;border-left:4px solid #f59e0b;border-radius:8px;padding:0.75rem 1rem;display:flex;align-items:flex-start;gap:0.6rem;'><svg style='flex-shrink:0;margin-top:2px;' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='#f59e0b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg><span style='color:#92610a;font-size:0.88rem;font-weight:600;'>Please note: The shipping fee is shouldered by the buyer.</span></div>"
                                data-tl="Oo, nag-aalok kami ng serbisyo sa pagpapadala sa pamamagitan ng <strong>Lalamove</strong>. Ang availability ng delivery ay depende sa inyong lokasyon at sa pinakamalapit na branch. Makipag-ugnayan sa inyong gustong branch para kumpirmahin ang saklaw ng delivery at mga rate sa inyong lugar.<br><br><div style='margin-top:0.85rem;background:#fff8e1;border-left:4px solid #f59e0b;border-radius:8px;padding:0.75rem 1rem;display:flex;align-items:flex-start;gap:0.6rem;'><svg style='flex-shrink:0;margin-top:2px;' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='#f59e0b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg><span style='color:#92610a;font-size:0.88rem;font-weight:600;'>Pakitandaan: Ang bayad sa pagpapadala ay sisilin ng mamimili.</span></div>">
                                Yes, we offer delivery services through <strong>Lalamove</strong>. Delivery availability depends on your location and the branch nearest to you. Please contact your preferred branch to confirm delivery coverage and rates for your area.
                                <div style="margin-top: 0.85rem; background: #fff8e1; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 0.75rem 1rem; display: flex; align-items: flex-start; gap: 0.6rem;">
                                    <svg style="flex-shrink:0; margin-top:2px;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    <span style="color: #92610a; font-size: 0.88rem; font-weight: 600;">Please note: The shipping fee is shouldered by the buyer.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item" data-cat="ordering" data-aos="fade-up" data-aos-delay="100">
                        <button class="faq-question" aria-expanded="false">
                            <div class="faq-q-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            </div>
                            <span class="faq-q-text"
                                data-en="What payment methods do you accept?"
                                data-tl="Anong mga paraan ng pagbabayad ang tinatanggap ninyo?">What payment methods do you accept?</span>
                            <div class="faq-toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner"
                                data-en="We accept cash and bank transfer. Please confirm with your branch for the most up-to-date payment options."
                                data-tl="Tinatanggap namin ang cash at bank transfer. Mangyaring kumpirmahin sa inyong branch para sa pinakabagong mga pagpipilian sa pagbabayad.">
                                We accept cash and bank transfer. Please confirm with your branch for the most up-to-date payment options.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item" data-cat="ordering" data-aos="fade-up" data-aos-delay="150">
                        <button class="faq-question" aria-expanded="false">
                            <div class="faq-q-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            </div>
                            <span class="faq-q-text"
                                data-en="What are your branch operating hours?"
                                data-tl="Ano ang mga oras ng operasyon ng inyong mga branch?">What are your branch operating hours?</span>
                            <div class="faq-toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner"
                                data-en="Our <strong>Pulilan Branch</strong> is open <strong>Monday – Sunday</strong>. All other branches operate <strong>Monday – Saturday</strong>. For specific opening and closing times, please contact the branch directly through our <a href='/index.php#locations'>locations page</a>."
                                data-tl="Ang aming <strong>Pulilan Branch</strong> ay bukas <strong>Lunes – Linggo</strong>. Lahat ng ibang branch ay nagpapatakbo <strong>Lunes – Sabado</strong>. Para sa mga partikular na oras ng pagbubukas at pagsasara, makipag-ugnayan sa branch nang direkta sa pamamagitan ng aming <a href='/index.php#locations'>locations page</a>.">
                                Our <strong>Pulilan Branch</strong> is open <strong>Monday – Sunday</strong>. All other branches operate <strong>Monday – Saturday</strong>. For specific opening and closing times, please contact the branch directly through our <a href="/index.php#locations">locations page</a>.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WARRANTY & SUPPORT -->
                <div class="faq-group mb-4" data-group="warranty">
                    <div class="faq-section-title">Warranty & Support</div>

                    <div class="faq-item" data-cat="warranty" data-aos="fade-up" data-aos-delay="50">
                        <button class="faq-question" aria-expanded="false">
                            <div class="faq-q-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </div>
                            <span class="faq-q-text"
                                data-en="What should I do if I receive a damaged product?"
                                data-tl="Ano ang dapat kong gawin kung makatanggap ako ng sirang produkto?">What should I do if I receive a damaged product?</span>
                            <div class="faq-toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner"
                                data-en="If you receive a damaged or defective product, please contact us immediately — ideally within 24–48 hours of receipt. Take photos of the damage and reach out to the branch where you made your purchase. We will assess the situation and arrange for a replacement or resolution as quickly as possible.<br><br><div style='margin-top:0.85rem;background:#fff8e1;border-left:4px solid #f59e0b;border-radius:8px;padding:0.75rem 1rem;display:flex;align-items:flex-start;gap:0.6rem;'><svg style='flex-shrink:0;margin-top:2px;' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='#f59e0b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg><span style='color:#92610a;font-size:0.88rem;font-weight:600;'>Please note: Damage may sometimes occur during delivery via Lalamove. In such cases, the buyer must coordinate directly with Lalamove first before we can process any resolution.</span></div>"
                                data-tl="Kung makatanggap ka ng sirang o may depektong produkto, makipag-ugnayan sa amin agad — ideally sa loob ng 24–48 oras mula sa pagtanggap. Kumuha ng mga larawan ng pinsala at makipag-ugnayan sa branch kung saan ka bumili. Susuriin namin ang sitwasyon at magsisimulang magsagawa ng kapalit o solusyon sa lalong madaling panahon.<br><br><div style='margin-top:0.85rem;background:#fff8e1;border-left:4px solid #f59e0b;border-radius:8px;padding:0.75rem 1rem;display:flex;align-items:flex-start;gap:0.6rem;'><svg style='flex-shrink:0;margin-top:2px;' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='#f59e0b' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg><span style='color:#92610a;font-size:0.88rem;font-weight:600;'>Pakitandaan: Maaaring mangyari ang pinsala sa panahon ng paghahatid sa pamamagitan ng Lalamove. Sa ganitong kaso, ang mamimili ay dapat makipag-ugnayan nang direkta sa Lalamove muna bago namin maproseso ang anumang solusyon.</span></div>">
                                If you receive a damaged or defective product, please contact us immediately — ideally within 24–48 hours of receipt. Take photos of the damage and reach out to the branch where you made your purchase. We will assess the situation and arrange for a replacement or resolution as quickly as possible.
                                <div style="margin-top: 0.85rem; background: #fff8e1; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 0.75rem 1rem; display: flex; align-items: flex-start; gap: 0.6rem;">
                                    <svg style="flex-shrink:0; margin-top:2px;" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    <span style="color: #92610a; font-size: 0.88rem; font-weight: 600;">Please note: Damage may sometimes occur during delivery via Lalamove. In such cases, the buyer must coordinate directly with Lalamove first before we can process any resolution.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="faq-item" data-cat="warranty" data-aos="fade-up" data-aos-delay="100">
                        <button class="faq-question" aria-expanded="false">
                            <div class="faq-q-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </div>
                            <span class="faq-q-text">Can I return or exchange a product?</span>
                            <div class="faq-toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner">
                                Returns and exchanges are handled on a case-by-case basis. Products must be unused, in their original condition, and accompanied by proof of purchase. Custom or special orders may not be returnable. Please contact the branch where you purchased the product to initiate the process.
                            </div>
                        </div>
                    </div>

                    <div class="faq-item" data-cat="warranty" data-aos="fade-up" data-aos-delay="150">
                        <button class="faq-question" aria-expanded="false">
                            <div class="faq-q-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            </div>
                            <span class="faq-q-text">How do I properly maintain and clean your products?</span>
                            <div class="faq-toggle">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner">
                                Most of our products require minimal maintenance. General care tips:
                                <ul>
                                    <li>Wipe with a soft, damp cloth for routine cleaning</li>
                                    <li>Avoid harsh chemicals or abrasive scrubbers</li>
                                    <li>For outdoor products, periodic cleaning to remove dirt and debris is recommended</li>
                                    <li>Inspect and re-tighten any fasteners annually for structural pieces</li>
                                </ul>
                                Product-specific maintenance guides are available from our staff at purchase.
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- No results -->
            <div class="faq-no-results" id="noResults">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <p>No questions found matching your search.</p>
                <p style="font-size:0.85rem;">Try different keywords or browse by category.</p>
            </div>

            <!-- Still have questions CTA -->
            <div class="faq-cta operating-hours visit-us-parallax" data-aos="fade-up">
                <div class="visit-us-bg-image" style="right:auto; left:0;"></div>
                <div class="visit-us-bg-image" style="right:0; left:auto; transform:scaleX(-1) translateY(100%);"></div>
                <div style="position:relative;z-index:1;color:#303823;">
                    <h3 style="color:#303823;font-size:1.6rem;font-weight:800;margin-bottom:0.5rem;">Still have questions?</h3>
                    <p style="color:#555;margin-bottom:1.5rem;">Our team is happy to help. Reach out to us through any of our branches or social media.</p>
                    <div class="faq-cta-btns">
                        <a href="/index.php#locations" class="faq-cta-btn-primary" style="position:relative;z-index:2;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            Find a Branch
                        </a>
                        <a href="https://www.facebook.com/greenwoodphilippines" target="_blank" class="faq-cta-btn-secondary" style="position:relative;z-index:2;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                            Message us on Facebook
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </section>

</main>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js" defer></script>
<script src="/js/script.js" defer></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── AOS
    if (typeof AOS !== 'undefined') AOS.init({ duration: 600, once: true, offset: 40 });

    const items = document.querySelectorAll('.faq-item');
    const searchInput = document.getElementById('faqSearch');
    const resultCount = document.getElementById('noResults') ? document.getElementById('resultCount') : null;
    const noResults = document.getElementById('noResults');

    // ── Count badges
    function updateCounts() {
        const cats = { all: 0, products: 0, installation: 0, ordering: 0, warranty: 0 };
        items.forEach(item => {
            const cat = item.dataset.cat;
            cats.all++;
            if (cats[cat] !== undefined) cats[cat]++;
        });
        document.getElementById('countAll').textContent = cats.all;
        document.getElementById('countProducts').textContent = cats.products;
        document.getElementById('countInstallation').textContent = cats.installation;
        document.getElementById('countOrdering').textContent = cats.ordering;
        document.getElementById('countWarranty').textContent = cats.warranty;
    }
    updateCounts();

    // ── Accordion
    items.forEach(item => {
        const btn = item.querySelector('.faq-question');
        const answer = item.querySelector('.faq-answer');
        btn.addEventListener('click', function () {
            const isOpen = item.classList.contains('open');
            // Close all
            items.forEach(i => {
                i.classList.remove('open');
                i.querySelector('.faq-answer').style.maxHeight = '0';
                i.querySelector('.faq-question').setAttribute('aria-expanded', 'false');
            });
            // Open clicked
            if (!isOpen) {
                item.classList.add('open');
                answer.style.maxHeight = answer.scrollHeight + 'px';
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // ── Category filter
    let activeCat = 'all';
    document.querySelectorAll('.faq-cat-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.faq-cat-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeCat = this.dataset.cat;
            applyFilters();
        });
    });

    // ── Search
    searchInput.addEventListener('input', applyFilters);

    function applyFilters() {
        const query = searchInput.value.toLowerCase().trim();
        let visible = 0;

        items.forEach(item => {
            const cat = item.dataset.cat;
            const text = item.querySelector('.faq-q-text').textContent.toLowerCase();
            const answerText = item.querySelector('.faq-answer-inner').textContent.toLowerCase();
            const matchesCat = activeCat === 'all' || cat === activeCat;
            const matchesSearch = !query || text.includes(query) || answerText.includes(query);

            if (matchesCat && matchesSearch) {
                item.classList.remove('hidden');
                visible++;
            } else {
                item.classList.add('hidden');
                // Close if hidden
                item.classList.remove('open');
                item.querySelector('.faq-answer').style.maxHeight = '0';
            }
        });

        // Show/hide group headings
        document.querySelectorAll('.faq-group').forEach(group => {
            const anyVisible = group.querySelectorAll('.faq-item:not(.hidden)').length > 0;
            group.style.display = anyVisible ? '' : 'none';
        });

        // Result count
        if (query && resultCount) {
            resultCount.textContent = visible + ' result' + (visible !== 1 ? 's' : '');
            resultCount.style.display = 'inline-block';
        } else if (resultCount) {
            resultCount.style.display = 'none';
        }

        // No results
        if (noResults) noResults.style.display = visible === 0 ? 'block' : 'none';
    }

    // ── Sync right CTA parallax image with left
    const ctaBgImages = document.querySelectorAll('.faq-cta .visit-us-bg-image');
    if (ctaBgImages.length === 2) {
        const ctaSection = ctaBgImages[0].closest('.visit-us-parallax');
        function updateFaqCtaParallax() {
            const rect = ctaSection.getBoundingClientRect();
            const winH = window.innerHeight;
            const progress = Math.max(0, Math.min(1, (winH - rect.top) / (winH - winH * 0.2)));
            const pct = ((1 - progress) * 100);
            ctaBgImages[0].style.transform = 'translateY(' + pct + '%)';
            ctaBgImages[1].style.transform = 'scaleX(-1) translateY(' + pct + '%)';
        }
        window.addEventListener('scroll', updateFaqCtaParallax, { passive: true });
        updateFaqCtaParallax();
    }

    // ── Language Toggle
    let currentLang = 'en';
    document.querySelectorAll('#langToggle .lang-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            currentLang = this.dataset.lang;
            document.querySelectorAll('#langToggle .lang-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Switch all q-text
            document.querySelectorAll('.faq-q-text[data-en]').forEach(el => {
                el.textContent = el.dataset[currentLang] || el.dataset.en;
            });

            // Switch all answer-inner (set innerHTML for rich content)
            document.querySelectorAll('.faq-answer-inner[data-en]').forEach(el => {
                el.innerHTML = el.dataset[currentLang] || el.dataset.en;
            });

            // Switch CTA text spans
            document.querySelectorAll('.faq-cta-content [data-en]').forEach(el => {
                if (el.tagName === 'SPAN' || el.tagName === 'H3' || el.tagName === 'P') {
                    el.textContent = el.dataset[currentLang] || el.dataset.en;
                }
            });

            // Switch section titles
            const sectionTitles = {
                en: { products: 'Products', installation: 'Installation', ordering: 'Ordering & Delivery', warranty: 'Warranty & Support' },
                tl: { products: 'Mga Produkto', installation: 'Pag-install', ordering: 'Pag-order at Paghahatid', warranty: 'Warranty at Suporta' }
            };
            document.querySelectorAll('.faq-section-title').forEach(el => {
                const group = el.closest('.faq-group')?.dataset.group;
                if (group && sectionTitles[currentLang][group]) {
                    el.firstChild.textContent = sectionTitles[currentLang][group];
                }
            });

            // Switch search placeholder
            const searchEl = document.getElementById('faqSearch');
            if (searchEl) {
                searchEl.placeholder = currentLang === 'tl'
                    ? 'Maghanap ng tanong... hal. delivery, pag-install, warranty'
                    : 'Search questions... e.g. delivery, installation, warranty';
            }

            // Re-apply filters so search still works after switch
            applyFilters();
        });
    });

});
</script>
</body>
</html>