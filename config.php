<?php

return [
    /**
     * A list of stylesheets to be included in the head of the page
     */
    'styles' => [
        get_template_directory_uri() . '/assets/css/bootstrap-4.0.0.min.css',
        get_template_directory_uri() . '/assets/css/style.css'
    ],
    /**
     * A list of script which should be included at the end of the body.
     */
    'scripts' => [
        get_template_directory_uri() . '/assets/js/custom.js'
    ],
    /**
     * A list of menus the theme can display.
     */
    'menus' => [
        'primary' => __('Main Navigation'),
        'footer' => __('Footer')
    ]
];