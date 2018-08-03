<?php

namespace IrRegular\Itinerant\Instruction;

use IrRegular\Itinerant\NodeAdapter\NodeAdapterInterface;

class UserDefined implements InstructionInterface
{
    /** @var array */
    private $instruction;

    public function __construct(
        array $instruction,
        array $args
    ) {
        $this->instruction = $this->substitutePlaceholders($instruction, $args);
    }

    public function apply(NodeAdapterInterface $node): \Generator
    {
        $result = yield [$this->instruction, $node];
        yield $result;
    }

    private function substitutePlaceholders(array $instruction, array $args): array
    {
        // substitute numeric placeholders with the actual arguments
        array_walk_recursive($instruction, function (&$value) use ($args) {
            if (is_numeric($value)) {
                $value = $args[(int)$value] ?? null;
            }
        });

        return $instruction;
    }
}
