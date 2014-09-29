<html>
	<head>
		<title></title>
		<style>
		</style>
	</head>
	<body>
		<pre>
<?php
$markerData = array();
		
function generatePersonData($address, $profileURL, $profileName){
	// get latitude & longitude from Google API
	$addressClean = str_replace (" ", "+", $address);
	$details_url = "http://maps.googleapis.com/maps/api/geocode/json?address=" . $addressClean ;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $details_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	sleep(2);
	$geoloc = json_decode(curl_exec($ch), true);
	
	// use it to genertate the person marker
	global $markerData;
	$markerDataNextSlot = sizeof($markerData);
	$markerData[$markerDataNextSlot] = new stdClass();
	$markerData[$markerDataNextSlot]->latlng[0] = $geoloc['results'][0]['geometry']['location']['lat'];
	$markerData[$markerDataNextSlot]->latlng[1] = $geoloc['results'][0]['geometry']['location']['lng'];
	$markerData[$markerDataNextSlot]->popup = '<a href="'.$profileURL.'">'.$profileName.'</a>';
	$markerData[$markerDataNextSlot]->icon = 'user';
	
	// debug output
	//if ($geoloc['error_message']) { print "\n*****ERROR*****\n$geoloc['error_message']\n**\n"; }
	print "==#$markerDataNextSlot==\n";
	print "\nLooked up $profileName (address = $address) and got:\n";
	print_r($geoloc);
	print_r($markerData[$markerDataNextSlot]);
	print "\n";
	// return this to be output in CDATA
	return json_encode($markerData[$markerDataNextSlot]);
}

// print generatePersonData('New Haven, 06520, United States','/user/1','Tom Ash');

// prepare to fetch info for people with EA Profiles
// ******* NB: should switch to "include "/confidential-docroot/include-files/sql-login-for-eahub.php;""

$sql_login_for_eahub_username = "(private)";
$sql_login_for_eahub_password = "(private)";
$db = mysql_connect("localhost", $sql_login_for_eahub_username, $sql_login_for_eahub_password);
mysql_select_db("(private)",$db);
mysql_set_charset('utf8',$db); 


// start a loop, and in each instance:
	// fetch info for a person with an EA Profile (name, link, city, country, postcode)
	
	/// find the people who should be on the map - treating this as people who've given countries
	/// TODO: Think if there's a better set of people to include, or way for people to opt into the map
	$__demographicsProfileID = mysql_query("SELECT entity_id FROM field_data_field_in_which_country_do_you_li"); // LIMIT would go here
	while ($_demographicsProfileID = mysql_fetch_row($__demographicsProfileID))
	{
		$_userid = mysql_fetch_row(mysql_query("SELECT uid FROM profile WHERE pid = $_demographicsProfileID[0]"));
		$userid = $_userid[0];
		$_eapName = mysql_fetch_row(mysql_query("SELECT name FROM users WHERE uid = $userid"));
		$eapName = $_eapName[0];
		
		// get their address
		/// get the person's demographics profile2 profile
		
		/* debugging:
		print "==Trying to run:==\n";
		print "SELECT pid FROM profile WHERE uid = $userid AND type LIKE 'basic_information';";
		print "\n\n";
		*/
		$_demographicsProfileID = mysql_fetch_row(mysql_query("SELECT pid FROM profile WHERE uid = $userid AND type LIKE 'basic_information';"));
		/* debugging:
		print "====Result:====\n";
		print_r($_demographicsProfileID);
		*/
		$demographicsProfileID = $_demographicsProfileID[0];
		
		/// get their city
		/* debugging:
		print "==Trying to run:==\n";
		print "SELECT field_in_which_city_do_you_live__value FROM field_data_field_in_which_city_do_you_live_ WHERE entity_id = $demographicsProfileID;";
		print "\n\n";
		*/
		$_city = mysql_fetch_row(mysql_query("SELECT field_in_which_city_do_you_live__value FROM field_data_field_in_which_city_do_you_live_ WHERE entity_id = $demographicsProfileID;"));
		/* debugging:
		print "====Result:====\n";
		print_r($_city);
		*/
		$city = $_city[0];
		
		/// get their country
		$_country = mysql_fetch_row(mysql_query("SELECT field_in_which_country_do_you_li_value FROM field_data_field_in_which_country_do_you_li WHERE entity_id = $demographicsProfileID;"));
		$country = $_country[0];
		
		// get their postal code
		$_postalCode = mysql_fetch_row(mysql_query("SELECT field_postal_code_value FROM field_data_field_postal_code WHERE entity_id = $demographicsProfileID;"));
		$postalCode = $_postalCode[0];
		
		
		
		// concatenate this data and pass it to generatePersonData()
		$concatenatedLocation = '';
		if ($city) $concatenatedLocation .= "$city, ";
		if ($postalCode) $concatenatedLocation .= "$postalCode, ";
		$concatenatedLocation .= $country;
		/* debug:
		print "$concatenatedLocation\n";
		print " -- /users/$userid  -- $eapName\n\n";
		*/
		generatePersonData($concatenatedLocation,"/user/$userid",$eapName);
		// save the results to be written into the CDATA section of the map webpage

	}
	
	
	
	
	print "\n\n\n\n";
	print json_encode($markerData);
?>
		</pre>
	</body>
</html>
