<?php
namespace Fabulator\Fitbit;

use Fabulator\Fitbit\Module;

use \Datetime;

class Sleep extends Module
{

    /**
     * Get sleep log https://dev.fitbit.com/docs/sleep/#get-sleep-logs
     * @param  Datetime $date for date
     * @return object         fitbit response
     */
    public function get(Datetime $date)
    {
        return $this->fitbit->get('sleep/date/' . $date->format('Y-m-d'));
    }

    /**
     * Log sleep https://dev.fitbit.com/docs/sleep/#log-sleep
     * @param  Datetime $start    start of sleep
     * @param  int      $duration duration in miliseconds
     * @return object             Fitbit response
     */
    public function log(Datetime $start, $duration)
    {
        return $this->fitbit->post('sleep', [
                'startTime' => $start->format('H:i'),
                'duration' => $duration,
                'date' => $start->format('Y-m-d')
            ]);
    }

    /**
     * Delete sleep log https://dev.fitbit.com/docs/sleep/#delete-sleep-log
     * @param  integer $id  id of log
     * @return object       Fitbit response
     */
    public function delete($id)
    {
        return $this->fitbit->delete('sleep/' . $id);
    }

    /**
     * Get sleeping time series https://dev.fitbit.com/docs/sleep/#sleep-time-series
     * @param  string   $type  startTime, timeInBed, minutesAsleep, awakeningsCount, minutesAwake, minutesToFallAsleep, minutesAfterWakeup, efficiency
     * @param  Datetime $start start of time series
     * @param  Datetime $end   end of time series
     * @return object          fitbit response
     */
    public function getTimeSeries($type, Datetime $start, Datetime $end)
    {
        return $this->fitbit->get('sleep/' . $type . '/date/' . $start->format('Y-m-d') . '/' . $end->format('Y-m-d'));
    }

    /**
     * Get sleep goal https://dev.fitbit.com/docs/sleep/#get-sleep-goal
     * @return object   fitbit response
     */
    public function getGoal()
    {
        return $this->fitbit->get('sleep/goal');
    }

    /**
     * Set fitbit sleeping goal https://dev.fitbit.com/docs/sleep/#update-sleep-goal
     * @param int $minDuration number of minutes
     */
    public function setGoal($minDuration)
    {
        return $this->fitbit->post(
            'sleep/goal',
            ['minDuration' => $minDuration]
        );
    }
}
