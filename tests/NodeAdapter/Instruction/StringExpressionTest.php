<?php

namespace JaneOlszewska\Itinerant\NodeAdapter\Instruction;

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

    public function testParsesBody()
    {
        $body = $this->get_string_stream('once_td(seq(s2, once_td(s1)))');

        $node = new StringExpression($body);

        $this->assertEquals(['once_td', ['seq', ['s2'], ['once_td', ['s1']]]], $node->getNode());

        fclose($body);
    }

    public function testThrowsWhenEncounteringUnknownSymbol()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown symbol: derp');

        $expression = $this->get_string_stream('seq(id, derp)');
        $node = new StringExpression($expression, null, ['seq', 'id']);
        // here's where it has to realise children and subsequently throws
        $node->getNode();
    }

    public function testThrowsWhenNameNotFound()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid expression: name not found');

        $expression = $this->get_string_stream('(id, derp)');
        new StringExpression($expression);
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
