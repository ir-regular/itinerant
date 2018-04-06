<?php

namespace JaneOlszewska\Itinerant\NodeAdapter\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class StringExpression implements NodeAdapterInterface
{
    /** @var string|null */
    private $name;

    /** @var \Iterator */
    private $children;

    /** @var string */
    private $lastReadCharacter;

    /**
     * @param resource $definition
     * @param null|string $peeked
     */
    public function __construct($definition, ?string $peeked = null)
    {
        $this->name = $peeked;

        while (($c = fgetc($definition)) !== false && $this->isWordCharacter($c)) {
            $this->name .= $c;
        }

        $this->lastReadCharacter = $c;

        if ($c == '(') {
            $this->children = $this->match($definition);
        }
    }

    public function getNode()
    {
        $instruction = [$this->getValue()];

        foreach ($this->getChildren() as $child) {
            $instruction[] = $child->getNode();
        }

        return $instruction;
    }

    public function getValue()
    {
        return $this->name;
    }

    public function getChildren(): \Iterator
    {
        if ($this->children) {
            yield from $this->children;
        }
    }

    public function setChildren(array $children = []): void
    {
        $this->children = new \ArrayIterator($children);
    }

    /**
     * @param resource $definition
     * @return \Iterator
     */
    private function match($definition): \Iterator
    {
        while ($c = fgetc($definition)) {
            if (')' == $c) {
                break;
            } elseif ($this->isWordCharacter($c)) {
                // this new object will advance $definition stream internally
                $child = new self($definition, $c);

                yield $child;

                // last child eats up the closing ')' so we need to check it to know we ended
                if ($child->lastReadCharacter == ')') {
                    break;
                }
            }
        }

        $this->lastReadCharacter = $c;
    }

    private function isWordCharacter($char)
    {
        return ctype_alnum($char) || $char == '_';
    }
}
