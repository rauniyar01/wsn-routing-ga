<?php

namespace Podorozhny\Dissertation;

interface NetworkBuilder
{
    /**
     * @param BaseStation $baseStation
     * @param Node[]      $nodes
     *
     * @return Network|false
     */
    public function build(BaseStation $baseStation, array $nodes);
}
