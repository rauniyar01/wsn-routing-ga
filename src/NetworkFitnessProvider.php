<?php

namespace Podorozhny\Dissertation;

use Assert\Assert;

final class NetworkFitnessProvider
{
    private static $instance;

    /** @var Node[] */
    private static $nodes;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    protected function __wakeup()
    {
    }

    /**
     * @return NetworkFitnessProvider
     */
    public static function getInstance(): NetworkFitnessProvider
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * @param array $nodes
     */
    public static function setNodes(array $nodes)
    {
        self::$nodes = array_values($nodes);
    }

    /**
     * @param bool[] $bits
     *
     * @return string
     */
    public static function getFitness(array $bits): string
    {
        Assert::thatAll($bits)->boolean();

        Assert::that(count($bits))->eq(count(self::$nodes));

        if (array_sum($bits) === 0) {
            return 0;
        }

        $baseStation = new BaseStation(FIELD_SIZE * 10 / 2, FIELD_SIZE * 10 / 2);

        $clusterHeads = [];
        $clusterNodes = [];

        foreach (self::$nodes as $key => $node) {
            if (!$bits[$key]) {
                continue;
            }

            $node->makeClusterHead();

            $clusterHeads[] = $node;
        }

        foreach (self::$nodes as $key => $node) {
            if ($bits[$key]) {
                continue;
            }

            $node->makeClusterNode($node->getNearestNeighbor($clusterHeads));

            $clusterNodes[] = $node;
        }

        $network = new Network($baseStation, $clusterHeads, $clusterNodes);

        $totalCharge = $network->getTotalCharge();

        (new OneRoundChargeReducer())->reduce($network);

        $totalChargeConsumption = bcsub($totalCharge, $network->getTotalCharge(), BC_SCALE);

        $averageChargeConsumption = bcdiv($totalChargeConsumption, $network->getNodesCount(), BC_SCALE);

        if ($averageChargeConsumption == 0) {
            return PHP_INT_MAX;
        }

        return bcdiv(1, $averageChargeConsumption, BC_SCALE);
    }
}
