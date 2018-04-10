<?php

namespace JaneOlszewska\Itinerant\Validation;

use JaneOlszewska\Itinerant\NodeAdapter\Fail;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\NodeAdapter\Sequence;
use JaneOlszewska\Itinerant\Instruction\ExpressionResolver;
use JaneOlszewska\Itinerant\Instruction\InstructionStack;

/**
 * Witness the ultimate coolness: Itinerant is self-validating!
 */
class InstructionValidator
{
    private const TD_PRE = 'td_pre';

    /** @var int[] */
    private $argCounts = [
        ExpressionResolver::ID => 0,
        ExpressionResolver::FAIL => 0,
        ExpressionResolver::SEQ => 2,
        ExpressionResolver::CHOICE => 2,
        ExpressionResolver::ALL => 1,
        ExpressionResolver::ONE => 1,
        ExpressionResolver::ADHOC => 2,
    ];

    /** @var ExpressionResolver */
    private $resolver;

    /** @var InstructionStack */
    private $stack;

    public function __construct()
    {
        // Yes, both classes are instantiated twice (the 'original' instances are in Itinerant).
        // This guarantees that TD_PRE strategy won't conflict with user's strategies.
        $resolver = new ExpressionResolver();
        // top-down, depth first, prefix application
        $resolver->register(self::TD_PRE, ['seq', '0', ['all', [self::TD_PRE, '0']]]);
        $this->resolver = $resolver;

        $this->stack = new InstructionStack($this->resolver);
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
            new Sequence($instruction)
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
     * @return array
     */
    public function sanitiseRegistered(string $strategy, array $instruction)
    {
        $strategy = $this->validateStrategyKey($strategy);
        $substitutions = $this->validateSubstitutions($strategy, $instruction);
        $argCount = count($substitutions);
        $result = $this->validateInstruction($strategy, $instruction, $argCount);

        // successfully validated: save the arg count
        $this->argCounts[$strategy] = $argCount;

        return $result->getNode();
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

    /**
     * @param string $strategy
     * @return string
     */
    private function validateStrategyKey(string $strategy): string
    {
        if (array_key_exists($strategy, $this->argCounts)) {
            throw new \InvalidArgumentException("Cannot overwrite registered strategy key: {$strategy}");
        }

        if (is_numeric($strategy)) {
            throw new \InvalidArgumentException("Cannot register strategy under a numeric key: {$strategy}");
        }

        return $strategy;
    }

    /**
     * @param string $strategy
     * @param array $instruction
     * @return int[]
     */
    private function validateSubstitutions(string $strategy, array $instruction): array
    {
        $substitutions = [];

        $getSubs = function (NodeAdapterInterface $node) use (&$substitutions): ?NodeAdapterInterface {
            $value = $node->getValue();

            if (is_numeric($value)) {
                $substitutions[$value] = true;
            }

            return $node;
        };

        // note that I'm ignoring the output: only interested in the side effect here
        $this->stack->apply(
            [self::TD_PRE, ['adhoc', ['fail'], $getSubs]],
            new Sequence($instruction)
        );

        $substitutions = array_keys($substitutions);
        sort($substitutions);

        if ($substitutions && ($substitutions != range(0, count($substitutions) - 1))) {
            $sequence = implode(', ', $substitutions);
            throw new \InvalidArgumentException(
                "Cannot register strategy: {$strategy}. Non-contiguous substitution sequence: [{$sequence}]"
            );
        }

        return $substitutions;
    }

    /**
     * @param string $strategy
     * @param array $instruction
     * @param int $argCount
     * @return NodeAdapterInterface
     */
    private function validateInstruction(
        string $strategy,
        array $instruction,
        int $argCount
    ): NodeAdapterInterface {
        $sanitiseRegisteredAction = new SanitiseRegisteredAction(
            array_merge([$strategy => $argCount], $this->argCounts)
        );

        $result = $this->stack->apply(
            [self::TD_PRE, ['adhoc', ['fail'], $sanitiseRegisteredAction]],
            new Sequence($instruction)
        );

        if (Fail::fail() === $result) {
            $error = $this->formatErrorMessage($sanitiseRegisteredAction);
            throw new \InvalidArgumentException($error);
        }
        return $result;
    }
}
