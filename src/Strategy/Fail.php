<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Fail
{
    /**
     * @var NodeAdapterInterface
     */
    private $failValue;

    public function __construct(NodeAdapterInterface $failValue)
    {
        $this->failValue = $failValue;
    }

    public function __invoke(): NodeAdapterInterface
    {
        // ignore arguments and return terminating value 'fail'
        return $this->failValue;
    }
}
