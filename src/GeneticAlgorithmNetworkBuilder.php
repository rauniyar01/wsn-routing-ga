<?php

namespace Podorozhny\Dissertation;

use Assert\Assertion;
use Podorozhny\Dissertation\Ga\Genotype;
use Podorozhny\Dissertation\Ga\Population;
use Symfony\Component\Console\Output\OutputInterface;

class GeneticAlgorithmNetworkBuilder implements NetworkBuilder
{
    const INITIAL_CLUSTER_HEADS_RATIO = 0.1;

    /** @var NetworkFitnessProvider */
    private $fitnessProvider;

    /** @var OutputInterface */
    private $output;

    public function __construct(NetworkFitnessProvider $fitnessProvider, OutputInterface $output)
    {
        $this->fitnessProvider = $fitnessProvider;
        $this->output          = $output;
    }

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

        $this->fitnessProvider->setNodes($nodes);

        $genotypes = [];

        for ($i = 0; $i < Population::MAX_SIZE; $i++) {
            $genotypes[] = $this->getRandomGenotype($nodesCount);
        }

        $population = new Population($genotypes);

        $bestGenotype = $population->getBestGenotype();

        $this->printStats($population);

//        while ($bestFitness < NetworkFitnessProvider::GOAL) {
//        while ($population->getGenerationNumber() < 50 && $bestGenotype->getFitness() < 100000000) {
        while ($population->getGenerationNumber() < 50) {
            $population->produceNewGeneration();

            $bestCurrentPopulationGenotype = $population->getBestGenotype();

            if (0 !== bccomp($bestCurrentPopulationGenotype->getFitness(), $bestGenotype->getFitness(), BC_SCALE)) {
                $this->printStats($population);
            }

            if (1 === bccomp($bestCurrentPopulationGenotype->getFitness(), $bestGenotype->getFitness(), BC_SCALE)) {
                $bestGenotype = $bestCurrentPopulationGenotype;
            }
        }

        $this->output->writeln(
            sprintf(
                '<comment>Chose best genotype (fitness %.6f): %s.</comment>',
                $population->getBestGenotype()->getFitness(),
                (string) $population->getBestGenotype()
            )
        );

        $genes = $bestGenotype->getGenes();

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

        return new Network($baseStation, $clusterHeads, $clusterNodes);
    }

    /**
     * @param int $nodesCount
     *
     * @return Genotype
     */
    public function getRandomGenotype(int $nodesCount): Genotype
    {
        $genes = [];

        for ($i = 0; $i < $nodesCount; $i++) {
            $genes[] = false;
        }

        $clusterHeadsCount = $nodesCount * self::INITIAL_CLUSTER_HEADS_RATIO;

        do {
            $genes[array_rand($genes)] = true;
        } while (array_sum($genes) < $clusterHeadsCount);

        return new Genotype($genes);
    }

    /**
     * @param Population $population
     */
    private function printStats(Population $population)
    {
        $this->output->writeln(
            sprintf(
                'Generation: %d. Best fitness: %.6f. Best genotype: %s.',
                number_format($population->getGenerationNumber(), 0, '', ' '),
                $population->getBestGenotype()->getFitness(),
                (string) $population->getBestGenotype()
            )
        );
    }
}
