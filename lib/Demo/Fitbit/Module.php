<?php

namespace Demo\Fitbit;

use Demo\FitBit;

class Module
{
    public $fitbit;

    public function __construct(FitBit $fitbit)
    {
        $this->fitbit = $fitbit;
    }
}
