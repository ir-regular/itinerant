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
     * @param NodeAdapterInterface[]|null $unprocessed
     * @param NodeAdapterInterface[]|null $processed
     * @return void
     */
    public function push(
        array $strategy,
        NodeAdapterInterface $datum = null,
        ?array $unprocessed = null,
        ?array $processed = null
    ): void {
        // strat, datum, childrenUnprocessed, childrenProcessed
        $this->stack[] = [
            'strat' => $strategy,
            'input' => [$datum, $unprocessed],
            'result' => [null, $processed]
        ];

        $this->last++;
    }

    // todo: I don't quite like how processed/unprocessed children are stored, but I don't want to think of it now

    public function getCurrentStratArguments()
    {
        return array_slice($this->stack[$this->last]['strat'], 1);
    }

    public function getCurrentStrat()
    {
        return $this->stack[$this->last]['strat'][0];
    }

    public function getOriginalDatum(): NodeAdapterInterface
    {
        return $this->stack[$this->last]['input'][0];
    }

    /**
     * @return NodeAdapterInterface[]|null
     */
    public function getUnprocessedChildren(): ?array
    {
        return $this->stack[$this->last]['input'][1];
    }

    /**
     * @return NodeAdapterInterface[]|null
     */
    public function getProcessedChildren(): ?array
    {
        return $this->stack[$this->last]['result'][1];
    }
}
