/**
 * TMW Global Accordion JavaScript v3.2
 * Unified accordion behavior for ALL pages
 *
 * Features:
 * - TMW custom accordion (home page, etc.)
 * - Fixes Readmore.js scroll behavior (scrolls UP on close)
 * - Arrow direction: DOWN when closed, UP when open
 *
 * @package RetrotubeChild
 * @version 3.2.0
 */

(function() {
    'use strict';

    // Wait for DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initTMWAccordion();
        initReadmoreAccordions();
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

            if (toggle.dataset.tmwAccordionBound === 'true') {
                return;
            }

            toggle.dataset.tmwAccordionBound = 'true';

            applyLineClamp(content);
            evaluateToggleVisibility(content, toggle);

            var textSpan = toggle.querySelector('.tmw-accordion-text');
            var icon = toggle.querySelector('i');

            var readMoreText = toggle.getAttribute('data-readmore-text') || 'Read more';
            var closeText = toggle.getAttribute('data-close-text') || 'Close';

            toggle.addEventListener('click', function(e) {
                e.preventDefault();

                var isCollapsed = content.classList.contains('more');

                if (isCollapsed) {
                    content.classList.remove('more');
                    toggle.classList.add('expanded');
                    if (textSpan) textSpan.textContent = closeText;
                    if (icon) {
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-up');
                    }
                } else {
                    content.classList.add('more');
                    toggle.classList.remove('expanded');
                    if (textSpan) textSpan.textContent = readMoreText;
                    if (icon) {
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-chevron-down');
                    }

                    scrollToElement(accordion);
                }
            });
        });
    }

    /**
     * Fix Readmore.js scroll behavior on video/model pages
     * Makes it scroll UP (to the accordion) when closing instead of DOWN
     */
    function initReadmoreAccordions() {
        setTimeout(function() {
            var morelinks = document.querySelectorAll('a.morelink[data-readmore-toggle], a[data-readmore-toggle].morelink, a.morelink, a[data-readmore-toggle]');

            morelinks.forEach(function(link) {
                if (link.dataset.tmwAccordionBound === 'true') {
                    return;
                }

                link.dataset.tmwAccordionBound = 'true';
                link.classList.add('tmw-accordion-readmore');
                ensureReadmoreTextSpan(link);
                ensureReadmoreIcon(link);
                normalizeReadmoreLabels(link);
                syncReadmoreState(link);

                link.addEventListener('click', function(e) {
                    e.preventDefault();

                    var content = getReadmoreContent(link);
                    var wasExpanded = link.getAttribute('aria-expanded') === 'true';

                    if (wasExpanded) {
                        setTimeout(function() {
                            var container = link.closest('.video-description') ||
                                link.closest('.entry-content') ||
                                content ||
                                link.parentElement;

                            if (container) {
                                scrollToElement(container);
                            }
                        }, 350);
                    }

                    setTimeout(function() {
                        ensureReadmoreTextSpan(link);
                        ensureReadmoreIcon(link);
                        normalizeReadmoreLabels(link);
                        syncReadmoreState(link);
                    }, 350);
                });
            });
        }, 500);
    }

    /**
     * Apply custom line clamp value using CSS variable.
     * @param {Element} content - The accordion content element
     */
    function applyLineClamp(content) {
        var lines = content.getAttribute('data-tmw-accordion-lines');
        if (!lines) return;
        content.style.setProperty('--tmw-accordion-lines', lines);
    }

    /**
     * Hide the toggle when content doesn't overflow.
     * @param {Element} content - The accordion content element
     * @param {Element} toggle - The toggle link
     */
    function evaluateToggleVisibility(content, toggle) {
        var toggleWrap = toggle.closest('.tmw-accordion-toggle-wrap');
        if (!toggleWrap || !content.classList.contains('more')) return;

        var needsToggle = contentOverflows(content);
        if (!needsToggle) {
            toggleWrap.style.display = 'none';
        }
    }

    /**
     * Check if content exceeds the clamped height.
     * @param {Element} content - The accordion content element
     * @returns {boolean}
     */
    function contentOverflows(content) {
        var clone = content.cloneNode(true);
        clone.classList.remove('more');
        clone.style.visibility = 'hidden';
        clone.style.position = 'absolute';
        clone.style.height = 'auto';
        clone.style.maxHeight = 'none';
        clone.style.webkitLineClamp = 'unset';
        clone.style.width = content.getBoundingClientRect().width + 'px';
        document.body.appendChild(clone);

        var needsToggle = clone.scrollHeight > content.clientHeight + 5;
        document.body.removeChild(clone);
        return needsToggle;
    }

    /**
     * Smooth scroll to an element
     * @param {Element} element - The element to scroll to
     */
    function scrollToElement(element) {
        if (!element) return;

        var headerOffset = 120;
        var elementPosition = element.getBoundingClientRect().top;
        var offsetPosition = elementPosition + window.pageYOffset - headerOffset;

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
        if (existingSpan) return;

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

        textNodes.forEach(function(node) {
            link.removeChild(node);
        });

        var icon = link.querySelector('i');
        if (icon) {
            link.insertBefore(textSpan, icon);
        } else {
            link.appendChild(textSpan);
        }
    }

    /**
     * Ensure Readmore.js links have an icon for styling
     * @param {Element} link - The Readmore.js toggle link
     */
    function ensureReadmoreIcon(link) {
        if (!link || link.querySelector('i')) return;

        var icon = document.createElement('i');
        icon.className = 'fa fa-chevron-down';
        link.appendChild(icon);
    }

    /**
     * Normalize Readmore.js labels for consistent behavior
     * @param {Element} link - The Readmore.js toggle link
     */
    function normalizeReadmoreLabels(link) {
        if (!link) return;

        if (!link.getAttribute('data-readmore-text')) {
            link.setAttribute('data-readmore-text', 'Read more');
        }

        if (!link.getAttribute('data-close-text')) {
            link.setAttribute('data-close-text', 'Close');
        }
    }

    /**
     * Sync Readmore.js toggle state with text/icon
     * @param {Element} link - The Readmore.js toggle link
     */
    function syncReadmoreState(link) {
        if (!link) return;

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
     * Resolve the Readmore.js content element
     * @param {Element} link - The Readmore.js toggle link
     * @returns {Element|null}
     */
    function getReadmoreContent(link) {
        if (!link) return null;

        var toggleId = link.getAttribute('data-readmore-toggle');
        if (toggleId) {
            return document.getElementById(toggleId);
        }

        return link.previousElementSibling;
    }

    /**
     * Also handle dynamically loaded content (AJAX)
     */
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    initTMWAccordion();
                    initReadmoreAccordions();
                }
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

})();
