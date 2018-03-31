<?php

namespace JaneOlszewska\Itinerant;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class StrategyStack
{
    /**
     * @var array
     */
    protected $stack = [];

    /**
     * @var int
     */
    protected $last = -1;

    public function isEmpty(): bool
    {
        return ($this->last == -1);
    }

    public function pop()
    {
        $this->last--;
        return array_pop($this->stack);
    }

    /**
     * @param array $strategy
     * @param NodeAdapterInterface|null $datum
     * @return void
     */
    public function push(
        array $strategy,
        NodeAdapterInterface $datum = null
    ): void {
        // strat, datum, childrenUnprocessed, childrenProcessed
        $this->stack[] = [
            'strategy' => $strategy[0],
            'arguments' => array_slice($strategy, 1),
            'input' => $datum,
        ];

        $this->last++;
    }

    public function getCurrentStratArguments()
    {
        return $this->stack[$this->last]['arguments'];
    }

    public function getCurrentStrat()
    {
        return $this->stack[$this->last]['strategy'];
    }

    public function getOriginalDatum(): NodeAdapterInterface
    {
        return $this->stack[$this->last]['input'];
    }
}
