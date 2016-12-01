<?php

namespace Fitbit\lib;

class Body extends Module
{
    /**
     * Get body time series https://dev.fitbit.com/docs/body/#body-time-series
     * @param  string   $resource can be bmi, dat and weight
     * @param  \DateTime $from     from date
     * @param  \DateTime $to       to date
     * @return object             response from Fitbit
     */
    public function get($resource, \DateTime $from, \DateTime $to)
    {
        return $this->fitbit->get('body/' . $resource . '/date/' . $from->format('Y-m-d') . '/' . $to->format('Y-m-d'));
    }
}
