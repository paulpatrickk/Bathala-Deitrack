document.addEventListener("DOMContentLoaded", function () {

    // ===============================
    // FADE-IN ANIMATION
    // ===============================
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add("show");
            }
        });
    });

    document.querySelectorAll(".fade-in").forEach(el => observer.observe(el));


    // ===============================
    // NAVBAR SCROLL + ACTIVE LINKS
    // ===============================
    const navbar = document.querySelector('.navbar');
    const sections = document.querySelectorAll('section');
    const navLinks = document.querySelectorAll('.nav-link');

    window.addEventListener('scroll', () => {

        // Navbar shadow
        if (navbar) {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }

        // Active nav link
        let current = '';
        sections.forEach(section => {
            if (window.scrollY >= section.offsetTop - 100) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${current}`) {
                link.classList.add('active');
            }
        });

    });


    // ===============================
    // LOGIN MODAL CLICK OUTSIDE
    // ===============================
    const modal = document.getElementById('loginModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });
    }


    // ===============================
    // HERO FLOATING ROLLS
    // ===============================
    const layer = document.getElementById('rollLayer');

    if (layer) {
        const rolls = [
            {size:90, top:8,  left:5,  delay:0,   dur:6},
            {size:60, top:15, left:80, delay:1,   dur:7},
            {size:110,top:55, left:88, delay:2,   dur:8},
            {size:75, top:70, left:2,  delay:0.5, dur:6.5},
            {size:50, top:35, left:92, delay:1.5, dur:5.5},
            {size:80, top:78, left:50, delay:3,   dur:7.5},
            {size:45, top:5,  left:50, delay:2.5, dur:5},
            {size:65, top:60, left:72, delay:0.8, dur:9},
            
            
        ];

        rolls.forEach(r => {
            const d = document.createElement('div');
            d.className = 'roll';
            d.style.cssText = `
                width:${r.size}px;
                height:${r.size}px;
                top:${r.top}%;
                left:${r.left}%;
                animation-delay:${r.delay}s;
                animation-duration:${r.dur}s;
            `;
            layer.appendChild(d);
        });
    }

});

