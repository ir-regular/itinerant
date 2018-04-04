<?php

namespace JaneOlszewska\Itinerant\Strategy;

class StrategyResolver
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

    /**
     * @param array $strategy
     * @return StrategyInterface
     * @throws \DomainException when first element of $strategy is an unregistered strategy key
     */
    public function resolve(array $strategy): StrategyInterface
    {
        $key = array_shift($strategy);
        $args = $strategy;

        switch ($key) {
            case self::SEQ:
                return new Seq($args[0], $args[1]);
            case self::CHOICE:
                return new Choice($args[0], $args[1]);
            case self::ALL:
                return new All($args[0]);
            case self::ONE:
                return new One($args[0]);
            case self::ADHOC:
                return new Adhoc($args[0], $args[1]);
            default:
                if (isset($this->strategies[$key])) {
                    return new UserDefined($this->strategies[$key], $args);
                } else {
                    throw new \DomainException('Invalid strategy: validation process failed');
                }
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
