<?php
namespace Demo;

use Demo\Fitbit\Water;
use Demo\Fitbit\Activity;
use Demo\Fitbit\Profile;
use Demo\Fitbit\Body;
use Demo\Fitbit\Heart;
use Demo\Fitbit\Sleep;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class FitBit
{

    private $baseAPIUrl = 'https://api.fitbit.com/';
    private $oauthUrl = 'https://www.fitbit.com/oauth2/';
    private $baseAPIVersion = '1';
    private $APIFormat = '.json';

    private $clientId;
    private $secret;

    private $token;

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
        $this->heart = new Heart($this);
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
     * @param  bool $ssl
     * @return object
     */
    private function tokenRequest($parameters, $ssl = false)
    {
        $url = $this->baseAPIUrl . 'oauth2/token?' . http_build_query($parameters);
        $defaults = [
            'headers' => [
                'content-type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic '. base64_encode($this->clientId . ':' . $this->secret)
                ]
        ];
        return $this->send($url, 'POST', [], $defaults, $ssl);
    }

    /**
     * Refresh old access token
     * @param  object $token
     * @param  bool   $ssl
     * @return object
     */
    public function refreshToken($token, $ssl = false)
    {
        $parameters = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->refresh_token
        ];

        return $this->tokenRequest($parameters, $ssl);
    }

    /**
     * Get new access token
     * @param  string $code
     * @param  string $redirect_uri
     * @param  bool   $ssl
     * @return object
     */
    public function getToken($code, $redirect_uri, $ssl = false)
    {
        $parameters = [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'redirect_uri' => $redirect_uri
        ];

        return $this->tokenRequest($parameters, $ssl);
    }

    /**
     * Use Fitbit auth token
     * @param object $token
     */
    public function setToken($token)
    {
        $this->token = $token;
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
     * @param  bool   $ssl
     * @return object               fitbit response
     */
    public function post($endpoint, $data, $ssl = false)
    {
        $url = 'user/' . $this->user . '/' . $endpoint . $this->APIFormat;
        return $this->sentToRestAPI($url, 'POST', $data, $ssl);
    }

    /**
     * Send simple get request
     * @param  string  $endpoint Fitbit endpoint
     * @param  array   $data     data to send as GET parameter
     * @param  boolean $withUser send with info about user
     * @param  bool   $ssl
     * @return object            fitbit response
     */
    public function get($endpoint, $data = [], $withUser = true, $ssl = false)
    {
        $userPrefix = ($withUser ? 'user/'. $this->user .'/' : '');
        $url = $userPrefix . $endpoint . $this->APIFormat . '?' . http_build_query($data);
        return $this->sentToRestAPI($url, 'GET', [], $ssl);
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
     * @param  bool   $ssl
     * @return object            fitbit response
     */
    public function delete($endpoint, $ssl = false)
    {
        $url = 'user/' . $this->user . '/' . $endpoint . $this->APIFormat;
        return $this->sentToRestAPI($url, 'DELETE', [], $ssl);
    }

    /**
     * Sent request to Fitbit REST API
     * @param  [string] $url    endpoint
     * @param  [string] $method http method
     * @param  array  $data     sent data
     * @param  bool $ssl
     * @return object         response from Fitbit
     */
    private function sentToRestAPI($url, $method, $data = [], $ssl = false)
    {
        $url = $this->baseAPIUrl . $this->baseAPIVersion . '/' . $url;
        $defaults = [
            'headers'  => [
                'Authorization' => 'Bearer ' . $this->token->access_token
            ]
        ];
        return $this->send($url, $method, $data, $defaults, $ssl);
    }

    /**
     * Do an API request
     * @param  string $url          where to send
     * @param  string $method       method of request
     * @param  array  $data         data to send in body
     * @param  array  $defaults     http client set
     * @param  bool   $ssl
     * @return object               fitbit response
     */
    private function send($url, $method, $data = [], $defaults = [], $ssl = false)
    {
        $client = new Client([
            'base_url' => $url,
            'defaults' => $defaults
        ]);

        if (!$ssl) {
            $client->setDefaultOption('verify', false);
        }

        $method = strtolower($method);

        $settings = [];

        if ($method == 'post') {
            $settings['body'] = $data;
        }

        try {
            $request = $client
                ->$method($url, $settings);
        } catch (ClientException $e) {
            throw new FitbitAPIException($e->getMessage(), $e->getRequest(), $e->getResponse(), $e->getPrevious());
        }

        return json_decode($request->getBody()->getContents());
    }
}
