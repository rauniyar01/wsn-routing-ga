<?php

namespace Podorozhny\Dissertation;

final class SensorNode extends Node
{
    /** @var string */
    private $charge;

    /** @var bool */
    private $dead;

    /** @var Node|null */
    private $clusterHead;

    public function __construct(int $x, int $y, float $charge = 100)
    {
        parent::__construct($x, $y);

        $this->setCharge($charge);
    }

    /** @return string */
    public function getCharge(): string
    {
        return $this->charge;
    }

    /** @return bool */
    public function isDead(): bool
    {
        return $this->dead;
    }

    /**
     * @param string $value
     *
     * @return SensorNode
     */
    public function reduceCharge(string $value): self
    {
        $this->setCharge(bcsub($this->charge, $value, BC_SCALE));

        return $this;
    }

    /**
     * @param string $charge
     *
     * @return SensorNode
     */
    private function setCharge(string $charge): self
    {
        $this->charge = bccomp($charge, 0, BC_SCALE) === 1 ? $charge : 0;
        $this->dead   = bccomp($charge, 0, BC_SCALE) !== 1;

        return $this;
    }

    /** @return SensorNode */
    public function makeClusterHead(): self
    {
        $this->clusterHead = null;

        return $this;
    }

    /**
     * @param Node $clusterHead
     *
     * @return SensorNode
     */
    public function makeClusterNode(Node $clusterHead): self
    {
        $this->clusterHead = $clusterHead;

        return $this;
    }

    /** @return Node|null */
    public function getClusterHead()
    {
        return $this->clusterHead;
    }

    /** @return bool */
    public function isClusterHead(): bool
    {
        return !$this->clusterHead instanceof Node;
    }
}
