<?php

namespace BlueMountain\Context;

use Timber\Post;

class Single
{
    public function __invoke($context)
    {
        $context['post'] = new Post();

        return $context;
    }
}