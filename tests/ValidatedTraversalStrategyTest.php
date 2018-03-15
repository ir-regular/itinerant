<?php

namespace JaneOlszewska\Itinerant\Tests;

use JaneOlszewska\Itinerant\ChildHandler\SecondElement;
use JaneOlszewska\Itinerant\TraversalStrategy;
use JaneOlszewska\Itinerant\ValidatedTraversalStrategy;
use PHPUnit\Framework\TestCase;

class ValidatedTraversalStrategyTest extends TestCase
{
    /** @var ValidatedTraversalStrategy */
    private $ts;

    /** @var object */
    private $fail;

    protected function setUp()
    {
        parent::setUp();

        $childHandler = new SecondElement();
        $this->fail = ['nope', []];

        $this->ts = new ValidatedTraversalStrategy($childHandler, $this->fail);
    }

    public function testSanitisationForInbuiltSingleArgumentNodes()
    {
        $node = ['good node', []];

        // note we no longer have to enclose zero-argument strategies in arrays

        $this->assertEquals($this->fail, $this->ts->apply(TraversalStrategy::FAIL, $node));
        $this->assertEquals($node, $this->ts->apply(TraversalStrategy::ID, $node));
    }

    public function testSanitisationForRegisteredSingleArgumentNodes()
    {
        $action = function () {
            return 'whatever';
        };

        $nodes = [
            'root node', [
                ['good node', []],
                ['good node', []],
            ]
        ];

        // note we don't have to enclose either 'fail' or 'meh' in []

        $this->ts->registerStrategy('meh', ['adhoc', 'fail', $action], 0);
        $this->assertEquals(['root node', ['whatever', 'whatever']], $this->ts->apply(['all', 'meh'], $nodes));
    }

        public function testApplyStrategyValidation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp("/Invalid argument structure for the strategy: .+/");
        $this->ts->apply('all', null);

        // todo: we could test all ways the validation should work... this is just the initial test
    }

    public function testRegisterStrategyValidation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp("/Invalid argument structure for the strategy: .+/");
        $this->ts->registerStrategy('broken', ['does_not_work', 'because it is', 'broken'], 0);

        // todo: again we could test all ways the validation should work... this is just the initial test
    }

    public function testCannotReRegisterInbuiltStrategy()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp("/Cannot overwrite inbuilt strategy key: .+/");
        $this->ts->registerStrategy('nop', ['fail'], 0);
    }
}
