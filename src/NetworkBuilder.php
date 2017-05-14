<?php

namespace Podorozhny\Dissertation;

interface NetworkBuilder
{
    /**
     * @param BaseStation  $baseStation
     * @param SensorNode[] $sensorNodes
     *
     * @return Network|false
     */
    public function build(BaseStation $baseStation, array $sensorNodes);
}
