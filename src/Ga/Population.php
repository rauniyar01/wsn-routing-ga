<?php

namespace Podorozhny\Dissertation\Ga;

use Assert\Assert;

class Population
{
    const MAX_SIZE = 10;

    const OBSOLESCENCE_RATIO = 0.4;

    /** @var Genotype[] */
    private $genotypes;

    /** @var int */
    private $generationNumber = 1;

    public function __construct(array $genotypes)
    {
        Assert::thatAll($genotypes)->isInstanceOf(Genotype::class);
        Assert::that(count($genotypes))->greaterOrEqualThan(2);

        $this->genotypes = $genotypes;

        $this->sortGenotypes();
    }

    /**
     * @return Genotype[]
     */
    public function getGenotypes(): array
    {
        return $this->genotypes;
    }

    /**
     * @return Genotype
     */
    public function getBestGenotype(): Genotype
    {
        return reset($this->genotypes);
    }

    /**
     * @return int
     */
    public function getGenerationNumber(): int
    {
        return $this->generationNumber;
    }

    /**
     * @return Population
     */
    public function produceNewGeneration(): self
    {
        $replaceCount = ceil(count($this->genotypes) * self::OBSOLESCENCE_RATIO);

        for ($i = 0; $i < $replaceCount; $i++) {
            $this->replaceWorstGenotypesWithNewChildren();
        }

        foreach ($this->genotypes as $genotype) {
            $genotype->mutate(0.5);
        }

        $this->sortGenotypes();

        $this->generationNumber++;

        return $this;
    }

    /**
     * @return Population
     */
    private function replaceWorstGenotypesWithNewChildren(): self
    {
        list($firstChild, $secondChild) = reset($this->genotypes)->mate(next($this->genotypes));

        array_pop($this->genotypes);
        array_pop($this->genotypes);

        $this->genotypes[] = $firstChild;
        $this->genotypes[] = $secondChild;

        $this->sortGenotypes();

        return $this;
    }

    /**
     * @return Population
     */
    private function sortGenotypes(): self
    {
        usort(
            $this->genotypes,
            function (Genotype $firstGenotype, Genotype $secondGenotype) {
                return $secondGenotype->getFitness() <=> $firstGenotype->getFitness();
            }
        );

        return $this;
    }
}
