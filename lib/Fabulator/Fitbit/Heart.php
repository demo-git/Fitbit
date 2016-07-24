<?php
namespace Fabulator\Fitbit;

use Fabulator\Fitbit\Module;

use \Datetime;

class Heart extends Module
{
    /**
     * Get time series of HR. Must be personal app.
     * @param  Datetime $date        Day of hearth rate.
     * @param  string   $detailLevel Detail level, could min sec or min
     * @param  string   $startTime   Start time in HH:MM format
     * @param  string   $endTime     End time in HH:MM format
     * @return object                response from Fitbit
     */
    public function getDetailedHR(Datetime $date, $detailLevel = 'sec', $startTime = '00:00', $endTime = '23:39')
    {
        return $this->fitbit->get(
            'activities/heart/date/' . $date->format('Y-m-d') .
            '/1d/1' . $detailLevel .
            '/time/' . $startTime .
            '/' . $endTime
        );
    }
}
