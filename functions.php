<?php

require_once dirname(__FILE__) . '/loader.class.php';
require_once dirname(__FILE__) . '/plugins/timber-library/timber.php';

global $loader;

$loader = new \BlueMountain\Loader();
$loader->boot();

add_action('after_switch_theme', function(...$args){
    // Theme now activated
});
