<?php
namespace Fabulator;

use Fabulator\Fitbit\Water;
use Fabulator\Fitbit\Activity;
use Fabulator\Fitbit\Profile;
use Fabulator\Fitbit\Body;
use Fabulator\Fitbit\Hearth;
use Fabulator\Fitbit\Sleep;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

use \Exception;

class FitBit
{

    private $baseAPIUrl = 'https://api.fitbit.com/';
    private $baseAPIVersion = '1';
    private $oauthUrl = 'https://www.fitbit.com/oauth2/';

    private $http;
    private $clientId;
    private $secret;

    public $water;
    public $activity;
    public $profile;
    public $body;
    public $hearth;
    public $sleep;

    private $user = '-';

    public function __construct($clientId, $secret)
    {
        $this->clientId = $clientId;
        $this->secret = $secret;
        $this->water = new Water($this);
        $this->activity = new Activity($this);
        $this->profile = new Profile($this);
        $this->body = new Body($this);
        $this->hearth = new Hearth($this);
        $this->sleep = new Sleep($this);
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
            throw new Exception("Token request failed: " . $errors->errors[0]->message);
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
    public function post($endpoint, $data)
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
    public function get($endpoint, $data = [], $withUser = true)
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
    public function info($endpoint, $data)
    {
        return $this->get($endpoint, $data, false);
    }

    /**
     * Send delete request
     * @param  string  $endpoint Fitbit endpoint
     * @return object            fitbit response
     */
    public function delete($endpoint)
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
            throw new Exception("API call failed: " . $errors->errors[0]->message);
        }

        return json_decode($request->getBody()->getContents());
    }

}
