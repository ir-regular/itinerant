<?php

namespace JaneOlszewska\Itinerant\Strategy;

class Id
{
    public function __invoke($node)
    {
        return $node;
    }
}
