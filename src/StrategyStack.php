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

    public function push($strategy, $datum = null, ?array $unprocessed = null, ?array $processed = null): void
    {
        $stratKey = array_shift($strategy);
        $stratArgs = $strategy;

        // strat, datum, childrenUnprocessed, childrenProcessed
        $this->stack[] = [
            'strat' => [$stratKey, $stratArgs],
            'input' => [$datum, $unprocessed],
            'result' => [null, $processed]
        ];

        $this->last++;
    }

    // todo: I don't quite like how processed/unprocessed children are stored, but I don't want to think of it now

    public function getCurrentStratArguments()
    {
        return $this->stack[$this->last]['strat'][1];
    }

    public function getCurrentStratKey()
    {
        return $this->stack[$this->last]['strat'][0];
    }

    public function getCurrentStratArg($index)
    {
        if (!array_key_exists($index, $this->stack[$this->last]['strat'][1])) {
            $strat = $this->getCurrentStratKey();
            $count = count($this->stack[$this->last]['strat'][1]);
            throw new \InvalidArgumentException(
                "Too few arguments supplied for strategy {$strat}: {$index}'th requested, {$count} available"
            );
        }

        return $this->stack[$this->last]['strat'][1][$index];
    }

    public function getOriginalDatum()
    {
        return $this->stack[$this->last]['input'][0];
    }

    public function getUnprocessedChildren(): ?array
    {
        return $this->stack[$this->last]['input'][1];
    }

    public function getProcessedChildren(): ?array
    {
        return $this->stack[$this->last]['result'][1];
    }
}
