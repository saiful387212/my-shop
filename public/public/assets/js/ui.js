// ============================================================
// FILE: public/assets/js/ui.js
// PURPOSE: UI Interactions for responsive design
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================
    // 1. MOBILE MENU TOGGLE
    // ============================================
    
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const navbar = document.querySelector('.navbar');
    
    if (mobileToggle && navbar) {
        mobileToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            navbar.classList.toggle('active');
            
            // Update aria-expanded
            const isExpanded = this.classList.contains('active');
            this.setAttribute('aria-expanded', isExpanded);
            
            // Prevent body scroll when menu is open
            document.body.style.overflow = isExpanded ? 'hidden' : '';
        });
    }
    
    // Close menu on outside click
    document.addEventListener('click', function(event) {
        if (mobileToggle && navbar) {
            const isClickInside = mobileToggle.contains(event.target) || 
                                 navbar.contains(event.target);
            
            if (!isClickInside && navbar.classList.contains('active')) {
                mobileToggle.classList.remove('active');
                navbar.classList.remove('active');
                mobileToggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }
        }
    });
    
    // ============================================
    // 2. MOBILE USER DROPDOWN
    // ============================================
    
    const userMenu = document.querySelector('.user-menu');
    
    if (userMenu && window.innerWidth < 768) {
        userMenu.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            if (userMenu) {
                userMenu.classList.remove('active');
            }
        });
    }
    
    // ============================================
    // 3. CLOSE MOBILE MENU ON RESIZE
    // ============================================
    
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            if (mobileToggle && navbar) {
                mobileToggle.classList.remove('active');
                navbar.classList.remove('active');
                mobileToggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }
        }
    });
    
    // ============================================
    // 4. SMOOTH SCROLL FOR ANCHOR LINKS
    // ============================================
    
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            
            if (targetId === '#' || targetId === '') return;
            
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                e.preventDefault();
                
                // Close mobile menu if open
                if (mobileToggle && navbar) {
                    mobileToggle.classList.remove('active');
                    navbar.classList.remove('active');
                    mobileToggle.setAttribute('aria-expanded', 'false');
                    document.body.style.overflow = '';
                }
                
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // ============================================
    // 5. PRODUCT CARD TOUCH SUPPORT
    // ============================================
    
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(function(card) {
        // For touch devices: show actions on tap
        card.addEventListener('touchstart', function() {
            const actions = this.querySelector('.product-actions');
            if (actions) {
                // Toggle visibility for touch devices
                if (actions.style.opacity === '1') {
                    actions.style.opacity = '0';
                    actions.style.transform = 'translateX(20px)';
                } else {
                    actions.style.opacity = '1';
                    actions.style.transform = 'translateX(0)';
                }
            }
        });
    });
    
    // ============================================
    // 6. KEYBOARD NAVIGATION FOR DROPDOWN
    // ============================================
    
    const userMenuLinks = document.querySelectorAll('.user-dropdown a');
    
    userMenuLinks.forEach(function(link, index) {
        link.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const dropdown = this.closest('.user-dropdown');
                if (dropdown) {
                    dropdown.style.opacity = '0';
                    dropdown.style.visibility = 'hidden';
                }
            }
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const next = userMenuLinks[index + 1];
                if (next) {
                    next.focus();
                } else {
                    userMenuLinks[0].focus();
                }
            }
            
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prev = userMenuLinks[index - 1];
                if (prev) {
                    prev.focus();
                } else {
                    userMenuLinks[userMenuLinks.length - 1].focus();
                }
            }
        });
    });
    
    // ============================================
    // 7. INTERSECTION OBSERVER FOR ANIMATIONS
    // ============================================
    
    if ('IntersectionObserver' in window) {
        const animatedElements = document.querySelectorAll('.product-card, .category-card, .benefit-card');
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });
        
        animatedElements.forEach(function(el) {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(el);
        });
    }
    
    // ============================================
    // 8. RESPONSIVE TABLE HANDLING
    // ============================================
    
    const responsiveTables = document.querySelectorAll('.table-responsive');
    
    responsiveTables.forEach(function(table) {
        // Wrap table in responsive container
        const wrapper = document.createElement('div');
        wrapper.className = 'table-wrapper';
        wrapper.style.cssText = 'overflow-x: auto; -webkit-overflow-scrolling: touch;';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    });
    
    // ============================================
    // 9. CONSOLE LOG
    // ============================================
    
    console.log('✅ UI enhanced successfully!');
    console.log('📱 Mobile menu: ' + (window.innerWidth < 768 ? 'Active' : 'Desktop'));
    console.log('📐 Screen width: ' + window.innerWidth + 'px');
});