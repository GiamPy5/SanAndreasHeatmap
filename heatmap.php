<?php

ini_set('display_errors', 1);

require_once('assets/classes/sanandreas_heatmap.php');

$heatmap =
    array(
        'debug'  => FALSE,
        'noc'    => 32,
        'r'      => 25,
        'dither' => FALSE,
        'format' => 'jpeg'
    );

$directory =
    array(
        'assets_classes' => 'assets/classes/',
        'assets_images'  => 'assets/images/',
    );

$database =
    array(
        'type'     => 'array',

        'hostname' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'heatmap',

        'table' => 'coordinates',
        'x_column' => 'x',
        'y_column' => 'y'
    );

$Heatmap = new CSanAndreasHeatmap($database, $directory, $heatmap);

$Heatmap->create_map("map6000.jpg");

$Heatmap->apply_heatmap();

$Heatmap->output_map();

$Heatmap->free_map_memory();
