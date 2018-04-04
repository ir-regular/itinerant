<?php

namespace JaneOlszewska\Itinerant;

class StrategyStack
{
    /**
     * @var array
     */
    protected $stack = [];

    public function isEmpty(): bool
    {
        return empty($this->stack);
    }

    public function pop(): array
    {
        return array_pop($this->stack);
    }

    /**
     * @param array $strategy
     * @return void
     */
    public function push(array $strategy): void
    {
        $this->stack[] = $strategy;
    }
}
