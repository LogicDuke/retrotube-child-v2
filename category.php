<?php
/**
 * Category archive template.
 *
 * @package suspended-flavor-flavor
 */

defined('ABSPATH') || exit;

get_header();

$sidebar_class = function_exists('wpst_get_sidebar_position_class') 
    ? wpst_get_sidebar_position_class() 
    : 'with-sidebar-right';
?>

<div id="primary" class="content-area <?php echo esc_attr($sidebar_class); ?>">
    <main id="main" class="site-main <?php echo esc_attr($sidebar_class); ?>" role="main">

        <header class="page-header">
            <?php the_archive_title('<h1 class="widget-title"><i class="fa fa-folder-open"></i>', '</h1>'); ?>

            <?php
            // Show description at top if CPT has content OR theme setting is 'top'
            $desc_position = function_exists('xbox_get_field_value') 
                ? xbox_get_field_value('wpst-options', 'cat-desc-position') 
                : 'top';
            
            $show_top = ($desc_position === 'top');
            
            // Force show if we have CPT content
            if (function_exists('tmw_get_category_page_content')) {
                $cpt_data = tmw_get_category_page_content();
                if ($cpt_data['has_content']) {
                    $show_top = true;
                }
            }

            if ($show_top) {
                the_archive_description('<div class="archive-description">', '</div>');
            }
            ?>

            <?php get_template_part('template-parts/content', 'filters'); ?>
        </header>

        <?php if (have_posts()) : ?>

            <div class="videos-list">
                <?php
                while (have_posts()) :
                    the_post();
                    get_template_part('template-parts/loop', 'video');
                endwhile;
                ?>
            </div>

            <?php
            if (function_exists('wpst_page_navi')) {
                wpst_page_navi();
            } else {
                the_posts_pagination();
            }
            ?>

        <?php else : ?>

            <?php get_template_part('template-parts/content', 'none'); ?>

        <?php endif; ?>

        <?php
        // Description at bottom
        if ($desc_position === 'bottom') :
            ?>
            <div class="clear"></div>
            <?php the_archive_description('<div class="archive-description">', '</div>'); ?>
        <?php endif; ?>

    </main>
</div>

<?php
get_sidebar();
get_footer();
