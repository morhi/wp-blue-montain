<?php

namespace BlueMountain\Context;

use Timber\Post;

class FullWidth
{
    public function __invoke($context)
    {
        $context['post'] = new Post();

        return $context;
    }
}