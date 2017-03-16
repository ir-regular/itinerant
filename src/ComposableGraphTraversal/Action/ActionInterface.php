<?php

namespace JaneOlszewska\Experiments\ComposableGraphTraversal\Action;

interface ActionInterface
{
    public function isApplicableTo($d): bool;

    /**
     * @param mixed $d
     * @return mixed $d or its clone, potentially transformed by application of action
     */
    public function applyTo($d);
}
