<?php
/**
 * Template Name: Videos Page
 */
get_header();

$filter   = isset( $_GET['filter'] ) ? sanitize_text_field( wp_unslash( $_GET['filter'] ) ) : '';
$cat      = isset( $_GET['cat'] ) ? absint( $_GET['cat'] ) : 0;
$instance = array();

if ( $filter && is_numeric( $filter ) ) {
    $cat    = absint( $filter );
    $filter = 'latest';
}

$tmw_video_widget_class = class_exists( 'TMW_WP_Widget_Videos_Block_Fixed' ) ? 'TMW_WP_Widget_Videos_Block_Fixed' : 'wpst_WP_Widget_Videos_Block';

if ( $filter ) {
    if ( 'latest' === $filter ) {
        $instance = array(
            'title'          => __( 'Latest videos', 'retrotube-child' ),
            'video_type'     => 'latest',
            'video_number'   => 12,
            'video_category' => $cat,
        );
    } elseif ( 'random' === $filter ) {
        $instance = array(
            'title'          => __( 'Random videos', 'retrotube-child' ),
            'video_type'     => 'random',
            'video_number'   => 12,
            'video_category' => $cat,
        );
    } elseif ( 'related' === $filter ) {
        $instance = array(
            'title'          => __( 'Related videos', 'retrotube-child' ),
            'video_type'     => 'random',
            'video_number'   => 12,
            'video_category' => $cat,
        );
    } elseif ( 'longest' === $filter ) {
        $instance = array(
            'title'          => __( 'Longest videos', 'retrotube-child' ),
            'video_type'     => 'longest',
            'video_number'   => 12,
            'video_category' => $cat,
        );
    } elseif ( 'popular' === $filter ) {
        $instance = array(
            'title'          => __( 'Most popular videos', 'retrotube-child' ),
            'video_type'     => 'popular',
            'video_number'   => 12,
            'video_category' => $cat,
        );
    } elseif ( 'most-viewed' === $filter ) {
        $instance = array(
            'title'          => __( 'Most viewed videos', 'retrotube-child' ),
            'video_type'     => 'most-viewed',
            'video_number'   => 12,
            'video_category' => $cat,
        );
    }
}

tmw_render_sidebar_layout('', function () use ( $filter, $instance, $tmw_video_widget_class ) {
    ?>
      <header class="entry-header">
        <h1 class="entry-title"><i class="fa fa-video-camera"></i> Videos</h1>
      </header>

      <?php if ( $filter ) : ?>

        <?php if ( ! empty( $instance ) ) : ?>
          <?php
          the_widget(
              $tmw_video_widget_class,
              $instance,
              array(
                  'before_widget' => '<section class="widget widget_videos_block">',
                  'after_widget'  => '</section>',
                  'before_title'  => '<h2 class="widget-title">',
                  'after_title'   => '</h2>',
              )
          );
          ?>
        <?php else : ?>
          <p><?php esc_html_e( 'No videos found for this filter.', 'retrotube-child' ); ?></p>
        <?php endif; ?>

      <?php elseif ( is_page( 'videos' ) ) : ?>

        <?php
        the_widget(
            $tmw_video_widget_class,
            array(
                'title'          => 'Videos being watched',
                'video_type'     => 'random',
                'video_number'   => 8,
                'video_category' => 0,
            ),
            array(
                'before_widget' => '<section class="widget widget_videos_block">',
                'after_widget'  => '</section>',
                'before_title'  => '<h2 class="widget-title">',
                'after_title'   => '</h2>',
            )
        );

        the_widget(
            $tmw_video_widget_class,
            array(
                'title'          => 'Latest videos',
                'video_type'     => 'latest',
                'video_number'   => 6,
                'video_category' => 0,
            ),
            array(
                'before_widget' => '<section class="widget widget_videos_block">',
                'after_widget'  => '</section>',
                'before_title'  => '<h2 class="widget-title">',
                'after_title'   => '</h2>',
            )
        );

        the_widget(
            $tmw_video_widget_class,
            array(
                'title'          => 'Longest videos',
                'video_type'     => 'longest',
                'video_number'   => 12,
                'video_category' => 0,
            ),
            array(
                'before_widget' => '<section class="widget widget_videos_block">',
                'after_widget'  => '</section>',
                'before_title'  => '<h2 class="widget-title">',
                'after_title'   => '</h2>',
            )
        );
        ?>

      <?php endif; ?>
    <?php
});

get_footer();
