<?php

namespace Podorozhny\Dissertation;

use Assert\Assertion;
use Podorozhny\Dissertation\Ga\Gene;
use Podorozhny\Dissertation\Ga\Population;

class GeneticAlgorithmNetworkBuilder implements NetworkBuilder
{
    const INITIAL_CLUSTER_HEADS_RATIO = 0.1;

    /**
     * {@inheritdoc}
     */
    public function build(BaseStation $baseStation, array $nodes): Network
    {
        /** @var Node[] $nodes */

        Assertion::allIsInstanceOf($nodes, Node::class);

        $nodes = array_values(
            array_filter(
                $nodes,
                function (Node $node) {
                    return !$node->isDead();
                }
            )
        );

        $nodesCount = count($nodes);

        if ($nodesCount < 2) {
            return new Network($baseStation, [], []);
        }

        NetworkFitnessProvider::getInstance()::setNodes($nodes);

        $genes = [];

        for ($i = 0; $i < Population::MAX_SIZE; $i++) {
            $genes[] = $this->getRandomGene($nodesCount);
        }

        $population = new Population($genes);

        $bestFitness = $population->getBestMember()->getFitness();

        $this->printStats($population);

//        $goal = 2000;

//        while ($bestFitness < $goal) {
        while ($population->getGenerationNumber() < 5 && $bestFitness != PHP_INT_MAX) {
            $population->produceNewGeneration();

            $fitness = $population->getBestMember()->getFitness();

            if ($fitness === $bestFitness) {
                continue;
            }

            $this->printStats($population);

            $bestFitness = $fitness;
        }

        $bits = $population->getBestMember()->getBits();

        $clusterHeads = [];
        $clusterNodes = [];

        foreach ($nodes as $key => $node) {
            if (!$bits[$key]) {
                continue;
            }

            $node->makeClusterHead();

            $clusterHeads[] = $node;
        }

        foreach ($nodes as $key => $node) {
            if ($bits[$key]) {
                continue;
            }

            $node->makeClusterNode($node->getNearestNeighbor($clusterHeads));

            $clusterNodes[] = $node;
        }

        return new Network($baseStation, $clusterHeads, $clusterNodes);
    }

    /**
     * @param int $nodesCount
     *
     * @return Gene
     */
    public function getRandomGene(int $nodesCount): Gene
    {
        $bits = [];

        for ($i = 0; $i < $nodesCount; $i++) {
            $bits[] = false;
        }

        $clusterHeadsCount = $nodesCount * self::INITIAL_CLUSTER_HEADS_RATIO;

        do {
            $bits[array_rand($bits)] = true;
        } while (array_sum($bits) < $clusterHeadsCount);

        return new Gene($bits);
    }

    /**
     * @param Population $population
     */
    private function printStats(Population $population)
    {
        echo sprintf(
            'Generation: %d. Fitness: %f. Bits: %s.',
            $population->getGenerationNumber(),
            $population->getBestMember()->getFitness(),
            $population->getBestMember()->getBitsString()
        );

        echo "\n";
    }
}
