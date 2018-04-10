<?php

namespace JaneOlszewska\Itinerant\Instruction;

class InstructionResolver
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
     * @param array $instruction
     * @return StrategyInterface
     * @throws \DomainException when first element of $strategy is an unregistered strategy key
     */
    public function resolve(array $instruction): StrategyInterface
    {
        $strategy = array_shift($instruction);
        $args = $instruction;

        switch ($strategy) {
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
                if (isset($this->strategies[$strategy])) {
                    return new UserDefined($this->strategies[$strategy], $args);
                } else {
                    throw new \DomainException('Invalid strategy: validation process failed');
                }
        }
    }

    /**
     * @param string $strategy
     * @param array $instruction
     */
    public function registerStrategy(string $strategy, array $instruction): void
    {
        $this->strategies[$strategy] = $instruction;
    }
}
