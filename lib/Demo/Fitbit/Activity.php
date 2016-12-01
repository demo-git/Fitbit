<?php

namespace Demo\Fitbit;

class Activity extends Module
{

    /**
     * Add new activity https://dev.fitbit.com/docs/activity/#log-activity
     * @param  \DateTime $date
     * @param  string $activityTypeId
     * @param  int $duration duration in sec
     * @param  float $distance
     * @param  int $calories
     * @param  string $distanceUnit name of unit
     * @return object
     */
    public function log(\DateTime $date, $activityTypeId, $duration, $distance = null, $calories = null, $distanceUnit = null)
    {
        return $this->edit($date, $activityTypeId, $duration, $distance, $calories, $distanceUnit, null);
    }

    /**
     * Get info about single activity (i did not found documentation)
     * @param  string $logId activity log id
     * @return object
     */
    public function get($logId)
    {
        return $this->fitbit->get('activities/' . $logId);
    }

    /**
     * Add new activity https://dev.fitbit.com/docs/activity/#log-activity
     * @param  \DateTime $date
     * @param  string $activityTypeId
     * @param  int $duration duration in sec
     * @param  float $distance
     * @param  int $calories
     * @param  string $distanceUnit name of unit
     * @param  int $logId
     * @return object
     */
    public function edit($date, $activityTypeId, $duration, $distance = null, $calories = null, $distanceUnit = null, $logId = null)
    {
        $data = [
            'date' => $date->format('Y-m-d'),
            'startTime' => $date->format('H:i'),
            'activityId' => $activityTypeId,
            'durationMillis' => $duration
        ];

        if ($calories !== null) {
            $data['manualCalories'] = (int) $calories;
        }

        if ($distance !== null) {
            $data['distance'] = $distance;
        }

        if ($distanceUnit !== null) {
            $data['distanceUnit'] = $distanceUnit;
        }

        return $this->fitbit->post('activities' . ($logId ? '/'. $logId : ''), $data);
    }

    /**
     * Delete Fitbit activity https://dev.fitbit.com/docs/activity/#delete-activity-log
     * @param  string $logId
     * @return object
     */
    public function delete($logId)
    {
        return $this->fitbit->delete('activities/' . $logId);
    }

    /**
     * Get Fitbit activity list https://dev.fitbit.com/docs/activity/#get-activity-logs-list
     * @param  \Datetime|null  $before
     * @param  \Datetime|null  $after
     * @param  string  $sort
     * @param  integer $limit
     * @param  integer $offset
     * @return object
     */
    public function getList($before = null, $after = null, $sort = 'desc', $limit = 10, $offset = 0)
    {
        $data = [
            'sort' => $sort,
            'offset' => $offset,
            'limit' => $limit
        ];

        if ($after !== null) {
            $data['afterDate'] = $after->format('Y-m-d') . 'T' . $after->format('H:i:s');
        }

        if ($before !== null) {
            $data['beforeDate'] = $before->format('Y-m-d') . 'T' . $before->format('H:i:s');
        }

        return $this->fitbit->get('activities/list', $data);
    }

}
