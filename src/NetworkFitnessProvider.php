<?php

namespace Podorozhny\Dissertation;

use Assert\Assert;

final class NetworkFitnessProvider
{
    const GOAL = 1000;

    /** @var Node[] */
    private $nodes;

    /**
     * @param array $nodes
     */
    public function setNodes(array $nodes)
    {
        $this->nodes = [];

        foreach ($nodes as $node) {
            $this->nodes[] = clone $node;
        }
    }

    /**
     * @return array
     */
    private function getNodes(): array
    {
        $nodes = [];

        foreach ($this->nodes as $node) {
            $nodes[] = clone $node;
        }

        return $nodes;
    }

    /**
     * @param bool[] $genes
     *
     * @return string
     */
    public function getFitness(array $genes): string
    {
        Assert::thatAll($genes)->boolean();

        $nodes = $this->getNodes();

        Assert::that(count($genes))->eq(count($nodes));

        if (array_sum($genes) === 0) {
            return 0;
        }

        $baseStation = new BaseStation(FIELD_SIZE * 10 / 2, FIELD_SIZE * 10 / 2);

        $clusterHeads = [];
        $clusterNodes = [];

        foreach ($nodes as $key => $node) {
            if (!$genes[$key]) {
                continue;
            }

            $node->makeClusterHead();

            $clusterHeads[] = $node;
        }

        foreach ($nodes as $key => $node) {
            if ($genes[$key]) {
                continue;
            }

            $node->makeClusterNode($node->getNearestNeighbor($clusterHeads));

            $clusterNodes[] = $node;
        }

        $network = new Network($baseStation, $clusterHeads, $clusterNodes);

        $totalCharge = $network->getTotalCharge();
//        $deadNodesCount = $network->getDeadNodesCount();

        (new OneRoundChargeReducer())->reduce($network);

        $totalChargeConsumption = bcsub($totalCharge, $network->getTotalCharge(), BC_SCALE);

//        $nodesDied = $network->getDeadNodesCount() - $deadNodesCount;

        $averageChargeConsumption = bcdiv($totalChargeConsumption, $network->getNodesCount(), BC_SCALE);

        if ($averageChargeConsumption == 0) {
            return self::GOAL;
        }

        $fitness = bcdiv(1, $averageChargeConsumption, BC_SCALE);

//        if ($nodesDied > 0) {
//            $fitness += (1 / $nodesDied);
//        }

        return $fitness;
    }
}
