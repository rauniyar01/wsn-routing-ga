<?php

namespace Podorozhny\Dissertation;

use Assert\Assertion;
use Podorozhny\Dissertation\Ga\Genotype;
use Podorozhny\Dissertation\Ga\Population;
use Podorozhny\Dissertation\Ga\PopulationManager;
use Symfony\Component\Console\Output\OutputInterface;

class GeneticAlgorithmNetworkBuilder implements NetworkBuilder
{
    const INITIAL_CLUSTER_HEADS_RATIO = 0.1;

    /** @var PopulationManager */
    private $populationManager;

    /** @var OutputInterface */
    private $output;

    public function __construct(PopulationManager $populationManager, OutputInterface $output)
    {
        $this->populationManager = $populationManager;
        $this->output            = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function build(BaseStation $baseStation, array $nodes)
    {
        Assertion::allIsInstanceOf($nodes, Node::class);

        /** @var Node[] $nodes */
        $nodes = array_values(
            array_filter(
                $nodes,
                function (Node $node) {
                    return !$node->isDead();
                }
            )
        );

        if (count($nodes) < 2) {
            return false;
        }

        $this->populationManager->setNodes($nodes);

        $genotypes = [];

        for ($i = 0; $i < Population::MAX_SIZE; $i++) {
            $genotypes[] = $this->getRandomGenotype(count($nodes));
        }

        $population = $this->populationManager->create($genotypes);

        $bestGenotype = $population->getBestGenotype();
        $bestFitness  = $this->populationManager->getFitness($bestGenotype);

        $this->printStats($population);

//        while ($bestFitness < NetworkFitnessCalculator::GOAL) {
//        while ($population->getGenerationNumber() < 50 && $bestGenotype->getFitness() < 100000000) {
        while ($population->getGenerationNumber() < 50) {
            $this->populationManager->produceNewGeneration($population);

            $bestCurrentPopulationGenotype = $population->getBestGenotype();
            $bestCurrentPopulationFitness  = $this->populationManager->getFitness($bestCurrentPopulationGenotype);

            if (0 !== bccomp($bestCurrentPopulationFitness, $bestFitness, BC_SCALE)) {
                $this->printStats($population);
            }

            if (1 === bccomp($bestCurrentPopulationFitness, $bestFitness, BC_SCALE)) {
                $bestGenotype = $bestCurrentPopulationGenotype;
                $bestFitness  = $bestCurrentPopulationFitness;
            }
        }

        if (array_sum($bestGenotype->getGenes()) === 0) {
            return false;
        }

        $this->output->writeln(
            sprintf(
                '<comment>Chose best genotype (fitness %.6f): %s.</comment>',
                $bestFitness,
                (string) $bestGenotype
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
        $bestGenotype = $population->getBestGenotype();
        $bestFitness  = $this->populationManager->getFitness($bestGenotype);

        $this->output->writeln(
            sprintf(
                'Generation: %d. Best fitness: %.6f. Best genotype: %s.',
                number_format($population->getGenerationNumber(), 0, '', ' '),
                $bestFitness,
                (string) $bestGenotype
            )
        );
    }
}
