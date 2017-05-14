<?php

namespace Podorozhny\Dissertation;

use Assert\Assertion;
use Podorozhny\Dissertation\Ga\Genotype;
use Podorozhny\Dissertation\Ga\Population;
use Podorozhny\Dissertation\Ga\PopulationManager;
use Symfony\Component\Console\Output\OutputInterface;

class GeneticAlgorithmNetworkBuilder implements NetworkBuilder
{
    const GENERATIONS_COUNT = 20;
//    const GENERATIONS_COUNT = 500;
//    const GENERATIONS_COUNT               = 1000;
    const INITIAL_CLUSTER_HEADS_RATIO_MIN = 0.05;
    const INITIAL_CLUSTER_HEADS_RATIO_MAX = 0.1;

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

        if (count($nodes) < 1) {
            return false;
        }

        $this->populationManager->setNodes($nodes);

        $genotypes = [];

        for ($i = 0; $i < Population::SIZE; $i++) {
            $genotypes[] = $this->getRandomGenotype($nodes);
        }

        $population = $this->populationManager->create($genotypes);

        $bestGenotype = clone $population->getBestGenotype();

        $this->printStats($population);

//        while ($bestFitness < NetworkFitnessCalculator::GOAL) {
//        while ($population->getGenerationNumber() < 50 && $bestGenotype->getFitness() < 100000000) {
        while ($population->getGenerationNumber() < self::GENERATIONS_COUNT) {
            $this->populationManager->produceNewGeneration($population);

            $bestCurrentPopulationGenotype = clone $population->getBestGenotype();

//            if (0 !== bccomp($bestCurrentPopulationFitness, $bestFitness, BC_SCALE)) {
            if ($population->getGenerationNumber() % (self::GENERATIONS_COUNT / 20) === 0) {
                $this->printStats($population);
            }

            if (1 === bccomp(
                    $this->populationManager->getFitness($bestCurrentPopulationGenotype),
                    $this->populationManager->getFitness($bestGenotype),
                    BC_SCALE
                )
            ) {
                $bestGenotype = clone $bestCurrentPopulationGenotype;
            }
        }

        $this->output->writeln(
            sprintf(
                '<comment>Chose best genotype with fitness %.6f. Cluster heads: %d/%d.</comment>',
                $this->populationManager->getFitness($bestGenotype),
                array_sum($bestGenotype->getGenes()),
                count($bestGenotype->getGenes())
            )
        );

        $genes = $bestGenotype->getGenes();

        $clusterHeads = [];
        $clusterNodes = [];

        foreach ($nodes as $node) {
            if (!$genes[$node->getId()]) {
                continue;
            }

            $node->makeClusterHead();

            $clusterHeads[] = $node;
        }

        foreach ($nodes as $node) {
            if ($genes[$node->getId()]) {
                continue;
            }

            $nearestClusterHead = $node->getNearestNeighbor($clusterHeads);

            if (!$nearestClusterHead instanceof Node ||
                $node->distanceToNeighbor($baseStation) <= $node->distanceToNeighbor($nearestClusterHead)
            ) {
                $nearestClusterHead = $baseStation;
            }

            $node->makeClusterNode($nearestClusterHead);

            $clusterNodes[] = $node;
        }

        return new Network($baseStation, $clusterHeads, $clusterNodes);
    }

    /**
     * @param Node[] $nodes
     *
     * @return Genotype
     */
    public function getRandomGenotype(array $nodes): Genotype
    {
        $genes = [];

        foreach ($nodes as $node) {
            $genes[$node->getId()] = false;
        }

        $clusterHeadsRatio = mt_rand(
            100 * self::INITIAL_CLUSTER_HEADS_RATIO_MIN,
            100 * self::INITIAL_CLUSTER_HEADS_RATIO_MAX
        );

        $clusterHeadsRatio /= 100;

        $clusterHeadsCount = count($nodes) * $clusterHeadsRatio;

        do {
            $genes[Util::arrayRand($genes)] = true;
        } while (array_sum($genes) < $clusterHeadsCount);

        return new Genotype($genes);
    }

    /**
     * @param Population $population
     */
    private function printStats(Population $population)
    {
        $bestGenotype       = $population->getBestGenotype();
        $bestCurrentFitness = $this->populationManager->getFitness($bestGenotype);

        $this->output->writeln(
            sprintf(
                'Generation: %s / %s. Best fitness: %.6f / %.6f. Cluster heads: %d/%d.',
                str_pad(
                    number_format($population->getGenerationNumber(), 0, '', ' '),
                    mb_strlen(number_format(self::GENERATIONS_COUNT, 0, '', ' ')),
                    ' ',
                    STR_PAD_LEFT
                ),
                number_format(self::GENERATIONS_COUNT, 0, '', ' '),
                $bestCurrentFitness,
                $this->populationManager->getFitness($bestGenotype),
                array_sum($bestGenotype->getGenes()),
                count($bestGenotype->getGenes())
            )
        );
    }
}
