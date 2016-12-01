<?php

namespace Fitbit\lib;

use Fitbit\FitBit;

class Module
{
    public $fitbit;

    public function __construct(FitBit $fitbit)
    {
        $this->fitbit = $fitbit;
    }
}
