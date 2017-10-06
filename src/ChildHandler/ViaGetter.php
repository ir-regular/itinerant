<?php

namespace JaneOlszewska\Itinerant\ChildHandler;

class ViaGetter implements ChildHandlerInterface
{
    public function getChildren($node): ?array
    {
        return method_exists($node, 'getChildren') ? $node->getChildren() : null;
    }

    public function setChildren(&$node, array $children = []): void
    {
        if (method_exists($node, 'setChildren')) {
            $node->setChildren($children);
        }
    }
}
