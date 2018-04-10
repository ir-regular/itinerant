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
     * @param resource $stream
     * @return void
     */
    public function registerFromStream($stream): void
    {
        while (($c = fgetc($stream)) !== false) {
            if (!ctype_space($c)) {
                $definition = (new StringDefinition($stream, $c))->getNode();

                $this->register(...$definition);
            }
        }
    }

    /**
     * @param string $strategy
     * @param array $instruction
     * @return void
     */
    public function register(string $strategy, array $instruction): void
    {
        $instruction = $this->validator->validateUserInstruction($strategy, $instruction);

        $this->resolver->register($strategy, $instruction);
    }

    /**
     * @param array|string $instruction
     * @param NodeAdapterInterface $node
     * @return NodeAdapterInterface
     */
    public function apply($instruction, NodeAdapterInterface $node): NodeAdapterInterface
    {
        $instruction = $this->validator->validate($instruction);

        return $this->stack->apply($instruction, $node);
    }
}
