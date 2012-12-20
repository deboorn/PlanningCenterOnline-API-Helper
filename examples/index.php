<?php


	/*
	 * PlanningCenterOnline Helper Class Examples.
	 * @license apache license 2.0, code is distributed "as is", use at own risk, all rights reserved
	 * @copyright 2012 Daniel Boorn
	 * @author Daniel Boorn daniel.boorn@gmail.com - Available for hire. Email for more info.
	 * @requires PHP PECL OAuth, http://php.net/oauth
	 */

	ini_set('display_errors','1');

	session_start();
	
	//hb2E8avZ9C
	
	//session_destroy();
	//session_start();
	//var_dump($_SESSION);
	//die('clear session for debug');

	
	require('../src/PlanningCenterOnline.php');
	
	
	//contact PCO via email to request consumer key/secret for API
	$settings = array(
		'key'=>'you key here',
		'secret'=>'your secret here',
		'debug'=>false,
	);
	
	echo "<pre>";//view formatted debug output
	
	$pco = new PlanningCenterOnline($settings);
	
	/**
	 * BEGIN: Login Examples
	 */

	$callbackUrl = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['SCRIPT_NAME']}";//e.g. url to this page on return from auth
	
	#Login Example 1 -- login using session saving of access token
	
	//$r = $pco->login($callbackUrl);	
	//$r = $pco->login($callbackUrl,PlanningCenterOnline::TOKEN_CACHE_SESSION);//produces same result
	$r = $pco->login($callbackUrl,PlanningCenterOnline::TOKEN_CACHE_FILE);//saves access token to file
	
	
	#Login Example 2 -- login to PCO and use custom handlers to get/save access token. e.g. alternative to extending class for database caching
	/**
	* Custom Get Access Token Handler
	* @param string $username
	*/
	/*
	function handleGetAccessToken(){
		var_dump('handle get access token');
		//e.g. to get token from database by username
		//must return object with properties "oauth_token" and "oauth_token_secret" OR null for no cache
		//return null;
		return (object) array(
			'oauth_token'=>'oauth token here',
			'oauth_token_secret'=>'oauth token secret here',
		);
	}
	*/
	
	/**
	* Custom Save Access Token Handler
	* @param string $username
	* @param object $token
	*/
	
	/*
	function handleSaveAccessToken($token){
		var_dump($token,'handle save access token');
		//save access token here
		//e.g. to save to database
	}
	
	//login with custom access token get/save handlers
	$r = $pco->login($callbackUrl,PlanningCenterOnline::TOKEN_CACHE_CUSTOM,array(
		'getAccessToken'=>'handleGetAccessToken',
		'saveAccessToken'=>'handleSaveAccessToken',
	));
	*/
	
	if(!$r){
		die("Login Failed");
	}
	
	/**
	 * BEGIN: query examples
	 */
	
	#Example 1 - Organization
	$o = $pco->organization;
	//var_dump($o);
	echo "Organization: {$o->id} - {$o->name} - {$o->owner_name}\n";
	$serviceId = null;//used for example 2
	foreach($o->service_types as $key=>$service){
		echo "Service: {$service->id} - {$service->name}\n";
		if(!$serviceId) $serviceId = $service->id;//grab first service id for use in examples below
	}
	
	
	#Example 2 - Plans
	
	//get all plans by service id
	$plans = $pco->getPlansByServiceId($serviceId);
	//var_dump($plans);
	$planId = null;//used for example
	echo "Total Plans Found: " . sizeof($plans) . "\n";
	foreach($plans as $plan){
		echo "{$plan->id}\n";
		if(!$planId) $planId = $plan->id;//used in example below
	}
	
	
	//get plan by id
	$plan = $pco->getPlanById($planId);
	//var_dump($plan);
	echo "Plan ID: {$plan->id}\n";

	
	#Example 3 - People
	
	//get all people
	/*
	$people = $pco->people;
	var_dump($people);
	$total = sizeof($people);
	echo "Total People in API: {$total}\n";
	*/
	
	//search people
	/*
	$people = $pco->getPeople(array(
		'name'=>"Boorn",
		//'people_ids'=>'34,12,51',
		//'since'=>'2008051208300',//yyyymmddhhmmss in UTC
		//'show_disabled_accounts'=>'true',
		//'show_all_accounts'=>'true',
	));
	//var_dump($people);
	echo "Total People Found in Search: " . sizeof($people) . "\n";
	foreach($people as $person){
		echo "{$person->name}\n";
	}
	
	
	//edit person
	//obtain record by person id
	$model = $pco->getPersonById($people[0]->id);	
	//var_dump($model);
	//let's edit the first name and street of the first address	
	$model->first_name = "Daniel";
	$model->contact_data->addresses[0]->street = "123 Jump St";
	//save updates
	$model = $pco->updatePerson($model);
	*/
	
	//create person
	
	//example 1 -- create person object with name, address supplied
	$model = (object) array(
		'first_name'=>"John",
		'last_name'=>"Doe",
		'contact_data'=> (object) array(
			'addresses'=> array(
				(object) array('city'=>'La Quinta','state'=>'CA','street'=>'78-100 Main Street','zip'=>"92253","location"=>"Work"),
				//(object) array('city'=>'La Quinta','state'=>'CA','street'=>'21st Jump Street','zip'=>"92253","location"=>"Home"),
			),
			'email_addresses'=> array(
				(object) array('address'=>'mrjohnsmith@company.com','location'=>'Work'),
				//(object) array('address'=>'johnny.smith@example.com','location'=>'Home'),
			)
		),
	);
	//save model and return full record
	$model = $pco->createPerson($model);
	//var_dump($model);
	echo "Created {$model->name}, ID:{$model->id}\n";
	
	
	//still todo, implement remaining api resources, however this should be a enough to jump start your application
	
	
	
	
	
	
	