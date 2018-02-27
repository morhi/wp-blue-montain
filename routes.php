<?php

Routes::map('/custom', function ($params) {
    echo 'Custom Route';
    die();
});