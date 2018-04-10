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
class ExpressionValidator
{
    private const TD_PRE = 'td_pre';

    /** @var int[] */
    private $instructionArgumentCounts = [
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
        // This guarantees that TD_PRE instruction won't conflict with user's instructions.
        $resolver = new ExpressionResolver();
        // top-down, depth first, prefix application
        $resolver->register(self::TD_PRE, ['seq', '0', ['all', [self::TD_PRE, '0']]]);
        $this->resolver = $resolver;

        $this->stack = new InstructionStack($this->resolver);
    }

    /**
     * Check that $expression is a valid Itinerant expression.
     *
     * @param array|string $expression
     * @return array
     * @throws \InvalidArgumentException if $expression is found invalid
     */
    public function validate($expression)
    {
        $validateAction = new ValidateExpressionAction($this->instructionArgumentCounts);

        // use own stack instead of Itinerant::apply() to avoid infinite recursion
        $result = $this->stack->apply(
            [self::TD_PRE, ['adhoc', ['fail'], $validateAction]],
            new Sequence($expression)
        );

        if (Fail::fail() === $result) {
            $error = $this->formatErrorMessage($validateAction);
            throw new \InvalidArgumentException($error);
        }

        $result = $result->getNode();

        return $result;
    }

    /**
     * Check that $definition is a valid Itinerant expression.
     *
     * Compared to Itinerant::validate(), this method needs slightly expanded validation rules
     * since instructions for user-defined strategies contain argument substitution markers.
     *
     * @param string $name
     * @param array $definition
     * @return array
     */
    public function validateUserInstruction(string $name, array $definition)
    {
        $placeholders = $this->extractPlaceholders($definition);

        $this->validateInstructionName($name);
        $this->validatePlaceholders($placeholders, $name);

        $argumentCount = count($placeholders);
        $result = $this->validateDefinition($name, $definition, $argumentCount);

        // successfully validated: save the arg count
        $this->instructionArgumentCounts[$name] = $argumentCount;

        return $result->getNode();
    }

    /**
     * @param string $name
     */
    private function validateInstructionName(string $name): void
    {
        if (array_key_exists($name, $this->instructionArgumentCounts)) {
            throw new \InvalidArgumentException("Cannot overwrite existing instruction: {$name}");
        }

        if (is_numeric($name)) {
            throw new \InvalidArgumentException("Cannot register instruction with a numeric name: {$name}");
            // ...because it would look like a placeholder
        }
    }

    private function extractPlaceholders(array $expression): array
    {
        $placeholders = [];

        $extractPlaceholders = function (NodeAdapterInterface $node) use (&$placeholders): ?NodeAdapterInterface {
            $value = $node->getValue();

            if (is_numeric($value)) {
                $placeholders[$value] = true;
            }

            return $node;
        };

        // note that I'm ignoring the output: only interested in the side effect here
        // (also, yeah, I could have done that using array_walk_recursive)
        $this->stack->apply(
            [self::TD_PRE, ['adhoc', ['fail'], $extractPlaceholders]],
            new Sequence($expression)
        );

        $placeholders = array_keys($placeholders);
        sort($placeholders);

        return $placeholders;
    }

    /**
     * @param array $placeholders
     * @param string $instruction
     */
    private function validatePlaceholders(array $placeholders, string $instruction): void
    {
        if ($placeholders && ($placeholders != range(0, count($placeholders) - 1))) {
            $sequence = implode(', ', $placeholders);
            throw new \InvalidArgumentException(
                "Cannot register instruction: {$instruction}. Non-contiguous substitution sequence: [{$sequence}]"
            );
        }
    }

    /**
     * @param string $instruction
     * @param array $definition
     * @param int $argumentCount
     * @return NodeAdapterInterface
     */
    private function validateDefinition(
        string $instruction,
        array $definition,
        int $argumentCount
    ): NodeAdapterInterface {
        $validateDefinitionAction = new ValidateInstructionDefinitionAction(
            array_merge([$instruction => $argumentCount], $this->instructionArgumentCounts)
        );

        $result = $this->stack->apply(
            [self::TD_PRE, ['adhoc', ['fail'], $validateDefinitionAction]],
            new Sequence($definition)
        );

        if (Fail::fail() === $result) {
            $error = $this->formatErrorMessage($validateDefinitionAction);
            throw new \InvalidArgumentException($error);
        }
        return $result;
    }

    /**
     * @param ValidateExpressionAction $action
     * @return string
     */
    private function formatErrorMessage(ValidateExpressionAction $action): string
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
