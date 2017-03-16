<?php

namespace JaneOlszewska\Experiments\ComposableGraphTraversal\Action;

class ValidateUserRegisteredTraversalStrategy extends ValidateTraversalStrategy
{
    protected function isZeroArgumentNode($d): bool
    {
        return parent::isZeroArgumentNode($d)
            || is_numeric($d); // allow for substitutions
        // todo: make sure that substitutions are in a valid range (0...(count_of_args - 1)
    }

    protected function isValidStrategy($d): bool
    {
        return parent::isValidStrategy($d); // todo: allow for self-reference
    }
}
