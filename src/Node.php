<?php

namespace Podorozhny\Dissertation;

use Assert\Assertion;
use Ramsey\Uuid\Uuid;

class Node
{
    /** @var string */
    private $id;

    /** @var int in decimeters */
    private $x;

    /** @var int in decimeters */
    private $y;

    /** @var string */
    private $charge;

    /** @var bool */
    private $dead;

    /** @var Node|null */
    private $clusterHead;

    public function __construct(int $x, int $y, float $charge = 100)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->x  = $x;
        $this->y  = $y;
        $this->setCharge($charge);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getX(): int
    {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getY(): int
    {
        return $this->y;
    }

    /**
     * @return string
     */
    public function getCharge(): string
    {
        return $this->charge;
    }

    /**
     * @return bool
     */
    public function isDead(): bool
    {
        return $this->dead;
    }

    /**
     * @param string $value
     *
     * @return Node
     */
    public function reduceCharge(string $value): self
    {
        $this->setCharge(bcsub($this->charge, $value, BC_SCALE));

        return $this;
    }

    /**
     * @param string $charge
     *
     * @return Node
     */
    private function setCharge(string $charge): self
    {
        $this->charge = bccomp($charge, 0, BC_SCALE) === 1 ? $charge : 0;
        $this->dead   = bccomp($charge, 0, BC_SCALE) !== 1;

        return $this;
    }

    /**
     * @return Node
     */
    public function makeClusterHead(): self
    {
        $this->clusterHead = null;

        return $this;
    }

    /**
     * @param Node $clusterHead
     *
     * @return Node
     */
    public function makeClusterNode(Node $clusterHead): self
    {
        $this->clusterHead = $clusterHead;

        return $this;
    }

    /**
     * @return Node|null
     */
    public function getClusterHead()
    {
        return $this->clusterHead;
    }

    /**
     * @return bool
     */
    public function isClusterHead(): bool
    {
        return !$this->clusterHead instanceof Node;
    }

    /**
     * @param int $x
     * @param int $y
     *
     * @return int in decimeters
     */
    public function distanceTo(int $x, int $y): int
    {
        $distanceX = $this->getX() - $x;
        $distanceY = $this->getY() - $y;

        return ceil(sqrt($distanceX * $distanceX + $distanceY * $distanceY));
    }

    /**
     * @param Node $node
     *
     * @return int in decimeters
     */
    public function distanceToNeighbor(Node $node): int
    {
        return $this->distanceTo($node->getX(), $node->getY());
    }

    /**
     * @param Node[] $nodes
     *
     * @return Node|false
     */
    public function getNearestNeighbor(array $nodes)
    {
        if (count($nodes) === 0) {
            return false;
        }

        Assertion::allIsInstanceOf($nodes, Node::class);

        $nearest         = reset($nodes);
        $nearestDistance = $this->distanceToNeighbor($nearest);

        foreach ($nodes as $node) {
            $distance = $this->distanceToNeighbor($node);

            if ($this->getId() === $nearest->getId() || $distance < $nearestDistance) {
                $nearest         = $node;
                $nearestDistance = $distance;
            }
        }

        return $nearest;
    }
}
