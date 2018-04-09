<?php

namespace JaneOlszewska\Itinerant\NodeAdapter\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class StringExpression implements NodeAdapterInterface
{
    /** @var array */
    private $node;

    /** @var string */
    private $name;

    /** @var \Iterator */
    private $children;

    /** @var string|bool */
    private $lastReadCharacter;

    /**
     * @param resource $definition
     * @param null|string $peeked
     */
    public function __construct($definition, ?string $peeked = null)
    {
        $this->name = $this->extractName($definition, $peeked);

        if ($this->lastReadCharacter == '(') {
            $this->children = $this->extractChildren($definition);
        }
    }

    public function getNode()
    {
        if (isset($this->node)) {
            return $this->node;
        }

        $this->node = [$this->getValue()];

        foreach ($this->getChildren() as $child) {
            $this->node[] = $child->getNode();
        }

        return $this->node;
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
     * @param string|null $peeked
     * @return string
     */
    private function extractName($definition, ?string $peeked = null): string
    {
        if (!$peeked) {
            while (($c = fgetc($definition)) !== false && ctype_space($c)) {
                // skip whitespace
            }
            if ($c !== false) {
                $peeked = $c;
            }
        }

        if (!$this->isWordCharacter($peeked)) {
            throw new \UnexpectedValueException("Invalid expression: name not found");
        }

        $name = $peeked;

        while (($c = fgetc($definition)) !== false && $this->isWordCharacter($c)) {
            $name .= $c;
        }

        $this->lastReadCharacter = $c;

        return $name;
    }

    /**
     * @param resource $definition
     * @return \Iterator
     */
    private function extractChildren($definition): \Iterator
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

    /**
     * @param string $char
     * @return bool
     */
    private function isWordCharacter(string $char): bool
    {
        return ctype_alnum($char) || $char == '_';
    }
}
