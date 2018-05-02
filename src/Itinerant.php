<?php

namespace JaneOlszewska\Itinerant;

use JaneOlszewska\Itinerant\NodeAdapter\Instruction\StringDefinition;
use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\Instruction\ExpressionResolver;
use JaneOlszewska\Itinerant\Instruction\InstructionStack;
use JaneOlszewska\Itinerant\Validation\ExpressionValidator;

class Itinerant
{
    /** @var ExpressionResolver */
    private $resolver;

    /** @var InstructionStack */
    private $stack;

    /** @var ExpressionValidator */
    private $validator;

    public function __construct()
    {
        $this->resolver = new ExpressionResolver();
        $this->stack = new InstructionStack($this->resolver);
        $this->validator = new ExpressionValidator();
    }

    /**
     * @param string $instruction
     * @param array $definition
     * @return void
     */
    public function register(string $instruction, array $definition): void
    {
        $definition = $this->validator->validateUserInstruction($instruction, $definition);

        $this->resolver->register($instruction, $definition);
    }

    /**
     * @param array|string $expression
     * @param NodeAdapterInterface $node
     * @return NodeAdapterInterface
     */
    public function apply($expression, NodeAdapterInterface $node): NodeAdapterInterface
    {
        $expression = $this->validator->validate($expression);

        return $this->stack->apply($expression, $node);
    }
}
