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
    }

    /**
     * @param array $genotypes
     *
     * @return Population
     */
    public function setGenotypes(array $genotypes): self
    {
        $this->genotypes = $genotypes;

        return $this;
    }

    /**
     * @return Genotype[]
     */
    public function getGenotypes(): array
    {
        return $this->genotypes;
    }

    /**
     * @return Population
     */
    public function incrementGenerationNumber(): self
    {
        $this->generationNumber++;

        return $this;
    }

    /**
     * @return int
     */
    public function getGenerationNumber(): int
    {
        return $this->generationNumber;
    }

    /**
     * @return Genotype
     */
    public function getBestGenotype(): Genotype
    {
        return reset($this->genotypes);
    }
}
