<?php

namespace Podorozhny\Dissertation;

use Assert\Assertion;

class Network
{
    const DEAD_NODES_LIMIT = 0.8;

    /** @var BaseStation */
    private $baseStation;

    /** @var Node[] */
    private $clusterHeads;

    /** @var Node[] */
    private $clusterNodes;

    public function __construct(BaseStation $baseStation, array $clusterHeads, array $clusterNodes)
    {
        Assertion::true(count($clusterHeads) > 0);
        Assertion::true(count($clusterNodes) > 0);
        Assertion::allIsInstanceOf($clusterHeads, Node::class);
        Assertion::allIsInstanceOf($clusterNodes, Node::class);

        $this->baseStation  = $baseStation;
        $this->clusterHeads = $clusterHeads;
        $this->clusterNodes = $clusterNodes;
    }

    /**
     * @return BaseStation
     */
    public function getBaseStation(): BaseStation
    {
        return $this->baseStation;
    }

    /**
     * @return Node[]
     */
    public function getClusterHeads(): array
    {
        return $this->clusterHeads;
    }

    /**
     * @return Node[]
     */
    public function getClusterNodes(): array
    {
        return $this->clusterNodes;
    }

    /**
     * @return Node[]
     */
    public function getNodes(): array
    {
        return array_merge($this->clusterHeads, $this->clusterNodes);
    }

    /**
     * @return bool
     */
    public function isAlive(): bool
    {
        $deadNodesCount = 0;

        foreach ($this->getNodes() as $node) {
            if ($node->isDead()) {
                $deadNodesCount++;
            }
        }

        return $deadNodesCount / count($this->getNodes()) <= self::DEAD_NODES_LIMIT;
    }

    /**
     * @return float
     */
    public function getTotalEnergy(): float
    {
        $totalCharge = 0;

        foreach ($this->getNodes() as $node) {
            $totalCharge += $node->getCharge();
        }

        return $totalCharge;
    }

    /**
     * @return float
     */
    public function getAverageEnergy(): float
    {
        return $this->getTotalEnergy() / count($this->getNodes());
    }
}
