(() => {
  const AFFILIATE_MATCH = /livejasmin/i;
  let loaded = false;
  let idleTimer = null;

  const loadAffiliateScripts = () => {
    if (loaded) {
      return;
    }
    loaded = true;

    const scripts = document.querySelectorAll('script[data-tmw-affiliate-src]');
    scripts.forEach((node) => {
      const src = node.getAttribute('data-tmw-affiliate-src');
      if (!src) {
        return;
      }
      const script = document.createElement('script');
      script.src = src;
      script.async = true;
      node.parentNode.insertBefore(script, node.nextSibling);
      node.removeAttribute('data-tmw-affiliate-src');
    });

    document.dispatchEvent(new CustomEvent('tmw:affiliate:loaded', { detail: { provider: 'livejasmin' } }));
  };

  const scheduleIdleLoad = () => {
    if (idleTimer) {
      clearTimeout(idleTimer);
    }
    idleTimer = setTimeout(loadAffiliateScripts, 5000);
  };

  const isAffiliateLink = (link) => {
    if (!link || !link.href) {
      return false;
    }
    return AFFILIATE_MATCH.test(link.href);
  };

  const markAffiliateLinks = (root = document) => {
    root.querySelectorAll('a[href]').forEach((link) => {
      if (isAffiliateLink(link)) {
        link.dataset.tmwAffiliateLink = '1';
      }
    });
  };

  const observeAffiliateLinks = () => {
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (!(node instanceof HTMLElement)) {
            return;
          }
          if (node.matches && node.matches('a[href]')) {
            markAffiliateLinks(node.parentNode || document);
          } else if (node.querySelectorAll) {
            markAffiliateLinks(node);
          }
        });
      });
    });
    observer.observe(document.body, { childList: true, subtree: true });
  };

  const setupScrollObserver = () => {
    if (!('IntersectionObserver' in window)) {
      window.addEventListener('scroll', loadAffiliateScripts, { once: true, passive: true });
      return;
    }

    const sentinel = document.createElement('div');
    sentinel.setAttribute('aria-hidden', 'true');
    sentinel.style.cssText = 'position:absolute;top:0;left:0;width:1px;height:1px;';
    document.body.appendChild(sentinel);

    const placeSentinel = () => {
      const targetTop = Math.round(document.body.scrollHeight * 0.5);
      sentinel.style.top = `${targetTop}px`;
    };

    placeSentinel();
    window.addEventListener('resize', placeSentinel);

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          loadAffiliateScripts();
          observer.disconnect();
        }
      });
    });

    observer.observe(sentinel);
  };

  const setupLinkTrigger = () => {
    document.addEventListener('click', (event) => {
      const link = event.target.closest('a[href]');
      if (!link) {
        return;
      }
      loadAffiliateScripts();
    }, { capture: true, once: true });
  };

  const setupIdleTrigger = () => {
    ['mousemove', 'keydown', 'touchstart', 'scroll', 'pointerdown'].forEach((evt) => {
      window.addEventListener(evt, scheduleIdleLoad, { passive: true });
    });
    scheduleIdleLoad();
  };

  const init = () => {
    markAffiliateLinks();
    observeAffiliateLinks();
    setupScrollObserver();
    setupLinkTrigger();
    setupIdleTrigger();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
