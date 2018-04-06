<?php

namespace JaneOlszewska\Itinerant\NodeAdapter\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class StringDefinition implements NodeAdapterInterface
{
    /**
     * @var resource
     */
    private $definition;

    /**
     * @var NodeAdapterInterface
     */
    private $declaration;

    /**
     * @param resource $definition
     */
    public function __construct($definition)
    {
        $this->definition = $definition;

        $this->declaration = $this->getExpression($definition);
    }

    public function getNode()
    {
        return $this->definition;
    }

    public function getValue()
    {
        return $this->declaration->getValue();
    }

    public function getChildren(): \Iterator
    {
        yield 'declaration' => $this->declaration;
        yield 'instruction' => $this->getInstruction($this->definition);
    }

    public function setChildren(array $children = []): void
    {
        // cannot amend children
    }

    private function getInstruction($definition): NodeAdapterInterface
    {
        // (this forces the stream to be consumed up to the point where the instruction starts)

        iterator_count($this->declaration->getChildren());

        while (($c = fgetc($definition)) !== false) {
            if ($this->isWordCharacter($c)) {
                break;
            }
        }

        if ($c !== false) {
            return $this->getExpression($this->definition, $c);
        }
    }

    private function getExpression($definition, ?string $peeked = null): NodeAdapterInterface
    {
        return new StringExpression($definition, $peeked);
    }

    private function isWordCharacter($char)
    {
        return ctype_alnum($char) || $char == '_';
    }
}
