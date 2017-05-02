<?php

namespace Podorozhny\Dissertation\Ga;

use Assert\Assert;
use Podorozhny\Dissertation\NetworkFitnessProvider;

class Gene
{
    /** @var bool[] */
    private $bits;

    /** @var string */
    private $fitness;

    public function __construct(array $bits)
    {
        Assert::thatAll($bits)->boolean();
        Assert::that(count($bits))->greaterOrEqualThan(2);

        $this->bits = $bits;

        $this->updateFitness();
    }

    /**
     * @return bool[]
     */
    public function getBits(): array
    {
        return $this->bits;
    }

    /**
     * @return string
     */
    public function getFitness(): string
    {
        return $this->fitness;
    }

    /**
     * @return string
     */
    public function getBitsString(): string
    {
        return implode(
            '',
            array_map(
                function (bool $bit) {
                    return (int) $bit;
                },
                $this->getBits()
            )
        );
    }

    /**
     * @param float $chance
     *
     * @return Gene
     */
    public function mutate(float $chance): self
    {
        Assert::that($chance)->float()->between(0, 1);

        if ($chance * 100 <= mt_rand(0, 100)) {
            return $this;
        }

        $key = array_rand($this->bits);

        $this->bits[$key] = !$this->bits[$key];

        $this->updateFitness();

        return $this;
    }

    /**
     * @param Gene $gene
     *
     * @return Gene[]
     */
    public function mate(Gene $gene): array
    {
        $pivot = round(count($this->bits) / 2);

        $firstChildBits  = array_merge(array_slice($this->bits, 0, $pivot), array_slice($gene->getBits(), $pivot));
        $secondChildBits = array_merge(array_slice($gene->getBits(), 0, $pivot), array_slice($this->bits, $pivot));

        return [new Gene($firstChildBits), new Gene($secondChildBits)];
    }

    /**
     * @return Gene
     */
    public function updateFitness(): self
    {
        $fitnessProvider = NetworkFitnessProvider::getInstance();

        $this->fitness = $fitnessProvider::getFitness($this->getBits());

        return $this;
    }
}
