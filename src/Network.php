<?php

namespace Podorozhny\Dissertation;

use Assert\Assertion;

final class Network
{
    /** @var BaseStation */
    private $baseStation;

    /** @var SensorNode[] */
    private $clusterHeads;

    /** @var SensorNode[] */
    private $clusterNodes;

    public function __construct(BaseStation $baseStation, array $clusterHeads, array $clusterNodes)
    {
        Assertion::allIsInstanceOf($clusterHeads, SensorNode::class);
        Assertion::allIsInstanceOf($clusterNodes, SensorNode::class);

        $this->baseStation  = $baseStation;
        $this->clusterHeads = $clusterHeads;
        $this->clusterNodes = $clusterNodes;
    }

    /** @return BaseStation */
    public function getBaseStation(): BaseStation
    {
        return $this->baseStation;
    }

    /** @return SensorNode[] */
    public function getClusterHeads(): array
    {
        return $this->clusterHeads;
    }

    /** @return SensorNode[] */
    public function getClusterNodes(): array
    {
        return $this->clusterNodes;
    }

    /** @return SensorNode[] */
    public function getSensorNodes(): array
    {
        return array_merge($this->clusterHeads, $this->clusterNodes);
    }

    /** @return bool */
    public function isAlive(): bool
    {
        if (count($this->getClusterNodes()) < 1) {
            return false;
        }

        return true;
    }

    /** @return int */
    public function getSensorNodesCount(): int
    {
        return count($this->getSensorNodes());
    }

    /** @return string */
    public function getTotalCharge(): string
    {
        $totalCharge = 0;

        foreach ($this->getSensorNodes() as $node) {
            $totalCharge = bcadd($totalCharge, $node->getCharge(), BC_SCALE);
        }

        return $totalCharge;
    }

    /** @return string */
    public function getAverageCharge(): string
    {
        return bcdiv($this->getTotalCharge(), $this->getSensorNodesCount(), BC_SCALE);
    }
}
