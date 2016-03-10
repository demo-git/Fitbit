<?php
namespace Fabulator\Fitbit;

use Fabulator\Fitbit;

class Module
{
    public $fitbit;

    public function __construct(Fitbit $fitbit)
    {
        $this->fitbit = $fitbit;
    }
}
