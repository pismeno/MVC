<?php

require_once get_template_directory() . '/config.php';
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

function init()
{
    register_nav_menus([
        'header-menu'   => 'Header Menu',
        'footer-menu'   => 'Footer Menu (Sloupec 1)',
        'footer-col-2'  => 'Footer Menu (Sloupec 2 - Sídlo)',
        'footer-col-3'  => 'Footer Menu (Sloupec 3 - Adresa)',
        'footer-col-4'  => 'Footer Menu (Sloupec 4 - Otevírací doba)',
    ]);
}

function post_init()
{
    add_theme_support('post-thumbnails');
}

add_action('init', 'init');
add_action('after_setup_theme', 'post_init');