<?php

namespace JaneOlszewska\Itinerant\Action;

class ValidateUserRegisteredTraversalStrategy extends ValidateTraversalStrategy
{
    /**
     * @param mixed $d
     * @return bool
     */
    protected function isZeroArgumentNode($d): bool
    {
        return parent::isZeroArgumentNode($d)
            || is_numeric($d); // allow for substitutions
        // todo: make sure that substitutions are in a valid range (0...(count_of_args - 1) - must set parent key
    }

    protected function sanitiseZeroArgumentNode($d)
    {
        return is_numeric($d) ? $d : parent::sanitiseZeroArgumentNode($d);
    }
}
