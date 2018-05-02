<?php

namespace JaneOlszewska\Itinerant\Config;

use JaneOlszewska\Itinerant\Itinerant;

class Configurator
{
    /**
     * @param Itinerant $itinerant
     * @param resource $stream
     * @return void
     */
    public static function registerFromStream(Itinerant $itinerant, $stream): void
    {
        while (($c = fgetc($stream)) !== false) {
            if (!ctype_space($c)) {
                $definition = (new StringDefinition($stream, $c))->getNode();

                $itinerant->register(...$definition);
            }
        }
    }
}
