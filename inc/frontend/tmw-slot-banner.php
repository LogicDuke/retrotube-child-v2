<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tmw_model_slot_is_enabled' ) ) {
	function tmw_model_slot_is_enabled( int $post_id ): bool {
		return get_post_meta( $post_id, '_tmw_slot_enabled', true ) === '1';
	}
}

if ( ! function_exists( 'tmw_model_slot_get_shortcode' ) ) {
	function tmw_model_slot_get_shortcode( int $post_id ): string {
		$sc = (string) get_post_meta( $post_id, '_tmw_slot_shortcode', true );
		return trim( $sc );
	}
}

if ( ! function_exists( 'tmw_model_slot_render_html' ) ) {
	function tmw_model_slot_render_html( int $post_id ): string {
		if ( ! tmw_model_slot_is_enabled( $post_id ) ) {
			return '';
		}

		$shortcode = tmw_model_slot_get_shortcode( $post_id );
		if ( $shortcode === '' ) {
			return '';
		}

		$out = do_shortcode( $shortcode );
		$out = is_string( $out ) ? trim( $out ) : '';
		if ( $out === '' ) {
			return '';
		}

		return '<div class="tmw-slot-banner-zone"><div class="tmw-slot-banner">' . $out . '</div></div>';
	}
}
