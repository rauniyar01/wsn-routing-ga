<?php

namespace Podorozhny\Dissertation\Ga;

use Assert\Assert;
use Podorozhny\Dissertation\Util;

final class Genotype
{
    /** @var bool[] */
    private $genes;

    public function __construct(array $genes)
    {
        Assert::that(count($genes))->greaterOrEqualThan(1);
        Assert::thatAll($genes)->boolean();
        Assert::thatAll(array_keys($genes))->uuid();

        ksort($genes);

        $this->genes = $genes;
    }

    /** @return string */
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

    /** @return bool[] */
    public function getGenes(): array
    {
        return $this->genes;
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

        $genesToMutate = mt_rand(1, ceil(count($this->genes) / 25));

        for ($i = 0; $i < $genesToMutate; $i++) {
            $key = Util::arrayRand($this->genes);

            $this->genes[$key] = !$this->genes[$key];
        }

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
}
