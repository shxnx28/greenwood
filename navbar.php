<!-- Navigation -->
<nav class="navbar navbar-expand-xl fixed-top navbar-transparent" aria-label="Greenwood Philippines main navigation">
    <div class="container">
        <a class="navbar-brand" href="/" aria-label="Greenwood Philippines – Home">
            <div class="brand-logo">
                <img class="logo-default" src="/assets/images/nobg.webp" alt="Greenwood Philippines Logo – Wall, Floor & Ceiling Solutions" width="30" height="30">
                <img class="logo-white" src="/assets/images/whitenobg.webp" alt="Greenwood Philippines Logo – Wall, Floor & Ceiling Solutions" width="30" height="30">
                <div class="brand-text">
                    <strong>GREENWOOD</strong>
                    <span>PHILIPPINES</span>
                </div>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/#home">HOME</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/#products">PRODUCTS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/#projects">PROJECTS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/#influencers">INFLUENCERS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/#about">ABOUT</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/#locations">CONTACT</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<script>
(function () {
    var nav = document.querySelector('.navbar');
    var path = window.location.pathname;
    var isHome = path === '/' || path === '/index.php';
    var hasDarkHero = isHome || path === '/faq.php';

    if (!hasDarkHero) {
        nav.classList.add('scrolled');
        nav.classList.remove('navbar-transparent');
        return;
    }

    function onScroll() {
        if (window.scrollY > 60) {
            nav.classList.add('scrolled');
            nav.classList.remove('navbar-transparent');
        } else {
            nav.classList.remove('scrolled');
            nav.classList.add('navbar-transparent');
        }
    }
    if (hasDarkHero) {
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }
})();
</script>