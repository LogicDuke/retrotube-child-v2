<?php
/**
 * Category Pages CPT - editable content for category archives.
 *
 * @package suspended-flavor-flavor
 * @since 5.0.0
 */

namespace TMW\Categories;

defined('ABSPATH') || exit;

class CategoryPages {

    const POST_TYPE = 'category_page';

    public function __construct() {
        add_action('init', [$this, 'register_post_type'], 5);
        add_action('created_category', [$this, 'on_category_created'], 10, 2);
        add_action('edited_category', [$this, 'on_category_edited'], 10, 2);
        add_action('pre_delete_term', [$this, 'on_category_deleted'], 10, 2);
        add_action('admin_init', [$this, 'maybe_create_for_existing']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('add_meta_boxes', [$this, 'add_metabox']);
        add_filter('category_row_actions', [$this, 'add_row_action'], 10, 2);
        add_filter('get_the_archive_description', [$this, 'filter_description'], 15);
        add_filter('get_the_archive_title', [$this, 'filter_title'], 15);
        add_action('after_setup_theme', [$this, 'register_functions']);
    }

    public function register_functions() {
        if (!function_exists('tmw_get_category_page_content')) {
            function tmw_get_category_page_content($category = null) {
                return CategoryPages::get_content($category);
            }
        }
    }

    public function register_post_type() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name'          => __('Category Pages', 'flavor'),
                'singular_name' => __('Category Page', 'flavor'),
                'edit_item'     => __('Edit Category Page', 'flavor'),
            ],
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_rest'        => true,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions'],
            'capability_type'     => 'post',
        ]);
    }

    public static function get_post($category) {
        if (is_numeric($category)) $category = get_term($category, 'category');
        if (!$category instanceof \WP_Term) return null;

        $post_id = get_term_meta($category->term_id, '_tmw_category_page_id', true);
        if ($post_id) {
            $post = get_post($post_id);
            if ($post && $post->post_type === self::POST_TYPE && $post->post_status !== 'trash') {
                return $post;
            }
        }

        $posts = get_posts([
            'post_type'      => self::POST_TYPE,
            'name'           => $category->slug,
            'posts_per_page' => 1,
            'post_status'    => ['publish', 'draft'],
        ]);

        if (!empty($posts)) {
            update_term_meta($category->term_id, '_tmw_category_page_id', $posts[0]->ID);
            return $posts[0];
        }
        return null;
    }

    public static function create_post($category) {
        if (is_numeric($category)) $category = get_term($category, 'category');
        if (!$category instanceof \WP_Term) return new \WP_Error('invalid_term', 'Invalid category');

        $existing = self::get_post($category);
        if ($existing) return $existing->ID;

        $post_id = wp_insert_post([
            'post_type'    => self::POST_TYPE,
            'post_title'   => $category->name,
            'post_name'    => $category->slug,
            'post_content' => $category->description ?: '',
            'post_status'  => 'publish',
            'meta_input'   => ['_tmw_linked_category_id' => $category->term_id],
        ], true);

        if (!is_wp_error($post_id)) {
            update_term_meta($category->term_id, '_tmw_category_page_id', $post_id);
        }
        return $post_id;
    }

    public static function get_content($category = null) {
        $default = ['title' => '', 'content' => '', 'excerpt' => '', 'has_content' => false, 'post_id' => 0];
        if ($category === null) $category = get_queried_object();
        if (!$category instanceof \WP_Term) return $default;

        $post = self::get_post($category);
        if (!$post || $post->post_status !== 'publish') return $default;

        $content = $post->post_content;
        if (!empty($content)) $content = apply_filters('the_content', $content);

        return [
            'title'       => $post->post_title,
            'content'     => $content,
            'excerpt'     => $post->post_excerpt,
            'has_content' => !empty(trim($post->post_content)),
            'post_id'     => $post->ID,
        ];
    }

    public function on_category_created($term_id, $tt_id) {
        self::create_post($term_id);
    }

    public function on_category_edited($term_id, $tt_id) {
        $term = get_term($term_id, 'category');
        $post = self::get_post($term);
        if (!$post) return;

        if ($post->post_name !== $term->slug) {
            wp_update_post(['ID' => $post->ID, 'post_name' => $term->slug]);
        }
    }

    public function on_category_deleted($term_id, $taxonomy) {
        if ($taxonomy !== 'category') return;
        $term = get_term($term_id, 'category');
        $post = self::get_post($term);
        if ($post) wp_trash_post($post->ID);
    }

    public function maybe_create_for_existing() {
        if (get_option('tmw_category_pages_init_v5')) return;
        if (!current_user_can('manage_options')) return;

        $categories = get_terms(['taxonomy' => 'category', 'hide_empty' => false]);
        if (is_wp_error($categories)) return;

        foreach ($categories as $cat) {
            if ($cat->slug === 'uncategorized') continue;
            self::create_post($cat);
        }
        update_option('tmw_category_pages_init_v5', 1);
    }

    public function add_admin_menu() {
        add_submenu_page('edit.php', __('Category Pages', 'flavor'), __('Category Pages', 'flavor'), 'manage_categories', 'edit.php?post_type=' . self::POST_TYPE);
    }

    public function add_metabox() {
        add_meta_box('tmw_category_page_info', __('Linked Category', 'flavor'), [$this, 'render_metabox'], self::POST_TYPE, 'side', 'high');
    }

    public function render_metabox($post) {
        $term_id = get_post_meta($post->ID, '_tmw_linked_category_id', true);
        if (!$term_id) { echo '<p>No linked category.</p>'; return; }
        $term = get_term($term_id, 'category');
        if (!$term || is_wp_error($term)) { echo '<p>Category not found.</p>'; return; }
        ?>
        <p><strong>Category:</strong> <?php echo esc_html($term->name); ?></p>
        <p><strong>Slug:</strong> <code><?php echo esc_html($term->slug); ?></code></p>
        <p><strong>Videos:</strong> <?php echo esc_html($term->count); ?></p>
        <p><a href="<?php echo esc_url(get_term_link($term)); ?>" class="button" target="_blank">View</a> <a href="<?php echo esc_url(get_edit_term_link($term->term_id, 'category')); ?>" class="button">Edit</a></p>
        <?php
    }

    public function add_row_action($actions, $term) {
        $post = self::get_post($term);
        if ($post) {
            $actions['edit_page'] = sprintf('<a href="%s">Edit Page Content</a>', esc_url(get_edit_post_link($post->ID)));
        }
        return $actions;
    }

    public function filter_description($desc) {
        if (!is_category()) return $desc;
        $data = self::get_content();
        return $data['has_content'] ? $data['content'] : $desc;
    }

    public function filter_title($title) {
        if (!is_category()) return $title;
        $cat = get_queried_object();
        $post = self::get_post($cat);
        if (!$post || $post->post_status !== 'publish') return $title;
        return ($post->post_title !== $cat->name) ? esc_html($post->post_title) : $title;
    }
}
