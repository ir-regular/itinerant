<?php

namespace JaneOlszewska\Experiments\ComposableGraphTraversal\ChildHandler;

interface ChildHandlerInterface
{
    public function getChildren($node): ?array;

    public function setChildren(&$node, array $children = []): void;
}
