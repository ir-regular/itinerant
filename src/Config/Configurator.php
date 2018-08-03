<?php

namespace IrRegular\Itinerant\Config;

use IrRegular\Itinerant\Itinerant;

class Configurator
{
    /**
     * @param Itinerant $itinerant
     * @param string $filepath
     * @return void
     */
    public static function registerFromFile(Itinerant $itinerant, string $filepath): void
    {
        $fp = fopen($filepath, 'r');

        try {
            self::registerFromStream($itinerant, $fp);
        } finally {
            fclose($fp);
        }
    }

    /**
     * @param Itinerant $itinerant
     * @param resource $stream Expects an open stream - does NOT close it after reading
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
