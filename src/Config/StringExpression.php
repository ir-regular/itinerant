<?php

namespace IrRegular\Itinerant\Config;

use IrRegular\Itinerant\NodeAdapter\NodeAdapterInterface;

/**
 * @TODO move to Utils namespace
 */
class StringExpression implements NodeAdapterInterface
{
    /** @var array */
    private $node;

    /** @var string */
    private $name;

    /** @var \Iterator */
    private $arguments;

    /** @var string|bool */
    private $lastReadCharacter;

    /**
     * @param resource $source
     * @param null|string $peeked
     * @param array|null $substitutions
     */
    public function __construct($source, ?string $peeked = null, $substitutions = null)
    {
        $this->name = $this->extractName($source, $peeked);

        if ($substitutions && array_key_exists($this->name, $substitutions)) {
            $this->node = strval($substitutions[$this->name]);

        } elseif ($this->lastReadCharacter == '(') {
            $this->arguments = $this->extractArguments($source, $substitutions);
        }
    }

    public function getNode()
    {
        if (!isset($this->node)) {
            $this->node = [$this->getValue()];

            foreach ($this->getChildren() as $child) {
                $this->node[] = $child->getNode();
            }
        }

        return $this->node;
    }

    public function getValue()
    {
        return $this->name;
    }

    public function getChildren(): \Iterator
    {
        if ($this->arguments) {
            yield from $this->arguments;
        }
    }

    public function setChildren(array $children = []): void
    {
        $this->arguments = new \ArrayIterator($children);
    }

    /**
     * @param resource $source
     * @param string|null $peeked Character last read from $source which indicated start of expression
     * @return string
     */
    private function extractName($source, ?string $peeked = null): string
    {
        if (!$peeked || ctype_space($peeked)) {
            while (($c = fgetc($source)) !== false && ctype_space($c)) {
                // skip whitespace
            }
            if ($c !== false) {
                $peeked = $c;
            }
        }

        if ($peeked === false || !$this->isWordCharacter($peeked)) {
            throw new \UnexpectedValueException("Invalid expression: name not found");
        }

        $name = $peeked;

        while (($c = fgetc($source)) !== false && $this->isWordCharacter($c)) {
            $name .= $c;
        }

        $this->lastReadCharacter = $c;

        return $name;
    }

    /**
     * @param resource $source
     * @param array|null $substitutions
     * @return \Iterator
     */
    private function extractArguments($source, $substitutions = null): \Iterator
    {
        while ($c = fgetc($source)) {
            if (')' == $c) {
                break;
            } elseif ($this->isWordCharacter($c)) {
                // this new object will advance $definition stream internally
                $argument = new self($source, $c, $substitutions);

                yield $argument;

                // If the last sub-expression had no arguments itself, it would have eaten the parenthesis
                // indicating that current node is closed.
                // Therefore, we need to check its lastReadCharacter to know we can finish processing.
                if (!$argument->arguments && $argument->lastReadCharacter == ')') {
                    $c = $argument->lastReadCharacter;
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
