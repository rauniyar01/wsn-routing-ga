<?php

namespace Podorozhny\Dissertation;

class NetworkRunner
{
    /** @var int */
    private $rounds = 0;

    /** @var NetworkBuilder */
    private $builder;

    /** @var OneRoundChargeReducer */
    private $reducer;

    /** @var Network */
    private $network;

    public function __construct(NetworkBuilder $builder, OneRoundChargeReducer $reducer)
    {
        $this->builder = $builder;
        $this->reducer = $reducer;
    }

    /**
     * @return int
     */
    public function getRounds(): int
    {
        return $this->rounds;
    }

    /**
     * @return Network
     */
    public function getNetwork(): Network
    {
        return $this->network;
    }

    /**
     * @param BaseStation|null $baseStation
     * @param Node[]           $nodes
     *
     * @return bool
     */
    public function run(BaseStation $baseStation = null, array $nodes = []): bool
    {
        if ($this->network instanceof Network) {
            $baseStation = $this->network->getBaseStation();
            $nodes       = $this->network->getNodes();
        }

        if (!$baseStation instanceof BaseStation) {
            throw new \InvalidArgumentException('No base station provided!');
        }

        if (count($nodes) === 0) {
            throw new \InvalidArgumentException('No nodes provided!');
        }

        $this->network = $this->builder->build($baseStation, $nodes);

        if (!$this->network instanceof Network) {
            return false;
        }

        $this->reducer->reduce($this->network);

        $this->rounds++;

        return $this->network->isAlive();
    }
}
