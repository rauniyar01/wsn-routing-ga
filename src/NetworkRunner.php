<?php

namespace Podorozhny\Dissertation;

final class NetworkRunner
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

    /** @return int */
    public function getRounds(): int
    {
        return $this->rounds;
    }

    /** @return Network */
    public function getNetwork(): Network
    {
        return $this->network;
    }

    /**
     * @param BaseStation|null $baseStation
     * @param SensorNode[]     $sensorNodes
     *
     * @return bool
     */
    public function run(BaseStation $baseStation = null, array $sensorNodes = []): bool
    {
        if ($this->network instanceof Network) {
            $baseStation = $this->network->getBaseStation();
            $sensorNodes = $this->network->getSensorNodes();
        }

        if (!$baseStation instanceof BaseStation) {
            throw new \InvalidArgumentException('No base station provided!');
        }

        if (count($sensorNodes) === 0) {
            throw new \InvalidArgumentException('No sensor nodes provided!');
        }

        $this->network = $this->builder->build($baseStation, $sensorNodes);

        if (!$this->network instanceof Network) {
            return false;
        }

        $genes = [];

        foreach ($sensorNodes as $sensorNode) {
            $genes[$sensorNode->getId()] = $sensorNode->isClusterHead();
        }

        $this->reducer->reduce($this->network);

        $this->rounds++;

        return $this->network->isAlive();
    }
}
