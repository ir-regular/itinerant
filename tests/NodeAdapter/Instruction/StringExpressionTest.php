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

    /**
     * @param string $string
     * @return resource
     */
    private function get_string_stream(string $string)
    {
        return fopen('data://text/plain,' . $string, 'r');
    }
}
