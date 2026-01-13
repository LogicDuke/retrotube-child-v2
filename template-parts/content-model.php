<?php
$model_id   = get_the_ID();
$model_name = get_the_title();
$tmw_debug_enabled = defined('TMW_DEBUG') && TMW_DEBUG;

if ($tmw_debug_enabled) {
	$model_slug = get_post_field('post_name', $model_id);
	$slot_enabled_exists = metadata_exists('post', $model_id, '_tmw_slot_enabled');
	$slot_shortcode_exists = metadata_exists('post', $model_id, '_tmw_slot_shortcode');
	$slot_enabled_value = $slot_enabled_exists ? get_post_meta($model_id, '_tmw_slot_enabled', true) : '';
	$slot_shortcode_value = $slot_shortcode_exists ? get_post_meta($model_id, '_tmw_slot_shortcode', true) : '';
	$slot_is_enabled = function_exists('tmw_model_slot_is_enabled') ? tmw_model_slot_is_enabled((int) $model_id) : ($slot_enabled_value === '1');
	$slot_shortcode = function_exists('tmw_model_slot_get_shortcode')
		? tmw_model_slot_get_shortcode((int) $model_id)
		: ($slot_shortcode_value !== '' ? $slot_shortcode_value : '[tmw_slot_machine]');
	$slot_raw_len = 0;
	if (function_exists('tmw_render_model_slot_banner')) {
		$slot_output = do_shortcode($slot_shortcode);
		$slot_raw_len = is_string($slot_output) ? strlen(trim($slot_output)) : 0;
	}
	$wpst_like_exists = function_exists('wpst_get_post_like_link');
	$wpst_rate_exists = function_exists('wpst_get_post_like_rate');
	$like_html = $wpst_like_exists ? wpst_get_post_like_link($model_id) : '';
	$like_html_len = is_string($like_html) ? strlen(trim($like_html)) : 0;
	$like_has_markup = $like_html_len > 0 && strpos($like_html, '<') !== false;

	error_log('[TMW-SLOT-AUDIT] model_id=' . $model_id . ' slug=' . ($model_slug !== '' ? $model_slug : 'unknown'));
	if ($slot_enabled_exists || $slot_shortcode_exists) {
		error_log('[TMW-SLOT-AUDIT] slot_meta enabled_exists=' . ($slot_enabled_exists ? 'yes' : 'no') . ' enabled_value=' . ($slot_enabled_value !== '' ? $slot_enabled_value : 'empty') . ' shortcode_exists=' . ($slot_shortcode_exists ? 'yes' : 'no') . ' shortcode_value=' . ($slot_shortcode_value !== '' ? $slot_shortcode_value : 'empty'));
	}
	error_log('[TMW-SLOT-AUDIT] slot_enabled=' . ($slot_is_enabled ? 'yes' : 'no') . ' shortcode=' . $slot_shortcode . ' raw_len=' . $slot_raw_len);
	error_log('[TMW-LIKE-AUDIT] wpst_get_post_like_link_exists=' . ($wpst_like_exists ? 'yes' : 'no') . ' wpst_get_post_like_rate_exists=' . ($wpst_rate_exists ? 'yes' : 'no') . ' like_html_len=' . $like_html_len . ' has_vote_markup=' . ($like_has_markup ? 'yes' : 'no'));
	error_log('[TMW-MODEL-AUDIT] template-parts/content-model.php loaded for ' . $model_name);
}

$banner_url      = tmw_resolve_model_banner_url( $model_id );

$cta_url   = function_exists( 'get_field' ) ? get_field( 'model_link', $model_id ) : '';
$cta_label = function_exists( 'get_field' ) ? get_field( 'model_link_label', $model_id ) : '';
$cta_note  = function_exists( 'get_field' ) ? get_field( 'model_link_note', $model_id ) : '';

if ( empty( $cta_label ) ) {
        $cta_label = __( 'Watch Live', 'retrotube' );
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> itemprop="performer" itemscope itemtype="http://schema.org/Person">
        <header class="entry-header">

                <div class="video-player box-shadow model-banner">
                        <?php
                        if ( defined( 'TMW_BANNER_DEBUG' ) && TMW_BANNER_DEBUG ) {
                                echo "\n<!-- TMW Banner URL: " . esc_html( $banner_url ? $banner_url : 'EMPTY' ) . " -->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        }
                        ?>
                        <?php if ( ! tmw_render_model_banner( $model_id, 'frontend' ) ) : ?>
                                <div class="tmw-banner-container">
                                        <div class="tmw-banner-frame frontend">
                                                <div class="no-banner-placeholder">
                                                        <p><?php esc_html_e( 'No banner image uploaded yet.', 'retrotube' ); ?></p>
                                                </div>
                                        </div>
                                </div>
                        <?php endif; ?>

                        <?php if ( $cta_url ) : ?>
                                <a class="button model-cta" id="model-cta" href="<?php echo esc_url( $cta_url ); ?>" target="_blank" rel="nofollow noopener">
                                        <i class="fa fa-video-camera"></i>
                                        <?php echo esc_html( $cta_label ); ?>
                                </a>
                        <?php endif; ?>

                        <?php if ( $cta_note ) : ?>
                                <p class="model-cta-note"><?php echo wp_kses_post( $cta_note ); ?></p>
                        <?php endif; ?>
                </div>

		<?php if ( $tmw_debug_enabled ) : ?>
			<?php error_log( '[TMW-MODEL-HEADER] Using video-style header block on model=' . $model_name ); ?>
		<?php endif; ?>

		<div class="title-block box-shadow">
			<?php the_title( '<h1 class="entry-title model-name" itemprop="name">', '</h1>' ); ?>
			<?php if ( xbox_get_field_value( 'wpst-options', 'enable-rating-system' ) == 'on' ) : ?>
				<?php
				$rating_percent = wpst_get_post_like_rate( get_the_ID() );
				$is_rated_yet   = ( $rating_percent === false ) ? ' not-rated-yet' : '';
				$rating_percent = ( $rating_percent === false ) ? 0 : (float) $rating_percent;
				?>
				<div id="rating" class="<?php echo esc_attr( trim( $is_rated_yet ) ); ?>">
					<span id="video-rate"><?php echo wpst_get_post_like_link( get_the_ID() ); ?></span>
				</div>
			<?php endif; ?>
			<div id="video-tabs" class="tabs">
				<button class="tab-link active about" data-tab-id="video-about">
					<i class="fa fa-info-circle"></i> <?php esc_html_e( 'About', 'wpst' ); ?>
				</button>
				<?php if ( xbox_get_field_value( 'wpst-options', 'enable-video-share' ) == 'on' ) : ?>
					<button class="tab-link share" data-tab-id="video-share">
						<i class="fa fa-share"></i> <?php esc_html_e( 'Share', 'wpst' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>

		<div class="video-meta-inline">
			<?php
			echo '<span class="video-meta-item video-meta-model"><i class="fa fa-star"></i> Model:&nbsp;' . esc_html( $model_name ) . '</span>';
			echo '<span class="video-meta-item video-meta-author"><i class="fa fa-user"></i> From:&nbsp;<a href="' . esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ) . '">' . esc_html( get_the_author() ) . '</a></span>';
			echo '<span class="video-meta-item video-meta-date"><i class="fa fa-calendar"></i> Date:&nbsp;' . esc_html( get_the_date() ) . '</span>';
			?>
		</div>

		<div class="clear"></div>


        </header><!-- .entry-header -->

		<div class="entry-content">
			<?php
			$views_count    = function_exists( 'tmw_get_display_model_views' )
				? tmw_get_display_model_views( (int) get_the_ID() )
				: ( function_exists( 'wpst_get_post_views' ) ? wpst_get_post_views( get_the_ID() ) : 0 );
			$likes_count    = function_exists( 'wpst_get_post_likes' ) ? wpst_get_post_likes( get_the_ID() ) : 0;
			$dislikes_count = function_exists( 'wpst_get_post_dislikes' ) ? wpst_get_post_dislikes( get_the_ID() ) : 0;
			$views_count    = is_numeric( $views_count ) ? (int) $views_count : 0;
			$likes_count    = is_numeric( $likes_count ) ? (int) $likes_count : 0;
			$dislikes_count = is_numeric( $dislikes_count ) ? (int) $dislikes_count : 0;
			?>
			<?php if ( xbox_get_field_value( 'wpst-options', 'enable-views-system' ) == 'on' || xbox_get_field_value( 'wpst-options', 'enable-rating-system' ) == 'on' ) : ?>
				<div id="rating-col">
					<?php if ( xbox_get_field_value( 'wpst-options', 'enable-views-system' ) == 'on' ) : ?>
						<div id="video-views"><span><?php echo esc_html( $views_count ); ?></span> <?php esc_html_e( 'views', 'wpst' ); ?></div>
					<?php endif; ?>
					<?php if ( xbox_get_field_value( 'wpst-options', 'enable-rating-system' ) == 'on' ) : ?>
						<div class="rating-bar"><div class="rating-bar-meter" style="width: <?php echo esc_attr( $rating_percent ); ?>%;"></div></div>
						<div class="rating-result">
							<div class="percentage"><?php echo esc_html( $rating_percent ); ?>%</div>
							<div class="likes">
								<i class="fa fa-thumbs-up"></i> <span class="likes_count"><?php echo esc_html( $likes_count ); ?></span>
								<i class="fa fa-thumbs-down fa-flip-horizontal"></i> <span class="dislikes_count"><?php echo esc_html( $dislikes_count ); ?></span>
							</div>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="tab-content">
				<?php $width = ( xbox_get_field_value( 'wpst-options', 'enable-views-system' ) == 'off' && xbox_get_field_value( 'wpst-options', 'enable-rating-system' ) == 'off' ) ? '100' : '70'; ?>
				<div id="video-about" class="width<?php echo $width; ?>">
                                <div class="video-description">
                                        <?php if ( xbox_get_field_value( 'wpst-options', 'show-description-video-about' ) == 'on' ) : ?>
                                                <div class="desc <?php echo ( xbox_get_field_value( 'wpst-options', 'truncate-description' ) == 'on' ) ? 'more' : ''; ?>">
													<!-- [TMW-SLOT-AUDIT] BEGIN the_content -->
                                                        <?php the_content(); ?>
													<!-- [TMW-SLOT-AUDIT] END the_content -->
                                                </div>
                                        <?php endif; ?>
                                </div>

								<?php if ( xbox_get_field_value( 'wpst-options', 'show-categories-video-about' ) == 'on' || xbox_get_field_value( 'wpst-options', 'show-tags-video-about' ) == 'on' ) : ?>
										<!-- [TMW-SLOT-AUDIT] BEGIN tags -->
                                        <div class="tags"><?php wpst_entry_footer(); ?></div>
								<!-- [TMW-SLOT-AUDIT] END tags -->
                                <?php endif; ?>
						</div>
			</div><!-- END .tab-content -->
			<?php
			// === TMW SLOT BANNER ZONE ===
			if (function_exists('tmw_render_model_slot_banner_zone')) :
				$tmw_slot_html = tmw_render_model_slot_banner_zone((int) get_the_ID());
				if ($tmw_slot_html !== '') :
					if (defined('TMW_DEBUG') && TMW_DEBUG) {
						error_log('[TMW-SLOT-FIX] template content-model.php echo len=' . strlen($tmw_slot_html) . ' model_id=' . get_the_ID());
					}
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $tmw_slot_html;
				elseif (defined('TMW_DEBUG') && TMW_DEBUG) :
					// Debug comment showing current meta values
					$d_id = get_the_ID();
					$d_en = get_post_meta($d_id, '_tmw_slot_enabled', true);
					$d_mo = get_post_meta($d_id, '_tmw_slot_mode', true);
					$d_sc = get_post_meta($d_id, '_tmw_slot_shortcode', true);
					printf(
						'<!-- TMW_SLOT_DEBUG: post_id=%d enabled="%s" mode="%s" shortcode="%s" shortcode_exists=%s -->',
						$d_id,
						esc_attr($d_en),
						esc_attr($d_mo),
						esc_attr($d_sc),
						shortcode_exists('tmw_slot_machine') ? 'yes' : 'NO'
					);
				endif;
			endif;
			// === END TMW SLOT BANNER ZONE ===
			?>
						<?php
						$tmw_model_tags_count = get_query_var('tmw_model_tags_count', null);
						$tmw_model_tags       = get_query_var('tmw_model_tags_data', []);
						?>
                        <?php if ( $tmw_model_tags_count !== null ) : ?>
                                <!-- === TMW-TAGS-BULLETPROOF-RESTORE === -->
                                <div class="post-tags entry-tags tmw-model-tags<?php echo $tmw_model_tags_count === 0 ? ' no-tags' : ''; ?>">
                                        <span class="tag-title">
                                                <i class="fa fa-tags" aria-hidden="true"></i>
                                                <?php
                                                echo $tmw_model_tags_count === 0
                                                        ? esc_html__('(No tags linked — audit mode)', 'retrotube')
                                                        : esc_html__('Tags:', 'retrotube');
                                                ?>
                                        </span>
                                        <?php if ($tmw_model_tags_count > 0 && is_array($tmw_model_tags)) : ?>
                                                <?php foreach ($tmw_model_tags as $tag) : ?>
                                                        <a href="<?php echo get_tag_link( $tag->term_id ); ?>"
                                                                class="label"
                                                                title="<?php echo esc_attr( $tag->name ); ?>">
                                                                <i class="fa fa-tag"></i><?php echo esc_html( $tag->name ); ?>
                                                        </a>
                                                <?php endforeach; ?>
                                        <?php endif; ?>
                                </div>
                                <!-- === END TMW-TAGS-BULLETPROOF-RESTORE === -->
                                <?php endif; ?>

                        <?php get_template_part( 'template-parts/model-videos' ); ?>

						<?php if ( xbox_get_field_value( 'wpst-options', 'enable-video-share' ) == 'on' ) : ?>
								<?php get_template_part( 'template-parts/content', 'share-buttons' ); ?>
						<?php endif; ?>
        </div><!-- .entry-content -->

        <?php if ( xbox_get_field_value( 'wpst-options', 'display-related-videos' ) == 'on' ) : ?>
                <?php get_template_part( 'template-parts/content', 'related' ); ?>
        <?php endif; ?>

        <?php
        if ( xbox_get_field_value( 'wpst-options', 'enable-comments' ) == 'on' ) {
                if ( comments_open() || get_comments_number() ) :
                        comments_template();
                endif;
        }
        ?>
</article><!-- #post-## -->
