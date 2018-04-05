<?php

namespace JaneOlszewska\Itinerant\Validation;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

/**
 * Internal library action: validates and sanitises strategy expansion instructions
 *
 * The resulting expansion instructions constitute valid input for InstructionResolver::registerStrategy()
 *
 * SanitiseRegisteredAction validates every node of the strategy expansion (instruction) by checking that:
 *   - the node is an array OR a numeric string indicating N-th argument substitution
 *   - if array, its first element is a string key, on the list of registered actions
 *   - if array, array element count equals (registered argument count + 1)
 *
 * For readability, the above ruleset has an exception. Zero-argument strategies can be represented
 * as instructions as strings (keys) instead of single-element arrays.
 *
 * SanitiseRegisteredAction sanitises such strings by converting them into single-element arrays.
 */
class SanitiseRegisteredAction extends SanitiseAppliedAction
{
    /**
     * @param NodeAdapterInterface $d
     * @return bool
     */
    protected function isZeroArgumentStrategy(NodeAdapterInterface $d): bool
    {
        return parent::isZeroArgumentStrategy($d)
            || is_numeric($d->getValue()); // allow for substitutions
    }

    protected function sanitiseZeroArgumentNode(NodeAdapterInterface $d)
    {
        // do not wrap substitutions
        return is_numeric($d->getValue()) ? $d : parent::sanitiseZeroArgumentNode($d);
    }
}
