<?php

/**
 * TwitPic to Posterous Import Script
 *
 * At present Posterous' method of importing in TwitPic images involves the use
 * of an Adobe Air app... ignoring the fact that it doesn't work it's the best
 * use of a shit technology, should have used Silverlight.
 *
 */
 
 

/* SET INITIAL VARS */

// various credentials
$twitpic_username		=	'';
$posterous_email		=	'';
$posterous_password		=	'';

// get posterous api token
$posterous_api_token	=	get_posterous_api_key($posterous_email,$posterous_password);



/* FETCH TWITPIC IMAGES */

// twitpic api url
$twitpic_api_url="http://api.twitpic.com/2/users/show.json?username=%s&page=%s";

$page=0;
$image_count=0;
$last_page=false;

// loop while we're not at last page +1
while($last_page==false) {

	$page++;
	
	$url=sprintf($twitpic_api_url,$twitpic_username,$page);
	
	// get the user info (no auth needed)... surpress errors
	$response=@file_get_contents($url);
	
	// if response is false we must have fallen off of the page list
	if($response===false) {
		$last_page=true;
		break;
	}

	// decode the json response
	$response=json_decode($response);
	
	// loop through the images...
	foreach($response->images as $image) {
		
		// ...posting them to posterous
		set_posterous_post($posterous_api_token,$image->message,get_twitpic_url($image->short_id),$image->timestamp);

	}

}


/* FUNCTIONS */	
	
function dbug_r($arr) { echo("<pre>".print_r($arr,true)."</pre>"); }

function get_posterous_api_key($username,$password) {

	$api_url="http://posterous.com/api/2/auth/token";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $api_url);
	curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);

	$response	=	curl_exec($ch);
	$http_code	=	curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
	if($http_code==401) {
		return false;
	}
	
	$response=json_decode($response);
	
	return $response->api_token;

}

function set_posterous_post($api_token,$title='',$image_url,$display_date='') {

	$url="http://posterous.com/api/2/users/me/sites/tpimport3/posts";
	$fields_string='';
  
	$fields = array(
		'post'	=>	array(
			'title'			=>	urlencode($title),
			'body'			=>	urlencode('<img src="'.$image_url.'" class="posterous_download_image" >'),
			'tags'			=>	urlencode('Imported from TwitPic'),
			'auto_post'		=>	FALSE,
			'display_date'	=>	urlencode($display_date),
			'source'		=>	urlencode('http://beneverard.co.uk/twitpic_to_posterous_importer')
		),
		'api_token'	=>	$api_token
	);
	
	//url-ify the data for the POST
	foreach($fields as $key=>$value) {
		if(is_array($value)) {
			foreach($value as $key1=>$value2) {
				
				$fields_string .= $key.'['.$key1.']='.$value2.'&'; 
			}
		} else {
			$fields_string .= $key.'='.$value.'&'; 
		}
	}
	rtrim($fields_string,'&');

	//open connection
	$ch = curl_init();
	
	//set the url, number of POST vars, POST data
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_USERPWD, $posterous_email.':'.$posterous_password);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_POST,TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$fields_string);
	
	//execute post
	$result = curl_exec($ch);
	
	//echo "<pre>".print_r(json_decode($result),true)."</pre>";

	//exit;
}

// http://forrst.com/posts/TwitPic_Hack_Get_Full_TwitPic_Image_using_PHP-mFQ

function get_twitpic_url($id) {

	// full url of the TwitPic photo
	$url = "http://twitpic.com/".$id;

	// make the cURL request to TwitPic URL
	$curl2 = curl_init();
	curl_setopt($curl2, CURLOPT_URL, $url);
	curl_setopt($curl2, CURLOPT_AUTOREFERER, true);
	curl_setopt($curl2, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($curl2, CURLOPT_TIMEOUT, 10);

	$html = curl_exec($curl2);

	$HttpCode = curl_getinfo($curl2, CURLINFO_HTTP_CODE);
	$totalTime = curl_getinfo($curl2, CURLINFO_TOTAL_TIME);
	
	// if the HTTPCode is not 200 - you got issues
	if ($HttpCode != 200)
	{
		?><p>Unable to connect to TwitPic. Please try again later.</p><?
	}
	else
	{
		// if you are not getting any HTML returned, you got another issue.
		if ($html == "")
		{
			?><p>Yikes! TwitPic is experiencing heavy load. Please close this window and try again.</p><?
		}
		else
		{
			$dom = new DOMDocument();
			@$dom->loadHTML($html);
			// grab all the on the page
			$xpath = new DOMXPath($dom);
			$hrefs = $xpath->evaluate("/html/body//img");
 						foreach( $hrefs as $href ) { $url = $href->getAttribute('id'); 
				// for all the images on the page find the one with the ID of photo-display
				if ($url == "photo-display")
				{
					// get the SRC attribute of the element with the ID of photo-display
					$image = $href->getAttribute('src');
					
					return $image;
					
				}
			}

		}
	}

}




?>