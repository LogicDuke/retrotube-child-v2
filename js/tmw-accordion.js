/**
 * TMW Global Accordion JavaScript v3.0
 * Unified accordion behavior for ALL pages
 * 
 * Features:
 * - TMW custom accordion (home page, etc.)
 * - Fixes Readmore.js scroll behavior (scrolls UP on close)
 * - Arrow direction: DOWN when closed, UP when open
 *
 * @package RetrotubeChild
 * @version 3.0.0
 */

(function() {
    'use strict';

    // Wait for DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initTMWAccordion();
        fixReadmoreScrollBehavior();
    });

    /**
     * Initialize TMW Custom Accordion
     * Used on: archive-model.php (home/models page)
     */
    function initTMWAccordion() {
        var accordions = document.querySelectorAll('.tmw-accordion');
        
        accordions.forEach(function(accordion) {
            var content = accordion.querySelector('.tmw-accordion-content');
            var toggle = accordion.querySelector('.tmw-accordion-toggle');
            
            if (!content || !toggle) return;
            
            // Get text span and icon
            var textSpan = toggle.querySelector('.tmw-accordion-text');
            var icon = toggle.querySelector('i');
            
            // Translations (can be customized)
            var readMoreText = toggle.getAttribute('data-readmore-text') || 'Read more';
            var closeText = toggle.getAttribute('data-close-text') || 'Close';
            
            // Click handler
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                
                var isCollapsed = content.classList.contains('more');
                
                if (isCollapsed) {
                    // Expand
                    content.classList.remove('more');
                    toggle.classList.add('expanded');
                    if (textSpan) textSpan.textContent = closeText;
                    if (icon) {
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-up');
                    }
                } else {
                    // Collapse
                    content.classList.add('more');
                    toggle.classList.remove('expanded');
                    if (textSpan) textSpan.textContent = readMoreText;
                    if (icon) {
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-chevron-down');
                    }
                    
                    // Scroll to accordion top
                    scrollToElement(accordion);
                }
            });
        });
    }

    /**
     * Fix Readmore.js scroll behavior on video/model pages
     * Makes it scroll UP (to the accordion) when closing instead of DOWN
     */
    function fixReadmoreScrollBehavior() {
        // Wait a bit for Readmore.js to initialize
        setTimeout(function() {
            var morelinks = document.querySelectorAll('a.morelink[data-readmore-toggle], a[data-readmore-toggle]');
            
            morelinks.forEach(function(link) {
                // Store original click handlers
                var originalOnClick = link.onclick;
                
                // Add our click handler
                link.addEventListener('click', function(e) {
                    var toggleId = link.getAttribute('data-readmore-toggle');
                    var content = toggleId ? document.getElementById(toggleId) : null;
                    
                    if (!content) {
                        // Try to find content as previous sibling
                        content = link.previousElementSibling;
                    }
                    
                    if (!content) return;
                    
                    // Check if we're closing (content is currently expanded)
                    var isExpanded = content.style.height !== '' && 
                                     parseInt(content.style.height) > 100;
                    
                    // If closing, scroll to the content area after animation
                    if (isExpanded) {
                        setTimeout(function() {
                            // Find the video-description container or parent
                            var container = link.closest('.video-description') || 
                                          link.closest('.entry-content') ||
                                          content.parentElement;
                            
                            if (container) {
                                scrollToElement(container);
                            }
                        }, 350); // Wait for Readmore.js animation
                    }
                });
            });
        }, 500);
    }

    /**
     * Smooth scroll to an element
     * @param {Element} element - The element to scroll to
     */
    function scrollToElement(element) {
        if (!element) return;
        
        var headerOffset = 100; // Account for fixed header
        var elementPosition = element.getBoundingClientRect().top;
        var offsetPosition = elementPosition + window.pageYOffset - headerOffset;
        
        // Only scroll if element is above current view
        if (elementPosition < 0) {
            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
    }

    /**
     * Also handle dynamically loaded content (AJAX)
     */
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    // Re-initialize for any new accordions
                    initTMWAccordion();
                    fixReadmoreScrollBehavior();
                }
            });
        });
        
        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

})();
