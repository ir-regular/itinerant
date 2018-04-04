<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class StrategyResolver
{
    const ID = 'id';
    const FAIL = 'fail';
    const SEQ = 'seq';
    const CHOICE = 'choice';
    const ALL = 'all';
    const ONE = 'one';
    const ADHOC = 'adhoc';

    public const INBUILT_STRATEGIES = [
        self::ID,
        self::FAIL,
        self::SEQ,
        self::CHOICE,
        self::ALL,
        self::ONE,
        self::ADHOC,
    ];

    /** @var array */
    private $strategies = [];

    public function resolve(array $strategy, NodeAdapterInterface $datum)
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
     * @param string $key
     * @param array $expansion
     */
    public function registerStrategy(string $key, array $expansion): void
    {
        $this->strategies[$key] = $expansion;
    }
}