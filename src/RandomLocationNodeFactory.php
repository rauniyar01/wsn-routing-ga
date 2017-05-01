<?php

namespace Podorozhny\Dissertation;

class RandomLocationNodeFactory
{
    /**
     * @param int $fieldWidth  in meters
     * @param int $fieldHeight in meters
     *
     * @return Node
     */
    public function create(int $fieldWidth, int $fieldHeight): Node
    {
        return new Node(mt_rand(0, $fieldWidth * 10), mt_rand(0, $fieldHeight * 10));
    }
}
