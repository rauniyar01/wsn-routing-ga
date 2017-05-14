<?php

namespace Podorozhny\Dissertation;

class BaseStation extends Node
{
    public function __construct(int $x, int $y)
    {
        parent::__construct($x, $y);
    }
}
