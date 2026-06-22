<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <?php
    // Database connection
    require_once 'admin/db.php';
    
    // Custom header image path - CHANGE THIS TO YOUR DESIRED IMAGE
    $headerImagePath = 'assets/images/project_cover.webp'; // Change this path as needed
    
    $pageTitle = 'Our Projects';
    ?>
    
    <title>Finished Projects | Greenwood Philippines</title>

    <!-- Primary SEO Meta Tags -->
    <meta name="description" content="Browse real installation projects by Greenwood Philippines. See how our wall panels, flooring, ceiling systems, and fence materials transform Filipino homes and commercial spaces.">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Greenwood Philippines">
    <link rel="canonical" href="https://greenwoodphilippines.com/projects.php">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://greenwoodphilippines.com/projects.php">
    <meta property="og:title" content="Our Projects | Greenwood Philippines">
    <meta property="og:description" content="See real installations using Greenwood Philippines materials – wall panels, flooring, ceiling systems, and fence solutions for homes and businesses.">
    <meta property="og:image" content="https://greenwoodphilippines.com/assets/images/project_cover.webp">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Greenwood Philippines – Real Project Installations">
    <meta property="og:site_name" content="Greenwood Philippines">
    <meta property="og:locale" content="en_PH">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Our Projects | Greenwood Philippines">
    <meta name="twitter:description" content="See real wall, floor, ceiling, and fence installations by Greenwood Philippines.">
    <meta name="twitter:image" content="https://greenwoodphilippines.com/assets/images/project_cover.webp">
    <meta name="twitter:image:alt" content="Greenwood Philippines – Real Project Installations">

    <!-- Schema.org Structured Data -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "CollectionPage",
      "name": "Our Projects – Greenwood Philippines",
      "description": "Browse real installation projects showcasing Greenwood Philippines wall, floor, ceiling, and fence materials.",
      "url": "https://greenwoodphilippines.com/projects.php",
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
    
    <!-- GLightbox CSS for image gallery -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    
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

        /* Pre-apply scrolled state before JS runs to prevent flash */
        .navbar {
            background: rgba(255, 255, 255, 0.97) !important;
            box-shadow: 0 2px 20px rgba(0,0,0,0.10) !important;
        }
        .navbar .navbar-brand { opacity: 1 !important; pointer-events: auto !important; }
        .navbar .navbar-nav .nav-link { color: var(--green-dark, #303823) !important; }

        .album-header-bg {
            position: absolute;
            inset: -20px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: blur(14px);
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
            text-decoration: none;
            display: block;
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
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 20px 15px 15px 15px;
            color: white;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .gallery-item:hover .gallery-item-overlay {
            opacity: 1;
        }
        
        .album-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(45, 80, 22, 0.9);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
            z-index: 5;
        }
        
        .image-count {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(0, 0, 0, 0.75);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 5px;
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

        /* Filter buttons — 2-col grid, lone item centered */
        .filter-section .d-flex {
            display: grid !important;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .filter-section .filter-btn:first-child { grid-column: 1 / -1; }
        .filter-btn:last-child:nth-child(even) { grid-column: 1 / -1; justify-self: center; width: 50%; }
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

    <!-- Projects Header with Background Image -->
    <section class="album-header">
        <div class="album-header-bg"></div>
        <div class="album-header-overlay"></div>
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <div class="album-header-logo">
                    <img src="assets/images/nobg.webp" alt="Greenwood Philippines">
                </div>
                <h1 class="album-header-title">Our <span>Projects</span></h1>
                <p class="album-header-sub">Discover our portfolio of exceptional landscaping and outdoor projects</p>
            </div>
        </div>
    </section>

    <!-- Project Stats Info Card -->
    <section class="py-4">
        <div class="container">
            <div class="album-info-card" data-aos="fade-up">
                <div class="album-stats">
                    <?php
                    // Get total number of albums
                    $albumCountQuery = "SELECT COUNT(DISTINCT album) as total FROM project_images";
                    $albumCountResult = $conn->query($albumCountQuery);
                    $totalAlbums = 0;
                    if ($albumCountResult && $albumCountResult->num_rows > 0) {
                        $totalAlbums = $albumCountResult->fetch_assoc()['total'];
                    }
                    
                    // Get total number of images
                    $imageCountQuery = "SELECT COUNT(*) as total FROM project_images";
                    $imageCountResult = $conn->query($imageCountQuery);
                    $totalImages = 0;
                    if ($imageCountResult && $imageCountResult->num_rows > 0) {
                        $totalImages = $imageCountResult->fetch_assoc()['total'];
                    }
                    
                    // Get unique categories
                    $categoryQuery = "SELECT COUNT(DISTINCT category) as total FROM project_images WHERE category IS NOT NULL AND category != ''";
                    $categoryResult = $conn->query($categoryQuery);
                    $totalCategories = 0;
                    if ($categoryResult && $categoryResult->num_rows > 0) {
                        $totalCategories = $categoryResult->fetch_assoc()['total'];
                    }
                    ?>
                    
                    <div class="stat-item">
                        <i class="bi bi-folder"></i>
                        <div>
                            <small class="text-muted d-block">Total Projects</small>
                            <strong><?php echo $totalAlbums; ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <i class="bi bi-image"></i>
                        <div>
                            <small class="text-muted d-block">Total Images</small>
                            <strong><?php echo $totalImages; ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <i class="bi bi-tag"></i>
                        <div>
                            <small class="text-muted d-block">Categories</small>
                            <strong><?php echo $totalCategories; ?></strong>
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
                <button class="filter-btn active" data-filter="all">All Projects</button>
                <?php
                // Get unique categories for filter buttons
                $categoryFilterQuery = "SELECT DISTINCT category FROM project_images WHERE category IS NOT NULL AND category != '' ORDER BY category";
                $categoryFilterResult = $conn->query($categoryFilterQuery);
                
                if ($categoryFilterResult && $categoryFilterResult->num_rows > 0) {
                    while ($cat = $categoryFilterResult->fetch_assoc()) {
                        $category = htmlspecialchars($cat['category']);
                        $categoryLower = strtolower($category);
                        echo "<button class=\"filter-btn\" data-filter=\"{$categoryLower}\">{$category}</button>";
                    }
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Projects Gallery Grid -->
    <section class="py-5">
        <div class="container">
            <div class="gallery-grid">
                <?php
                // Fetch all albums with their first image
                $sql = "SELECT 
                            album,
                            MIN(image_path) as first_image,
                            MIN(title) as album_title,
                            MIN(description) as album_description,
                            MIN(category) as category,
                            MIN(city) as city,
                            MIN(location) as location,
                            MIN(year) as year,
                            COUNT(*) as image_count
                        FROM project_images 
                        GROUP BY album 
                        ORDER BY MIN(uploaded_at) DESC";

                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    $delay = 0;

                    while ($album = $result->fetch_assoc()) {
                        $albumName = htmlspecialchars($album['album']);
                        $albumTitle = htmlspecialchars($album['album_title'] ?: $albumName);
                        $albumDescription = htmlspecialchars($album['album_description'] ?? '');
                        $category = htmlspecialchars($album['category'] ?? '');
                        $categoryClass = strtolower($category);
                        $location = htmlspecialchars($album['city'] ?: $album['location'] ?: '');
                        $year = htmlspecialchars($album['year'] ?? '');
                        $imageCount = $album['image_count'];

                        // IMAGE PATH FIX
                        $imagePathRaw = $album['first_image'] ?? '/assets/images/placeholder-project.jpg';

                        if (strpos($imagePathRaw, 'uploads/') === 0) {
                            $imagePath = '/admin/' . htmlspecialchars($imagePathRaw);
                        } elseif (strpos($imagePathRaw, 'projects/') === 0) {
                            $imagePath = '/admin/uploads/' . htmlspecialchars($imagePathRaw);
                        } else {
                            $imagePath = htmlspecialchars($imagePathRaw);
                        }

                        echo "
                        <a href=\"/view_album.php?album=" . urlencode($albumName) . "\" 
                           class=\"gallery-item album-item\" 
                           data-category=\"{$categoryClass}\"
                           data-aos=\"zoom-in\" 
                           data-aos-delay=\"{$delay}\">

                            <img src=\"{$imagePath}\" 
                                 alt=\"{$albumTitle}\" 
                                 loading=\"lazy\">

                            <div class=\"image-count\">
                                <i class=\"bi bi-images\"></i>
                                <span>{$imageCount}</span>
                            </div>";

                        if (!empty($category)) {
                            echo "<div class=\"album-badge\">{$category}</div>";
                        }

                        echo "
                            <div class=\"gallery-item-overlay\">
                                <h5 class=\"mb-2 fw-bold\">{$albumTitle}</h5>";

                        if (!empty($albumDescription)) {
                            echo "<p class=\"mb-2 small\">{$albumDescription}</p>";
                        }

                        if (!empty($location) || !empty($year)) {
                            echo "<div class=\"small\">";
                            if (!empty($location)) {
                                echo "<i class=\"bi bi-geo-alt me-1\"></i>{$location}";
                            }
                            if (!empty($location) && !empty($year)) {
                                echo " • ";
                            }
                            if (!empty($year)) {
                                echo "<i class=\"bi bi-calendar me-1\"></i>{$year}";
                            }
                            echo "</div>";
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
                        <i class=\"bi bi-folder-x\" style=\"font-size: 4rem; color: #ccc;\"></i>
                        <p class=\"text-muted mt-3\">No projects found.</p>
                    </div>";
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta-section py-5 text-white">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-3" data-aos="fade-up">Ready to Start Your Project?</h2>
            <p class="lead mb-4" data-aos="fade-up" data-aos-delay="100">Let us help you bring your outdoor vision to life with our premium materials and expert guidance.</p>
            <div data-aos="fade-up" data-aos-delay="200">
                <a href="/index.php#locations" class="btn btn-light btn-lg me-3">Contact Us</a>
                <a href="/catalog.php" class="btn btn-outline-light btn-lg">View Products</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
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
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });
        
        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const albumItems = document.querySelectorAll('.album-item');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Update active button
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filterValue = this.getAttribute('data-filter');
                    
                    // Filter albums
                    albumItems.forEach(item => {
                        const itemCategory = item.getAttribute('data-category');
                        
                        if (filterValue === 'all' || itemCategory === filterValue) {
                            item.style.display = 'block';
                            // Re-trigger animation
                            item.classList.remove('aos-animate');
                            setTimeout(() => {
                                item.classList.add('aos-animate');
                            }, 10);
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