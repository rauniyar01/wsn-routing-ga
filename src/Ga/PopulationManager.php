<?php

namespace Podorozhny\Dissertation\Ga;

use Assert\Assert;
use Podorozhny\Dissertation\NetworkFitnessCalculator;
use Podorozhny\Dissertation\SensorNode;
use Podorozhny\Dissertation\Util;

final class PopulationManager
{
    /** @var NetworkFitnessCalculator */
    private $fitnessCalculator;

    /** @var int */
    private $populationSize;

    /** @var float */
    private $eliteGenotypeRate;

    /** @var float */
    private $crossoverRate;

    /** @var float */
    private $mutationRate;

    /** @var SensorNode[] */
    private $sensorNodes;

    public function __construct(
        NetworkFitnessCalculator $fitnessCalculator,
        int $populationSize,
        float $eliteGenotypeRate,
        float $crossoverRate,
        float $mutationRate
    )
    {
        $this->fitnessCalculator = $fitnessCalculator;
        $this->populationSize    = $populationSize;
        $this->eliteGenotypeRate = $eliteGenotypeRate;
        $this->crossoverRate     = $crossoverRate;
        $this->mutationRate      = $mutationRate;
    }

    /**
     * @param array $sensorNodes
     *
     * @return PopulationManager
     */
    public function setSensorNodes(array $sensorNodes): self
    {
        $this->sensorNodes = $sensorNodes;

        return $this;
    }

    /**
     * @param Genotype[] $genotypes
     *
     * @return Population
     */
    public function create(array $genotypes): Population
    {
        Assert::thatAll($genotypes)->isInstanceOf(Genotype::class);
        Assert::that(count($genotypes))->eq($this->populationSize);

        return new Population($this->sortGenotypes($genotypes));
    }

    /**
     * @param Population $population
     *
     * @return PopulationManager
     */
    public function produceNewGeneration(Population $population): self
    {
        $genotypes = $population->getGenotypes();

        $eliteGenotypesCount = (int) ceil($this->populationSize * $this->eliteGenotypeRate / 2) * 2;

        if ($eliteGenotypesCount >= $this->populationSize) {
            $eliteGenotypesCount = 0;
        }

        /** @var Genotype[] $newGenotypes */
        $newGenotypes = [];

        for ($i = 0; $i < $eliteGenotypesCount; $i++) {
            $newGenotypes[$i] = $genotypes[$i];

            if ($i < $eliteGenotypesCount / 2) {
                $newGenotypes[$i]->mutate($this->mutationRate);
            }
        }

        for ($i = 0; $i < (int) floor($this->populationSize * $this->crossoverRate / 2); $i++) {
            $fatherKey = Util::arrayRand($genotypes);

            do {
                $motherKey = Util::arrayRand($genotypes);
            } while ($fatherKey !== $motherKey);

            $father = $genotypes[$fatherKey];
            $mother = $genotypes[$motherKey];

            list($firstChild, $secondChild) = $father->mate($mother);

            $newGenotypes[] = $firstChild->mutate($this->mutationRate);
            $newGenotypes[] = $secondChild->mutate($this->mutationRate);
        }

        while (count($newGenotypes) < $this->populationSize) {
            $randomGenotype = $genotypes[Util::arrayRand($genotypes)];

            if (in_array($randomGenotype, $newGenotypes, true)) {
                continue;
            }

            $newGenotypes[] = $randomGenotype->mutate($this->mutationRate);
        }

        $this->sortGenotypes($genotypes);

        $population->setGenotypes($genotypes);

        $population->incrementGenerationNumber();

        return $this;
    }

    /**
     * @param Genotype $genotype
     *
     * @return string
     */
    public function getFitness(Genotype $genotype)
    {
        return $this->fitnessCalculator->getFitness($this->sensorNodes, $genotype->getGenes());
    }

    /**
     * @param array $genotypes
     *
     * @return array
     */
    private function sortGenotypes(array $genotypes): array
    {
        usort(
            $genotypes,
            function (Genotype $firstGenotype, Genotype $secondGenotype) {
                return $this->getFitness($secondGenotype) <=> $this->getFitness($firstGenotype);
            }
        );

        return $genotypes;
    }
}
