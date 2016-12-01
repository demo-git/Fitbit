<?php

namespace Fitbit\lib;

class Sleep extends Module
{

    /**
     * Get sleep log https://dev.fitbit.com/docs/sleep/#get-sleep-logs
     * @param  \DateTime $date for date
     * @return object         fitbit response
     */
    public function get(\DateTime $date)
    {
        return $this->fitbit->get('sleep/date/' . $date->format('Y-m-d'));
    }

    /**
     * Log sleep https://dev.fitbit.com/docs/sleep/#log-sleep
     * @param  \DateTime $start    start of sleep
     * @param  int      $duration duration in miliseconds
     * @return object             Fitbit response
     */
    public function log(\DateTime $start, $duration)
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
     * @param  \DateTime $start start of time series
     * @param  \DateTime $end   end of time series
     * @return object          fitbit response
     */
    public function getTimeSeries($type, \DateTime $start, \DateTime $end)
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
     * @return Object fitbit response
     */
    public function setGoal($minDuration)
    {
        return $this->fitbit->post(
            'sleep/goal',
            ['minDuration' => $minDuration]
        );
    }
}
