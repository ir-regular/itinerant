<?php

namespace JaneOlszewska\Itinerant;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;
use JaneOlszewska\Itinerant\Strategy\Adhoc;
use JaneOlszewska\Itinerant\Strategy\All;
use JaneOlszewska\Itinerant\Strategy\Choice;
use JaneOlszewska\Itinerant\Strategy\Fail;
use JaneOlszewska\Itinerant\Strategy\Id;
use JaneOlszewska\Itinerant\Strategy\One;
use JaneOlszewska\Itinerant\Strategy\Seq;
use JaneOlszewska\Itinerant\Strategy\UserDefined;

class TraversalStrategy
{
    const ID = 'id';
    const FAIL = 'fail';
    const SEQ = 'seq';
    const CHOICE = 'choice';
    const ALL = 'all';
    const ONE = 'one';
    const ADHOC = 'adhoc';

    /** @var array */
    private $strategies = [];

    /** @var StrategyStack */
    private $stack;

    /** @var NodeAdapterInterface */
    private $fail;

    /**
     * @param NodeAdapterInterface $fail A representation of "fail", valid for whatever nodes/adhoc methods will be used
     */
    public function __construct(NodeAdapterInterface $fail)
    {
        $this->stack = new StrategyStack();
        $this->fail = $fail;
    }

    /**
     * @return NodeAdapterInterface
     */
    public function getFail(): NodeAdapterInterface
    {
        return $this->fail;
    }

    /**
     * @param string $key
     * @param array $expansion
     * @param int $argCount
     */
    public function registerStrategy(string $key, array $expansion, int $argCount): void
    {
        // @TODO: remove $argCount, have ValidatedTraversalStrategy figure it out on its own
        $this->strategies[$key] = $expansion;
    }

    public function isStrategyRegistered(string $key): bool
    {
        return isset($this->strategies[$key]);
    }

    private function getStrategy($key, $datum, $args)
    {
        switch ($key) {
            case self::ID:
                return new Id($datum);
            case self::FAIL:
                return new Fail($this->fail);
            case self::SEQ:
                return new Seq($this->fail, $datum, ...$args);
            case self::CHOICE:
                return new Choice($this->fail, $datum, ...$args);
            case self::ALL:
                return new All($this->fail, $datum, ...$args);
            case self::ONE:
                return new One($this->fail, $datum, ...$args);
            case self::ADHOC:
                return new Adhoc($datum, ...$args);
            default:
                return $this->strategies[$key]
                    ? new UserDefined($this->strategies[$key], $args, $datum)
                    : null;
        }
    }

    /**
     * @param array $s
     * @param NodeAdapterInterface $datum
     * @return NodeAdapterInterface
     */
    public function apply($s, NodeAdapterInterface $datum): NodeAdapterInterface
    {
        $this->stack->push($s);
        $currentDatum = $datum;

        do {
            $strategy = $this->stack->getCurrentStrat();
            $args = $this->stack->getCurrentStratArguments();

            if (is_string($strategy)) {
                if (!$strategy = $this->getStrategy($strategy, $currentDatum, $args)) {
                    throw new \DomainException('Invalid strategy: validation process failed');
                }
            }

            $this->stack->pop();

            $result = $strategy($currentDatum);

            if ($result instanceof NodeAdapterInterface) {
                // strategy terminal: $currentDatum transformed into $result
                // pass the result into the strategy lower on the stack
                $currentDatum = $result;
            } else {
                // strategy non-terminal, continue applying further instructions to the same datum
                foreach ($result as [$nextStrategy, $nextDatum]) {
                    if (is_array($nextStrategy) && is_string($nextStrategy[0])) {
                        $nextStrategy = $this->getStrategy($nextStrategy[0], $nextDatum, array_slice($nextStrategy, 1));
                    }

                    $this->stack->push([$nextStrategy]);
                }
            }
        } while (!$this->stack->isEmpty());

        return $result;
    }
}
