(function () {
    'use strict';

    var loaded = false;

    function loadDeferredScripts() {
        if (loaded) {
            return;
        }
        loaded = true;

        try {
            var nodes = document.querySelectorAll('script[type="text/tmw-deferred"][data-src]');
            if (!nodes.length) {
                return;
            }

            nodes.forEach(function (node) {
                var src = node.getAttribute('data-src');
                if (!src) {
                    return;
                }

                var script = document.createElement('script');
                script.src = src;
                script.async = true;

                var crossorigin = node.getAttribute('crossorigin');
                if (crossorigin !== null) {
                    script.setAttribute('crossorigin', crossorigin || 'anonymous');
                }

                var referrerpolicy = node.getAttribute('referrerpolicy');
                if (referrerpolicy) {
                    script.setAttribute('referrerpolicy', referrerpolicy);
                }

                document.head.appendChild(script);
            });
        } catch (error) {
            // Swallow errors to avoid breaking the page.
        }
    }

    function onFirstInteraction() {
        loadDeferredScripts();
        removeListeners();
    }

    function removeListeners() {
        ['touchstart', 'scroll', 'mousedown', 'keydown'].forEach(function (eventName) {
            window.removeEventListener(eventName, onFirstInteraction, listenerOptions);
        });
    }

    var listenerOptions = { passive: true };

    ['touchstart', 'scroll', 'mousedown', 'keydown'].forEach(function (eventName) {
        window.addEventListener(eventName, onFirstInteraction, listenerOptions);
    });

    window.setTimeout(loadDeferredScripts, 3500);
})();
