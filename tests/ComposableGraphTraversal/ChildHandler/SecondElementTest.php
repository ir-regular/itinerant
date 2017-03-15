<?php

namespace JaneOlszewska\Experiments\Tests\ComposableGraphTraversal\ChildHandler;

use JaneOlszewska\Experiments\ComposableGraphTraversal\ChildHandler\SecondElement;
use PHPUnit\Framework\TestCase;

class SecondElementTest extends TestCase
{
    /** @var SecondElement */
    private $ch;

    protected function setUp()
    {
        parent::setUp();

        $this->ch = new SecondElement();
    }

    public function testGetChildren()
    {
        $a = [1, [2, 3]];
        $this->assertEquals([2, 3], $this->ch->getChildren($a));
    }

    public function testSetChildren()
    {
        $a = [1, [2, 3]];
        $this->ch->setChildren($a, [4, 5]);
        $this->assertEquals([1, [4, 5]], $a);
    }
}
