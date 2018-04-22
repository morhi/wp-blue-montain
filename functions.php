<?php

// Since this theme needs the composer autoload file the user must run the install command.
if (!file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    add_action('admin_notices', function () {
        ?>
        <div class="error notice">
            <p><?php _e('Could not find autoloader. Run command "composer install" first before using this theme!'); ?></p>
        </div>
        <?php
    });
} else {
    require dirname(__FILE__) . '/vendor/autoload.php';

    $app = new \BlueMountain\Application();
    $app->boot();
}

add_action('after_switch_theme', function (...$args) {
    // Theme now activated
});