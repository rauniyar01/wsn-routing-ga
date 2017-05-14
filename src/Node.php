<?php

namespace Podorozhny\Dissertation;

use Assert\Assertion;
use Ramsey\Uuid\Uuid;

abstract class Node
{
    /** @var string */
    private $id;

    /** @var int in meters */
    private $x;

    /** @var int in meters */
    private $y;

    public function __construct(int $x, int $y)
    {
        $this->id = Uuid::uuid4()->toString();
        $this->x  = $x * 10;
        $this->y  = $y * 10;
    }

    /** @return string */
    public function getId(): string
    {
        return $this->id;
    }

    /** @return int */
    public function getX(): int
    {
        return $this->x;
    }

    /** @return int */
    public function getY(): int
    {
        return $this->y;
    }

    /**
     * @param int $x
     * @param int $y
     *
     * @return int in meters
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
     * @return int in meters
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
