<?php


	/*
	 * PlanningCenterOnline Helper Class Examples.
	 * @license Non-Commercial Creative Commons, http://creativecommons.org/licenses/by-nc/2.0/, code is distributed "as is", use at own risk, all rights reserved
	 * @copyright 2012 Daniel Boorn
	 * @author Daniel Boorn daniel.boorn@gmail.com - Available for hire. Email for more info.
	 * @requiresPHP PECL OAuth, http://php.net/oauth
	 */

	ini_set('display_errors','1');

	session_start();
	
	
	//session_destroy();
	//session_start();
	//var_dump($_SESSION);
	//die('clear session for debug');

	$download_dir = '/Users/Shared';
	require('../src/com.rapiddigitalllc/PlanningCenterOnline.php');
	
	
	//contact PCO via email to request consumer key/secret for API
	$settings = array(
		'key'=>'btOLMrXDUoM8Q6Fq0LjL',
		'secret'=>'nLCWcH8zYy2klWscDlOpA5uytZom2Rcui9ZbjlS9',
		'debug'=>true,
	);
	
	echo "<pre>";//view formatted debug output
	
	$pco = new PlanningCenterOnline($settings);
	
	/**
	 * BEGIN: Login Examples
	 */

	$callbackUrl = "http://{$_SERVER['HTTP_HOST']}{$_SERVER['SCRIPT_NAME']}"; //e.g. url to this page on return from auth
	#Login Example 1 -- login using session saving of access token
	
	//!!Please remember to set your consumer key/secret above!!
	
	//$r = $pco->login($callbackUrl);	
	//$r = $pco->login($callbackUrl,PlanningCenterOnline::TOKEN_CACHE_SESSION);//produces same result
	$r = $pco->login($callbackUrl,PlanningCenterOnline::TOKEN_CACHE_FILE);//saves access token to file
	
	
	#Login Example 2 -- login to PCO and use custom handlers to get/save access token. e.g. alternative to extending class for database caching
	/**
	* Custom Get Access Token Handler
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
        
//        //print out organization stuff as a string...
//        $j = json_encode($pco->fetch('https://www.planningcenteronline.com/organization.json'));
//        echo "{$j}\n";
        
//	echo "Organization: {$o->id} - {$o->name} - {$o->owner_name}\n";
//	$serviceId = null;//used for example 2
//	foreach($o->service_types as $key=>$service){
//                echo "Service: {$service->id} - {$service->name} - {$service->type}\n";
//		if(!$serviceId) $serviceId = $service->id;//grab first service id for use in examples below
//	}
//        foreach($o->service_type_folders as $key=>$service_folder_types){
//            echo "Service Folder: {$service_folder_types->id} - {$service_folder_types->name} - \n";
//            foreach($service_folder_types->service_types as $key=>$service){
//                echo "     Service: {$service->id} - {$service->name} - {$service->type}\n";
//            //    //if(!$serviceId) $serviceId = $service->id;//grab first service id for use in examples below
//            }
//	}

        //Find the most recent Service for DC
        $DC_FOLDER=0;
        $dcservice = $o->service_type_folders[$DC_FOLDER]->service_types[0];
        echo "DC Service: {$dcservice->id}\n";          

//        $j = json_encode($dcservice);
//        echo "    DC Service: {$j}\n";

        
////        //Find the most recent Service for KT
//        $KT_FOLDER=1;
//        $ktservice = $o->service_type_folders[$KT_FOLDER]->service_types[0];
//        echo "KT Service: {$ktservice->id}\n";          
        
	#Example 2 - Plans for DC
	
//	//get all plans by service id
	$plans = $pco->getPlansByServiceId($dcservice->id);
//	//var_dump($plans);
//	$planId = null;//used for example
//	echo "Total Plans Found: " . sizeof($plans) . "\n";
//	foreach($plans as $plan){
//		echo "{$plan->id} - {$plan->dates}\n";
//		if(!$planId) $planId = $plan->id;//used in example below
//	}
        
//        //Get the DC Upcoming Plan...
//        $j = json_encode($plans[0]);
//        echo "    Service: {$j}\n";
//        
//        $j = json_encode($plans[0]->id);
//        echo "     First Item: {$j}\n";
        
        //Item 0 is the most recent plan...
        echo "Fetching DC Plan: {$plans[0]->id} - {$plans[0]->dates}\n";
        $plan = $pco->getPlanById($plans[0]->id);

        $j = json_encode($plan);
        echo "{$j}\n";
        
        //Iterate through all the items...
        $save_attachments = null;
        $n = 0;
        foreach($plan->items as $item){
            echo "  Item: $item->title\n";
            //start saving attachments after the 'Service' title.
            if ($item->title == "PW Set") {
                $save_attachments = TRUE;
            }
            
            
            if ($save_attachments == TRUE) {
                //iterate through all attachments to find the media files...for now just images.
                $len = count($item->attachments);
                //echo "      Saving: at least $len attachments.\n";
                foreach($item->attachments as $attachment){
                    //may add other types someday...
                    if (strpos($attachment->content_type,"image") !== FALSE) {
                        $n = $n + 1;
                        echo "      Saving: $attachment->filename ($attachment->content_type)\n";
                        //write to file...
                        //Other things I should check for: 
                        //$attachment->downloadable = true, 
                        $new_file_name = "$download_dir/test{$n}.jpg";
                        $url = $attachment->url;
                        echo "      Saving from URL: $url\n";
                        //var_dump($item->attachments[0]);
                        
                        $r = $pco->getAttachment($url,NULL,OAUTH_HTTP_METHOD_GET,$attachment->content_type);
                        //$r = $pco->fetch($attachment->url);
                        copy($r['redirect_url'],$new_file_name);                        //$temp_file_contents = collect_file($url);
                        //echo var_dump($temp_file_contents);
                        
                        
//                        write_to_file($temp_file_contents,$new_file_name);
                    }      
                }
            }      
        }

    
//        

      
                
//	//get plan by id
//	$plan = $pco->getPlanById($planId);
//	//var_dump($plan);
//	echo "Plan ID: {$plan->id}\n";

	
	#Example 3 - People
	
	//get all people
	/*
	$people = $pco->people;
	var_dump($people);
	$total = sizeof($people);
	echo "Total People in API: {$total}\n";
	*/
	
	//search people
	
	/*$people = $pco->getPeople(array(
		'name'=>"David",
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
	/*
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
	*/
	
	//still todo, implement remaining api resources, however this should be enough to jump start your application
        