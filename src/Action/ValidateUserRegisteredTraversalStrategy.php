<?php

namespace JaneOlszewska\Itinerant\Action;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class ValidateUserRegisteredTraversalStrategy extends ValidateTraversalStrategy
{
    /**
     * @param NodeAdapterInterface $d
     * @return bool
     */
    protected function isZeroArgumentNode(NodeAdapterInterface $d): bool
    {
        return parent::isZeroArgumentNode($d)
            || is_numeric($d->getValue()); // allow for substitutions
        // todo: make sure that substitutions are in a valid range (0...(count_of_args - 1) - must set parent key
    }

    protected function sanitiseZeroArgumentNode(NodeAdapterInterface $d)
    {
        // do not wrap substitutions
        return is_numeric($d->getValue()) ? $d : parent::sanitiseZeroArgumentNode($d);
    }
}
