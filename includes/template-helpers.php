<?php
/**
 * E-commerce Template Helper Functions
 *
 * This file centralizes all content-fetching logic for the home page. Currently, it
 * returns placeholder data. To make the site dynamic, you will replace the
 * hardcoded arrays in these functions with your database/CMS queries.
 */

function get_content_for_section($key, $count) {
    $items = [];
    for ($i = 1; $i <= $count; $i++) {
        $items[] = [
            'img_src' => "https://picsum.photos/seed/{$key}{$i}/400/400",
            'title'   => ucfirst($key) . ' ' . $i,
            'price'   => '$' . number_format(rand(10, 100), 2),
            'url'     => '#'
        ];
    }
    return $items;
}

function get_mosaic_section_content() {
    return [
        ['type' => 'big', 'img_src' => 'https://picsum.photos/seed/mosaic1/600/400', 'alt' => 'Big promotion'],
        ['type' => 'wide', 'img_src' => 'https://picsum.photos/seed/mosaic2/600/200', 'alt' => 'Wide promotion'],
        ['type' => 'card', 'img_src' => 'https://picsum.photos/seed/mosaic3/300/200', 'alt' => 'Card promotion'],
        ['type' => 'tall', 'img_src' => 'https://picsum.photos/seed/mosaic4/300/400', 'alt' => 'Tall promotion'],
        ['type' => 'wide', 'img_src' => 'https://picsum.photos/seed/mosaic5/600/200', 'alt' => 'Wide promotion 2'],
        ['type' => 'card', 'img_src' => 'https://picsum.photos/seed/mosaic6/300/200', 'alt' => 'Card promotion 2'],
        ['type' => 'card', 'img_src' => 'https://picsum.photos/seed/mosaic7/300/200', 'alt' => 'Card promotion 3'],
    ];
}

function get_furniture_section_content() {
    $items = [];
    $titles = ['Sleeper Chair', 'Kitchen Cart', 'TV Stand', 'Platform Bed', 'Mattress', 'Folding Chairs'];
    for ($i = 0; $i < count($titles); $i++) {
        $items[] = [
            'img_src'      => "https://picsum.photos/seed/furn{$i}/220/200",
            'title'        => $titles[$i],
            'price'        => 'Now $' . number_format(rand(40, 200), 2),
            'strike_price' => '$' . number_format(rand(201, 400), 2),
            'url'          => '#'
        ];
    }
    return $items;
}
?>