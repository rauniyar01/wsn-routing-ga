<?php

namespace Podorozhny\Dissertation\Ga;

use Podorozhny\Dissertation\NetworkFitnessCalculator;
use Podorozhny\Dissertation\Node;
use Podorozhny\Dissertation\Util;

class PopulationManager
{
    const ELITE_GENOTYPE_RATE = 0.1;
    const CROSSOVER_RATE      = 0.6;

    const MUTATION_RATE       = 0.5;

    /** @var NetworkFitnessCalculator */
    private $fitnessCalculator;

    /** @var Node[] */
    private $nodes;

    public function __construct(NetworkFitnessCalculator $fitnessCalculator)
    {
        $this->fitnessCalculator = $fitnessCalculator;
    }

    /**
     * @param array $nodes
     *
     * @return PopulationManager
     */
    public function setNodes(array $nodes): self
    {
        $this->nodes = $nodes;

        return $this;
    }

    /**
     * @param array $genotypes
     *
     * @return Population
     */
    public function create(array $genotypes): Population
    {
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

        $eliteGenotypesCount = ceil(Population::SIZE * self::ELITE_GENOTYPE_RATE / 2) * 2;

        /** @var Genotype[] $newGenotypes */
        $newGenotypes = [];

        for ($i = 0; $i < $eliteGenotypesCount; $i++) {
            $newGenotypes[$i] = $genotypes[$i];

            if ($i < $eliteGenotypesCount / 2) {
                $newGenotypes[$i]->mutate(self::MUTATION_RATE);
            }
        }

        for ($i = 0; $i < floor(Population::SIZE * self::CROSSOVER_RATE / 2); $i++) {
            $fatherKey = Util::arrayRand($genotypes);

            do {
                $motherKey = Util::arrayRand($genotypes);
            } while ($fatherKey !== $motherKey);

            $father = $genotypes[$fatherKey];
            $mother = $genotypes[$motherKey];

            list($firstChild, $secondChild) = $father->mate($mother);

            $newGenotypes[] = $firstChild->mutate(self::MUTATION_RATE);
            $newGenotypes[] = $secondChild->mutate(self::MUTATION_RATE);
        }

        while (count($newGenotypes) < Population::SIZE) {
            $randomGenotype = $genotypes[Util::arrayRand($genotypes)];

            if (in_array($randomGenotype, $newGenotypes, true)) {
                continue;
            }

            $newGenotypes[] = $randomGenotype->mutate(self::MUTATION_RATE);
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
        return $this->fitnessCalculator->getFitness($this->nodes, $genotype->getGenes());
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
