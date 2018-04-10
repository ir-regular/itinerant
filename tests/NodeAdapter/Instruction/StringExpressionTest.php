<?php

namespace JaneOlszewska\Tests\Itinerant\NodeAdapter\Instruction;

use JaneOlszewska\Itinerant\NodeAdapter\Instruction\StringExpression;
use PHPUnit\Framework\TestCase;

class StringExpressionTest extends TestCase
{
    public function testParsesDeclaration()
    {
        $declaration = $this->get_string_stream('below_eq(s1, s2)');

        $node = new StringExpression($declaration);

        $this->assertEquals(['below_eq', ['s1'], ['s2']], $node->getNode());

        fclose($declaration);
    }

    public function testParsesDefinition()
    {
        $body = $this->get_string_stream('once_td(seq(s2, once_td(s1)))');

        $node = new StringExpression($body);

        $this->assertEquals(['once_td', ['seq', ['s2'], ['once_td', ['s1']]]], $node->getNode());

        fclose($body);
    }

    public function testThrowsWhenNameNotFound()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid expression: name not found');

        $expression = $this->get_string_stream('(id, derp)');
        new StringExpression($expression);
    }

    public function testNameStartsWithPeekedCharacter()
    {
        $stream = $this->get_string_stream('test(id)');
        $peeked = fgetc($stream);
        $node = new StringExpression($stream, $peeked);

        $this->assertEquals('test', $node->getValue());
    }

    public function testAllowsWhitespaceBeforeExpression()
    {
        $stream = $this->get_string_stream("\n below_eq(s1, s2)");
        $node = new StringExpression($stream);
        $this->assertEquals(['below_eq', ['s1'], ['s2']], $node->getNode());
        fclose($stream);
    }

    /**
     * @param string $string
     * @return resource
     */
    private function get_string_stream(string $string)
    {
        return fopen('data://text/plain,' . $string, 'r');
    }
}
