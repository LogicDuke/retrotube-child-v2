(function () {
    'use strict';

    var hasRun = false;
    var timerId = null;

    function runDelayedScripts() {
        if (hasRun) {
            return;
        }
        hasRun = true;

        if (timerId) {
            clearTimeout(timerId);
            timerId = null;
        }

        var delayedScripts = document.querySelectorAll('script[type="text/plain"][data-tmw-delay][data-src]');
        if (!delayedScripts.length) {
            return;
        }

        delayedScripts.forEach(function (placeholder) {
            var src = placeholder.getAttribute('data-src');
            if (!src) {
                return;
            }

            var script = document.createElement('script');
            script.src = src;
            script.async = true;
            document.head.appendChild(script);
        });
    }

    function onFirstInteraction() {
        runDelayedScripts();
    }

    function addInteractionListeners() {
        var options = { passive: true, once: true };
        ['touchstart', 'scroll', 'mousedown', 'keydown'].forEach(function (eventName) {
            window.addEventListener(eventName, onFirstInteraction, options);
        });
    }

    addInteractionListeners();
    window.addEventListener('load', runDelayedScripts, { once: true });
    timerId = window.setTimeout(runDelayedScripts, 2500);
})();
