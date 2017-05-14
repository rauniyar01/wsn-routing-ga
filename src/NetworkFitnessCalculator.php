<?php

namespace Podorozhny\Dissertation;

use Assert\Assert;

final class NetworkFitnessCalculator
{
    /** @var OneRoundChargeReducer */
    private $reducer;

    /** @var BaseStation */
    private $baseStation;

    /** @var array */
    private $fitnessCache = [];

    public function __construct(OneRoundChargeReducer $reducer, BaseStation $baseStation)
    {
        $this->reducer     = $reducer;
        $this->baseStation = $baseStation;
    }

    /**
     * @param SensorNode[] $sensorNodes
     * @param bool[]       $genes
     *
     * @return string
     */
    public function getFitness(array $sensorNodes, array $genes): string
    {
        $cacheKey = $this->getCacheKey($genes);

        if (array_key_exists($cacheKey, $this->fitnessCache)) {
            return $this->fitnessCache[$cacheKey];
        }

        $clonedSensorNodes = [];

        foreach ($sensorNodes as $sensorNode) {
            $clonedSensorNodes[] = clone $sensorNode;
        }

        $sensorNodes = $clonedSensorNodes;

        Assert::thatAll($genes)->boolean();

        Assert::that(count($genes))->eq(count($sensorNodes));

        $clusterHeads = [];
        $clusterNodes = [];

        foreach ($sensorNodes as $sensorNode) {
            if (!$genes[$sensorNode->getId()]) {
                continue;
            }

            $sensorNode->makeClusterHead();

            $clusterHeads[] = $sensorNode;
        }

        foreach ($sensorNodes as $sensorNode) {
            if ($genes[$sensorNode->getId()]) {
                continue;
            }

            $nearestClusterHead = $sensorNode->getNearestNeighbor($clusterHeads);

            if (!$nearestClusterHead instanceof Node ||
                $sensorNode->distanceToNeighbor($this->baseStation) <=
                $sensorNode->distanceToNeighbor($nearestClusterHead)
            ) {
                $nearestClusterHead = $this->baseStation;
            }

            $sensorNode->makeClusterNode($nearestClusterHead);

            $clusterNodes[] = $sensorNode;
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
