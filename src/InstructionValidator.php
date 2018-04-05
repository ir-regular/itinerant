<?php

namespace JaneOlszewska\Itinerant;

use JaneOlszewska\Itinerant\Action\SanitiseAppliedAction;
use JaneOlszewska\Itinerant\Action\SanitiseRegisteredAction;
use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\NodeAdapter\RestOfElements;
use JaneOlszewska\Itinerant\Strategy\InstructionResolver;

/**
 * Witness the ultimate coolness: Itinerant is self-validating!
 */
class InstructionValidator
{
    private const TD_PRE = 'td_pre';

    /** @var int[] */
    private $argCounts = [
        InstructionResolver::ID => 0,
        InstructionResolver::FAIL => 0,
        InstructionResolver::SEQ => 2,
        InstructionResolver::CHOICE => 2,
        InstructionResolver::ALL => 1,
        InstructionResolver::ONE => 1,
        InstructionResolver::ADHOC => 2,
    ];

    /** @var InstructionResolver */
    private $resolver;

    /** @var StrategyStack */
    private $stack;

    public function __construct()
    {
        // Yes, both classes are instantiated twice (the 'original' instances are in Itinerant).
        // This guarantees that TD_PRE strategy won't conflict with user's strategies.
        $resolver = new InstructionResolver();
        // top-down, depth first, prefix application
        $resolver->registerStrategy(self::TD_PRE, ['seq', '0', ['all', [self::TD_PRE, '0']]]);
        $this->resolver = $resolver;

        $this->stack = new StrategyStack($this->resolver);
    }

    /**
     * Sanitise the instructions applied to a node directly.
     *
     * @param array|string $instruction
     * @return array
     * @throws \InvalidArgumentException if $instruction is found invalid
     */
    public function sanitiseApplied($instruction)
    {
        $sanitiseAppliedAction = new SanitiseAppliedAction($this->argCounts);

        // apply without validation to avoid infinite recursion
        $result = $this->stack->apply(
            [self::TD_PRE, ['adhoc', ['fail'], $sanitiseAppliedAction]],
            new RestOfElements($instruction)
        );

        if (Fail::fail() === $result) {
            $error = $this->formatErrorMessage($sanitiseAppliedAction);
            throw new \InvalidArgumentException($error);
        }

        $result = $result->getNode();

        return $result;
    }

    /**
     * Sanitise the instructions provided for user-defined strategies.
     *
     * Compared to Itinerant::sanitiseApplied(), this method needs slightly expanded validation rules
     * since instructions for user-defined strategies contain argument substitution markers.
     *
     * @param string $strategy
     * @param array $instruction
     * @param int $argCount
     * @return array
     */
    public function sanitiseRegistered(string $strategy, array $instruction, int $argCount)
    {
        if (array_key_exists($strategy, $this->argCounts)) {
            throw new \InvalidArgumentException("Cannot overwrite registered strategy key: {$strategy}");
        }

        if (is_numeric($strategy)) {
            throw new \InvalidArgumentException("Cannot register strategy under a numeric key: {$strategy}");
        }

        $sanitiseRegisteredAction = new SanitiseRegisteredAction(
            array_merge([$strategy => $argCount], $this->argCounts)
        );

        $result = $this->stack->apply(
            [self::TD_PRE, ['adhoc', ['fail'], $sanitiseRegisteredAction]],
            new RestOfElements($instruction)
        );

        if (Fail::fail() === $result) {
            $error = $this->formatErrorMessage($sanitiseRegisteredAction);
            throw new \InvalidArgumentException($error);
        }

        $result = $result->getNode();

        return $result;
    }

    /**
     * @param SanitiseAppliedAction $action
     * @return string
     */
    private function formatErrorMessage(SanitiseAppliedAction $action): string
    {
        // (Neither node nor validation error should be null at this point)

        $instruction = ($node = $action->getInvalidNode()) ? $node->getValue() : null;
        $error = $action->getValidationError() ?: '';

        if (is_array($instruction) && isset($instruction[0])) {
            $error .= " (in {$instruction[0]})";
        }

        return $error;
    }
}
