<?php

namespace JaneOlszewska\Itinerant;

use JaneOlszewska\Itinerant\NodeAdapter\Instruction\StringDefinition;
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
     * @param resource $stream
     * @return void
     */
    public function registerStrategiesFromStream($stream): void
    {
        while (($c = fgetc($stream)) !== false) {
            if (!ctype_space($c)) {
                $definition = (new StringDefinition($stream, $c))->getNode();

                $this->registerStrategy(...$definition);
            }
        }
    }

    /**
     * @param string $strategy
     * @param array $instruction
     * @return void
     */
    public function registerStrategy(string $strategy, array $instruction): void
    {
        $instruction = $this->validator->sanitiseRegistered($strategy, $instruction);

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
