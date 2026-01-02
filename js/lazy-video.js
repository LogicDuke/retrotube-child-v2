(() => {
  const PROVIDERS = {
    youtube: {
      match: /(?:youtube\.com|youtu\.be)/i,
      thumb: (id) => `https://img.youtube.com/vi/${id}/hqdefault.jpg`,
    },
    vimeo: {
      match: /vimeo\.com/i,
      thumb: (id) => `https://vumbnail.com/${id}.jpg`,
    },
    dailymotion: {
      match: /dailymotion\.com|dai\.ly/i,
      thumb: (id) => `https://www.dailymotion.com/thumbnail/video/${id}`,
    },
  };

  const parseVideoId = (src) => {
    try {
      const url = new URL(src, window.location.href);
      const host = url.hostname.replace(/^www\./, '');

      if (PROVIDERS.youtube.match.test(host)) {
        if (url.searchParams.get('v')) {
          return { provider: 'youtube', id: url.searchParams.get('v') };
        }
        const match = url.pathname.match(/\/(embed|shorts)\/([^/?]+)/);
        if (match) {
          return { provider: 'youtube', id: match[2] };
        }
      }

      if (host === 'youtu.be') {
        const id = url.pathname.replace('/', '');
        return id ? { provider: 'youtube', id } : null;
      }

      if (PROVIDERS.vimeo.match.test(host)) {
        const match = url.pathname.match(/\/video\/(\d+)/);
        if (match) {
          return { provider: 'vimeo', id: match[1] };
        }
        const id = url.pathname.replace('/', '');
        return id ? { provider: 'vimeo', id } : null;
      }

      if (PROVIDERS.dailymotion.match.test(host)) {
        const match = url.pathname.match(/\/video\/([^/?]+)/);
        if (match) {
          return { provider: 'dailymotion', id: match[1] };
        }
        const id = url.pathname.replace('/', '');
        return id ? { provider: 'dailymotion', id } : null;
      }
    } catch (error) {
      return null;
    }

    return null;
  };

  const addAutoplay = (src) => {
    if (src.includes('autoplay=')) {
      return src;
    }
    const separator = src.includes('?') ? '&' : '?';
    return `${src}${separator}autoplay=1`;
  };

  const ensureThumb = (container) => {
    const existing = container.querySelector('img.lazy-video-thumb');
    if (existing && existing.getAttribute('src')) {
      return;
    }

    const src = container.getAttribute('data-src') || '';
    if (!src) {
      return;
    }

    const resolved = parseVideoId(src);
    if (!resolved) {
      return;
    }

    const provider = PROVIDERS[resolved.provider];
    if (!provider) {
      return;
    }

    const thumbUrl = provider.thumb(resolved.id);
    container.setAttribute('data-thumb', thumbUrl);

    if (!existing) {
      const img = document.createElement('img');
      img.className = 'lazy-video-thumb';
      img.alt = container.getAttribute('data-title') || 'Video thumbnail';
      img.loading = 'lazy';
      img.decoding = 'async';
      img.src = thumbUrl;
      container.insertBefore(img, container.firstChild);
    } else {
      existing.src = thumbUrl;
    }
  };

  const createIframe = (container) => {
    if (container.classList.contains('is-loaded')) {
      return;
    }

    const src = container.getAttribute('data-src');
    if (!src) {
      return;
    }

    const shouldAutoplay = container.getAttribute('data-autoplay') !== '0';
    const iframe = document.createElement('iframe');
    iframe.src = shouldAutoplay ? addAutoplay(src) : src;
    iframe.title = container.getAttribute('data-title') || 'Video player';
    iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
    iframe.allowFullscreen = true;
    iframe.loading = 'lazy';

    container.innerHTML = '';
    container.appendChild(iframe);
    container.classList.add('is-loaded');
  };

  const setupLazyVideo = (container) => {
    if (container.dataset.tmwLazyReady === '1') {
      return;
    }
    container.dataset.tmwLazyReady = '1';

    ensureThumb(container);

    const button = container.querySelector('.lazy-video-play');
    const activate = () => createIframe(container);

    if (button) {
      button.addEventListener('click', activate);
    }

    container.addEventListener('click', (event) => {
      if (event.target && event.target.closest('.lazy-video-play')) {
        return;
      }
      activate();
    });
  };

  const init = () => {
    document.querySelectorAll('.lazy-video').forEach(setupLazyVideo);
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
