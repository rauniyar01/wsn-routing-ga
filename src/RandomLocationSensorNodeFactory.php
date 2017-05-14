<?php

namespace Podorozhny\Dissertation;

final class RandomLocationSensorNodeFactory
{
    /**
     * @param int $fieldWidth  in meters
     * @param int $fieldHeight in meters
     *
     * @return SensorNode
     */
    public function create(int $fieldWidth, int $fieldHeight): SensorNode
    {
        return new SensorNode(mt_rand(0, $fieldWidth), mt_rand(0, $fieldHeight));
    }
}
