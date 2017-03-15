<?php

namespace JaneOlszewska\Experiments\Tests\ComposableGraphTraversal\ChildHandler;

use JaneOlszewska\Experiments\ComposableGraphTraversal\ChildHandler\ViaGetter;
use JaneOlszewska\Experiments\ComposableGraphTraversal\Datum;
use PHPUnit\Framework\TestCase;

class ViaGetterTest extends TestCase
{
    /** @var ViaGetter */
    private $ch;

    protected function setUp()
    {
        parent::setUp();

        $this->ch = new ViaGetter();
    }

    public function testGetChildren()
    {
        $children = [2, 3];
        $a = $this->getDatum($children);
        $this->assertEquals($children, $this->ch->getChildren($a));
    }

    public function testSetChildren()
    {
        $a = $this->getDatum([2, 3]);
        $this->ch->setChildren($a, [4, 5]);
        $this->assertEquals([4, 5], $a->getChildren());
    }

    /**
     * @param array $children
     * @return Datum
     */
    private function getDatum(array $children): Datum
    {
        return new class($children) implements Datum
        {
            /** @var \ArrayObject */
            private $c;

            public function __construct($children)
            {
                $this->c = new \ArrayObject($children);
            }

            public function getChildren(): ?array
            {
                return $this->c->getArrayCopy();
            }

            public function setChildren(array $children = []): void
            {
                $this->c->exchangeArray($children);
            }
        };
    }
}
