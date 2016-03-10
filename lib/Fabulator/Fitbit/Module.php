<?php
namespace Fabulator\Fitbit;

use Fabulator\Fitbit;

class Module
{
    private $fitbit;

    public function __construct(Fitbit $fitbit)
    {
        $this->fitbit = $fitbit;
    }
}
