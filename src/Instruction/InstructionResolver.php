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
    private $instructions = [];

    /**
     * @param array $expression
     * @return InstructionInterface
     * @throws \DomainException when first element of $expression is an unregistered instruction
     */
    public function resolve(array $expression): InstructionInterface
    {
        $instruction = array_shift($expression);
        $args = $expression;

        switch ($instruction) {
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
                if (isset($this->instructions[$instruction])) {
                    return new UserDefined($this->instructions[$instruction], $args);
                } else {
                    throw new \DomainException('Invalid strategy: validation process failed');
                }
        }
    }

    /**
     * @param string $instruction
     * @param array $definition A valid Itinerant expression
     */
    public function register(string $instruction, array $definition): void
    {
        $this->instructions[$instruction] = $definition;
    }
}
