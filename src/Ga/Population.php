<?php

namespace Podorozhny\Dissertation\Ga;

use Assert\Assert;

class Population
{
    const MAX_SIZE = 20;

    const OBSOLESCENCE_RATIO = 0.1;

    /** @var Gene[] */
    private $members;

    /** @var int */
    private $generationNumber = 1;

    public function __construct(array $members)
    {
        Assert::thatAll($members)->isInstanceOf(Gene::class);
        Assert::that(count($members))->greaterOrEqualThan(2);

        $this->members = $members;

        $this->sortMembers();
    }

    /**
     * @return Gene[]
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    /**
     * @return Gene
     */
    public function getBestMember(): Gene
    {
        return reset($this->members);
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
        $replaceCount = ceil(count($this->members) * self::OBSOLESCENCE_RATIO);

        for ($i = 0; $i < $replaceCount; $i++) {
            $this->replaceWorstMembersWithNewChildren();
        }

        foreach ($this->members as $member) {
            $member->mutate(0.5);
        }

        $this->sortMembers();

        $this->generationNumber++;

        return $this;
    }

    /**
     * @return Population
     */
    private function replaceWorstMembersWithNewChildren(): self
    {
        list($firstChild, $secondChild) = reset($this->members)->mate(next($this->members));

        array_pop($this->members);
        array_pop($this->members);

        $this->members[] = $firstChild;
        $this->members[] = $secondChild;

        $this->sortMembers();

        return $this;
    }

    /**
     * @return Population
     */
    private function sortMembers(): self
    {
        usort(
            $this->members,
            function (Gene $firstGene, Gene $secondGene) {
                return $secondGene->getFitness() <=> $firstGene->getFitness();
            }
        );

        return $this;
    }
}
