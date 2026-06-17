// ================================================
// Greenwood Philippines — script.js (optimised)
// ================================================

// ── AOS INIT (deferred to after first paint to avoid forced reflow)
function initAOS() {
    if (typeof AOS === 'undefined') return;
    AOS.init({
        duration: 700,
        easing: 'ease-out',
        once: true,
        offset: 60,
        startEvent: 'DOMContentLoaded'
    });
}
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        requestAnimationFrame(function() { setTimeout(initAOS, 0); });
    });
} else {
    requestAnimationFrame(function() { setTimeout(initAOS, 0); });
}

// ── THROTTLE HELPER (limits scroll handlers to 60fps max)
function throttle(fn, wait) {
    var last = 0;
    return function() {
        var now = Date.now();
        if (now - last >= wait) { last = now; fn.apply(this, arguments); }
    };
}

// ── NAVBAR SCROLL EFFECT (throttled)
var navbar = document.querySelector('.navbar');
window.addEventListener('scroll', throttle(function() {
    if (!navbar) return;
    var scrolled = window.scrollY > 10;
    navbar.classList.toggle('scrolled', scrolled);
    navbar.classList.toggle('navbar-transparent', !scrolled);
}, 16), { passive: true });

// ── ACTIVE NAV LINK (throttled, passive)
var sectionOffsets = [];
function cacheSectionOffsets() {
    sectionOffsets = [];
    document.querySelectorAll('section[id]').forEach(function(section) {
        sectionOffsets.push({ id: section.id, top: section.offsetTop });
    });
}
window.addEventListener('load', function() {
    requestAnimationFrame(function() {
        setTimeout(cacheSectionOffsets, 100);
    });
});
window.addEventListener('resize', throttle(cacheSectionOffsets, 200));

window.addEventListener('scroll', throttle(function() {
    var current = '';
    var scrollY = window.scrollY;
    for (var i = 0; i < sectionOffsets.length; i++) {
        if (scrollY >= sectionOffsets[i].top - 150) current = sectionOffsets[i].id;
    }
    document.querySelectorAll('.navbar-nav .nav-link').forEach(function(link) {
        var href = link.getAttribute('href') || '';
        link.classList.toggle('active', href === '#' + current || href.endsWith('#' + current));
    });
}, 100), { passive: true });

// ── SMOOTH SCROLLING
document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (!target) return;
        e.preventDefault();
        var top = target.getBoundingClientRect().top + window.scrollY - 80;
        window.scrollTo({ top: top, behavior: 'smooth' });
        var navCollapse = document.querySelector('.navbar-collapse');
        if (navCollapse && navCollapse.classList.contains('show')) {
            new bootstrap.Collapse(navCollapse, { toggle: true });
        }
    });
});

// ── COUNTER ANIMATION
function animateCounter(element, target, duration) {
    duration = duration || 1800;
    var startTime = null;
    function step(currentTime) {
        if (!startTime) startTime = currentTime;
        var progress = Math.min((currentTime - startTime) / duration, 1);
        var val = Math.floor(progress * target);
        if (target === 1)        element.textContent = '#1';
        else if (target === 100) element.textContent = val + '%';
        else if (target === 24)  element.textContent = '24/7';
        else                     element.textContent = val + '+';
        if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
}

var counterObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
        if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
            animateCounter(entry.target, parseInt(entry.target.getAttribute('data-target')));
            entry.target.classList.add('counted');
        }
    });
}, { threshold: 0.5 });

document.querySelectorAll('.stat-number').forEach(function(stat) {
    counterObserver.observe(stat);
});

// ── NATIVE LAZY LOAD FALLBACK
if (!('loading' in HTMLImageElement.prototype) && 'IntersectionObserver' in window) {
    var imgObserver = new IntersectionObserver(function(entries, observer) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                var img = entry.target;
                if (img.dataset.src) img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });
    document.querySelectorAll('img[loading="lazy"]').forEach(function(img) {
        imgObserver.observe(img);
    });
}

// ── SCROLL TO TOP BUTTON
(function() {
    var btn = document.createElement('button');
    btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>';
    btn.className = 'scroll-to-top';
    btn.setAttribute('aria-label', 'Scroll to top');
    document.body.appendChild(btn);

    window.addEventListener('scroll', throttle(function() {
        btn.style.opacity = window.pageYOffset > 300 ? '1' : '0';
        btn.style.pointerEvents = window.pageYOffset > 300 ? 'auto' : 'none';
    }, 200), { passive: true });

    btn.addEventListener('click', function() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();

// ── MODAL SCROLLBAR FIX
(function() {
    var style = document.createElement('style');
    style.textContent = 'body.modal-open{overflow-y:scroll!important;padding-right:0!important}.modal-open .navbar,.modal-open .fixed-top{padding-right:0!important}.modal{padding-right:0!important}';
    document.head.appendChild(style);

    var mo = new MutationObserver(function(mutations) {
        mutations.forEach(function(m) {
            if (m.type === 'attributes' && m.attributeName === 'style') {
                var el = m.target;
                if (el.style.paddingRight && el.style.paddingRight !== '0px') {
                    el.style.paddingRight = '0px';
                }
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        mo.observe(document.body, { attributes: true, attributeFilter: ['style'] });
        document.querySelectorAll('.modal').forEach(function(modal) {
            mo.observe(modal, { attributes: true, attributeFilter: ['style'] });
        });
        if (navbar) mo.observe(navbar, { attributes: true, attributeFilter: ['style'] });

        document.querySelectorAll('.btn-location-contact').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
            });
        });
    });
})();

// ── CAROUSEL: MOBILE 1-PER-SLIDE + COUNTER NAV + HEIGHT LOCK + DOT SCROLL
// On mobile (< 768px):
//   - Re-chunks carousel to 1-card-per-slide
//   - Replaces dot indicators with a "2 / 10" counter + progress bar
// On desktop: 3-per-slide with standard dot indicators.
(function() {

    var MOBILE_BP = 768;

    // ── INJECT COUNTER into .carousel-nav-below (once per carousel) ──
    function injectCounter(carouselEl) {
        if (!carouselEl) return;
        var nav = carouselEl.closest('section, .container');
        if (!nav) nav = carouselEl.parentElement;
        var navBelow = nav ? nav.querySelector('.carousel-nav-below') : null;
        if (!navBelow) return;
        if (navBelow.querySelector('.carousel-counter')) return; // already injected

        var counter = document.createElement('div');
        counter.className = 'carousel-counter';
        counter.innerHTML =
            '<div class="carousel-counter-text"><span class="current">1</span> / <span class="total">1</span></div>';

        // Insert between the dots container and next button
        var dotsEl = navBelow.querySelector('.project-dots');
        if (dotsEl) {
            navBelow.insertBefore(counter, dotsEl.nextSibling);
        } else {
            var nextBtn = navBelow.querySelector('.carousel-control-next');
            if (nextBtn) navBelow.insertBefore(counter, nextBtn);
            else navBelow.appendChild(counter);
        }
    }

    // ── UPDATE COUNTER ────────────────────────────────────────────
    function updateCounter(carouselEl, currentIndex, total) {
        var nav = carouselEl.closest('section, .container');
        if (!nav) nav = carouselEl.parentElement;
        var navBelow = nav ? nav.querySelector('.carousel-nav-below') : null;
        if (!navBelow) return;
        var counter = navBelow.querySelector('.carousel-counter');
        if (!counter) return;

        counter.querySelector('.current').textContent = currentIndex + 1;
        counter.querySelector('.total').textContent   = total;
    }

    // ── REBUILD SLIDES ────────────────────────────────────────────
    function rebuildSlides(carouselEl, perSlide) {
        if (!carouselEl) return;
        var inner = carouselEl.querySelector('.carousel-inner');
        if (!inner) return;

        var allCols = Array.from(inner.querySelectorAll('.carousel-item .col-md-4'));
        if (!allCols.length) return;

        var bsInstance = bootstrap.Carousel.getInstance(carouselEl);
        if (bsInstance) bsInstance.dispose();

        var existingItems = Array.from(inner.querySelectorAll('.carousel-item'));
        existingItems.forEach(function(item) { item.remove(); });

        for (var i = 0; i < allCols.length; i += perSlide) {
            var chunk = allCols.slice(i, i + perSlide);
            var item  = document.createElement('div');
            item.className = 'carousel-item' + (i === 0 ? ' active' : '');

            var row = document.createElement('div');
            row.className = 'row g-4 justify-content-center';

            chunk.forEach(function(col) {
                col.style.cssText = '';
                row.appendChild(col);
            });

            item.appendChild(row);
            inner.appendChild(item);
        }

        var totalSlides = Math.ceil(allCols.length / perSlide);
        rebuildDots(carouselEl, totalSlides);
        updateCounter(carouselEl, 0, totalSlides);

        new bootstrap.Carousel(carouselEl, { ride: false, wrap: true });
        bindDotScroll(carouselEl);
        bindCounterSync(carouselEl, totalSlides);

        // Notify index.php counter binding about the new total
        carouselEl.dispatchEvent(new CustomEvent('carousel:rebuilt', { detail: { total: totalSlides } }));
    }

    // ── REBUILD DOTS ──────────────────────────────────────────────
    function rebuildDots(carouselEl, slideCount) {
        var nav = carouselEl.closest('section, .container');
        var dotsContainer = nav ? nav.querySelector('.project-dots') : null;
        if (!dotsContainer) {
            var navBelow = carouselEl.querySelector('.carousel-nav-below');
            if (navBelow) dotsContainer = navBelow.querySelector('.project-dots');
        }
        if (!dotsContainer) return;

        dotsContainer.innerHTML = '';
        for (var i = 0; i < slideCount; i++) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('data-bs-target', '#' + carouselEl.id);
            btn.setAttribute('data-bs-slide-to', i);
            btn.setAttribute('aria-label', 'Slide ' + (i + 1));
            if (i === 0) btn.classList.add('active');
            dotsContainer.appendChild(btn);
        }

        var navBelowEl = nav ? nav.querySelector('.carousel-nav-below') : null;
        if (!navBelowEl) navBelowEl = carouselEl.querySelector('.carousel-nav-below');
        if (navBelowEl) navBelowEl.style.display = slideCount <= 1 ? 'none' : '';
    }

    // ── BIND COUNTER SYNC ─────────────────────────────────────────
    function bindCounterSync(carouselEl, total) {
        // Use a named handler stored on the element to avoid stacking listeners
        if (carouselEl._counterHandler) {
            carouselEl.removeEventListener('slide.bs.carousel', carouselEl._counterHandler);
        }
        carouselEl._counterHandler = function(e) {
            updateCounter(carouselEl, e.to, total);
        };
        carouselEl.addEventListener('slide.bs.carousel', carouselEl._counterHandler);
    }

    // ── LOCK HEIGHT ───────────────────────────────────────────────
    function lockCarouselHeight(carouselEl) {
        if (!carouselEl) return;
        var inner = carouselEl.querySelector('.carousel-inner');
        if (!inner) return;

        inner.style.height = '';
        var items = Array.from(inner.querySelectorAll('.carousel-item'));
        var maxH = 0;

        items.forEach(function(item) {
            item.style.position   = 'relative';
            item.style.visibility = 'visible';
            item.style.opacity    = '0';
            item.style.display    = 'block';
        });

        inner.getBoundingClientRect();
        items.forEach(function(item) { maxH = Math.max(maxH, item.offsetHeight); });

        items.forEach(function(item) {
            item.style.position   = '';
            item.style.visibility = '';
            item.style.opacity    = '';
            item.style.display    = '';
        });

        if (maxH > 0) inner.style.height = maxH + 'px';
    }

    // ── DOT SCROLL ────────────────────────────────────────────────
    function bindDotScroll(carouselEl) {
        if (!carouselEl) return;
        if (carouselEl._dotScrollHandler) {
            carouselEl.removeEventListener('slide.bs.carousel', carouselEl._dotScrollHandler);
        }
        carouselEl._dotScrollHandler = function(e) {
            var section = carouselEl.closest('section, .container');
            var dotsContainer = section ? section.querySelector('.project-dots') : null;
            if (!dotsContainer) {
                var nav = carouselEl.querySelector('.carousel-nav-below');
                if (nav) dotsContainer = nav.querySelector('.project-dots');
            }
            if (!dotsContainer) return;
            var targetDot = dotsContainer.querySelectorAll('button')[e.to];
            if (!targetDot) return;
            var cRect = dotsContainer.getBoundingClientRect();
            var dRect = targetDot.getBoundingClientRect();
            dotsContainer.scrollLeft += dRect.left - cRect.left - (cRect.width / 2) + (dRect.width / 2);
        };
        carouselEl.addEventListener('slide.bs.carousel', carouselEl._dotScrollHandler);
    }

    // ── APPLY LAYOUT ──────────────────────────────────────────────
    function applyLayout(carouselEl) {
        if (!carouselEl) return;
        var isMobile = window.innerWidth < MOBILE_BP;
        injectCounter(carouselEl);
        rebuildSlides(carouselEl, isMobile ? 1 : 3);
        setTimeout(function() { lockCarouselHeight(carouselEl); }, 80);
    }

    document.addEventListener('DOMContentLoaded', function() {
        var projectsCarousel   = document.getElementById('projectsCarousel');
        var influencerCarousel = document.getElementById('influencerCarousel');

        applyLayout(projectsCarousel);
        applyLayout(influencerCarousel);

        window.addEventListener('load', function() {
            setTimeout(function() {
                lockCarouselHeight(projectsCarousel);
                lockCarouselHeight(influencerCarousel);
            }, 150);
        });

        var resizeTimer;
        var lastWasMobile = window.innerWidth < MOBILE_BP;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                var nowMobile = window.innerWidth < MOBILE_BP;
                if (nowMobile !== lastWasMobile) {
                    lastWasMobile = nowMobile;
                    applyLayout(projectsCarousel);
                    applyLayout(influencerCarousel);
                } else {
                    lockCarouselHeight(projectsCarousel);
                    lockCarouselHeight(influencerCarousel);
                }
            }, 200);
        });
    });

})();

// ── COPY TO CLIPBOARD
function copyToClipboard(text, button) {
    var originalHTML = button.innerHTML;
    var doSuccess = function() {
        button.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Copied!';
        button.classList.remove('btn-outline-success', 'btn-outline-secondary');
        button.classList.add('btn-success');
        setTimeout(function() {
            button.innerHTML = originalHTML;
            button.classList.remove('btn-success');
            button.classList.add(originalHTML.includes('Number') ? 'btn-outline-success' : 'btn-outline-secondary');
        }, 2000);
    };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(doSuccess).catch(function() { fallbackCopy(text, doSuccess); });
    } else {
        fallbackCopy(text, doSuccess);
    }
}

function fallbackCopy(text, callback) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0;';
    document.body.appendChild(ta);
    ta.focus(); ta.select();
    try { document.execCommand('copy'); callback(); }
    catch(e) { alert('Could not copy. Please copy manually: ' + text); }
    document.body.removeChild(ta);
}

// ── VISIT US PARALLAX
(function() {
    var bgEl = document.querySelector('.visit-us-parallax .visit-us-bg-image');
    if (!bgEl) return;
    var section = bgEl.closest('.visit-us-parallax');

    function updateParallax() {
        var rect = section.getBoundingClientRect();
        var winH = window.innerHeight;
        var isMobile = window.innerWidth <= 767;
        var triggerStart = winH;
        var triggerEnd   = winH * 0.2;
        var pos = rect.top;
        var progress = (triggerStart - pos) / (triggerStart - triggerEnd);
        progress = Math.max(0, Math.min(1, progress));
        bgEl.style.transform = 'translateY(' + ((1 - progress) * 100) + '%)';
    }

    window.addEventListener('scroll', updateParallax, { passive: true });
    updateParallax();
})()

// ── END OF SCRIPT