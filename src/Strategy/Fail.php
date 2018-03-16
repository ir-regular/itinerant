<?php

namespace JaneOlszewska\Itinerant\Strategy;

class Fail
{
    private $failValue;

    public function __construct($failValue)
    {
        $this->failValue = $failValue;
    }

    public function __invoke()
    {
        // ignore arguments and return terminating value 'fail'
        return $this->failValue;
    }
}
