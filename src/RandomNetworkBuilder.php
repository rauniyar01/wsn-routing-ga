<?php

namespace Podorozhny\Dissertation;

use Assert\Assertion;

final class RandomNetworkBuilder implements NetworkBuilder
{
    const CLUSTER_HEADS_RATIO = 0.1;

    /** {@inheritdoc} */
    public function build(BaseStation $baseStation, array $sensorNodes)
    {
        /** @var SensorNode[] $sensorNodes */
        Assertion::allIsInstanceOf($sensorNodes, SensorNode::class);

        $sensorNodes = array_filter(
            $sensorNodes,
            function (SensorNode $sensorNode) {
                return !$sensorNode->isDead();
            }
        );

        $sensorNodesCount = count($sensorNodes);

        if ($sensorNodesCount === 0) {
            return false;
        }

        $clusterHeadsCount = ceil($sensorNodesCount * self::CLUSTER_HEADS_RATIO);

        $keys = array_keys($sensorNodes);

        Util::shuffle($keys);

        $tmp = [];

        foreach ($keys as $id) {
            $tmp[$id] = $sensorNodes[$id];
        }

        $sensorNodes = $tmp;

        // Random cluster heads
        /** @var SensorNode[] $clusterHeads */
        $clusterHeads = array_slice($sensorNodes, 0, $clusterHeadsCount);

        /** @var SensorNode[] $clusterNodes */
        $clusterNodes = [];

        foreach ($sensorNodes as $sensorNode) {
            if (false !== array_search($sensorNode, $clusterHeads, true)) {
                $sensorNode->makeClusterHead();

                continue;
            }

            $sensorNode->makeClusterNode($sensorNode->getNearestNeighbor($clusterHeads));

            $clusterNodes[] = $sensorNode;
        }

        return new Network($baseStation, $clusterHeads, $clusterNodes);
    }
}
