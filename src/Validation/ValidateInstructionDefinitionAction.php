<?php

namespace IrRegular\Itinerant\Validation;

use IrRegular\Itinerant\NodeAdapter\Leaf;
use IrRegular\Itinerant\NodeAdapter\NodeAdapterInterface;

/**
 * Internal library action: validates and sanitises instruction definitions
 *
 * An instruction definition must be a valid Itinerant expressions (thus, see ValidateExpressionAction)
 *
 * In addition to the usual zero-argument instructions, the expression may also contain numerical strings
 * ('0', '1', ...) which are placeholders which the engine substitutes with arguments.
 *
 * The resulting expression constitutes valid input for InstructionResolver::register(_, $expression)
 *
 * @todo Scheduled for removal - instead, UserDefined can unwrap the argument before substituting
 */
class ValidateInstructionDefinitionAction extends ValidateExpressionAction
{
    protected function isZeroArgumentShorthand(NodeAdapterInterface $d): bool
    {
        return parent::isZeroArgumentShorthand($d)
            || $this->isArgumentPlaceholder($d);
    }

    protected function convertZeroArgumentShorthand(NodeAdapterInterface $d)
    {
        // do not wrap argument placeholders
        return $this->isArgumentPlaceholder($d)
            ? new Leaf($d->getValue())
            : parent::convertZeroArgumentShorthand($d);
    }

    private function isArgumentPlaceholder(NodeAdapterInterface $d): bool
    {
        return is_numeric($d->getValue());
    }
}
