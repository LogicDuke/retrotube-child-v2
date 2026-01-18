/**
 * TMW Global Accordion v5.0 - BULLETPROOF FINAL
 * 100% identical behavior on ALL pages
 *
 * @package RetrotubeChild
 * @version 5.0.0
 */

(function() {
    'use strict';

    // Config
    var CONFIG = {
        readMoreText: 'Read more',
        closeText: 'Close',
        headerOffset: 120,
        animationDelay: 350
    };

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        // Initialize both accordion types
        initCustomAccordions();
        
        // Readmore.js needs a delay to generate its elements
        setTimeout(initReadmoreAccordions, 500);
    }

    /* =============================================
       CUSTOM TMW ACCORDIONS
       Used on: Homepage, Model taxonomy
       ============================================= */
    function initCustomAccordions() {
        var toggles = document.querySelectorAll('.tmw-accordion-toggle');

        toggles.forEach(function(toggle) {
            // Skip if already initialized
            if (toggle.getAttribute('data-tmw-init') === 'done') return;
            toggle.setAttribute('data-tmw-init', 'done');

            // Find elements
            var accordion = toggle.closest('.tmw-accordion');
            if (!accordion) return;

            var content = accordion.querySelector('.tmw-accordion-content');
            if (!content) return;

            var textSpan = toggle.querySelector('.tmw-accordion-text');
            var icon = toggle.querySelector('i, .fa');

            // Click handler
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var isCollapsed = content.classList.contains('more');

                if (isCollapsed) {
                    // EXPAND
                    content.classList.remove('more');
                    if (textSpan) textSpan.textContent = CONFIG.closeText;
                    if (icon) swapIcon(icon, 'up');
                } else {
                    // COLLAPSE
                    content.classList.add('more');
                    if (textSpan) textSpan.textContent = CONFIG.readMoreText;
                    if (icon) swapIcon(icon, 'down');
                    
                    // Scroll to top
                    scrollToElement(accordion);
                }
            });
        });
    }

    /* =============================================
       READMORE.JS ACCORDIONS
       Used on: Video pages, Model single pages
       ============================================= */
    function initReadmoreAccordions() {
        var links = document.querySelectorAll('.video-description a.morelink, a.morelink[data-readmore-toggle], a[data-readmore-toggle].morelink');

        links.forEach(function(link) {
            // Skip if already initialized
            if (link.getAttribute('data-tmw-init') === 'done') return;
            link.setAttribute('data-tmw-init', 'done');

            // Wrap text in span for underline styling
            wrapTextInSpan(link);

            // Ensure icon exists
            ensureIcon(link);

            // Set initial state
            syncState(link);

            // Click handler - DON'T prevent default, let Readmore.js work
            link.addEventListener('click', function() {
                var wasExpanded = link.getAttribute('aria-expanded') === 'true';

                // After Readmore.js animation, sync our state
                setTimeout(function() {
                    syncState(link);

                    // If we just collapsed, scroll up
                    var nowExpanded = link.getAttribute('aria-expanded') === 'true';
                    if (wasExpanded && !nowExpanded) {
                        var container = link.closest('.video-description') || link.parentElement;
                        scrollToElement(container);
                    }
                }, CONFIG.animationDelay);
            });
        });
    }

    /* =============================================
       HELPER FUNCTIONS
       ============================================= */

    /**
     * Swap icon between up and down
     */
    function swapIcon(icon, direction) {
        if (!icon) return;
        
        if (direction === 'up') {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        } else {
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        }
    }

    /**
     * Wrap text nodes in span for styling
     */
    function wrapTextInSpan(link) {
        if (!link) return;
        if (link.querySelector('.tmw-accordion-text')) return;

        var icon = link.querySelector('i, .fa');
        var textContent = '';

        // Collect text nodes
        var textNodes = [];
        link.childNodes.forEach(function(node) {
            if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) {
                textNodes.push(node);
                textContent += node.textContent.trim() + ' ';
            }
        });

        textContent = textContent.trim() || CONFIG.readMoreText;

        // Remove old text nodes
        textNodes.forEach(function(node) {
            node.parentNode.removeChild(node);
        });

        // Create span
        var span = document.createElement('span');
        span.className = 'tmw-accordion-text';
        span.textContent = textContent;

        // Insert before icon or append
        if (icon && icon.parentNode === link) {
            link.insertBefore(span, icon);
        } else {
            link.appendChild(span);
        }
    }

    /**
     * Ensure link has an icon
     */
    function ensureIcon(link) {
        if (!link) return;
        if (link.querySelector('i, .fa')) return;

        var icon = document.createElement('i');
        icon.className = 'fa fa-chevron-down';
        link.appendChild(icon);
    }

    /**
     * Sync Readmore.js link state (text + icon)
     */
    function syncState(link) {
        if (!link) return;

        var isExpanded = link.getAttribute('aria-expanded') === 'true';
        var textSpan = link.querySelector('.tmw-accordion-text');
        var icon = link.querySelector('i, .fa');

        if (textSpan) {
            textSpan.textContent = isExpanded ? CONFIG.closeText : CONFIG.readMoreText;
        }

        if (icon) {
            swapIcon(icon, isExpanded ? 'up' : 'down');
        }
    }

    /**
     * Scroll to element (only if above viewport)
     */
    function scrollToElement(element) {
        if (!element) return;

        var rect = element.getBoundingClientRect();
        
        // Only scroll if element is above viewport
        if (rect.top < 0) {
            var scrollY = window.pageYOffset + rect.top - CONFIG.headerOffset;
            
            window.scrollTo({
                top: scrollY,
                behavior: 'smooth'
            });
        }
    }

    /* =============================================
       MUTATION OBSERVER for dynamic content
       ============================================= */
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function(mutations) {
            var shouldInit = false;
            
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) {
                        if (node.classList && (
                            node.classList.contains('tmw-accordion') ||
                            node.classList.contains('video-description') ||
                            node.querySelector && node.querySelector('.tmw-accordion, .video-description, a.morelink')
                        )) {
                            shouldInit = true;
                        }
                    }
                });
            });

            if (shouldInit) {
                initCustomAccordions();
                setTimeout(initReadmoreAccordions, 100);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

})();
