<?php

namespace JaneOlszewska\Experiments\ComposableGraphTraversal\ChildHandler;

class RestOfElements implements ChildHandlerInterface
{
    public function getChildren($node): ?array
    {
        return is_array($node) ? array_slice($node, 1) : null;
    }

    public function setChildren(&$node, array $children = []): void
    {
        if (is_array($node)) {
            array_splice($node, 1, count($node), $children);
        }
    }
}
