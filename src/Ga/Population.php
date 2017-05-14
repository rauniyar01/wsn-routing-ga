<?php

namespace Podorozhny\Dissertation\Ga;

final class Population
{
    /** @var Genotype[] */
    private $genotypes;

    /** @var int */
    private $generationNumber = 1;

    public function __construct(array $genotypes)
    {
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

    /** @return Genotype[] */
    public function getGenotypes(): array
    {
        return $this->genotypes;
    }

    /** @return Population */
    public function incrementGenerationNumber(): self
    {
        $this->generationNumber++;

        return $this;
    }

    /** @return int */
    public function getGenerationNumber(): int
    {
        return $this->generationNumber;
    }

    /** @return Genotype */
    public function getBestGenotype(): Genotype
    {
        return reset($this->genotypes);
    }
}
