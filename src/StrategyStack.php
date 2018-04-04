<?php

namespace JaneOlszewska\Itinerant;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\Strategy\StrategyResolver;

class StrategyStack
{
    /**
     * @var array
     */
    private $stack = [];

    /**
     * @var StrategyResolver
     */
    private $resolver;

    public function __construct(StrategyResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @param array $strategy
     * @param NodeAdapterInterface $node
     * @return NodeAdapterInterface
     */
    public function apply(array $strategy, NodeAdapterInterface $node): NodeAdapterInterface
    {
        $this->push([$strategy, $node]);
        $result = null;

        do {
            [$strategy, $node] = $this->pop();

            if (is_array($strategy)) {
                if (!$strategy = $this->resolver->resolve($strategy)) {
                    throw new \DomainException('Invalid strategy: validation process failed');
                }

                $continuation = $strategy($node);
                $result = $continuation->current();
            } else {
                $continuation = $strategy;
                // apply continuation to result of previous strategy
                $result = $continuation->send($result);
            }

            if (!($result instanceof NodeAdapterInterface)) {
                // strategy non-terminal, preserve current state
                $this->push([$continuation, null]);
                // ...and queue up a new instruction
                $this->push($result);
            }

            // else: strategy terminal. $currentDatum transformed into $result
            // pass the result into the strategy lower on the stack
        } while (!$this->isEmpty());

        return $result;
    }

    private function isEmpty(): bool
    {
        return empty($this->stack);
    }

    private function pop(): array
    {
        return array_pop($this->stack);
    }

    /**
     * @param array $strategy
     * @return void
     */
    private function push(array $strategy): void
    {
        $this->stack[] = $strategy;
    }
}
