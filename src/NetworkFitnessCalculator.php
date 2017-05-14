<?php

namespace Podorozhny\Dissertation;

use Assert\Assert;

final class NetworkFitnessCalculator
{
    const GOAL = '1000000.0';

    /** @var OneRoundChargeReducer */
    private $reducer;

    /** @var BaseStation */
    private $baseStation;

    /**
     * @var array
     */
    private $fitnessCache = [];

    public function __construct(OneRoundChargeReducer $reducer, BaseStation $baseStation)
    {
        $this->reducer     = $reducer;
        $this->baseStation = $baseStation;
    }

    /**
     * @param Node[] $nodes
     * @param bool[] $genes
     *
     * @return string
     */
    public function getFitness(array $nodes, array $genes): string
    {
        $cacheKey = $this->getCacheKey($genes);

        if (array_key_exists($cacheKey, $this->fitnessCache)) {
            return $this->fitnessCache[$cacheKey];
        }

        /** @var Node[] $clonedNodes */
        $clonedNodes = [];

        foreach ($nodes as $node) {
            $clonedNodes[] = clone $node;
        }

        Assert::thatAll($genes)->boolean();

        Assert::that(count($genes))->eq(count($clonedNodes));

        $clusterHeads = [];
        $clusterNodes = [];

        foreach ($clonedNodes as $node) {
            if (!$genes[$node->getId()]) {
                continue;
            }

            $node->makeClusterHead();

            $clusterHeads[] = $node;
        }

        foreach ($clonedNodes as $node) {
            if ($genes[$node->getId()]) {
                continue;
            }

            $nearestClusterHead = $node->getNearestNeighbor($clusterHeads);

            if (!$nearestClusterHead instanceof Node ||
                $node->distanceToNeighbor($this->baseStation) <= $node->distanceToNeighbor($nearestClusterHead)
            ) {
                $nearestClusterHead = $this->baseStation;
            }

            $node->makeClusterNode($nearestClusterHead);

            $clusterNodes[] = $node;
        }

        $network = new Network($this->baseStation, $clusterHeads, $clusterNodes);

        $totalCharge = $network->getTotalCharge();

        $this->reducer->reduce($network);

        $totalChargeConsumption = bcsub($totalCharge, $network->getTotalCharge(), BC_SCALE);

        $fitness = bcdiv(1, $totalChargeConsumption, BC_SCALE);

        return $this->fitnessCache[$cacheKey] = $fitness;
    }

    /**
     * @param bool[] $genes
     *
     * @return string
     */
    private function getCacheKey(array $genes): string
    {
        Assert::that(count($genes))->greaterThan(0);

        ksort($genes);

        $strings = [];

        foreach ($genes as $uuid => $isClusterNode) {
            $strings[] = sprintf('%s:%s', $uuid, $isClusterNode ? 'true' : 'false');
        }

        return md5(implode(';', $strings));
    }
}
