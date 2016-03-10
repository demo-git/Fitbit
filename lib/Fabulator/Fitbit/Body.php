<?php
namespace Fabulator\Fitbit;

use Fabulator\Fitbit\Module;

use \Datetime;

class Body extends Module
{
    /**
     * Get body time series https://dev.fitbit.com/docs/body/#body-time-series
     * @param  string   $resource can be bmi, dat and weight
     * @param  Datetime $from     from date
     * @param  Datetime $to       to date
     * @return object             response from Fitbit
     */
    public function get($resource, Datetime $from, Datetime $to)
    {
        return $this->fitbit->get('body/' . $resource . '/date/' . $from->format('Y-m-d') . '/' . $to->format('Y-m-d'));
    }
}
