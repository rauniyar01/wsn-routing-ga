<?php

namespace Podorozhny\Dissertation;

use Assert\Assert;

final class NetworkFitnessCalculator
{
    const GOAL = 1000;

    /**
     * @var array
     */
    private $fitnessCache = [];

    /**
     * @param Node[] $nodes
     * @param bool[] $genes
     *
     * @return string
     */
    public function getFitness(array $nodes, array $genes): string
    {
        $cacheKey = $this->getCacheKey($nodes, $genes);

        if (array_key_exists($cacheKey, $this->fitnessCache)) {
            return $this->fitnessCache[$cacheKey];
        }

        $clonedNodes = [];

        foreach ($nodes as $node) {
            $clonedNodes[] = clone $node;
        }

        Assert::thatAll($genes)->boolean();

        Assert::that(count($genes))->eq(count($clonedNodes));

        if (array_sum($genes) === 0) {
            return $this->fitnessCache[$cacheKey] = 0;
        }

        $baseStation = new BaseStation(FIELD_SIZE * 10 / 2, FIELD_SIZE * 10 / 2);

        $clusterHeads = [];
        $clusterNodes = [];

        foreach ($clonedNodes as $key => $node) {
            if (!$genes[$key]) {
                continue;
            }

            $node->makeClusterHead();

            $clusterHeads[] = $node;
        }

        foreach ($clonedNodes as $key => $node) {
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
            return $this->fitnessCache[$cacheKey] = self::GOAL;
        }

        $fitness = bcdiv(1, $averageChargeConsumption, BC_SCALE);

//        if ($nodesDied > 0) {
//            $fitness += (1 / $nodesDied);
//        }

        return $this->fitnessCache[$cacheKey] = $fitness;
    }

    /**
     * @param Node[] $nodes
     * @param bool[] $genes
     *
     * @return string
     */
    private function getCacheKey(array $nodes, array $genes): string
    {
        $coordinates = [];

        foreach ($nodes as $node) {
            $coordinates[] = sprintf('%d-%d', $node->getX(), $node->getY());
        }

        $genes = array_map(
            function (bool $gene) {
                return (int) $gene;
            },
            $genes
        );

        $string = implode('_', $coordinates) . '__' . implode('_', $genes);

        return md5($string);
    }
}
