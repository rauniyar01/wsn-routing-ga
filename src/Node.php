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

    /** @var float */
    private $charge;

    /** @var bool */
    private $dead;

    /** @var Node|null */
    private $clusterHead;

    public function __construct(int $x, int $y, float $charge = 100.0)
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
     * @return float
     */
    public function getCharge(): float
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
     * @param float $value
     *
     * @return Node
     */
    public function reduceCharge(float $value): self
    {
        $this->setCharge($this->charge - $value);

        return $this;
    }

    /**
     * @param float $charge
     *
     * @return Node
     */
    private function setCharge(float $charge): self
    {
        $this->charge = $charge > 0 ? $charge : 0;
        $this->dead   = !($this->charge > 0);

        return $this;
    }

    /**
     * @param Node|null $node
     *
     * @return Node
     */
    public function setClusterHead(Node $node = null): self
    {
        $this->clusterHead = $node;

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
     * @param Node $node
     *
     * @return int in decimeters
     */
    public function distanceToNeighbor(Node $node): int
    {
        $distanceX = $this->getX() - $node->getX();
        $distanceY = $this->getY() - $node->getY();

        return ceil(sqrt($distanceX * $distanceX + $distanceY * $distanceY));
    }

    /**
     * @param BaseStation $baseStation
     *
     * @return int in decimeters
     */
    public function distanceToBaseStation(BaseStation $baseStation): int
    {
        $distanceX = $this->getX() - $baseStation->getX();
        $distanceY = $this->getY() - $baseStation->getY();

        return ceil(sqrt($distanceX * $distanceX + $distanceY * $distanceY));
    }

    /**
     * @param Node[] $nodes
     *
     * @return Node
     */
    public function getNearestNeighbor(array $nodes): Node
    {
        Assertion::true(count($nodes) > 0);
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
