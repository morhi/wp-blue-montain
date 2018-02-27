<?php

namespace BlueMountain\Context;

use Timber\Post;

class Page
{
    public function __invoke($context)
    {
        $context['post'] = new Post();

        return $context;
    }
}