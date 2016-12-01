<?php

namespace Demo\Fitbit;

class Water extends Module
{

    /**
     * Get water log https://dev.fitbit.com/docs/food-logging/#get-water-logs
     * @param  \DateTime $date   date of log
     * @return object
     */
    public function get(\DateTime $date)
    {
        return $this->fitbit->get(
            'foods/log/water/date',
            ['date' => $date->format('Y-m-d')]
        );
    }

    /**
     * Add new water log https://dev.fitbit.com/docs/food-logging/#log-water
     * @param  \DateTime $date   date of log
     * @param  int $amount      amount of water
     * @param  string $unit     unit - ml fl oz and cup
     * @return object
     */
    public function log(\DateTime $date, $amount, $unit = 'ml')
    {

        $data = [
            'date' => $date->format('Y-m-d'),
            'amount' => $amount,
            'unit' => $unit
        ];

        return $this->fitbit->post('foods/log/water', $data);
    }

    /**
     * Delete water log https://dev.fitbit.com/docs/food-logging/#delete-water-log
     * @param  int $id   id of water log
     * @return object
     */
    public function delete($id)
    {
        return $this->fitbit->delete('foods/log/water/' . $id);
    }

    /**
     * Delete all water logs from one day
     * @param  \DateTime     $date the day
     */
    public function deleteForDay(\DateTime $date)
    {
        $logs = $this->get($date);
        if ($logs->water) {
            foreach ($logs->water as $log) {
                $this->delete($log->logId);
            }
        }
    }

    /**
     * Get water goal https://dev.fitbit.com/docs/food-logging/#get-water-goal
     * @return object
     */
    public function getGoal()
    {
        return $this->fitbit->get('foods/log/water/goal');
    }

    /**
     * Set water goal https://dev.fitbit.com/docs/food-logging/#update-water-goal
     * @param  integer $goal   water daily goal
     * @return object
     */
    public function setGoal($goal)
    {
        return $this->fitbit->post(
            'foods/log/water/goal',
            ['target' => $goal]
        );
    }
}
