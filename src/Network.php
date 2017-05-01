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
        Assertion::allUuid(array_keys($clusterHeads));
        Assertion::allUuid(array_keys($clusterNodes));
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
        if (count($this->getClusterHeads()) === 0) {
            return false;
        }

        if (count($this->getClusterNodes()) === 0) {
            return false;
        }

        if ($this->getDeadNodesCount() / $this->getNodesCount() > self::DEAD_NODES_LIMIT) {
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
     * @return int
     */
    public function getDeadNodesCount(): int
    {
        $deadNodesCount = 0;

        foreach ($this->getNodes() as $node) {
            if ($node->isDead()) {
                $deadNodesCount++;
            }
        }

        return $deadNodesCount;
    }

    /**
     * @return int
     */
    public function getAliveNodesCount(): int
    {
        return $this->getNodesCount() - $this->getDeadNodesCount();
    }

    /**
     * @return float
     */
    public function getTotalCharge(): float
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
    public function getAverageCharge(): float
    {
        $aliveNodesCount = $this->getAliveNodesCount();

        if ($aliveNodesCount === 0) {
            return 0;
        }

        return $this->getTotalCharge() / $aliveNodesCount;
    }
}
