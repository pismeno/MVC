<?php

function ob_action(string $action) : string
{
    ob_start();
    do_action($action);
    return ob_get_clean();
}

function replace_keys(string $viewFilename) : string
{
    $main_view = file_get_contents(get_template_directory() . '/views/' . $viewFilename);

    $post_template = file_get_contents(get_template_directory() . '/views/one_post.html');

    $all_posts = "";

    if (have_posts()) {
        while (have_posts()) {
            the_post();

            $current_post_html = $post_template;

            $POST_KEYS = [
                'CLASS' => in_category('3') ? 'post-cat-three' : 'post',
                'TITLE' => get_the_title(),
                'PERMALINK' => get_permalink(),
                'THUMBNAIL' => get_the_post_thumbnail(),
                'CONTENT' => get_the_content(),
                'TIME' => get_the_time('F jS, Y'),
                'AUTHOR_POSTS_LINK' => get_the_author_posts_link(),
                'CATEGORIES' => get_the_category_list(', ')
            ];

            foreach ($POST_KEYS as $key => $value) {
                $current_post_html = str_replace("{{ " . $key . " }}", $value, $current_post_html);
            }

            $all_posts .= $current_post_html;
        }
    }

    $GLOBAL_KEYS = [
        'WP_HEAD' => ob_action('wp_head'),
        'WP_FOOTER' => ob_action('wp_footer'),
        'PAGINATION' => get_the_posts_pagination(),
        'POST_LOOP' => $all_posts
    ];

    foreach ($GLOBAL_KEYS as $key => $value) {
        $main_view = str_replace("{{ " . $key . " }}", (string)$value, $main_view);
    }

    return $main_view;
}
function enqueue_styles() {
    wp_enqueue_style(
        'my-custom-style',
        get_template_directory_uri() . '/style.css',
        array(),
        '1.0.0',
        'all'
    );
}

add_theme_support( 'post-thumbnails' );