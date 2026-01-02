(() => {
  let loaded = false;

  const loadSlotAssets = () => {
    if (loaded) {
      return;
    }
    loaded = true;

    const styles = Array.from(document.querySelectorAll('link[data-tmw-slot-href]'));
    styles.forEach((node) => {
      const href = node.getAttribute('data-tmw-slot-href');
      if (!href) {
        return;
      }
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = href;
      link.media = node.getAttribute('media') || 'all';
      document.head.appendChild(link);
      node.removeAttribute('data-tmw-slot-href');
    });

    const scripts = Array.from(document.querySelectorAll('script[data-tmw-slot-src]'));
    if (scripts.length === 0) {
      initSlotMachine();
      return;
    }

    let pending = scripts.length;
    const done = () => {
      pending -= 1;
      if (pending <= 0) {
        initSlotMachine();
      }
    };

    scripts.forEach((node) => {
      const src = node.getAttribute('data-tmw-slot-src');
      if (!src) {
        done();
        return;
      }
      const script = document.createElement('script');
      script.src = src;
      script.async = true;
      script.addEventListener('load', done);
      script.addEventListener('error', done);
      document.body.appendChild(script);
      node.removeAttribute('data-tmw-slot-src');
    });
  };

  const initSlotMachine = () => {
    if (typeof window.tmwSlotMachineInit === 'function') {
      window.tmwSlotMachineInit();
    }
    if (typeof window.initSlotMachine === 'function') {
      window.initSlotMachine();
    }
    document.dispatchEvent(new CustomEvent('tmw:slotmachine:ready'));
  };

  const observeSlotMachine = () => {
    const targets = document.querySelectorAll('[data-tmw-slot-lazy], .tmw-slot-machine');
    if (targets.length === 0) {
      return;
    }

    if (!('IntersectionObserver' in window)) {
      loadSlotAssets();
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          loadSlotAssets();
          observer.disconnect();
        }
      });
    }, { threshold: 0.1 });

    targets.forEach((target) => observer.observe(target));
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', observeSlotMachine, { once: true });
  } else {
    observeSlotMachine();
  }
})();
