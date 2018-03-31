<?php

namespace JaneOlszewska\Itinerant;

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
     * @return void
     */
    public function push(array $strategy): void {
        $this->stack[] = [
            'strategy' => $strategy[0],
            'arguments' => array_slice($strategy, 1),
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
}
