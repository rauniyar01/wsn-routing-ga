<?php

namespace Podorozhny\Dissertation;

use Assert\Assertion;

class Network
{
    /** @var BaseStation */
    private $baseStation;

    /** @var Node[] */
    private $clusterHeads;

    /** @var Node[] */
    private $clusterNodes;

    public function __construct(BaseStation $baseStation, array $clusterHeads, array $clusterNodes)
    {
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
        if (count($this->getClusterHeads()) < 1) {
            return false;
        }

        if (count($this->getClusterNodes()) < 1) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function getNodesCount(): int
    {
        return count($this->getNodes());
    }

    /**
     * @return string
     */
    public function getTotalCharge(): string
    {
        $totalCharge = 0;

        foreach ($this->getNodes() as $node) {
            $totalCharge = bcadd($totalCharge, $node->getCharge(), BC_SCALE);
        }

        return $totalCharge;
    }

    /**
     * @return string
     */
    public function getAverageCharge(): string
    {
        return bcdiv($this->getTotalCharge(), $this->getNodesCount(), BC_SCALE);
    }
}
