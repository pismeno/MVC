<?php

require_once get_template_directory() . '/App/ViewBuilder.php';
use App\ViewBuilder;

function get_the_view(string $bladeFilePath): string
{
    $builder = new ViewBuilder($bladeFilePath);
    return $builder->build();
}

function the_view(string $bladeFilePath): void
{
    echo get_the_view($bladeFilePath);
}

function enqueue_theme_styles()
{
    wp_enqueue_style(
        'my-custom-style',
        get_template_directory_uri() . '/style.css',
        array(),
        '1.0.0',
        'all'
    );
}

function setup_theme()
{
    add_theme_support('post-thumbnails');
}

add_action('wp_enqueue_scripts', 'enqueue_theme_styles');
add_action('after_setup_theme', 'setup_theme');