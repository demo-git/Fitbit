<?php
namespace Fabulator;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use \Datetime;

class FitBit
{

    private $baseAPIUrl = 'https://api.fitbit.com/';
    private $baseAPIVersion = '1';
    private $oauthUrl = 'https://www.fitbit.com/oauth2/';

    private $http;
    private $clientId;
    private $secret;

    private $user = '-';

    public function __construct($clientId, $secret)
    {
        $this->clientId = $clientId;
        $this->secret = $secret;
    }

    /**
     * Get login url for Fitbit
     * @param  string $redirectUri Callback url, have to be in app settings
     * @param  array $scope        (activity,nutrition,heartrate,location,nutrition,profile,settings,sleep,social,weight)
     * @param  string $responseType code|token
     * @param  int $expiresIn    86400|604800|2592000
     * @param  string $prompt       none|login|consent
     * @param  string $state
     * @return string
     */
    public function getLoginUrl($redirectUri, $scope, $responseType = 'code', $expiresIn = null, $prompt = 'none', $state = null)
    {

        if ($responseType == 'code' && $expiresIn != null) {
            throw new Exception("You can use expires in parameter only if response_type is token");
        }

        $parameters = [
            'response_type' => $responseType,
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'scope' => join(' ', $scope),
            'prompt' => $prompt
        ];
        if ($expiresIn != null) {
            $parameters['expires_in'] = $expiresIn;
        }
        if ($state != null) {
            $parameters['state'] = $state;
        }
        return $this->oauthUrl . 'authorize' . '?' . http_build_query($parameters);
    }

    /**
     * Request access token from fitbit
     * @param  array $parameters
     * @return object
     */
    private function tokenRequest($parameters)
    {
        $client = new Client();
        try {
            $request = $client->post(
                $this->baseAPIUrl . 'oauth2/token?' . http_build_query($parameters),
                [
                'headers' => [
                    'content-type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic '. base64_encode($this->clientId . ':' . $this->secret)
                    ]
                ]
            );
        } catch (ClientException $e) {
            $errors = json_decode($e->getResponse()->getBody()->getContents());
            throw new \Exception("Token request failed: " . $errors->errors[0]->message);
        }
        return json_decode($request->getBody()->getContents());
    }

    /**
     * Refresh old access token
     * @param  object $token
     * @return object
     */
    public function refreshToken($token)
    {
        $parameters = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->refresh_token
        ];

        return $this->tokenRequest($parameters);
    }

    /**
     * Get new access token
     * @param  string $code
     * @return object
     */
    public function getToken($code, $redirect_uri)
    {
        $parameters = [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirect_uri
        ];

        return $this->tokenRequest($parameters);
    }

    /**
     * Use Fitbit auth token
     * @param object $token
     */
    public function setToken($token)
    {
        $this->http = new Client(
            [
            'base_url' => $this->baseAPIUrl . $this->baseAPIVersion .'/',
            'defaults' => [
                'headers'  => [
                        'Authorization' => 'Bearer ' . $token->access_token
                    ]
                ]
            ]
        );
    }

    /**
     * Set Fitbit user, default is '-'
     * @param string $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Send simple post
     * @param  string $endpoint     Fitbit endpoint
     * @param  array $data          data to send as POST
     * @return object               fitbit response
     */
    public function sendPost($endpoint, $data)
    {
        $url = 'user/'. $this->user .'/' . $endpoint . '.json';
        return $this->send($url, 'POST', $data);
    }

    /**
     * Send simple get request
     * @param  string  $endpoint Fitbit endpoint
     * @param  array   $data     data to send as GET parameter
     * @param  boolean $withUser send with info about user
     * @return object            fitbit response
     */
    public function sendGet($endpoint, $data = [], $withUser = true)
    {
        $url = ($withUser ? 'user/'. $this->user .'/' : '') . $endpoint . '.json' . '?' . http_build_query($data);
        return $this->send($url, 'GET');
    }

    /**
     * Send get without info about user
     * @param  string  $endpoint Fitbit endpoint
     * @param  array   $data     data to send as GET parameter
     * @return object            fitbit response
     */
    public function sendSimpleGet($endpoint, $data)
    {
        return $this->sendGet($endpoint, $data, false);
    }

    /**
     * Send delete request
     * @param  string  $endpoint Fitbit endpoint
     * @return object            fitbit response
     */
    public function sendDelete($endpoint)
    {
        $url = 'user/'. $this->user .'/' . $endpoint . '.json';
        return $this->send($url, 'DELETE');
    }

    /**
     * Do an API request
     * @param  string $url    where to send
     * @param  string $method method of request
     * @param  array  $data   data to send in body
     * @return object         fitbit response
     */
    public function send($url, $method, $data = [])
    {
        if (!$this->http) {
            throw new Exception("You did not set access token.");
        }

        $method = strtolower($method);

        $settings = [];

        if ($method == 'post') {
            $settings['body'] = $data;
        }

        try {
            $request = $this->http->$method($url, $settings);
        } catch (ClientException $e) {
            $errors = json_decode($e->getResponse()->getBody()->getContents());
            throw new \Exception("API call failed: " . $errors->errors[0]->message);
        }

        return json_decode($request->getBody()->getContents());
    }

    /**
     * Add new activity https://dev.fitbit.com/docs/activity/#log-activity
     * @param  DateTime $date
     * @param  string $activityTypeId
     * @param  int $duration duration in sec
     * @param  float $distance
     * @param  int $calories
     * @return object
     */
    public function addActivity($date, $activityTypeId, $duration, $distance = null, $calories = null, $distanceUnit = null, $logId = null)
    {
        $data = [
            'date' => $date->format('Y-m-d'),
            'startTime' => $date->format('H:i'),
            'activityId' => $activityTypeId,
            'durationMillis' => $duration
        ];
        if (isset($calories)) {
            $data['manualCalories'] = (int) $calories;
        }
        if (isset($distance)) {
            $data['distance'] = $distance;
        }
        if (isset($distanceUnit)) {
            $data['distanceUnit'] = $distanceUnit;
        }

        return $this->sendPost('activities' . ($logId ? '/'. $logId : ''), $data);
    }

    /**
     * Edit existing activity https://dev.fitbit.com/docs/activity/#log-activity
     * @param  string $logId ID of edited actitvity
     * @param  DateTime $date
     * @param  string $activityTypeId
     * @param  int $duration duration in sec
     * @param  float $distance distance in km
     * @param  int $calories
     * @return object
     */
    public function editActivity($logId, $date, $activityTypeId, $duration, $distance = null, $calories = null, $distanceUnit = null)
    {
        return $this->addActivity($date, $activityTypeId, $duration, $distance, $calories, $distanceUnit, $logId);
    }

    /**
     * Delete Fitbit activity https://dev.fitbit.com/docs/activity/#delete-activity-log
     * @param  string $logId
     * @return object
     */
    public function deleteActivity($logId)
    {
        return $this->sendDelete("activities/" . $logId);
    }

    /**
     * Get Fitbit activity list https://dev.fitbit.com/docs/activity/#get-activity-logs-list
     * @param  Datetime|null  $before
     * @param  Datetime|null  $after
     * @param  string  $sort
     * @param  integer $limit
     * @param  integer $offset
     * @return object
     */
    public function getActivityList($before = null, $after = null, $sort = 'desc', $limit = 10, $offset = 0)
    {
        $data = [
            'sort' => $sort,
            'offset' => $offset,
            'limit' => $limit
        ];

        if ($after) {
            $data['afterDate'] = $after->format('Y-m-d') . 'T' . $after->format('H:i:s');
        }

        if ($before) {
            $data['beforeDate'] = $before->format('Y-m-d') . 'T' . $before->format('H:i:s');
        }

        if (($after && $before) || (!$after && !$before)) {
            throw new Exception('You have to specify only after date or only before date');
        }

        return $this->sendGet('activities/list', $data);
    }

    /**
     * List all activites in Fitbit https://dev.fitbit.com/docs/activity/#browse-activity-types
     * @return object List of activities
     */
    public function browseActivity()
    {
        return $this->sendSimpleGet('activities');
    }

    /**
     * WATER LOGING
     */

    /**
     * Get water log https://dev.fitbit.com/docs/food-logging/#get-water-logs
     * @param  Datetime $date   date of log
     * @return object
     */
    public function getWaterLog(Datetime $date)
    {
        return $this->sendGet("foods/log/water/date", ['date' => $date->format('Y-m-d')]);
    }

    /**
     * Add new water log https://dev.fitbit.com/docs/food-logging/#log-water
     * @param  Datetime $date   date of log
     * @param  int $amount      amount of water
     * @param  string $unit     unit
     * @return object
     */
    public function logWater($date, $amount, $unit = 'ml')
    {

        $units = ['ml', 'fl oz', 'cup'];

        $data = [
            'date' => $date->format('Y-m-d'),
            'amount' => $amount,
            'unit' => $unit
        ];

        if (!in_array($unit, $units)) {
            throw new Exception("Invalid unit. Only ml fl oz and cup are allowed");
        }

        return $this->sendPost("foods/log/water", $data);
    }

    /**
     * Delete water log https://dev.fitbit.com/docs/food-logging/#delete-water-log
     * @param  int $id   id of water log
     * @return object
     */
    public function deleteWaterLog($id)
    {
        return $this->sendDelete("foods/log/water/" . $id);
    }

    /**
     * Get water goal https://dev.fitbit.com/docs/food-logging/#get-water-goal
     * @param  float $goal   water daily goal
     * @return object
     */
    public function getWaterGoal()
    {
        return $this->sendGet("foods/log/water/goal");
    }

    /**
     * Set water goal https://dev.fitbit.com/docs/food-logging/#update-water-goal
     * @param  float $goal   water daily goal
     * @return object
     */
    public function setWaterGoal($goal)
    {
        return $this->sendPost("foods/log/water/goal", ['target' => $goal]);
    }

    /**
     * Delete all water logs from one day
     * @param  Datetime     $date the day
     */
    public function deleteWaterLogForDay(Datetime $date)
    {
        $logs = $this->getWaterLog($date);
        if ($logs->water) {
            foreach ($logs->water as $log) {
                $this->deleteWaterLog($log->logId);
            }
        }
    }

    /**
     * HEARTH RATE TRACKING
     */

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
        $data = [
            'detail-level' => '1' . $detailLevel,
            'start-time' => $startTime,
            'end-time' => $endTime
        ];

        return $this->sendGet('activities/heart/date/' . $date->format('Y-m-d') . '/1d', $data);
    }

    public function getProfile()
    {
        return $this->sendGet('profile');
    }
}
