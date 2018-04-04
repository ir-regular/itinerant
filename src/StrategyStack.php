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
     * @param array $s
     * @param NodeAdapterInterface $datum
     * @return NodeAdapterInterface
     */
    public function apply($s, NodeAdapterInterface $datum): NodeAdapterInterface
    {
        $this->push($s);
        $currentDatum = $datum;

        do {
            $strategy = $this->pop();

            if (is_string($strategy[0])) {
                if (!$strategy = $this->resolver->resolve($strategy, $currentDatum)) {
                    throw new \DomainException('Invalid strategy: validation process failed');
                }
            } else {
                $strategy = $strategy[0];
            }

            $result = $strategy($currentDatum);

            if ($result instanceof NodeAdapterInterface) {
                // strategy terminal: $currentDatum transformed into $result
                // pass the result into the strategy lower on the stack
                $currentDatum = $result;
            } else {
                // strategy non-terminal, continue applying further instructions to the same datum
                foreach ($result as [$nextStrategy, $nextDatum]) {
                    if (is_array($nextStrategy) && is_string($nextStrategy[0])) {
                        $nextStrategy = $this->resolver->resolve($nextStrategy, $nextDatum);
                    }

                    $this->push([$nextStrategy]);
                }
            }
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
