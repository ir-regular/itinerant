<?php

namespace JaneOlszewska\Itinerant\Action;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

/**
 * Internal library action: validates and sanitises strategy expansion given to StrategyResolver::registerStrategy()
 *
 * SanitiseRegisteredAction validates every node of the expansion by checking that:
 *   - the node is an array OR a numeric string indicating N-th argument substitution
 *   - if array, its first element is a string key, on the list of registered actions
 *   - if array, array element count equals (registered argument count + 1)
 *
 * For readability, the above ruleset has an exception. Zero-argument strategies can be represented
 * as strings (keys) instead of single-element arrays.
 *
 * SanitiseRegisteredAction sanitises such strings by converting them into single-element arrays.
 */
class SanitiseRegisteredAction extends SanitiseAppliedAction
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
