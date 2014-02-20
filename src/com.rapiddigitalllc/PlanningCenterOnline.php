<?php

/**
 * Helper Class for the PlanningCenterOnline.com API.
 * @class PlanningCenterOnline
 * @license Non-Commercial Creative Commons, http://creativecommons.org/licenses/by-nc/2.0/, code is distributed "as is", use at own risk, all rights reserved
 * @copyright 2012 Daniel Boorn
 * @author Daniel Boorn daniel.boorn@gmail.com - Available for hire. Email for more info.
 * @requires PHP PECL OAuth, http://php.net/oauth
 */
class PlanningCenterOnline {

    const TOKEN_CACHE_FILE = 0;
    const TOKEN_CACHE_SESSION = 1;
    const TOKEN_CACHE_CUSTOM = 2;

    /**
     * @var object
     */
    private $settings;

    /**
     * @var
     */
    public $accessToken;

    /**
     * @var array
     */
    public $paths = array(
        'tokenCache'   => '../tokens/', //file path to local folder
        'baseUrl'      => 'https://www.planningcenteronline.com',
        'general'      => array(
            'requestToken' => '/oauth/request_token',
            'accessToken'  => '/oauth/access_token',
            'authorizeUrl' => '/oauth/authorize',
        ),
        'organization' => '/organization.json',
        'plans'        => array(
            'serviceTypes' => '/service_types/%s/plans.json',
            'plan'         => '/plans/%s.json',
        ),
        'people'       => array(
            'people' => '/people.json',
            'person' => '/people/%s.json',
        ),
    );

    /**
     * @param $settings
     */
    public function __construct($settings) {
        $this->settings = (object)$settings;
    }

    /**
     * @param $property
     * @return mixed
     */
    public function __get($property) {
        $property = 'get' . ucfirst($property);
        if (method_exists($this, $property)) {
            return call_user_func(array($this, $property));
        }
        return null;
    }

    /**
     * @param $object
     */
    public function debug($object) {
        if ($this->settings->debug) var_dump($object);
    }

    /**
     * BEGIN: API Resource Functions
     */

    public function getOrganization() {
        $url = $this->paths['baseUrl'] . $this->paths['organization'];
        return $this->fetch($url);
    }

    /**
     * @param $serviceId
     * @return mixed
     */
    public function getPlansByServiceId($serviceId) {
        $url = $this->paths['baseUrl'] . sprintf($this->paths['plans']['serviceTypes'], $serviceId);
        return $this->fetch($url);
    }

    /**
     * @param $planId
     * @return mixed
     */
    public function getPlanById($planId) {
        $url = $this->paths['baseUrl'] . sprintf($this->paths['plans']['plan'], $planId);
        return $this->fetch($url);
    }

    /**
     * @param null $criteria
     * @return null
     */
    public function getPeople($criteria = null) {
        $url = $this->paths['baseUrl'] . $this->paths['people']['people'];
        if ($criteria) {
            $url .= "?" . http_build_query($criteria);
        }
        $response = $this->fetch($url);
        return isset($response->people) ? $response->people : null;

    }

    /**
     * @param $personId
     * @return mixed
     */
    public function getPersonById($personId) {
        $url = $this->paths['baseUrl'] . sprintf($this->paths['people']['person'], $personId);
        return $this->fetch($url);
    }

    /**
     * @param $model
     * @return mixed
     */
    public function updatePerson($model) {
        $url = $this->paths['baseUrl'] . sprintf($this->paths['people']['person'], $model->id);
        $xml = $this->modelToXml($model, 'person');
        return $this->fetch($url, $xml, OAUTH_HTTP_METHOD_PUT, 'application/xml');
    }

    /**
     * @param $model
     * @return mixed
     */
    public function createPerson($model) {
        $url = $this->paths['baseUrl'] . $this->paths['people']['people'];
        $xml = $this->modelToXml($model, 'person');
        return $this->fetch($url, $xml, OAUTH_HTTP_METHOD_POST, 'application/xml');
    }

    /**
     * @param $str
     * @return string
     */
    protected function pluralToSinglar($str) {
        $list = array("ies" => "y", "es" => "", "s" => "");
        foreach ($list as $key => $value) {
            $x = substr($str, strlen($key) * -1, strlen($key));
            if ($x == $key) return substr($str, 0, strlen($str) - strlen($key)) . $value;
        }
        return $str;
    }

    /**
     * @param $node
     * @param $name
     * @return string
     */
    protected function objectToPcoXml($node, $name) {
        $xml = '';
        foreach ($node as $key => $value) {
            if (gettype($key) == "integer") $key = $name;
            //$key = str_replace("_","-",$key);//api will accept "_"
            switch (gettype($value)) {
                case "array":
                    $xml .= "<{$key} type='array'>{$this->objectToPcoXml($value, $this->pluralToSinglar($key))}</{$key}>";
                    break;
                case "object":
                    $xml .= "<{$key}>{$this->objectToPcoXml($value, $key)}</{$key}>";
                    break;
                default:
                    $xml .= "<{$key}>{$value}</{$key}>";
            }
        }
        return $xml;
    }

    /**
     * @param $node
     * @param $name
     * @return string
     */
    protected function modelToXml($node, $name) {
        return "<{$name}>{$this->objectToPcoXml($node, $name)}</{$name}>";
    }

    /**
     * BEGIN: OAuth Functions
     */

    public function setAccessToken($token) {
        $this->accessToken = (object)$token;
    }

    /**
     * @param $url
     * @param null $data
     * @param string $method
     * @param string $contentType
     * @return mixed
     * @throws Exception
     */
    public function fetch($url, $data = null, $method = OAUTH_HTTP_METHOD_GET, $contentType = 'application/json') {
        try {
            $o = new OAuth($this->settings->key, $this->settings->secret, OAUTH_SIG_METHOD_HMACSHA1);
            $o->setToken($this->accessToken->oauth_token, $this->accessToken->oauth_token_secret);
            $headers = array(
                'Content-Type' => $contentType,
            );
            if ($o->fetch($url, $data, $method, $headers)) {
                return json_decode($o->getLastResponse());
            }
        } catch (OAuthException $e) {
            $r = json_decode($e->lastResponse);
            if (isset($r->base)) {
                throw new Exception(implode("\n", $r->base));
            }
            die("Error Code: {$e->getCode()} - {$e->getMessage()}\n");
        }
    }

    /**
     * @return string
     */
    protected function getAccessTokenFileName() {
        return "{$this->paths['tokenCache']}.planningcenteronline_api.accesstoken";
    }

    /**
     * get access token from file
     * @return array|NULL
     */
    protected function getFileAccessToken() {
        $fileName = $this->getAccessTokenFileName();
        if (file_exists($fileName)) {
            return json_decode(file_get_contents($fileName));
        }
        return null;
    }

    /**
     * @return null|object
     */
    protected function getSessionAccessToken() {
        if (isset($_SESSION['PcoAccessToken'])) {
            //be sure to return object with "oauth_token" and "oauth_token_secret" properties
            return (object)$_SESSION['PcoAccessToken'];
        }
        return null;
    }

    /**
     * @param $cacheType
     * @param $custoHandlers
     * @return array|mixed|NULL|object
     */
    protected function getAccessToken($cacheType, $custoHandlers) {
        switch ($cacheType) {
            case self::TOKEN_CACHE_FILE:
                $token = $this->getFileAccessToken();
                break;
            case self::TOKEN_CACHE_SESSION:
                $token = $this->getSessionAccessToken();
                break;
            case self::TOKEN_CACHE_CUSTOM:
                $token = call_user_func($custoHandlers['getAccessToken']);
                break;
            default:
                $token = null;
        }
        if ($token && isset($token->oauth_token) && isset($token->oauth_token_secret)) return $token;
        return null;
    }

    /**
     * @param $token
     */
    protected function saveFileAccessToken($token) {
        $fileName = $this->getAccessTokenFileName();
        file_put_contents($fileName, json_encode($token));
    }

    /**
     * @param $token
     */
    protected function saveSessionAccessToken($token) {
        $_SESSION['PcoAccessToken'] = (object)$token;
    }

    /**
     * @param $token
     * @param $cacheType
     * @param $custoHandlers
     */
    protected function saveAccessToken($token, $cacheType, $custoHandlers) {
        switch ($cacheType) {
            case self::TOKEN_CACHE_FILE:
                $this->saveFileAccessToken($token);
                break;
            case self::TOKEN_CACHE_SESSION:
                $this->saveSessionAccessToken($token);
                break;
            case self::TOKEN_CACHE_CUSTOM:
                call_user_func($custoHandlers['saveAccessToken'], $token);
        }
    }


    /**
     * @param $callbackUrl
     * @return object
     */
    protected function obtainRequestToken($callbackUrl) {
        try {
            $o = new OAuth($this->settings->key, $this->settings->secret, OAUTH_SIG_METHOD_HMACSHA1);
            $callbackUrl = urlencode($callbackUrl);
            $url = "{$this->paths['baseUrl']}{$this->paths['general']['requestToken']}?oauth_callback={$callbackUrl}";
            return (object)$o->getAccessToken($url);
        } catch (OAuthException $e) {
            die("Error: {$e->getMessage()}\nCode: {$e->getCode()}\nResponse: {$e->lastResponse}\n");
        }
    }

    /**
     * @param $token
     * @param $callbackUrl
     */
    protected function redirectUserAuthorization($token, $callbackUrl) {
        try {
            $_SESSION['PcoRequestToken'] = $token;
            $o = new OAuth($this->settings->key, $this->settings->secret, OAUTH_SIG_METHOD_HMACSHA1);
            $o->setToken($token->oauth_token, $this->oauth_token_secret);
            $callbackUrl = urlencode($callbackUrl);
            $url = "{$this->paths['baseUrl']}{$this->paths['general']['authorizeUrl']}?oauth_token={$token->oauth_token}&oauth_callback={$callbackUrl}";
            @header("Location:{$url}");
            die("<script>window.location='{$url}'</script><meta http-equiv='refresh' content='0;URL=\"{$url}\"'>"); //backup redirect
        } catch (OAuthException $e) {
            die("Error: {$e->getMessage()}\nCode: {$e->getCode()}\nResponse: {$e->lastResponse}\n");
        }
    }

    /**
     * @return object
     * @throws Exception
     */
    protected function obtainUserAuthorationAccessToken() {
        $requestToken = $_SESSION['PcoRequestToken'];

        if ($requestToken->oauth_token != $_GET['oauth_token']) {
            throw new Exception('Returned OAuth Token Does Not Match Request Token');
        }

        try {
            $url = "{$this->paths['baseUrl']}{$this->paths['general']['accessToken']}";
            $o = new OAuth($this->settings->key, $this->settings->secret, OAUTH_SIG_METHOD_HMACSHA1);
            $o->setToken($requestToken->oauth_token, $requestToken->oauth_token_secret);
            return (object)$o->getAccessToken($url);
        } catch (OAuthException $e) {
            die("Error: {$e->getMessage()}\nCode: {$e->getCode()}\nResponse: {$e->lastResponse}\n");
        }
    }

    /**
     * @param $url
     * @param null $data
     * @param string $method
     * @param string $contentType
     * @return array
     * @throws Exception
     */
    public function getAttachment($url, $data = null, $method = OAUTH_HTTP_METHOD_GET, $contentType = 'application/json') {
        try {
            $o = new OAuth($this->settings->key, $this->settings->secret, OAUTH_SIG_METHOD_HMACSHA1);
            $o->setToken($this->accessToken->oauth_token, $this->accessToken->oauth_token_secret);
            $o->disableRedirects();
            $headers = array(
                'Content-Type' => $contentType,
            );
            $r = $o->fetch($url, $data, $method, $headers);
            var_dump($r);
            $rspinfo = $o->getLastResponseInfo();
            return $rspinfo;
        } catch (OAuthException $e) {
            $r = json_decode($e->lastResponse);
            if (isset($r->base)) {
                throw new Exception(implode("\n", $r->base));
            }
            die("Error Code: {$e->getCode()} - {$e->getMessage()}\n");
        }

    }

    /**
     * @param $callbackUrl
     * @param int $cacheType
     * @param null $custoHandlers
     * @return bool
     */
    public function login($callbackUrl, $cacheType = self::TOKEN_CACHE_SESSION, $custoHandlers = null) {

        //fetch cached token (if any)
        $token = $this->getAccessToken($cacheType, $custoHandlers);

        if ($token) {
            $this->accessToken = $token;
            return true;
        }

        //else handle callback (if any)
        if (isset($_GET['oauth_token'])) {
            $token = $this->obtainUserAuthorationAccessToken();
            $this->saveAccessToken($token, $cacheType, $custoHandlers);
            $this->accessToken = $token;
            return true;
        } else { //else start user authorization
            $token = $this->obtainRequestToken($callbackUrl);
            $this->redirectUserAuthorization($token, $callbackUrl);
        }

    }

}

	