<?php

namespace JaneOlszewska\Itinerant\ChildHandler;

interface ChildHandlerInterface
{
    public function getChildren($node): ?array;

    public function setChildren(&$node, array $children = []): void;
}
