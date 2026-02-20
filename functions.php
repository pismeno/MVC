<?php

function ob_action(string $action) : string
{
    ob_start();
    do_action($action);
    return ob_get_clean();
}

function replace_keys(string $viewFilename) : string
{
    $GLOBAL_KEYS = [
        'WP_HEAD' => ob_action('wp_head'),
        'WP_FOOTER' => ob_action('wp_footer')
    ];

    $view = file_get_contents(get_template_directory() . '/views/' . $viewFilename);

    foreach ($GLOBAL_KEYS as $key => $value) {
        $view = str_replace("{{ " . $key . " }}", $value, $view);
    }

    // TODO
    if (!have_posts()) $view = $view;

    while (have_posts()) {
        the_post();

        $POST_KEYS = [
            'CLASS' => in_category('3') ? 'post-cat-three' : 'post',
            'TITLE' => get_the_title(),
            'TITLE_ATTRIBUTE' => the_title_attribute(['echo' => false]),
            'PERMALINK' => get_permalink(),
            'CONTENT' => get_the_content(),
            'AUTHOR' => get_the_author(),
            'DATE' => get_the_date(),
            'TIME' => get_the_time('F jS, Y'),
            'AUTHOR_POSTS_LINK' => get_the_author_posts_link(),
            'CATEGORIES' => get_the_category_list(', ')
        ];


        foreach ($POST_KEYS as $key => $value) {
            $view = str_replace("{{ " . $key . " }}", $value, $view);
        }
    }

    return $view;
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

//add_action( 'wp_enqueue_scripts', 'enqueue_styles' );