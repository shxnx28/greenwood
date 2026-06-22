<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    require_once 'admin/db.php';
    $pageTitle = 'Our Influencers';
    ?>

    <title>Influencer Reviews | Greenwood Philippines</title>

    <!-- Primary SEO Meta Tags -->
    <meta name="description" content="Meet the influencers and content creators who trust and feature Greenwood Philippines products. See their reactions and honest reviews of our wall panels, flooring, and more.">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Greenwood Philippines">
    <link rel="canonical" href="https://greenwoodphilippines.com/influencers">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://greenwoodphilippines.com/influencers">
    <meta property="og:title" content="Our Influencers | Greenwood Philippines">
    <meta property="og:description" content="Meet content creators and influencers who feature Greenwood Philippines products in their homes and reviews.">
    <meta property="og:image" content="https://greenwoodphilippines.com/assets/images/parallax.webp">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Greenwood Philippines Influencers">
    <meta property="og:site_name" content="Greenwood Philippines">
    <meta property="og:locale" content="en_PH">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Our Influencers | Greenwood Philippines">
    <meta name="twitter:description" content="Meet content creators and influencers who feature Greenwood Philippines products.">
    <meta name="twitter:image" content="https://greenwoodphilippines.com/assets/images/parallax.webp">
    <meta name="twitter:image:alt" content="Greenwood Philippines Influencers">

    <!-- Schema.org Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "CollectionPage",
      "name": "Our Influencers – Greenwood Philippines",
      "description": "Browse content creators and influencers who feature Greenwood Philippines products.",
      "url": "https://greenwoodphilippines.com/influencers",
      "isPartOf": {
        "@type": "WebSite",
        "name": "Greenwood Philippines",
        "url": "https://greenwoodphilippines.com"
      }
    }
    </script>

    <link rel="icon" type="image/png" href="/assets/images/gw.png">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- AOS Animation Library -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"></noscript>
    <link rel="preload" href="https://unpkg.com/aos@2.3.1/dist/aos.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css"></noscript>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/influencers.css">

    <style>
        .album-header {
            background: #1a1f14;
            padding: 140px 0 80px;
            margin-top: 70px;
            position: relative;
            overflow: hidden;
        }

        /* Pre-apply scrolled state before JS runs to prevent flash */
        .navbar {
            background: rgba(255, 255, 255, 0.97) !important;
            box-shadow: 0 2px 20px rgba(0,0,0,0.10) !important;
        }
        .navbar .navbar-brand { opacity: 1 !important; pointer-events: auto !important; }
        .navbar .navbar-nav .nav-link { color: var(--green-dark, #303823) !important; }

        .album-header-video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            z-index: 0;
            filter: blur(3px);
            transform: scale(1.03);
        }

        .album-header-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
            background: linear-gradient(
                160deg,
                rgba(15, 20, 10, 0.60) 0%,
                rgba(30, 38, 20, 0.45) 50%,
                rgba(15, 20, 10, 0.62) 100%
            );
        }

        .album-header .container {
            position: relative;
            z-index: 2;
        }

        .album-header-logo {
            margin-bottom: 20px;
        }

        .album-header-logo img {
            height: 64px;
            width: auto;
            opacity: 0.92;
            filter: brightness(0) invert(1);
        }

        .album-header-title {
            font-size: 2.75rem;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.2;
            letter-spacing: -0.5px;
            margin: 0 0 16px;
        }

        .album-header-title span {
            color: #8fba52;
        }

        .album-header-sub {
            font-size: 1rem;
            color: rgba(255,255,255,0.75);
            font-weight: 400;
            margin: 0;
            line-height: 1.7;
        }

        @media (max-width: 768px) {
            .album-header { padding: 120px 0 60px; margin-top: 56px; }
            .album-header-title { font-size: 2rem; }
        }

        .album-info-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid var(--border-light, #e4e8df);
            margin-top: -40px;
            position: relative;
            z-index: 10;
        }

        .album-stats {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stat-item i {
            font-size: 2.4rem;
            color: #2d5016;
            line-height: 1;
            align-self: stretch;
            display: flex;
            align-items: center;
        }

        .filter-section {
            background: white;
            padding: 20px 0;
            border-bottom: 1px solid #e8e8e8;
        }

        .filter-btn {
            padding: 8px 20px;
            border: 2px solid #2d5016;
            background: white;
            color: #2d5016;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #2d5016;
            color: white;
        }
    @media (max-width: 575px) {
        /* Fix equal left/right spacing */
        .container { padding-left: 16px !important; padding-right: 16px !important; }

        /* Stats card */
        .album-info-card { padding: 20px 16px; margin-top: -28px; }
        .album-stats {
            display: flex;
            flex-wrap: nowrap;
            gap: 0;
            justify-content: center;
        }
        .stat-item {
            display: flex !important;
            flex: 1 1 0;
            box-sizing: border-box;
            padding: 10px 8px;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: flex-start;
            text-align: center;
            gap: 6px;
            min-width: 0;
        }
        .stat-item i {
            font-size: 1.6rem !important;
            line-height: 1;
            display: block !important;
            align-self: auto !important;
            width: auto !important;
            text-align: center;
        }
        .stat-item small { font-size: 0.7rem; display: block; }
        .stat-item strong { font-size: 1rem; display: block; }

        /* Filter buttons — centered row, smaller */
        .filter-section .d-flex { justify-content: center; gap: 8px !important; }
        .filter-btn {
            text-align: center;
            padding: 6px 12px;
            font-size: 0.8rem;
        }
    }
    </style>
    <?php include 'pixel.php'; ?>
</head>
<body>
    <!-- Navigation -->
    <?php include 'navbar.php'; ?>
<main id="main-content">

    <!-- Influencers Header with Background Image -->
    <section class="album-header">
        <video class="album-header-video" id="albumHeaderVideo" muted loop playsinline preload="none" poster="/assets/images/parallax.webp">
            <source data-src="/assets/videos/header.mp4" type="video/mp4">
        </video>
        <script>
        // Defer the 8.4 MB header video so it never blocks first paint. The
        // poster shows instantly; the video loads & plays only after the page
        // has finished loading (muted playback is allowed programmatically).
        window.addEventListener('load', function () {
            var v = document.getElementById('albumHeaderVideo');
            if (!v) return;
            var s = v.querySelector('source[data-src]');
            if (s && !s.getAttribute('src')) {
                s.setAttribute('src', s.getAttribute('data-src'));
                v.load();
            }
            var p = v.play();
            if (p && typeof p.catch === 'function') p.catch(function () {});
        });
        </script>
        <div class="album-header-overlay"></div>
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <div class="album-header-logo">
                    <img src="/assets/images/nobg.webp" alt="Greenwood Philippines">
                </div>
                <h1 class="album-header-title">Our <span>Influencers</span></h1>
                <p class="album-header-sub">Content creators and community members who trust and feature Greenwood Philippines</p>
            </div>
        </div>
    </section>

    <!-- Influencer Stats Info Card -->
    <section class="py-4">
        <div class="container">
            <div class="album-info-card" data-aos="fade-up">
                <div class="album-stats">
                    <?php
                    $totalResult = $conn->query("SELECT COUNT(*) as total FROM influencer_reactions");
                    $totalInfluencers = ($totalResult) ? $totalResult->fetch_assoc()['total'] : 0;

                    $platformResult = $conn->query("SELECT COUNT(DISTINCT platform) as total FROM influencer_reactions WHERE platform IS NOT NULL AND platform != ''");
                    $totalPlatforms = ($platformResult) ? $platformResult->fetch_assoc()['total'] : 0;

                    $withVideoResult = $conn->query("SELECT COUNT(*) as total FROM influencer_reactions WHERE reaction_url IS NOT NULL AND reaction_url != ''");
                    $totalWithVideo = ($withVideoResult) ? $withVideoResult->fetch_assoc()['total'] : 0;
                    ?>

                    <div class="stat-item">
                        <i class="bi bi-people"></i>
                        <div>
                            <small class="text-muted d-block">Total Influencers</small>
                            <strong><?php echo $totalInfluencers; ?></strong>
                        </div>
                    </div>

                    <div class="stat-item">
                        <i class="bi bi-grid"></i>
                        <div>
                            <small class="text-muted d-block">Platforms</small>
                            <strong><?php echo $totalPlatforms; ?></strong>
                        </div>
                    </div>

                    <div class="stat-item">
                        <i class="bi bi-play-circle"></i>
                        <div>
                            <small class="text-muted d-block">Reaction Videos</small>
                            <strong><?php echo $totalWithVideo; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Filter Section -->
    <section class="filter-section">
        <div class="container">
            <div class="d-flex justify-content-center gap-2 flex-wrap" data-aos="fade-up">
                <button class="filter-btn active" data-filter="all">All</button>
                <?php
                $platformFilterResult = $conn->query("SELECT DISTINCT platform FROM influencer_reactions WHERE platform IS NOT NULL AND platform != '' ORDER BY platform");
                if ($platformFilterResult && $platformFilterResult->num_rows > 0) {
                    while ($row = $platformFilterResult->fetch_assoc()) {
                        $platform = htmlspecialchars($row['platform']);
                        $platformLower = strtolower($platform);
                        echo "<button class=\"filter-btn\" data-filter=\"{$platformLower}\">{$platform}</button>";
                    }
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Influencers Grid -->
    <section class="py-5">
        <div class="container">
            <?php
            $result = $conn->query("SELECT * FROM influencer_reactions ORDER BY created_at DESC");

            if ($result && $result->num_rows > 0):
            ?>
            <div class="influencer-grid">
                <?php
                $delay = 0;
                while ($inf = $result->fetch_assoc()):
                    $id            = $inf['id'];
                    $name          = htmlspecialchars($inf['name'] ?? '');
                    $platform      = htmlspecialchars($inf['platform'] ?? '');
                    $platformLower = strtolower($platform);
                    $description   = htmlspecialchars($inf['description'] ?? '');
                    $reactionUrl   = htmlspecialchars($inf['reaction_url'] ?? '');

                    // Image path
                    $photoRaw = $inf['profile_photo'] ?? '';
                    if (!empty($photoRaw)) {
                        if (strpos($photoRaw, 'uploads/') === 0) {
                            $photoPath = '/admin/' . $photoRaw;
                        } else {
                            $photoPath = $photoRaw;
                        }
                    } else {
                        $photoPath = '';
                    }

                    // Platform icon
                    $platformIcons = [
                        'facebook'  => 'bi-facebook',
                        'instagram' => 'bi-instagram',
                        'tiktok'    => 'bi-tiktok',
                        'youtube'   => 'bi-youtube',
                        'twitter'   => 'bi-twitter-x',
                        'x'         => 'bi-twitter-x',
                    ];
                    $iconClass = $platformIcons[$platformLower] ?? 'bi-person-circle';

                    // Wrap in <a> if there's a reaction URL, otherwise <div>
                    $tag     = !empty($reactionUrl) ? 'a' : 'div';
                    $hrefAttr = !empty($reactionUrl) ? "href=\"{$reactionUrl}\" target=\"_blank\" rel=\"noopener noreferrer\"" : '';
                ?>
                <<?php echo $tag; ?> <?php echo $hrefAttr; ?>
                    class="influencer-card inf-item"
                    data-platform="<?php echo $platformLower; ?>"
                    data-aos="zoom-in"
                    data-aos-delay="<?php echo $delay; ?>">

                    <!-- Photo -->
                    <div class="inf-image-wrapper <?php echo empty($photoPath) ? 'placeholder-image' : ''; ?>">
                        <?php if (!empty($photoPath)): ?>
                            <img src="<?php echo $photoPath; ?>" alt="<?php echo $name; ?>" loading="lazy" class="inf-image">
                        <?php endif; ?>

                        <!-- Hover overlay -->
                        <div class="inf-hover-overlay">
                            <span class="inf-hover-text">
                                <?php if (!empty($reactionUrl)): ?>
                                    <i class="bi bi-play-circle-fill"></i> Watch Reaction
                                <?php else: ?>
                                    <i class="bi bi-person-fill"></i> <?php echo $name; ?>
                                <?php endif; ?>
                            </span>
                        </div>

                        <!-- Platform badge -->
                        <?php if (!empty($platform)): ?>
                        <div class="inf-platform-badge <?php echo $platformLower; ?>">
                            <i class="bi <?php echo $iconClass; ?>"></i>
                            <?php echo $platform; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="inf-info">
                        <h3 class="inf-name"><?php echo $name; ?></h3>
                        <?php if (!empty($description)): ?>
                            <p class="inf-description"><?php echo $description; ?></p>
                        <?php endif; ?>
                        <?php if (!empty($reactionUrl)): ?>
                            <span class="inf-watch-link">
                                <i class="bi bi-play-circle"></i> Watch Reaction
                            </span>
                        <?php endif; ?>
                    </div>

                </<?php echo $tag; ?>>
                <?php
                    $delay += 50;
                    if ($delay > 300) $delay = 0;
                endwhile;
                ?>
            </div>

            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-people" style="font-size: 4rem; color: #ccc;"></i>
                <p class="text-muted mt-3">No influencers found.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section py-5 bg-success text-white">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-3" data-aos="fade-up">Want to Collaborate?</h2>
            <p class="lead mb-4" data-aos="fade-up" data-aos-delay="100">Are you a content creator or influencer interested in featuring Greenwood Philippines? We'd love to work with you.</p>
            <div data-aos="fade-up" data-aos-delay="200">
                <a href="/index.php#locations" class="btn btn-light btn-lg me-3">Contact Us</a>
                <a href="/catalog" class="btn btn-outline-light btn-lg">View Products</a>
            </div>
        </div>
    </section>

</main><!-- /#main-content -->
<?php include 'footer.php'; ?>

<?php $conn->close(); ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- AOS Animation JS -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<!-- Custom JS -->
<script src="/js/script.js"></script>

<script>
    AOS.init({ duration: 800, once: true });

    document.addEventListener('DOMContentLoaded', function () {
        const filterButtons = document.querySelectorAll('.filter-btn');
        const infItems      = document.querySelectorAll('.inf-item');

        filterButtons.forEach(button => {
            button.addEventListener('click', function () {
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                const filterValue = this.getAttribute('data-filter');

                infItems.forEach(item => {
                    const itemPlatform = item.getAttribute('data-platform');
                    if (filterValue === 'all' || itemPlatform === filterValue) {
                        item.style.display = 'block';
                        item.classList.remove('aos-animate');
                        setTimeout(() => item.classList.add('aos-animate'), 10);
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    });
</script>

</body>
</html>