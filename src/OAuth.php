<?php 

	define("OAUTH_SIG_METHOD_HMACSHA1",'OAUTH_SIG_METHOD_HMACSHA1');
	define("OAUTH_HTTP_METHOD_POST",'POST');
	define("OAUTH_HTTP_METHOD_GET",'GET');
	define("OAUTH_HTTP_METHOD_PUT",'PUT');
	define("OAUTH_HTTP_METHOD_DELETE",'DELETE');
	

	/**
	 * OAuth<=>PECL-OAuth Adapter Class. Loaded by PlanningCenterOnline Class when PHP PECL OAuth is not present. For use only with PlanningCenterOnline Class.
	 * @class OAuth
	 * @license apache license 2.0, code is distributed "as is", use at own risk, all rights reserved
	 * @copyright 2012 Daniel Boorn
	 * @author Daniel Boorn daniel.boorn@gmail.com
	 */
	class OAuth{
		
		private $settings;
		private $token;
		private $consumer;
		public $lastResponse;
		
		/**
		 * constructor
		 * @param string $key
		 * @param string $secret
		 * @param const $signatureMethod
		 */
		public function __construct($key,$secret,$signatureMethod=OAUTH_SIG_METHOD_HMACSHA1){
			$this->settings = (object) array(
				'key'=>$key,
				'secret'=>$secret,
			);
			$this->consumer = new OAuthConsumer($this->settings->key, $this->settings->secret, NULL);
		}
		
		/**
		 * debug output
		 * @param string $obj
		 */
		protected function debug($obj){
			//var_dump($obj);
		}
		
		/**
		 * set token
		 * @param string $token
		 * @param string $secret
		 */
		public function setToken($token,$secret){
			$this->token = (object) array(
				'key'=>$token,
				'secret'=>$secret,
			);
		}
		
		/**
		 * obtains access token from api
		 * @param string $url
		 * @return null|object
		 */
		public function getAccessToken($url){
			$params = $requestToken = null;
			//auto build request token and verifier params (if needed)
			if(isset($_GET['oauth_verifier']) && isset($_GET['oauth_token']) && isset($_SESSION['PcoRequestToken'])){
				$params = array('oauth_verifier'=>$_GET['oauth_verifier']);
				$tmp = $_SESSION['PcoRequestToken'];
				$requestToken = (object) array(
					'key'=>$tmp->oauth_token,
					'secret'=>$tmp->oauth_token_secret,
				);
			}
			$request = OAuthRequest::from_consumer_and_token($this->consumer, $requestToken, OAUTH_HTTP_METHOD_GET, $url, $params);
			$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, $requestToken);
			$headers = array(
				'Content-Type: application/json',
				$request->to_header(),
			);
			$response = $this->sendRequest(OAUTH_HTTP_METHOD_GET, $url, $headers);
			$r = $this->parseResponse($response);
			return $r;
		}
		
		/**
		 * return last repsonse from api
		 * @return string
		 */
		public function getLastResponse(){
			return $this->lastResponse;
		}
		
		/**
		 * fetchs reponse from api by GET|POST with optional params and headers
		 * @param string $url
		 * @param string|array $params
		 * @param const $method
		 * @param array $headers (key=>value)
		 * @return boolean
		 */
		public function fetch($url,$params=null,$method=OAUTH_HTTP_METHOD_GET,$headers=null){
			
			$curlHeader = array();
			foreach($headers as $key=>$value){
				$curlHeader[] = "{$key}: {$value}";
			}
			
			switch($method){
				case OAUTH_HTTP_METHOD_GET:
					$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $params);
					$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, $this->token);
					$curlHeader[] = $request->to_header();
					$response = $this->sendRequest($method, $url, $curlHeader);
						
					break;
				case OAUTH_HTTP_METHOD_POST:
				case OAUTH_HTTP_METHOD_PUT:
					$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, null);
					$request->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $this->consumer, $this->token);
					$curlHeader[] = $request->to_header();
					$response = $this->sendRequest($method, $url, $curlHeader, $params);
					break;
			}
			$this->lastResponse = $this->parseResponseJson($response);
			return true;
		}
		
		/**
		 * parse response body and returned body params or null
		 * @param string $response
		 * @return stdClass $vars
		 */
		private function parseResponse($response){
			$parts = explode("\r\n\r\n", $response);
			$vars = null;
			if(is_array($parts) && sizeof($parts)>1){
				parse_str($parts[sizeof($parts)-1],$vars);
			}
			$this->debug($response);
			$this->debug($parts);
			return $vars;
		}
		
		/**
		 * parse response body and return body json string or null
		 * @param string $response
		 * @return string $jsonStr
		 */
		private function parseResponseJson($response){
			$parts = explode("\r\n\r\n", $response);
			$this->debug($response);
			$this->debug($parts);
			$vars = null;
			if(is_array($parts) && sizeof($parts)>1){
				$jsonStr = $parts[sizeof($parts)-1];
			}
			return $jsonStr;
		}
		
		/**
		 * Send request to API
		 * @author http://gdatatips.blogspot.com/2008/11/2-legged-oauth-in-php.html
		 * @param string $http_method
		 * @param string $url
		 * @param string $auth_header
		 * @param array $postData
		 * @return string $response
		 */
		private function sendRequest($http_method, $url, $auth_header=null, $postData=null) {
			$this->debug($url);
			$this->debug($auth_header);
			$this->debug($postData);
		
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_FAILONERROR, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_HEADER, true);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $auth_header);
				
			switch($http_method) {
				case 'POST':
					curl_setopt($curl, CURLOPT_POST, 1);
					curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
					break;
				case 'PUT':
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $http_method);
					curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
					break;
				case 'DELETE':
					curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $http_method);
					break;
			}
			
			$response = curl_exec($curl);
			$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			if (!$response) {
				throw new OAuthException(curl_error($curl),$code);
			}

			if($code != 200){
				throw new OAuthException($response,$code);
			}
			
			curl_close($curl);
			return $response;
		}
		
		/**
		 * Joins key:value pairs by inner_glue and each pair together by outer_glue
		 * @author http://gdatatips.blogspot.com/2008/11/2-legged-oauth-in-php.html
		 * @param string $inner_glue The HTTP method (GET, POST, PUT, DELETE)
		 * @param string $outer_glue Full URL of the resource to access
		 * @param array $array Associative array of query parameters
		 * @return string Urlencoded string of query parameters
		 */
		private function implodeAssoc($inner_glue, $outer_glue, $array) {
			$output = array();
			foreach($array as $key => $item) {
				$output[] = $key . $inner_glue . urlencode($item);
			}
			return implode($outer_glue, $output);
		}
		
	}