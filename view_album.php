<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php
    // Database connection
    require_once 'admin/db.php';
    
    // Get album name from URL
    $albumName = isset($_GET['album']) ? $_GET['album'] : '';
    $albumName = htmlspecialchars($albumName);
    
    // Fetch album details and first image
    $albumInfo = null;
    $headerImagePath = '';
    if (!empty($albumName)) {
        $sql = "SELECT * FROM project_images WHERE album = ? ORDER BY display_order ASC, uploaded_at ASC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $albumName);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $albumInfo = $result->fetch_assoc();
            
            // Get header image path
            $imagePathRaw = $albumInfo['image_path'] ?? '';
            if (!empty($imagePathRaw)) {
                if (strpos($imagePathRaw, 'uploads/') === 0) {
                    $headerImagePath = '/admin/' . $imagePathRaw;
                } elseif (strpos($imagePathRaw, 'projects/') === 0) {
                    $headerImagePath = '/admin/uploads/' . $imagePathRaw;
                } else {
                    $headerImagePath = $imagePathRaw;
                }
            }
        }
        $stmt->close();
    }
    
    $pageTitle = !empty($albumName) ? $albumName . ' - Project Album' : 'Project Album';
    ?>
    
    <?php
    $seoAlbumTitle = !empty($albumName) ? htmlspecialchars($albumName) : 'Project Album';
    $seoAlbumDesc  = 'View the ' . $seoAlbumTitle . ' project album by Greenwood Philippines. Real installation photos featuring our premium wall, floor, ceiling, and fence solutions.';
    $seoAlbumUrl   = 'https://www.greenwoodphilippines.com/view_album.php' . (!empty($albumName) ? '?album=' . urlencode($albumName) : '');
    $seoAlbumImage = !empty($headerImagePath)
        ? 'https://www.greenwoodphilippines.com/' . ltrim($headerImagePath, '/')
        : 'https://www.greenwoodphilippines.com/assets/images/project_cover.webp';
    ?>

    <title><?php echo $seoAlbumTitle; ?> | Project Gallery – Greenwood Philippines</title>

    <!-- Primary SEO Meta Tags -->
    <meta name="description" content="<?php echo $seoAlbumDesc; ?>">
    <meta name="keywords" content="<?php echo $seoAlbumTitle; ?>, Greenwood Philippines projects, installation gallery, wall panels, flooring, ceiling Philippines">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Greenwood Philippines">
    <link rel="canonical" href="<?php echo $seoAlbumUrl; ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $seoAlbumUrl; ?>">
    <meta property="og:title" content="<?php echo $seoAlbumTitle; ?> | Greenwood Philippines">
    <meta property="og:description" content="<?php echo $seoAlbumDesc; ?>">
    <meta property="og:image" content="<?php echo $seoAlbumImage; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?php echo $seoAlbumTitle; ?> – Greenwood Philippines Project Gallery">
    <meta property="og:site_name" content="Greenwood Philippines">
    <meta property="og:locale" content="en_PH">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $seoAlbumTitle; ?> | Greenwood Philippines">
    <meta name="twitter:description" content="<?php echo $seoAlbumDesc; ?>">
    <meta name="twitter:image" content="<?php echo $seoAlbumImage; ?>">
    <meta name="twitter:image:alt" content="<?php echo $seoAlbumTitle; ?> – Greenwood Philippines Project Gallery">

    <!-- Schema.org Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "ImageGallery",
      "name": "<?php echo $seoAlbumTitle; ?>",
      "description": "<?php echo $seoAlbumDesc; ?>",
      "url": "<?php echo $seoAlbumUrl; ?>",
      "isPartOf": {
        "@type": "WebSite",
        "name": "Greenwood Philippines",
        "url": "https://www.greenwoodphilippines.com"
      }
    }
    </script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- GLightbox CSS for image gallery -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">

<link rel="icon" type="image/png" href="/assets/images/gw.png">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/projects.css">
    
    <style>
        .album-header {
            background: #1a1f14;
            padding: 140px 0 80px;
            margin-top: 70px;
            position: relative;
            overflow: hidden;
        }

        .album-header-bg {
            position: absolute;
            inset: -20px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: blur(6px);
            opacity: 0.55;
            transform: scale(1.05);
            z-index: 0;
            <?php if (!empty($headerImagePath)): ?>
            background-image: url('<?php echo $headerImagePath; ?>');
            <?php endif; ?>
        }

        .album-header-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
            background: linear-gradient(
                160deg,
                rgba(15, 20, 10, 0.70) 0%,
                rgba(30, 38, 20, 0.55) 50%,
                rgba(15, 20, 10, 0.72) 100%
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
            height: 52px;
            width: auto;
            opacity: 0.9;
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

        .album-header-sub {
            font-size: 1rem;
            color: rgba(255,255,255,0.75);
            font-weight: 400;
            margin: 0;
            line-height: 1.7;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 24px;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: #fff;
        }

        /* Pre-apply scrolled navbar state */
        .navbar {
            background: rgba(255, 255, 255, 0.97) !important;
            box-shadow: 0 2px 20px rgba(0,0,0,0.10) !important;
        }
        .navbar .navbar-brand { opacity: 1 !important; pointer-events: auto !important; }
        .navbar .navbar-nav .nav-link { color: var(--green-dark, #303823) !important; }

        @media (max-width: 768px) {
            .album-header { padding: 120px 0 60px; margin-top: 56px; }
            .album-header-title { font-size: 2rem; }
        }

        @media (max-width: 576px) {
            .album-header { padding: 110px 0 50px; }
            .album-header-logo img { height: 44px; }
            .album-header-title { font-size: 1.7rem; }
        }
        
        .album-info-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08);
            margin-top: -40px;
            position: relative;
            z-index: 10;
        }
        
        .album-stats {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
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
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            aspect-ratio: 1;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .gallery-item:hover {
            transform: scale(1.05);
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .gallery-item-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
            padding: 15px;
            color: white;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .gallery-item:hover .gallery-item-overlay {
            opacity: 1;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        @media (max-width: 575px) {
            /* Fix equal left/right spacing */
            .container { padding-left: 16px !important; padding-right: 16px !important; }

            .album-info-card { padding: 18px 14px; margin-top: -28px; }
            .album-stats {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px 8px;
            }
            .stat-item {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: flex-start;
                text-align: center;
                gap: 5px;
                min-width: 0;
                padding: 8px 4px;
            }
            .stat-item i {
                font-size: 1.5rem !important;
                line-height: 1;
                display: block !important;
                align-self: auto !important;
                width: auto !important;
                text-align: center;
                color: #2d5016;
            }
            .stat-item small {
                font-size: 0.68rem;
                color: #888;
                display: block;
                letter-spacing: 0.02em;
            }
            .stat-item strong {
                font-size: 0.88rem;
                font-weight: 600;
                color: #2d3748;
                display: block;
                word-break: break-word;
            }
            .gallery-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'navbar.php'; ?>

    <?php if ($albumInfo): ?>
    <!-- Album Header with Background Image -->
    <section class="album-header">
        <div class="album-header-bg"></div>
        <div class="album-header-overlay"></div>
        <div class="container">
            <div data-aos="fade-up">
                <a href="/projects.php" class="back-link">
                    <i class="bi bi-arrow-left"></i> Back to Projects
                </a>
                <div class="album-header-logo">
                    <img src="/assets/images/nobg.webp" alt="Greenwood Philippines">
                </div>
                <h1 class="album-header-title"><?php echo htmlspecialchars($albumName); ?></h1>
                <?php if (!empty($albumInfo['description'])): ?>
                <p class="album-header-sub"><?php echo htmlspecialchars($albumInfo['description']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Album Info Card -->
    <section class="py-4">
        <div class="container">
            <div class="album-info-card" data-aos="fade-up">
                <div class="album-stats">
                    <?php if (!empty($albumInfo['category'])): ?>
                    <div class="stat-item">
                        <i class="bi bi-tag"></i>
                        <div>
                            <small class="text-muted d-block">Category</small>
                            <strong><?php echo ucfirst(strtolower(htmlspecialchars($albumInfo['category']))); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($albumInfo['city']) || !empty($albumInfo['location'])): ?>
                    <div class="stat-item">
                        <i class="bi bi-geo-alt"></i>
                        <div>
                            <small class="text-muted d-block">Location</small>
                            <strong><?php echo htmlspecialchars($albumInfo['city'] ?: $albumInfo['location']); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($albumInfo['year'])): ?>
                    <div class="stat-item">
                        <i class="bi bi-calendar"></i>
                        <div>
                            <small class="text-muted d-block">Year</small>
                            <strong><?php echo htmlspecialchars($albumInfo['year']); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($albumInfo['products_used'])): ?>
                    <div class="stat-item">
                        <i class="bi bi-box"></i>
                        <div>
                            <small class="text-muted d-block">Products Used</small>
                            <strong><?php echo htmlspecialchars($albumInfo['products_used']); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Image Gallery -->
<section class="py-5">
    <div class="container">
        <div class="gallery-grid">
            <?php
            // Fetch all images in this album
            $sql = "SELECT * FROM project_images 
                    WHERE album = ? 
                    ORDER BY display_order ASC, uploaded_at ASC";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $albumName);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $delay = 0;

                while ($image = $result->fetch_assoc()) {

                    // IMAGE PATH FIX (root → admin/uploads)
                    $imagePathRaw = $image['image_path'] ?? '/assets/images/placeholder-project.jpg';

                    if (strpos($imagePathRaw, 'uploads/') === 0) {
                        $imagePath = '/admin/' . htmlspecialchars($imagePathRaw);
                    } elseif (strpos($imagePathRaw, 'projects/') === 0) {
                        $imagePath = '/admin/uploads/' . htmlspecialchars($imagePathRaw);
                    } else {
                        $imagePath = htmlspecialchars($imagePathRaw);
                    }

                    $title = htmlspecialchars($image['title']);
                    $description = htmlspecialchars($image['description'] ?? '');

                    echo "
                    <a href=\"{$imagePath}\" 
                       class=\"glightbox gallery-item\" 
                       data-gallery=\"album-gallery\" 
                       data-aos=\"zoom-in\" 
                       data-aos-delay=\"{$delay}\">

                        <img src=\"{$imagePath}\" 
                             alt=\"{$title}\" 
                             loading=\"lazy\">

                        <div class=\"gallery-item-overlay\">
                            <h6 class=\"mb-0\">{$title}</h6>";

                    if (!empty($description)) {
                        echo "<small>{$description}</small>";
                    }

                    echo "
                        </div>
                    </a>";

                    $delay += 50;
                    if ($delay > 300) {
                        $delay = 0;
                    }
                }
            } else {
                echo "
                <div class=\"col-12 text-center py-5\">
                    <p class=\"text-muted\">No images found in this album.</p>
                </div>";
            }

            $stmt->close();
            ?>
        </div>
    </div>
</section>


    <?php else: ?>
    <!-- Album Not Found -->
    <section class="py-5" style="margin-top: 100px;">
        <div class="container text-center">
            <h2 class="display-4 mb-4">Album Not Found</h2>
            <p class="lead mb-4">The album you're looking for doesn't exist or has been removed.</p>
            <a href="/projects.php" class="btn btn-success btn-lg">Browse All Projects</a>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($albumInfo): ?>
    <!-- Call to Action -->
    <section class="cta-section py-5 text-white">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-3" data-aos="fade-up">Inspired by This Project?</h2>
            <p class="lead mb-4" data-aos="fade-up" data-aos-delay="100">Let us help you create something similar with our premium materials and expert guidance.</p>
            <div data-aos="fade-up" data-aos-delay="200">
                <a href="/index.php#contact" class="btn btn-light btn-lg me-3">Contact Us</a>
                <a href="/catalog.php" class="btn btn-outline-light btn-lg">View Products</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <?php $conn->close(); ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS Animation JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- GLightbox JS -->
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/js/script.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });
        
        // Initialize GLightbox
        const lightbox = GLightbox({
            touchNavigation: true,
            loop: true,
            autoplayVideos: true
        });
    </script>

</body>
</html><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php
    // Database connection
    require_once 'admin/db.php';
    
    // Get album name from URL
    $albumName = isset($_GET['album']) ? $_GET['album'] : '';
    $albumName = htmlspecialchars($albumName);
    
    // Fetch album details and first image
    $albumInfo = null;
    $headerImagePath = '';
    if (!empty($albumName)) {
        $sql = "SELECT * FROM project_images WHERE album = ? ORDER BY display_order ASC, uploaded_at ASC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $albumName);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $albumInfo = $result->fetch_assoc();
            
            // Get header image path
            $imagePathRaw = $albumInfo['image_path'] ?? '';
            if (!empty($imagePathRaw)) {
                if (strpos($imagePathRaw, 'uploads/') === 0) {
                    $headerImagePath = '/admin/' . $imagePathRaw;
                } elseif (strpos($imagePathRaw, 'projects/') === 0) {
                    $headerImagePath = '/admin/uploads/' . $imagePathRaw;
                } else {
                    $headerImagePath = $imagePathRaw;
                }
            }
        }
        $stmt->close();
    }
    
    $pageTitle = !empty($albumName) ? $albumName . ' - Project Album' : 'Project Album';
    ?>
    
    <?php
    $seoAlbumTitle = !empty($albumName) ? htmlspecialchars($albumName) : 'Project Album';
    $seoAlbumDesc  = 'View the ' . $seoAlbumTitle . ' project album by Greenwood Philippines. Real installation photos featuring our premium wall, floor, ceiling, and fence solutions.';
    $seoAlbumUrl   = 'https://www.greenwoodphilippines.com/view_album.php' . (!empty($albumName) ? '?album=' . urlencode($albumName) : '');
    $seoAlbumImage = !empty($headerImagePath)
        ? 'https://www.greenwoodphilippines.com/' . ltrim($headerImagePath, '/')
        : 'https://www.greenwoodphilippines.com/assets/images/project_cover.webp';
    ?>

    <title><?php echo $seoAlbumTitle; ?> | Project Gallery – Greenwood Philippines</title>

    <!-- Primary SEO Meta Tags -->
    <meta name="description" content="<?php echo $seoAlbumDesc; ?>">
    <meta name="keywords" content="<?php echo $seoAlbumTitle; ?>, Greenwood Philippines projects, installation gallery, wall panels, flooring, ceiling Philippines">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Greenwood Philippines">
    <link rel="canonical" href="<?php echo $seoAlbumUrl; ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $seoAlbumUrl; ?>">
    <meta property="og:title" content="<?php echo $seoAlbumTitle; ?> | Greenwood Philippines">
    <meta property="og:description" content="<?php echo $seoAlbumDesc; ?>">
    <meta property="og:image" content="<?php echo $seoAlbumImage; ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?php echo $seoAlbumTitle; ?> – Greenwood Philippines Project Gallery">
    <meta property="og:site_name" content="Greenwood Philippines">
    <meta property="og:locale" content="en_PH">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $seoAlbumTitle; ?> | Greenwood Philippines">
    <meta name="twitter:description" content="<?php echo $seoAlbumDesc; ?>">
    <meta name="twitter:image" content="<?php echo $seoAlbumImage; ?>">
    <meta name="twitter:image:alt" content="<?php echo $seoAlbumTitle; ?> – Greenwood Philippines Project Gallery">

    <!-- Schema.org Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "ImageGallery",
      "name": "<?php echo $seoAlbumTitle; ?>",
      "description": "<?php echo $seoAlbumDesc; ?>",
      "url": "<?php echo $seoAlbumUrl; ?>",
      "isPartOf": {
        "@type": "WebSite",
        "name": "Greenwood Philippines",
        "url": "https://www.greenwoodphilippines.com"
      }
    }
    </script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- GLightbox CSS for image gallery -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">

<link rel="icon" type="image/png" href="/assets/images/gw.png">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/projects.css">
    
    <style>
        .album-header {
            background: #1a1f14;
            padding: 140px 0 80px;
            margin-top: 70px;
            position: relative;
            overflow: hidden;
        }

        .album-header-bg {
            position: absolute;
            inset: -20px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: blur(6px);
            opacity: 0.55;
            transform: scale(1.05);
            z-index: 0;
            <?php if (!empty($headerImagePath)): ?>
            background-image: url('<?php echo $headerImagePath; ?>');
            <?php endif; ?>
        }

        .album-header-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
            background: linear-gradient(
                160deg,
                rgba(15, 20, 10, 0.70) 0%,
                rgba(30, 38, 20, 0.55) 50%,
                rgba(15, 20, 10, 0.72) 100%
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
            height: 52px;
            width: auto;
            opacity: 0.9;
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

        .album-header-sub {
            font-size: 1rem;
            color: rgba(255,255,255,0.75);
            font-weight: 400;
            margin: 0;
            line-height: 1.7;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 24px;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: #fff;
        }

        /* Pre-apply scrolled navbar state */
        .navbar {
            background: rgba(255, 255, 255, 0.97) !important;
            box-shadow: 0 2px 20px rgba(0,0,0,0.10) !important;
        }
        .navbar .navbar-brand { opacity: 1 !important; pointer-events: auto !important; }
        .navbar .navbar-nav .nav-link { color: var(--green-dark, #303823) !important; }

        @media (max-width: 768px) {
            .album-header { padding: 120px 0 60px; margin-top: 56px; }
            .album-header-title { font-size: 2rem; }
        }

        @media (max-width: 576px) {
            .album-header { padding: 110px 0 50px; }
            .album-header-logo img { height: 44px; }
            .album-header-title { font-size: 1.7rem; }
        }
        
        .album-info-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08);
            margin-top: -40px;
            position: relative;
            z-index: 10;
        }
        
        .album-stats {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
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
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .gallery-item {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            aspect-ratio: 1;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .gallery-item:hover {
            transform: scale(1.05);
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .gallery-item-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
            padding: 15px;
            color: white;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .gallery-item:hover .gallery-item-overlay {
            opacity: 1;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'navbar.php'; ?>

    <?php if ($albumInfo): ?>
    <!-- Album Header with Background Image -->
    <section class="album-header">
        <div class="album-header-bg"></div>
        <div class="album-header-overlay"></div>
        <div class="container">
            <div data-aos="fade-up">
                <a href="/projects.php" class="back-link">
                    <i class="bi bi-arrow-left"></i> Back to Projects
                </a>
                <div class="album-header-logo">
                    <img src="/assets/images/nobg.webp" alt="Greenwood Philippines">
                </div>
                <h1 class="album-header-title"><?php echo htmlspecialchars($albumName); ?></h1>
                <?php if (!empty($albumInfo['description'])): ?>
                <p class="album-header-sub"><?php echo htmlspecialchars($albumInfo['description']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Album Info Card -->
    <section class="py-4">
        <div class="container">
            <div class="album-info-card" data-aos="fade-up">
                <div class="album-stats">
                    <?php if (!empty($albumInfo['category'])): ?>
                    <div class="stat-item">
                        <i class="bi bi-tag"></i>
                        <div>
                            <small class="text-muted d-block">Category</small>
                            <strong><?php echo ucfirst(strtolower(htmlspecialchars($albumInfo['category']))); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($albumInfo['city']) || !empty($albumInfo['location'])): ?>
                    <div class="stat-item">
                        <i class="bi bi-geo-alt"></i>
                        <div>
                            <small class="text-muted d-block">Location</small>
                            <strong><?php echo htmlspecialchars($albumInfo['city'] ?: $albumInfo['location']); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($albumInfo['year'])): ?>
                    <div class="stat-item">
                        <i class="bi bi-calendar"></i>
                        <div>
                            <small class="text-muted d-block">Year</small>
                            <strong><?php echo htmlspecialchars($albumInfo['year']); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($albumInfo['products_used'])): ?>
                    <div class="stat-item">
                        <i class="bi bi-box"></i>
                        <div>
                            <small class="text-muted d-block">Products Used</small>
                            <strong><?php echo htmlspecialchars($albumInfo['products_used']); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Image Gallery -->
<section class="py-5">
    <div class="container">
        <div class="gallery-grid">
            <?php
            // Fetch all images in this album
            $sql = "SELECT * FROM project_images 
                    WHERE album = ? 
                    ORDER BY display_order ASC, uploaded_at ASC";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $albumName);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $delay = 0;

                while ($image = $result->fetch_assoc()) {

                    // IMAGE PATH FIX (root → admin/uploads)
                    $imagePathRaw = $image['image_path'] ?? '/assets/images/placeholder-project.jpg';

                    if (strpos($imagePathRaw, 'uploads/') === 0) {
                        $imagePath = '/admin/' . htmlspecialchars($imagePathRaw);
                    } elseif (strpos($imagePathRaw, 'projects/') === 0) {
                        $imagePath = '/admin/uploads/' . htmlspecialchars($imagePathRaw);
                    } else {
                        $imagePath = htmlspecialchars($imagePathRaw);
                    }

                    $title = htmlspecialchars($image['title']);
                    $description = htmlspecialchars($image['description'] ?? '');

                    echo "
                    <a href=\"{$imagePath}\" 
                       class=\"glightbox gallery-item\" 
                       data-gallery=\"album-gallery\" 
                       data-aos=\"zoom-in\" 
                       data-aos-delay=\"{$delay}\">

                        <img src=\"{$imagePath}\" 
                             alt=\"{$title}\" 
                             loading=\"lazy\">

                        <div class=\"gallery-item-overlay\">
                            <h6 class=\"mb-0\">{$title}</h6>";

                    if (!empty($description)) {
                        echo "<small>{$description}</small>";
                    }

                    echo "
                        </div>
                    </a>";

                    $delay += 50;
                    if ($delay > 300) {
                        $delay = 0;
                    }
                }
            } else {
                echo "
                <div class=\"col-12 text-center py-5\">
                    <p class=\"text-muted\">No images found in this album.</p>
                </div>";
            }

            $stmt->close();
            ?>
        </div>
    </div>
</section>


    <?php else: ?>
    <!-- Album Not Found -->
    <section class="py-5" style="margin-top: 100px;">
        <div class="container text-center">
            <h2 class="display-4 mb-4">Album Not Found</h2>
            <p class="lead mb-4">The album you're looking for doesn't exist or has been removed.</p>
            <a href="/projects.php" class="btn btn-success btn-lg">Browse All Projects</a>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($albumInfo): ?>
    <!-- Call to Action -->
    <section class="cta-section py-5 text-white">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-3" data-aos="fade-up">Inspired by This Project?</h2>
            <p class="lead mb-4" data-aos="fade-up" data-aos-delay="100">Let us help you create something similar with our premium materials and expert guidance.</p>
            <div data-aos="fade-up" data-aos-delay="200">
                <a href="/index.php#contact" class="btn btn-light btn-lg me-3">Contact Us</a>
                <a href="/catalog.php" class="btn btn-outline-light btn-lg">View Products</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <?php $conn->close(); ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS Animation JS -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- GLightbox JS -->
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/js/script.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });
        
        // Initialize GLightbox
        const lightbox = GLightbox({
            touchNavigation: true,
            loop: true,
            autoplayVideos: true
        });
    </script>

</body>
</html>