<?php

namespace JaneOlszewska\Itinerant;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\Strategy\InstructionResolver;

class Itinerant
{
    /** @var InstructionResolver */
    private $resolver;

    /** @var StrategyStack */
    private $stack;

    /** @var InstructionValidator */
    private $validator;

    public function __construct()
    {
        $this->resolver = new InstructionResolver();
        $this->stack = new StrategyStack($this->resolver);
        $this->validator = new InstructionValidator();
    }

    /**
     * @param string $strategy
     * @param array $instruction
     * @param int $argCount
     * @return void
     */
    public function registerStrategy(string $strategy, array $instruction, int $argCount): void
    {
        $instruction = $this->validator->sanitiseRegistered($strategy, $instruction, $argCount);

        $this->resolver->registerStrategy($strategy, $instruction);
    }

    /**
     * @param array|string $instruction
     * @param NodeAdapterInterface $node
     * @return NodeAdapterInterface
     */
    public function apply($instruction, NodeAdapterInterface $node): NodeAdapterInterface
    {
        $instruction = $this->validator->sanitiseApplied($instruction);

        return $this->stack->apply($instruction, $node);
    }
}
