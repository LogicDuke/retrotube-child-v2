<?php
/**
 * Homepage main query pagination alignment
 */

add_action('pre_get_posts', function ($query) {

    if (is_admin() || ! $query->is_main_query()) {
        return;
    }

    if ($query->is_front_page() || $query->is_home()) {

        $query->set('post_type', ['model']);
        $query->set('posts_per_page', 24);
        $query->set('paged', max(1, get_query_var('paged')));

    }

}, 10);
