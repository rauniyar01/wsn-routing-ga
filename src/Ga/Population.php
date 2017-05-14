<?php

namespace Podorozhny\Dissertation\Ga;

use Assert\Assert;

class Population
{
    const SIZE = 20;

    /** @var Genotype[] */
    private $genotypes;

    /** @var int */
    private $generationNumber = 1;

    public function __construct(array $genotypes)
    {
        $this->setGenotypes($genotypes);
    }

    /**
     * @param array $genotypes
     *
     * @return Population
     */
    public function setGenotypes(array $genotypes): self
    {
        Assert::that(count($genotypes))->eq(self::SIZE);
        Assert::thatAll($genotypes)->isInstanceOf(Genotype::class);

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
