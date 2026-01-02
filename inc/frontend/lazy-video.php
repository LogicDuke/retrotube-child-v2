<?php
if (!defined('ABSPATH')) { exit; }

if (!function_exists('tmw_lazy_video_parse_url')) {
    function tmw_lazy_video_parse_url(string $url): array {
        $url = trim($url);
        if ($url === '') {
            return [];
        }

        $parts = wp_parse_url($url);
        if (!$parts || empty($parts['host'])) {
            return [];
        }

        $host = strtolower($parts['host']);
        $host = preg_replace('/^www\./', '', $host);
        $path = $parts['path'] ?? '';
        $query = [];

        if (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $provider = '';
        $video_id = '';
        $embed_url = '';
        $thumb_url = '';

        if (in_array($host, ['youtube.com', 'm.youtube.com', 'youtube-nocookie.com'], true)) {
            if (!empty($query['v'])) {
                $video_id = $query['v'];
            } elseif (preg_match('#/embed/([^/?]+)#', $path, $match)) {
                $video_id = $match[1];
            } elseif (preg_match('#/shorts/([^/?]+)#', $path, $match)) {
                $video_id = $match[1];
            }

            if ($video_id !== '') {
                $provider = 'youtube';
                $embed_url = 'https://www.youtube.com/embed/' . rawurlencode($video_id);
                $thumb_url = 'https://img.youtube.com/vi/' . rawurlencode($video_id) . '/hqdefault.jpg';
            }
        } elseif ($host === 'youtu.be') {
            $video_id = ltrim($path, '/');
            if ($video_id !== '') {
                $provider = 'youtube';
                $embed_url = 'https://www.youtube.com/embed/' . rawurlencode($video_id);
                $thumb_url = 'https://img.youtube.com/vi/' . rawurlencode($video_id) . '/hqdefault.jpg';
            }
        } elseif ($host === 'vimeo.com' || $host === 'player.vimeo.com') {
            if (preg_match('#/video/(\d+)#', $path, $match)) {
                $video_id = $match[1];
            } else {
                $video_id = ltrim($path, '/');
            }

            if ($video_id !== '') {
                $provider = 'vimeo';
                $embed_url = 'https://player.vimeo.com/video/' . rawurlencode($video_id);
                $thumb_url = 'https://vumbnail.com/' . rawurlencode($video_id) . '.jpg';
            }
        } elseif ($host === 'dailymotion.com' || $host === 'dai.ly') {
            if (preg_match('#/video/([^/?]+)#', $path, $match)) {
                $video_id = $match[1];
            } else {
                $video_id = ltrim($path, '/');
            }

            if ($video_id !== '') {
                $provider = 'dailymotion';
                $embed_url = 'https://www.dailymotion.com/embed/video/' . rawurlencode($video_id);
                $thumb_url = 'https://www.dailymotion.com/thumbnail/video/' . rawurlencode($video_id);
            }
        }

        $data = [
            'provider'      => $provider,
            'video_id'      => $video_id,
            'embed_url'     => $embed_url,
            'thumbnail_url' => $thumb_url,
        ];

        return apply_filters('tmw_lazy_video_parse_url', $data, $url, $parts);
    }
}

if (!function_exists('tmw_lazy_video_build_iframe')) {
    function tmw_lazy_video_build_iframe(string $src, string $title = '', int $width = 0, int $height = 0): string {
        $attrs = [
            'src="' . esc_url($src) . '"',
            'title="' . esc_attr($title !== '' ? $title : __('Video player', 'retrotube')) . '"',
            'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"',
            'allowfullscreen',
            'loading="lazy"',
            'data-tmw-lazy-ignore="1"',
        ];

        if ($width > 0) {
            $attrs[] = 'width="' . (int) $width . '"';
        }
        if ($height > 0) {
            $attrs[] = 'height="' . (int) $height . '"';
        }

        return '<iframe ' . implode(' ', $attrs) . '></iframe>';
    }
}

if (!function_exists('tmw_lazy_video_render')) {
    function tmw_lazy_video_render(string $url, array $args = []): string {
        $defaults = [
            'title'      => '',
            'thumb'      => '',
            'class'      => '',
            'width'      => 0,
            'height'     => 0,
            'noscript'   => '',
            'embed_url'  => '',
            'autoplay'   => true,
        ];
        $args = wp_parse_args($args, $defaults);

        $data = tmw_lazy_video_parse_url($url);
        $embed_url = $args['embed_url'] !== '' ? $args['embed_url'] : ($data['embed_url'] ?? '');

        if ($embed_url === '') {
            return '';
        }

        $thumb = $args['thumb'] !== '' ? $args['thumb'] : ($data['thumbnail_url'] ?? '');
        $title = $args['title'] !== '' ? $args['title'] : __('Video thumbnail', 'retrotube');
        $class = trim('lazy-video ' . $args['class']);

        $width = (int) $args['width'];
        $height = (int) $args['height'];
        $aspect = 56.25;
        if ($width > 0 && $height > 0) {
            $aspect = round(($height / $width) * 100, 2);
        }

        $play_label = __('Play Video', 'retrotube');
        $autoplay = $args['autoplay'] ? '1' : '0';

        $thumb_markup = $thumb !== ''
            ? '<img class="lazy-video-thumb" src="' . esc_url($thumb) . '" alt="' . esc_attr($title) . '" loading="lazy" decoding="async">'
            : '<div class="lazy-video-thumb lazy-video-thumb--empty" role="presentation"></div>';

        $noscript_markup = $args['noscript'] !== '' ? $args['noscript'] : tmw_lazy_video_build_iframe($embed_url, $title, $width, $height);
        if (stripos($noscript_markup, '<iframe') !== false && strpos($noscript_markup, 'data-tmw-lazy-ignore') === false) {
            $noscript_markup = preg_replace('/<iframe\b/i', '<iframe data-tmw-lazy-ignore="1"', $noscript_markup, 1);
        }

        return sprintf(
            '<div class="%1$s" data-src="%2$s" data-provider="%3$s" data-video-id="%4$s" data-title="%5$s" data-thumb="%6$s" data-autoplay="%7$s" style="--lazy-video-aspect:%8$s%%;">%9$s<button class="lazy-video-play play-btn" type="button">%10$s</button><noscript>%11$s</noscript></div>',
            esc_attr($class),
            esc_url($embed_url),
            esc_attr($data['provider'] ?? ''),
            esc_attr($data['video_id'] ?? ''),
            esc_attr($title),
            esc_url($thumb),
            esc_attr($autoplay),
            esc_attr($aspect),
            $thumb_markup,
            esc_html($play_label),
            $noscript_markup
        );
    }
}

if (!function_exists('tmw_lazy_video_replace_iframes')) {
    function tmw_lazy_video_replace_iframes(string $html): string {
        if (stripos($html, '<iframe') === false) {
            return $html;
        }

        $pattern = '#<iframe\b([^>]*?)src=(["\'])([^"\']+)\2([^>]*)></iframe>#i';

        return preg_replace_callback($pattern, function ($matches) {
            $src = $matches[3] ?? '';
            if ($src === '') {
                return $matches[0];
            }

            if (strpos($matches[0], 'data-tmw-lazy-ignore') !== false) {
                return $matches[0];
            }

            $data = tmw_lazy_video_parse_url($src);
            if (empty($data['embed_url'])) {
                return $matches[0];
            }

            $attrs = $matches[1] . ' ' . $matches[4];
            $width = 0;
            $height = 0;
            $title = '';

            if (preg_match('#width=(["\'])(\d+)\1#i', $attrs, $wmatch)) {
                $width = (int) $wmatch[2];
            }
            if (preg_match('#height=(["\'])(\d+)\1#i', $attrs, $hmatch)) {
                $height = (int) $hmatch[2];
            }
            if (preg_match('#title=(["\'])([^"\']+)\1#i', $attrs, $tmatch)) {
                $title = $tmatch[2];
            }

            return tmw_lazy_video_render($src, [
                'title'    => $title,
                'width'    => $width,
                'height'   => $height,
                'noscript' => $matches[0],
                'class'    => 'lazy-video-iframe',
            ]);
        }, $html);
    }
}

if (!function_exists('tmw_lazy_video_wrap_html')) {
    function tmw_lazy_video_wrap_html(string $html): string {
        return tmw_lazy_video_replace_iframes($html);
    }
}

add_shortcode('lazy_video', function ($atts, $content = '') {
    $atts = shortcode_atts([
        'url'   => '',
        'src'   => '',
        'title' => '',
        'thumb' => '',
        'class' => '',
    ], $atts, 'lazy_video');

    $url = $atts['url'] ?: $atts['src'] ?: trim($content);
    if ($url === '') {
        return '';
    }

    return tmw_lazy_video_render($url, [
        'title' => $atts['title'],
        'thumb' => $atts['thumb'],
        'class' => $atts['class'],
    ]);
});

add_filter('do_shortcode_tag', function ($output, $tag, $attr) {
    if ($tag !== 'video') {
        return $output;
    }

    $url = '';
    if (is_array($attr)) {
        $url = $attr['url'] ?? $attr['src'] ?? '';
    }

    if ($url === '') {
        return $output;
    }

    $data = tmw_lazy_video_parse_url($url);
    if (empty($data['embed_url'])) {
        return $output;
    }

    $title = isset($attr['title']) ? (string) $attr['title'] : '';
    $thumb = isset($attr['poster']) ? (string) $attr['poster'] : '';

    return tmw_lazy_video_render($url, [
        'title' => $title,
        'thumb' => $thumb,
        'class' => 'lazy-video-shortcode',
    ]);
}, 10, 3);

add_filter('embed_oembed_html', function ($html, $url, $attr, $post_id) {
    if (!is_string($url) || $url === '') {
        return $html;
    }

    $data = tmw_lazy_video_parse_url($url);
    if (empty($data['embed_url'])) {
        return $html;
    }

    return tmw_lazy_video_render($url, [
        'noscript' => $html,
        'class'    => 'lazy-video-embed',
    ]);
}, 20, 4);

add_filter('the_content', function ($content) {
    if (is_admin()) {
        return $content;
    }

    return tmw_lazy_video_replace_iframes($content);
}, 12);

if (function_exists('tmw_enqueue_inline_css')) {
    tmw_enqueue_inline_css('
        .lazy-video {
            position: relative;
            display: block;
            width: 100%;
            max-width: 100%;
            background: #000;
            overflow: hidden;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,.35);
            aspect-ratio: 16 / 9;
        }
        .lazy-video::before {
            content: "";
            display: block;
            padding-top: var(--lazy-video-aspect, 56.25%);
        }
        .lazy-video > img,
        .lazy-video > .lazy-video-thumb {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .lazy-video iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
        .lazy-video-play {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,.75);
            color: #fff;
            border: 2px solid #fff;
            border-radius: 999px;
            padding: 10px 18px;
            font-size: 16px;
            cursor: pointer;
        }
        .lazy-video-play:focus {
            outline: 2px solid #fff;
            outline-offset: 2px;
        }
        .lazy-video-thumb--empty {
            background: #111;
        }
    ');
}
