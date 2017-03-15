<?php

namespace JaneOlszewska\Experiments\ComposableGraphTraversal\ChildHandler;

class SecondElement implements ChildHandlerInterface
{
    public function getChildren($node): ?array
    {
        return is_array($node) ? ($node[1] ?? null) : null;
    }

    public function setChildren(&$node, array $children = []): void
    {
        if (is_array($node)) {
            $node[1] = $children;
        }
    }
}
