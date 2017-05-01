<?php

namespace Podorozhny\Dissertation;

interface NetworkBuilder
{
    /**
     * @param BaseStation $baseStation
     * @param array       $nodes
     *
     * @return Network
     */
    public function build(BaseStation $baseStation, array $nodes): Network;
}
