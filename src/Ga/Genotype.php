<?php

namespace Podorozhny\Dissertation\Ga;

use Assert\Assert;
use Podorozhny\Dissertation\NetworkFitnessProvider;

class Genotype
{
    /** @var bool[] */
    private $genes;

    /** @var string */
    private $fitness;

    public function __construct(array $genes)
    {
        Assert::thatAll($genes)->boolean();
        Assert::that(count($genes))->greaterOrEqualThan(2);

        $this->genes = $genes;

        $this->updateFitness();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return implode(
            '',
            array_map(
                function (bool $gene) {
                    return (int) $gene;
                },
                $this->getGenes()
            )
        );
    }

    /**
     * @return bool[]
     */
    public function getGenes(): array
    {
        return $this->genes;
    }

    /**
     * @return string
     */
    public function getFitness(): string
    {
        return $this->fitness;
    }

    /**
     * @param float $chance
     *
     * @return Genotype
     */
    public function mutate(float $chance): self
    {
        Assert::that($chance)->float()->between(0, 1);

        if ($chance * 100 <= mt_rand(0, 100)) {
            return $this;
        }

        $key = array_rand($this->genes);

        $this->genes[$key] = !$this->genes[$key];

        $this->updateFitness();

        return $this;
    }

    /**
     * @param Genotype $genotype
     *
     * @return Genotype[]
     */
    public function mate(Genotype $genotype): array
    {
        $pivot = round(count($this->genes) / 2);

        $firstChildGenes = array_merge(
            array_slice($this->genes, 0, $pivot),
            array_slice($genotype->getGenes(), $pivot)
        );

        $secondChildGenes = array_merge(
            array_slice($genotype->getGenes(), 0, $pivot),
            array_slice(
                $this->genes,
                $pivot
            )
        );

        return [new Genotype($firstChildGenes), new Genotype($secondChildGenes)];
    }

    /**
     * @return Genotype
     */
    public function updateFitness(): self
    {
        // TODO!
        $fitnessProvider = new NetworkFitnessProvider();

        $this->fitness = $fitnessProvider->getFitness($this->getGenes());

        return $this;
    }
}
