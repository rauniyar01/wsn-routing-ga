<?php

namespace Podorozhny\Dissertation;

use Assert\Assertion;

class RandomNetworkBuilder implements NetworkBuilder
{
    const CLUSTER_HEADS_PERCENT = 10;

    /**
     * {@inheritdoc}
     */
    public function build(BaseStation $baseStation, array $nodes): Network
    {
        $nodesCount = count($nodes);

        $clusterHeadsCount = ceil($nodesCount * self::CLUSTER_HEADS_PERCENT / 100);

        Assertion::true($nodesCount > 0);
        Assertion::true($clusterHeadsCount > 0);
        Assertion::true($nodesCount >= $clusterHeadsCount);
        Assertion::allIsInstanceOf($nodes, Node::class);

        // The first 10% of nodes is cluster heads
        $clusterHeads = array_slice($nodes, 0, $clusterHeadsCount);

        /** @var Node[] $clusterNodes */
        $clusterNodes = [];

        foreach ($nodes as $node) {
            if (false !== array_search($node, $clusterHeads, true)) {
                $node->setClusterHead(null);

                continue;
            }

            $node->setClusterHead($node->getNearestNeighbor($clusterHeads));

            $clusterNodes[] = $node;
        }

        return new Network($baseStation, $clusterHeads, $clusterNodes);
    }
}
