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
    private $generationsLimit;

    /** @var int */
    private $populationSize;

    /** @var float */
    private $fitnessGoal;

    /** @var float */
    private $initialClusterHeadsRatioMin;

    /** @var float */
    private $initialClusterHeadsRatioMax;

    public function __construct(
        PopulationManager $populationManager,
        OutputInterface $output,
        int $generationsLimit,
        int $populationSize,
        float $fitnessGoal,
        float $initialClusterHeadsRatioMin,
        float $initialClusterHeadsRatioMax
    )
    {
        $this->populationManager           = $populationManager;
        $this->output                      = $output;
        $this->generationsLimit            = $generationsLimit;
        $this->populationSize              = $populationSize;
        $this->fitnessGoal                 = $fitnessGoal;
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
        $bestFitness  = $this->populationManager->getFitness($bestGenotype);

        $bestGenotypeSinceLastUpdate = $bestGenotype;
        $bestFitnessSinceLastUpdate  = null;

        $this->printStats($population, $bestGenotypeSinceLastUpdate);
        $bestGenotypeSinceLastUpdate = null;

        while ($bestFitness < $this->fitnessGoal &&
               ($generationNumber = $population->getGenerationNumber()) < $this->generationsLimit) {
            $this->populationManager->produceNewGeneration($population);

            $bestCurrentPopulationGenotype = clone $population->getBestGenotype();
            $bestCurrentPopulationFitness  = $this->populationManager->getFitness($bestCurrentPopulationGenotype);

            if (is_null($bestFitnessSinceLastUpdate) ||
                1 === bccomp($bestCurrentPopulationFitness, $bestFitnessSinceLastUpdate, BC_SCALE)
            ) {
                $bestGenotypeSinceLastUpdate = $bestCurrentPopulationGenotype;
                $bestFitnessSinceLastUpdate  = $bestCurrentPopulationFitness;
            }

            if ($generationNumber % ceil($this->generationsLimit / 20) === 0 ||
                $generationNumber === $this->generationsLimit
            ) {
                $this->printStats($population, $bestGenotypeSinceLastUpdate);
                $bestGenotypeSinceLastUpdate = null;
                $bestFitnessSinceLastUpdate = null;
            }

            if (1 === bccomp($bestCurrentPopulationFitness, $bestFitness, BC_SCALE)) {
                $bestGenotype = clone $bestCurrentPopulationGenotype;
                $bestFitness  = $bestCurrentPopulationFitness;
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

    /**
     * @param Population $population
     * @param Genotype   $bestGenotypeSinceLastUpdate
     */
    private function printStats(Population $population, Genotype $bestGenotypeSinceLastUpdate)
    {
        $message = sprintf(
            'Generation: %s/%s. Best fitness since last update: %.6f. Topology: %s.',
            str_pad(
                number_format($population->getGenerationNumber(), 0, '', ' '),
                mb_strlen(number_format($this->generationsLimit, 0, '', ' ')),
                ' ',
                STR_PAD_LEFT
            ),
            number_format($this->generationsLimit, 0, '', ' '),
            $this->populationManager->getFitness($bestGenotypeSinceLastUpdate),
            (string) $bestGenotypeSinceLastUpdate
        );

        $this->output->writeln($message);
    }
}
