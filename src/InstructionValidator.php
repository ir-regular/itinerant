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
    private const SANITISE_APPLIED = 'sanitise_applied';
    private const SANITISE_REGISTERED = 'sanitise_registered';

    /** @var int[] */
    private $argCounts = [
        InstructionResolver::ID => 0,
        InstructionResolver::FAIL => 0,
        InstructionResolver::SEQ => 2,
        InstructionResolver::CHOICE => 2,
        InstructionResolver::ALL => 1,
        InstructionResolver::ONE => 1,
        InstructionResolver::ADHOC => 2,
        self::SANITISE_APPLIED => 0,
        self::SANITISE_REGISTERED => 0
    ];

    /** @var SanitiseAppliedAction */
    private $sanitiseAppliedAction;

    /** @var SanitiseRegisteredAction */
    private $sanitiseRegisteredAction;

    /** @var InstructionResolver */
    private $resolver;

    /** @var StrategyStack */
    private $stack;

    public function __construct()
    {
        // Yes, this is the second copy of both classes (compared to Itinerant).
        // This guarantees that SANITISE_* strategies won't conflict with user's strategies.
        $this->resolver = new InstructionResolver();
        $this->stack = new StrategyStack($this->resolver);

        $this->registerValidationStrategies($this->resolver);
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
        $this->sanitiseAppliedAction->setStrategyArgumentCounts($this->argCounts);

        // apply without validation to avoid infinite recursion
        $result = $this->stack->apply([self::SANITISE_APPLIED], new RestOfElements($instruction));

        if (Fail::fail() === $result) {
            $error = $this->formatErrorMessage($this->sanitiseAppliedAction);
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

        $this->sanitiseRegisteredAction->setStrategyArgumentCounts(
            array_merge([$strategy => $argCount], $this->argCounts)
        );

        // apply without validation to avoid infinite recursion
        $result = $this->stack->apply([self::SANITISE_REGISTERED], new RestOfElements($instruction));

        if (Fail::fail() === $result) {
            $error = $this->formatErrorMessage($this->sanitiseRegisteredAction);
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

    private function registerValidationStrategies(InstructionResolver $resolver)
    {
        $this->sanitiseAppliedAction = new SanitiseAppliedAction();
        $this->sanitiseRegisteredAction = new SanitiseRegisteredAction();

        // Check for whether we encountered an adhoc action, callback formatted like an array
        $isNotCallableArray = function ($d) {
            return (is_array($d) && is_callable($d)) ? Fail::fail() : $d;
        };

        // register without validation to avoid infinite recursion
        $resolver->registerStrategy(
            self::SANITISE_APPLIED,
            ['choice',
                ['seq',
                    // if doesn't fail, safe to nest further
                    ['adhoc', ['fail'], $isNotCallableArray],
                    // top-down application of $this->sanitiseAppliedAction
                    ['seq', ['adhoc', ['fail'], $this->sanitiseAppliedAction], ['all', [self::SANITISE_APPLIED]]],
                ],
                // just validate without nesting
                ['adhoc', ['fail'], $this->sanitiseAppliedAction]
            ]
        );

        // register without validation to avoid infinite recursion
        $resolver->registerStrategy(
            self::SANITISE_REGISTERED,
            // top-down application of $this->sanitiseRegisteredAction
            ['seq', ['adhoc', ['fail'], $this->sanitiseRegisteredAction], ['all', [self::SANITISE_REGISTERED]]]
        );

        $this->argCounts[self::SANITISE_APPLIED] = 0;
        $this->argCounts[self::SANITISE_REGISTERED] = 0;
    }
}
