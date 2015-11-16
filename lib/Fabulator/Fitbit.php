<?php
namespace Fabulator;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

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
     * Send request to Fitbit API
     * @param  string $endpoint
     * @param  array $data
     * @param  string $method
     * @return object
     */
    private function sendRequest($endpoint, $data, $method)
    {
        if (!$this->http) {
            throw new Exception("You did not set access token.");
        }

        $method = strtolower($method);

        try {
            $request = $this
                ->http
                ->$method(
                    'user/'. $this->user .'/' . $endpoint . '.json' . (($method == 'get') ? ('?' . http_build_query($data)) : ''),
                    ['body' => $data]
                );
        } catch (ClientException $e) {
            $errors = json_decode($e->getResponse()->getBody()->getContents());
            throw new \Exception("API call failed: " . $errors->errors[0]->message);
        }

        return json_decode($request->getBody()->getContents());
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
        $parameters = [
            'date' => $date->format('Y-m-d'),
            'startTime' => $date->format('H:i'),
            'activityId' => $activityTypeId,
            'durationMillis' => $duration
        ];
        if (isset($calories)) {
            $parameters['manualCalories'] = (int) $calories;
        }
        if (isset($distance)) {
            $parameters['distance'] = $distance;
        }
        if (isset($distanceUnit)) {
            $parameters['distanceUnit'] = $distanceUnit;
        }

        return $this->sendRequest("activities" . ($logId ? "/". $logId : ''), $parameters, 'POST');
    }

    /**
     * Delete Fitbit activity https://dev.fitbit.com/docs/activity/#delete-activity-log
     * @param  string $logId
     * @return object
     */
    public function deleteActivity($logId)
    {
        return $this->sendRequest("activities/" . $logId, [], 'DELETE');
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
        $parameters = [
            'sort' => $sort,
            'offset' => $offset,
            'limit' => $limit
        ];

        if ($after) {
            $parameters['afterDate'] = $after->format('Y-m-d') . 'T' . $after->format('H:i:s');
        }

        if ($before) {
            $parameters['beforeDate'] = $before->format('Y-m-d') . 'T' . $before->format('H:i:s');
        }

        if (($after && $before) || (!$after && !$before)) {
            throw new Exception("You have to specify only after date or only before date");
        }

        return $this->sendRequest("activities/list", $parameters, 'GET');
    }


    /**
     * Get water log https://dev.fitbit.com/docs/food-logging/#get-water-logs
     * @param  Datetime $date   date of log
     * @return object
     */
    public function getWaterLog($date)
    {

        $parameters = [
            'date' => $date->format('Y-m-d')
        ];

        return $this->sendRequest("foods/log/water/date", $parameters, 'GET');
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

        $parameters = [
            'date' => $date->format('Y-m-d'),
            'amount' => $amount,
            'unit' => $unit
        ];

        if (!in_array($unit, $units)) {
            throw new Exception("Invalid unit. Only ml fl oz and cup are allowed");
        }

        return $this->sendRequest("foods/log/water", $parameters, 'POST');
    }

    /**
     * Delete water log https://dev.fitbit.com/docs/food-logging/#delete-water-log
     * @param  int $id   id of water log
     * @return object
     */
    public function deleteWaterLog($id)
    {
        return $this->sendRequest("foods/log/water/" . $id, [], 'DELETE');
    }

    /**
     * Delete all water logs from one day
     * @param  Datetime     $date the day
     */
    public function deleteWaterLogForDay($date)
    {
        $logs = $this->getWaterLog($date);
        if ($logs->water) {
            foreach ($logs->water as $log) {
                $this->deleteWaterLog($log->logId);
            }
        }
    }

    /**
     * Get water goal https://dev.fitbit.com/docs/food-logging/#get-water-goal
     * @param  float $goal   water daily goal
     * @return object
     */
    public function getWaterGoal()
    {
        return $this->sendRequest("foods/log/water/goal", [], 'GET');
    }

    /**
     * Set water goal https://dev.fitbit.com/docs/food-logging/#update-water-goal
     * @param  float $goal   water daily goal
     * @return object
     */
    public function setWaterGoal($goal)
    {
        return $this->sendRequest("foods/log/water/goal", ['target' => $goal], 'POST');
    }

    public function getProfile()
    {
        return $this->sendRequest("profile", [], 'GET');
    }
}
