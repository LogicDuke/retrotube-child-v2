<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'tmw_model_stats_increment_views' ) ) {
    function tmw_model_stats_increment_views( int $post_id ): void {
        if ( $post_id <= 0 ) {
            return;
        }

        $debug = defined( 'TMW_DEBUG' ) && TMW_DEBUG;

        $wpst_used = '';
        foreach ( [
            'wpst_set_post_views',
            'wpst_update_post_views',
            'wpst_increment_post_views',
            'wpst_set_post_views_count',
        ] as $fn ) {
            if ( function_exists( $fn ) ) {
                try {
                    $fn( $post_id );
                    $wpst_used = $fn;
                } catch ( \Throwable $e ) {
                    // Ignore and fall back to our own meta count.
                }
                break;
            }
        }

        if ( $wpst_used === '' ) {
            $key = '_tmw_model_views';
            $cur = get_post_meta( $post_id, $key, true );
            $cur = is_numeric( $cur ) ? (int) $cur : 0;
            update_post_meta( $post_id, $key, $cur + 1 );

            if ( $debug ) {
                error_log( '[TMW-MODEL-STATS] increment fallback model_id=' . $post_id . ' new=' . ( $cur + 1 ) );
            }
        } else {
            if ( $debug ) {
                error_log( '[TMW-MODEL-STATS] increment via ' . $wpst_used . ' model_id=' . $post_id );
            }
        }
    }
}

if ( ! function_exists( 'tmw_get_model_views_fallback' ) ) {
    function tmw_get_model_views_fallback( int $post_id ): int {
        $v = get_post_meta( $post_id, '_tmw_model_views', true );
        return is_numeric( $v ) ? (int) $v : 0;
    }
}

if ( ! function_exists( 'tmw_get_display_model_views' ) ) {
    function tmw_get_display_model_views( int $post_id ): int {
        $wpst = 0;
        if ( function_exists( 'wpst_get_post_views' ) ) {
            $wpst = wpst_get_post_views( $post_id );
            $wpst = is_numeric( $wpst ) ? (int) $wpst : 0;
        }
        if ( $wpst > 0 ) {
            return $wpst;
        }

        return tmw_get_model_views_fallback( $post_id );
    }
}

// Count EVERY visit/page load: +1 per load (no dedup).
add_action( 'wp', function () {
    if ( ! is_singular( 'model' ) ) {
        return;
    }

    // Avoid counting admin screens / AJAX / REST (not real front-end visits).
    if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        return;
    }

    $post_id = (int) get_queried_object_id();
    if ( $post_id <= 0 ) {
        return;
    }

    tmw_model_stats_increment_views( $post_id );
}, 20 );
