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

function setup_theme()
{
    add_theme_support('post-thumbnails');
}

add_action('after_setup_theme', 'setup_theme');