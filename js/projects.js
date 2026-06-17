// Projects Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS
    AOS.init({
        duration: 800,
        once: true,
        offset: 100
    });

    // Load featured projects for homepage carousel
    if (document.getElementById('projectsCarousel')) {
        loadFeaturedProjects();
    }

    // Filter functionality
    const filterButtons = document.querySelectorAll('[data-filter]');
    const projectItems = document.querySelectorAll('.project-item');
    const projectCount = document.getElementById('projectCount');

    if (filterButtons.length > 0) {
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                const filterValue = this.getAttribute('data-filter');
                
                // Update active button
                filterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Filter projects
                let visibleCount = 0;
                projectItems.forEach(item => {
                    const category = item.getAttribute('data-category');
                    
                    if (filterValue === 'all' || category === filterValue) {
                        item.classList.remove('hidden');
                        item.style.display = 'block';
                        visibleCount++;
                        
                        // Re-trigger AOS animation
                        setTimeout(() => {
                            item.classList.add('aos-animate');
                        }, 50);
                    } else {
                        item.classList.add('hidden');
                        setTimeout(() => {
                            item.style.display = 'none';
                        }, 300);
                    }
                });
                
                // Update count
                if (projectCount) {
                    projectCount.textContent = visibleCount;
                }
            });
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    const offsetTop = target.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    // Add lazy loading for project images
    const lazyImages = document.querySelectorAll('.project-image-full, .project-image');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const image = entry.target;
                    image.classList.add('loaded');
                    observer.unobserve(image);
                }
            });
        });

        lazyImages.forEach(image => {
            imageObserver.observe(image);
        });
    }

    // Auto-play carousel with pause on hover
    const carousel = document.querySelector('#projectsCarousel');
    if (carousel) {
        const bsCarousel = new bootstrap.Carousel(carousel, {
            interval: 5000,
            wrap: true,
            pause: 'hover'
        });
    }

    // Add scroll-to-top button functionality
    const scrollBtn = createScrollToTopButton();
    
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            scrollBtn.classList.add('show');
        } else {
            scrollBtn.classList.remove('show');
        }
    });

    scrollBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});

// Load Featured Projects for Homepage Carousel
async function loadFeaturedProjects() {
    try {
        // Fetch projects from API
        const response = await fetch('api/api_project_images.php');
        const data = await response.json();
        
        if (data.success && data.data && data.data.length > 0) {
            // Filter featured projects or take first 9
            let projects = data.data.filter(p => p.is_featured == 1).slice(0, 9);
            
            // If less than 9 featured, fill with non-featured
            if (projects.length < 9) {
                const nonFeatured = data.data.filter(p => p.is_featured != 1).slice(0, 9 - projects.length);
                projects = [...projects, ...nonFeatured];
            }
            
            // Build carousel
            if (projects.length > 0) {
                buildProjectsCarousel(projects);
            } else {
                showNoProjectsMessage();
            }
        } else {
            showNoProjectsMessage();
        }
    } catch (error) {
        console.error('Error loading projects:', error);
        showNoProjectsMessage();
    }
}

function buildProjectsCarousel(projects) {
    const carouselInner = document.querySelector('#projectsCarousel .carousel-inner');
    const carouselIndicators = document.querySelector('#projectsCarousel .carousel-indicators');
    
    if (!carouselInner) return;
    
    // Clear existing content
    carouselInner.innerHTML = '';
    if (carouselIndicators) carouselIndicators.innerHTML = '';
    
    // Split into slides of 3
    const slides = [];
    for (let i = 0; i < projects.length; i += 3) {
        slides.push(projects.slice(i, i + 3));
    }
    
    // Build slides
    slides.forEach((slide, slideIndex) => {
        // Create indicator button
        if (carouselIndicators && slides.length > 1) {
            const indicator = document.createElement('button');
            indicator.type = 'button';
            indicator.setAttribute('data-bs-target', '#projectsCarousel');
            indicator.setAttribute('data-bs-slide-to', slideIndex);
            if (slideIndex === 0) indicator.classList.add('active');
            carouselIndicators.appendChild(indicator);
        }
        
        // Create carousel item
        const carouselItem = document.createElement('div');
        carouselItem.className = 'carousel-item' + (slideIndex === 0 ? ' active' : '');
        
        const row = document.createElement('div');
        row.className = 'row g-4';
        
        // Add projects to slide
        slide.forEach(project => {
            const col = document.createElement('div');
            col.className = 'col-md-4';
            
            const imagePath = project.image_path || 'assets/images/placeholder-project.jpg';
            const title = escapeHtml(project.title || 'Untitled Project');
            const productsUsed = escapeHtml(project.products_used || '');
            
            col.innerHTML = `
                <div class="project-card">
                    <div class="project-image" style="background-image: url('${imagePath}');">
                        <div class="project-overlay">
                            <h4>${title}</h4>
                            ${productsUsed ? `<p>${productsUsed}</p>` : ''}
                        </div>
                    </div>
                </div>
            `;
            
            row.appendChild(col);
        });
        
        carouselItem.appendChild(row);
        carouselInner.appendChild(carouselItem);
    });
    
    // Show/hide carousel controls based on number of slides
    const prevBtn = document.querySelector('#projectsCarousel .carousel-control-prev');
    const nextBtn = document.querySelector('#projectsCarousel .carousel-control-next');
    
    if (slides.length <= 1) {
        if (prevBtn) prevBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'none';
        if (carouselIndicators) carouselIndicators.style.display = 'none';
    } else {
        if (prevBtn) prevBtn.style.display = 'flex';
        if (nextBtn) nextBtn.style.display = 'flex';
        if (carouselIndicators) carouselIndicators.style.display = 'flex';
    }
    
    // Re-initialize carousel after dynamic content load
    const carousel = document.querySelector('#projectsCarousel');
    if (carousel && typeof bootstrap !== 'undefined') {
        new bootstrap.Carousel(carousel, {
            interval: 5000,
            wrap: true,
            pause: 'hover'
        });
    }
    
    // Re-initialize AOS for new content
    if (typeof AOS !== 'undefined') {
        AOS.refresh();
    }
}

function showNoProjectsMessage() {
    const carouselInner = document.querySelector('#projectsCarousel .carousel-inner');
    if (!carouselInner) return;
    
    carouselInner.innerHTML = `
        <div class="carousel-item active">
            <div class="row g-4">
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No featured projects available. Check our <a href="projects.php">projects page</a> for all listings.</p>
                </div>
            </div>
        </div>
    `;
    
    // Hide controls
    const prevBtn = document.querySelector('#projectsCarousel .carousel-control-prev');
    const nextBtn = document.querySelector('#projectsCarousel .carousel-control-next');
    const indicators = document.querySelector('#projectsCarousel .carousel-indicators');
    
    if (prevBtn) prevBtn.style.display = 'none';
    if (nextBtn) nextBtn.style.display = 'none';
    if (indicators) indicators.style.display = 'none';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Create scroll-to-top button
function createScrollToTopButton() {
    const button = document.createElement('button');
    button.innerHTML = '↑';
    button.className = 'scroll-to-top';
    button.setAttribute('aria-label', 'Scroll to top');
    document.body.appendChild(button);
    
    // Add styles
    const style = document.createElement('style');
    style.textContent = `
        .scroll-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #2d5016;
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .scroll-to-top.show {
            opacity: 1;
            visibility: visible;
        }
        
        .scroll-to-top:hover {
            background-color: #4a8028;
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 768px) {
            .scroll-to-top {
                width: 45px;
                height: 45px;
                bottom: 20px;
                right: 20px;
                font-size: 20px;
            }
        }
    `;
    document.head.appendChild(style);
    
    return button;
}

// Project card hover effect enhancement
document.addEventListener('DOMContentLoaded', function() {
    const projectCards = document.querySelectorAll('.project-card-full');
    
    projectCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.borderColor = '#2d5016';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.borderColor = 'transparent';
        });
    });
});