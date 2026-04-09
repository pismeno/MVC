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
        'header-contact' => 'Header Contact',
        'header-col-1'   => 'Header Menu col 1 - Úvodní strana',
        'header-col-2'   => 'Header Menu col 2 - Podpora',
        'header-col-3'   => 'Header Menu col 3 - Obchodní podmínky',
        'header-col-4'   => 'Header Menu col 4 - Kontakt',
        'header-col-5'   => 'Header Menu col 5 - Doprava',
        'banner-title-1' => 'Banner Title 1',
        'banner-title-2' => 'Banner Title 2',
        'footer-col-1'   => 'Footer Menu col 1 - Vše o nákupu',
        'footer-col-2'  => 'Footer Menu col 2 - Sídlo',
        'footer-col-3'  => 'Footer Menu col 3 - Adresa',
        'footer-col-4'  => 'Footer Menu col 4 - Otevírací doba',
    ]);
}

function post_init()
{
    add_theme_support('post-thumbnails');
}

add_action('init', 'init');
add_action('after_setup_theme', 'post_init');