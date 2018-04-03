<?php

namespace JaneOlszewska\Itinerant\Strategy;

use JaneOlszewska\Itinerant\NodeAdapter\NodeAdapterInterface;

class Adhoc
{
    private $strategyIfInapplicable;
    private $action;
    /**
     * @var NodeAdapterInterface
     */
    private $node;

    public function __construct(
        $strategyIfInapplicable,
        $action,
        NodeAdapterInterface $node = null
    ) {
        $this->strategyIfInapplicable = $strategyIfInapplicable;
        $this->action = $action;

        if ($node) {
            $this->node = $node;
        }
    }

    public function __invoke(NodeAdapterInterface $node)
    {
        if (!$this->node) {
            $this->node = $node;
        }

        if (is_callable($this->action)) {
            // strategy resolved to applied action; terminal unless null returned
            // @TODO: document this clearly somewhere
            $res = ($this->action)($this->node);

            if ($res !== null) {
                // @TODO: it would be better to check this elsewhere
                if (!($res instanceof NodeAdapterInterface)) {
                    throw new \UnexpectedValueException('Adhoc callable result must be a NodeAdapterInterface');
                }

                return $res;
            }
        }

        return [[$this->strategyIfInapplicable, $this->node]];
    }
}
