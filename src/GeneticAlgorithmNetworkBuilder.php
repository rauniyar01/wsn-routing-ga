<?php

namespace Podorozhny\Dissertation;

use Assert\Assertion;
use Podorozhny\Dissertation\Ga\Genotype;
use Podorozhny\Dissertation\Ga\Population;
use Podorozhny\Dissertation\Ga\PopulationManager;
use Symfony\Component\Console\Output\OutputInterface;

final class GeneticAlgorithmNetworkBuilder implements NetworkBuilder
{
    /** @var PopulationManager */
    private $populationManager;

    /** @var OutputInterface */
    private $output;

    /** @var int */
    private $generationsCount;

    /** @var int */
    private $populationSize;

    /** @var float */
    private $initialClusterHeadsRatioMin;

    /** @var float */
    private $initialClusterHeadsRatioMax;

    public function __construct(
        PopulationManager $populationManager,
        OutputInterface $output,
        int $generationsCount,
        int $populationSize,
        float $initialClusterHeadsRatioMin,
        float $initialClusterHeadsRatioMax
    )
    {
        $this->populationManager           = $populationManager;
        $this->output                      = $output;
        $this->generationsCount            = $generationsCount;
        $this->populationSize              = $populationSize;
        $this->initialClusterHeadsRatioMin = $initialClusterHeadsRatioMin;
        $this->initialClusterHeadsRatioMax = $initialClusterHeadsRatioMax;
    }

    /** {@inheritdoc} */
    public function build(BaseStation $baseStation, array $sensorNodes)
    {
        Assertion::allIsInstanceOf($sensorNodes, SensorNode::class);

        /** @var SensorNode[] $sensorNodes */
        $sensorNodes = array_values(
            array_filter(
                $sensorNodes,
                function (SensorNode $sensorNodes) {
                    return !$sensorNodes->isDead();
                }
            )
        );

        if (count($sensorNodes) < 1) {
            return false;
        }

        $this->populationManager->setSensorNodes($sensorNodes);

        $genotypes = [];

        for ($i = 0; $i < $this->populationSize; $i++) {
            $genotypes[] = $this->getRandomGenotype($sensorNodes);
        }

        $population = $this->populationManager->create($genotypes);

        $bestGenotype = clone $population->getBestGenotype();

        $this->printStats($population);

//        while ($bestFitness < NetworkFitnessCalculator::GOAL) {
//        while ($population->getGenerationNumber() < 50 && $bestGenotype->getFitness() < 100000000) {
        while ($population->getGenerationNumber() < $this->generationsCount) {
            $this->populationManager->produceNewGeneration($population);

            $bestCurrentPopulationGenotype = clone $population->getBestGenotype();

            if ($population->getGenerationNumber() % ceil($this->generationsCount / 20) === 0 ||
                $population->getGenerationNumber() === $this->generationsCount
            ) {
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
                $sensorNode->distanceToNeighbor($baseStation) <= $sensorNode->distanceToNeighbor($nearestClusterHead)
            ) {
                $nearestClusterHead = $baseStation;
            }

            $sensorNode->makeClusterNode($nearestClusterHead);

            $clusterNodes[] = $sensorNode;
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
            100 * $this->initialClusterHeadsRatioMin,
            100 * $this->initialClusterHeadsRatioMax
        );

        $clusterHeadsRatio /= 100;

        $clusterHeadsCount = count($nodes) * $clusterHeadsRatio;

        do {
            $genes[Util::arrayRand($genes)] = true;
        } while (array_sum($genes) < $clusterHeadsCount);

        return new Genotype($genes);
    }

    /** @param Population $population */
    private function printStats(Population $population)
    {
        $bestGenotype = $population->getBestGenotype();
        $genes        = $bestGenotype->getGenes();

        $this->output->writeln(
            sprintf(
                'Generation: %s/%s. Best generation genotype fitness: %.6f. Cluster heads: %d/%d.',
                str_pad(
                    number_format($population->getGenerationNumber(), 0, '', ' '),
                    mb_strlen(number_format($this->generationsCount, 0, '', ' ')),
                    ' ',
                    STR_PAD_LEFT
                ),
                number_format($this->generationsCount, 0, '', ' '),
                $this->populationManager->getFitness($bestGenotype),
                array_sum($genes),
                count($genes)
            )
        );
    }
}
