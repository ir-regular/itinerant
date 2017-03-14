<?php

namespace JaneOlszewska\Experiments\ComposableGraphTraversal;

interface Action
{
    public function isApplicableTo(Datum $d): bool;

    public function applyTo(Datum $d): Datum;
}
