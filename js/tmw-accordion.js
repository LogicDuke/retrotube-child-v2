/**
 * TMW Global Accordion JavaScript v3.1
 * Unified accordion behavior for ALL pages
 * 
 * Features:
 * - TMW custom accordion (home page, etc.)
 * - Fixes Readmore.js scroll behavior (scrolls UP on close)
 * - Arrow direction: DOWN when closed, UP when open
 *
 * @package RetrotubeChild
 * @version 3.1.0
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
                ensureReadmoreTextSpan(link);
                syncReadmoreState(link);

                // Add our click handler
                if (link.dataset.tmwAccordionBound === 'true') {
                    return;
                }

                link.dataset.tmwAccordionBound = 'true';

                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    var toggleId = link.getAttribute('data-readmore-toggle');
                    var content = toggleId ? document.getElementById(toggleId) : null;
                    var wasExpanded = link.getAttribute('aria-expanded') === 'true';
                    
                    if (!content) {
                        // Try to find content as previous sibling
                        content = link.previousElementSibling;
                    }
                    
                    if (!content) return;
                    
                    // Check if we're closing (content is currently expanded)
                    var isExpanded = wasExpanded;
                    if (link.getAttribute('aria-expanded') === null) {
                        isExpanded = content.style.height !== '' &&
                                     parseInt(content.style.height, 10) > 100;
                    }
                    
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

                    // Update toggle text and icon after Readmore.js updates
                    setTimeout(function() {
                        ensureReadmoreTextSpan(link);
                        syncReadmoreState(link);
                    }, 350);

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
        
        var headerOffset = 120; // Account for fixed header
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
     * Ensure Readmore.js links have a text span for styling
     * @param {Element} link - The Readmore.js toggle link
     */
    function ensureReadmoreTextSpan(link) {
        if (!link) return;

        var existingSpan = link.querySelector('.tmw-accordion-text');
        if (existingSpan) {
            if (!link.getAttribute('data-readmore-text')) {
                var isExpanded = link.getAttribute('aria-expanded') === 'true';
                link.setAttribute('data-readmore-text', isExpanded ? 'Read more' : existingSpan.textContent.trim());
            }
            return;
        }

        var icon = link.querySelector('i');
        var textNodes = [];

        link.childNodes.forEach(function(node) {
            if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) {
                textNodes.push(node);
            }
        });

        if (!textNodes.length) return;

        var combinedText = textNodes.map(function(node) {
            return node.textContent.trim();
        }).join(' ');

        var textSpan = document.createElement('span');
        textSpan.className = 'tmw-accordion-text';
        textSpan.textContent = combinedText;

        if (!link.getAttribute('data-readmore-text')) {
            var isExpanded = link.getAttribute('aria-expanded') === 'true';
            link.setAttribute('data-readmore-text', isExpanded ? 'Read more' : combinedText);
        }

        textNodes.forEach(function(node) {
            link.removeChild(node);
        });

        if (icon) {
            link.insertBefore(textSpan, icon);
        } else {
            link.appendChild(textSpan);
        }
    }

    /**
     * Sync Readmore.js toggle state with text/icon
     * @param {Element} link - The Readmore.js toggle link
     */
    function syncReadmoreState(link) {
        if (!link) return;

        ensureReadmoreTextSpan(link);

        var textSpan = link.querySelector('.tmw-accordion-text');
        var icon = link.querySelector('i');
        var readMoreText = link.getAttribute('data-readmore-text') || 'Read more';
        var closeText = link.getAttribute('data-close-text') || 'Close';
        var isExpanded = link.getAttribute('aria-expanded') === 'true';

        if (!textSpan) return;

        textSpan.textContent = isExpanded ? closeText : readMoreText;
        if (icon) {
            icon.classList.toggle('fa-chevron-up', isExpanded);
            icon.classList.toggle('fa-chevron-down', !isExpanded);
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
