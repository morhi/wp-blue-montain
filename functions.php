<?php

require dirname(__FILE__) . '/vendor/autoload.php';

$app = new \BlueMountain\Application();
$app->boot();

add_action('after_switch_theme', function(...$args){
    // Theme now activated
});
