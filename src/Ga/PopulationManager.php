<?php

namespace Podorozhny\Dissertation\Ga;

use Podorozhny\Dissertation\NetworkFitnessCalculator;
use Podorozhny\Dissertation\Node;

class PopulationManager
{
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

        $replaceCount = ceil(count($genotypes) * Population::OBSOLESCENCE_RATIO);

        for ($i = 0; $i < $replaceCount; $i++) {
            $genotypes = $this->replaceWorstGenotypesWithNewChildren($genotypes);
        }

        foreach ($genotypes as $genotype) {
            $genotype->mutate(0.5);
        }

        $genotypes = $this->sortGenotypes($genotypes);

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
    private function replaceWorstGenotypesWithNewChildren(array $genotypes): array
    {
        list($firstChild, $secondChild) = reset($genotypes)->mate(next($genotypes));

        array_pop($genotypes);
        array_pop($genotypes);

        $genotypes[] = $firstChild;
        $genotypes[] = $secondChild;

        return $genotypes;
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
