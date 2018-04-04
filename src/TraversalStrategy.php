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

    public function __construct()
    {
        $this->stack = new StrategyStack();
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

    private function resolveStrategy($strategy, $datum)
    {
        $key = array_shift($strategy);
        $args = $strategy;

        switch ($key) {
            case self::ID:
                return new Id($datum);
            case self::FAIL:
                return new Fail();
            case self::SEQ:
                return new Seq($args[0], $args[1], $datum);
            case self::CHOICE:
                return new Choice($args[0], $args[1], $datum);
            case self::ALL:
                return new All($args[0], $datum);
            case self::ONE:
                return new One($args[0], $datum);
            case self::ADHOC:
                return new Adhoc($args[0], $args[1], $datum);
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
            $strategy = $this->stack->pop();

            if (is_string($strategy[0])) {
                if (!$strategy = $this->resolveStrategy($strategy, $currentDatum)) {
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
                        $nextStrategy = $this->resolveStrategy($nextStrategy, $nextDatum);
                    }

                    $this->stack->push([$nextStrategy]);
                }
            }
        } while (!$this->stack->isEmpty());

        return $result;
    }
}
