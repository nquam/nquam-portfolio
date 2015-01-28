<?
/*******************************************************************
POPULATION
*******************************************************************/
/**
* Queries DB for city/county designation for zips in a state
* @param object mysqli
* @param string state
* @param array $zip_array passed in by reference
* @return void
*/
function populateZips(mysqli $mysqli, $state, array &$array) {
	$sql = 'SELECT `zipID`,`zip_code`,`city`,`county`
		FROM `census_zip`
		WHERE `county` <> \'\'
		AND `state` = \''.$state.'\'';

	$result = query($mysqli,$sql);

	while($data = mysqli_fetch_assoc($result)) {
		$array[$data['zip_code']] = array('city'=>$data['city'],'county'=>$data['county']);
	}
}

/**
* Queries DB for features not explicitly defined, also populates $feature_array
* with featuer descriptions.
* @param object mysqli
* @param string db name of the MLS feed DB
* @param array $feature_lists passed in by reference
* @return void
*/
function populateFeatures(mysqli $mysqli, $db, array &$feature_lists, array &$feature_array) {
	$mysqli->select_db($db) or die($mysqli->error); // change to MLS feed DB

	$sql = 'SELECT * FROM feature';

	$result = query($mysqli,$sql);

	while($data = mysqli_fetch_assoc($result)) {
		switch($data['type']) {
			case 'Rooms':
				$feature_lists['interior_other'] = ListAppend($feature_lists['interior_other'],$data['ID']);
				break;
			case 'Flooring':
				$feature_lists['interior_floor'] = ListAppend($feature_lists['interior_floor'],$data['ID']);
				break;
			case 'Win/Doors':
				$feature_lists['interior_window'] = ListAppend($feature_lists['interior_window'],$data['ID']);
				break;
			case 'Lot':
				$feature_lists['exterior_lot'] = ListAppend($feature_lists['exterior_lot'],$data['ID']);
				break;
			case 'Style':
				$feature_lists['exterior_dwell'] = ListAppend($feature_lists['exterior_dwell'],$data['ID']);
				break;
			case 'Construction':
				$feature_lists['exterior_const'] = ListAppend($feature_lists['exterior_const'],$data['ID']);
				break;
			case 'Parking':
				$feature_lists['exterior_park'] = ListAppend($feature_lists['exterior_park'],$data['ID']);
				break;
			case 'Roof':
				$feature_lists['exterior_roof'] = ListAppend($feature_lists['exterior_roof'],$data['ID']);
				break;
			case 'Scenery':
				$feature_lists['exterior_water'] = ListAppend($feature_lists['exterior_water'],$data['ID']);
				break;
			case 'Exterior':
				$feature_lists['exterior_misc'] = ListAppend($feature_lists['exterior_misc'],$data['ID']);
				break;
			case 'Energy':
				$feature_lists['utility_energy'] = ListAppend($feature_lists['utility_energy'],$data['ID']);
				break;
		}
		$feature_array[$data['ID']] = $data['description'];
	}
}
/******************************************************************/
/**
* Puts together feature array for insert into DB
* @param array $row
* @param int $st
* @param int $end
* @return array $totalfeature
*/
function retsfeatures($row,$st,$end){
	$featureset="";
	for($i=0;$st<=$end;$i++){
		$featureuse[$i]=$row[$st];
		$st++;
	}
	for($s=0;$s<count($featureuse);$s++){
		if(isset($featureuse[$s])&&$featureuse[$s]!=""){
			$featureset.=$featureuse[$s].", ";
		}
	}
	//		0=$feature_interior_kitchen//		1=$feature_interior_misc//		2=$feature_interior_other//		3=$feature_interior_bedbath
	//		4=$feature_interior_floor//		5=$feature_interior_window//		6=$feature_exterior_lot//		7=$feature_exterior_dwell
	//		8=$feature_exterior_const//		9=$feature_exterior_park//		10=$feature_exterior_roof//		11=$feature_exterior_pool
	//		12=$feature_exterior_water//		13=$feature_exterior_misc//		14=$feature_utility_cool//		15=$feature_utility_heat
	//		16=$feature_utility_energy//		17=$feature_utility_sewer//		18=$feature_utility_water//		19=$feature_amenities
	// above is the legend for the feature array types below.
	// This data is compared to the string of the featureset and proper values are pulled
	// If the RETS doesn't comply with this data, strings can be added to the array as needed.
	// Do not remove any of the strings from these arrays. They are required for the feature array to work.
	$featurearray[0]=array("Compactor", "Countertop Range", "Dishwasher", "Disposal", "Freezer", "Microwave", "Range", "Refrigerator", "Wall Oven", "Granite/Solid Counters", "Pantry", "Dining Area", "Formal", "Skylight", "Kitchen Combo", "Breakfast Area", "Built-in Oven", "Built-in Refrig", "Cooktop Stove", "Electric Range", "Gas Range");
	$featurearray[1]=array("Ceiling Fans", "Cable Available", "Skylight", "Balcony/Deck", "Cathedral Ceil", "Ceiling Fans", "Fireplace", "Interior Balcony", "Skylight", "Central Vacuum", "Handicap Equipped", "Intercom", "Sauna/Steam/Hot Tub", "Security System", "Washer", "Dryer");
	$featurearray[2]=array("Dining Area", "Walk-up Attic", "Wetbar", "Linen Closet", "Living Room", "Family Room");
	$featurearray[3]=array("Cedar Closet", "Ceiling Fans", "Ceramic Tile Floor", "Fireplace", "Full Bath", "Granite/Solid Counters", "Half Bath", "Hard Wood Floor", "Hardwood Floor", "Hot Tub/Spa", "Linen Closet", "Skylight", "Walk-in Closet", "Wall to Wall Carpet", "Shower Over Tub", "Stall Shower", "Jack & Jill", "Sauna", "Shower and Tub", "Tub W/Jets");
	$featurearray[4]=array("Ceramic Tile Floor", "Hard Wood Floor", "Wall to Wall Carpet", "Hardwood", "Laminate", "Part Carpet", "W/W Carpet", "Vinyl/Linoleum", "Marble", "Simulated Wood");
	$featurearray[5]=array("Bay/Bow Windows", "Insulated Windows", "Storm Windows");
	$featurearray[6]=array("Additional Land Avail.", "Corner", "Easements", "Paved Drive", "Public", "Publicly Maint.", "Wooded", "Zero Lot Line");
	$featurearray[7]=array("Antique", "Bungalow", "Cape", "Colonial", "Contemporary", "Cottage", "Farmhouse", "Front to Back Split", "Gambrel /Dutch", "Multi-Level", "Raised Ranch", "Raised Ranch", "Saltbox", "Split Entry", "Victorian");
	$featurearray[8]=array("Concrete Block", "Fieldstone", "Poured Concrete", "Aluminum", "Asbestos", "Brick", "Clapboard", "Shingle", "Stone", "Stucco", "Vinyl", "Wood", "Brick", "Frame", "Post & Beam", "Stone/Concrete", "Fiberglass", "Redwood", "Pillars/Post");
	$featurearray[9]=array("Off-Street", "Tandem", "On Street Permit", "Assigned Attached", "Detached", "Garage Door Opener", "Heated", "Insulated", "Side Entry", "Storage", "Under", "Work Area");
	$featurearray[10]=array("Asphalt/Fiberglass Shingles", "Wood Shingles", "Slate", "Rubber", "Tile");
	$featurearray[11]=array("Above Ground Pool", "Inground Pool");
	$featurearray[12]=array("Bay", "Harbor", "Lake", "Lake/Pond", "Ocean", "Pond");
	$featurearray[13]=array("Above Ground Pool", "Cabana", "Deck", "Decor. Lighting", "Enclosed Porch", "Fenced Yard", "Gutters", "Handicap Access", "Hot Tub/Spa", "Inground Pool", "Patio", "Pool", "Porch", "Prof. Landscape", "Satellite Dish", "Screens", "Sprinkler System", "Storage Shed");
	$featurearray[14]=array("2 Units", "3 Units", "Wall AC", "Central Air", "Window AC", "1 Window Unit Incl", "Ceiling Fan");
	$featurearray[15]=array("Central Heat", "Electric Basebd", "Forced Air", "Gas", "Heat Pump", "Hot Air Gravity", "Hot Water Baseboard", "HW Radiators", "Oil", "Radiant", "Steam", "Baseboard Heaters", "Natural Gas", "Wall Furnace", "Wood Burning", "Stove Heater", "Propane", "Fireplace");
	$featurearray[16]=array("Attic Vent Elec.", "Insulated Doors", "Insulated Windows", "Prog. Thermostat", "Solar Features", "Storm Doors", "Storm Windows", "Ceiling Insulation", "Dual Pane Windows", "Low-Flow Shower", "Low-Flow Toilet", "Wall Insulation", "Weather Strip", "Floor Insulation");
	$featurearray[17]=array("City/Town Sewer", "Inspection Required for Sale", "Private Sewerage", "Sewer Public", "Sewer Private", "Standard Septic", "Septic Engineered");
	$featurearray[18]=array("City/Town Water", "Private Water", "Shared Well", "Well Private", "Water Private", "Storage Tanks",  "Water Public", "Water District");
	$featurearray[19]=array("Golf Course", "Clubroom", "Exercise Room", "Park", "Swimming Pool", "Tennis Court", "Walk/Jog Trails", "Security Gate" ,"Community Pool", "Clubhouse/Rec Room", "Hot Tub/Spa", "Water Access", "Jogging Trail", "Barbeque");//available but not included "Laundromat", "Medical Facility", "Public Transportation", "Shopping", "Stables", "Walk/Jog Trails");

	for($a=0;$a<=19;$a++){
		$totalfeature[$a]="";
	}
	for($a=0;$a<=19;$a++){
		for($b=0;$b<count($featurearray[$a]);$b++){
			if(stristr($featureset, $featurearray[$a][$b])) {
				if(!stristr($totalfeature[$a], $featurearray[$a][$b])) {
					// Checking data and inserting into the proper totalfeature array
					$totalfeature[$a].=convertamenities($featurearray[$a][$b]);
				}
			}
		}

	}
	for($c=0;$c<=19;$c++){
		$totalfeature[$c] = substr($totalfeature[$c],0,-2);
		//echo '$totalfeature['.$c.']='.$totalfeature[$c]."\n";
	}

	return $totalfeature;
}
/**
* Converts amenities names for RETS data
* @param array $featureuse
* @return string
*/
function convertamenities($featureuse){
	if($featureuse=='Clubroom'||$featureuse=='Club Room'||$featureuse=='Clubhouse/Rec Room'){
		return "Clubhouse, ";
	}
	elseif($featureuse=='Exercise Room'||$featureuse=='Gym'||$featureuse=='Recreation Room'){
		return "Fitness Center, ";
	}
	elseif($featureuse=='Pool'){
		return "Swimming Pool, ";
	}
	elseif($featureuse=='Walk/Jog Trails'||$featureuse=='Trails'||$featureuse=='Jogging Trail'){
		return "Nature Trails, ";
	}
	elseif($featureuse=='Park'||$featureuse=='Playground'){
		return "Child&rsquo;s Play Area, ";
	}
	elseif($featureuse=='Tennis Court'){
		return "Tennis, ";
	}
	elseif($featureuse=='Security Gate'){
		return "Gate, ";
	}
	elseif($featureuse=='Spa'||$featureuse=='Sauna'||$featureuse=='Hot Tub/Spa'){
		return "Spa/Hot Tub, ";
	}
	elseif($featureuse=='Water Access'){
		return "Boat/Kayak Ramp, ";
	}
	elseif($featureuse=='BBQ Area'||$featureuse=='Barbeque'){
		return "BBQ Picnic Area, ";
	}
	else{
		return $featureuse.", ";
	}
}
/*******************************************************************
GETTERS
*******************************************************************/
/**
* Takes list of featureIDs and converts them to long description
* @param string type category such as 'interior_misc' or 'interior_bedbath'
* @param string list list of IDs separated by comma
* @return string list of descriptions
*/
function getFeatures($type,&$list) {
	$ret_list = '';

	$tmp = explode(',',$list);
	foreach($tmp as $value) {
		if(ListFind($GLOBALS['feature_lists'][$type],$value)) {
			$ret_list = ListAppend($ret_list,$GLOBALS['feature_array'][$value],', ');
			$list = ListDeleteAt($list,ListFind($list,$value));
		}
	}
	return $ret_list;
}

/**
* Returns location based on census zip as MLS data is not so accurate, also the MLS
* is not consistent in naming (e.g. "St. Pete's", "St. Petersberg", etc.)
* @param string type - city or county
* @param string zip
* @param string default - return what is in the MLS data if zip is not found
* @return void
*/
function getLocation($type,$zip,$default) {
	if(isset($GLOBALS['zip_array'][$zip])) {
		return $GLOBALS['zip_array'][$zip][$type];
	} else {
		return strtoupper($default);
	}
}

/**
* Takes list of amenityIDs and converts them to long description
* @param string list list of IDs separated by comma
* @return string list of descriptions
*/
function getAmenities($list) {
	$ret_list = '';

	$tmp = explode(',',$list);
	foreach($tmp as $value) {
		foreach($GLOBALS['amenity_lists'] as $subkey=>$subvalue) {
			if(ListFind($subvalue,$value) && !ListFind($ret_list,$subkey,', ')) {
				$ret_list = ListAppend($ret_list,$subkey,', ');
			}
		}
	}

	return $ret_list;
}
/******************************************************************/



/*******************************************************************
DB PROPERTY INSERT
*******************************************************************/
/**
* Inserts property into DB
* @param object mysqli
* @param array imploder
* @return void
*/
function insertProperty(mysqli $mysqli, array $array) {
	$names = '`'.implode('`,`',array_keys($array)).'`';
	$values = '\''.implode('\',\'',$array).'\'';
	$sql = 'INSERT INTO `shadow` ('.$names.') VALUES ('.$values.')';

//	echo $sql;
//	echo $array['address'].",".$array['city'].",".$array['state'].",".$array['latitude'].",".$array['longitude']."\n";
	query($mysqli,$sql);
}

/**
* performs query on DB
* @param object mysqli
* @param string sql to execute
* @return void
*/
function query(mysqli $mysqli, $sql) {
	if(!$result = $mysqli->query($sql)) {
		echo $mysqli->error.'<br>';
		echo $sql.'<br><br>';
	}

	return $result;
}

/**
* writes values to file for archiving and later INSERT
* @array array array
* @param object fh
*/
function writeProperty(array $array,$fh) { // Writes entry to file
	@$values = implode('|',$array);
	$stringData = $values."\n";
	fwrite($fh, $stringData);
}
/******************************************************************/



/*******************************************************************
CLEANING/FORMATTING
*******************************************************************/
/**
* Escapes data before being entered into DB
* @param object mysqli
* @param string string to escape
* @return string escaped string
* @access public
*/
function escapeData(mysqli $mysqli, $string) {
	$string=str_replace("|"," ", $string);
	if(get_magic_quotes_gpc()) {
		return $string;
	} else {
		return $mysqli->real_escape_string($string);
	}
}

/**
* Formats MM/DD/YYYY dates into unixtime
* @param string date to format
* @return string unixtime
*/
function dateFormat($string) {
	if($string!='') {
		$temp = explode('/',$string);
		$string = mktime(0,0,0,$temp[0],$temp[1],$temp[2]);
	}
	return $string;
}

/**
* Formats "last, first" names into "first last"
* @param string name to format
* @return string formatted name
*/
function nameFormat($string) {
	$temp = explode(',',$string);
	return (count($temp)==2) ? $temp[1].' '.$temp[0]: $string;
}

/**
* Formats ten digit string in (000) 000-0000
* @param string phone to format
* @return string formatted phone
*/
function phoneFormat($string) {
	return (strlen($string)==10) ? '('.substr($string,0,3).') '.substr($string,3,3).'-'.substr($string,6): $string;
}


/**
* grabs coords
* @param string address to geocode
* @return array [lat,lon]
*/
function geocode($tmp) {
	$k = '9e9c7a71051534ae5c93b0be44a323c1';
	$url = 'http://geo.propertymaps.com/index.php?q='.urlencode($tmp['address']).'&c='.urlencode($tmp['city']).'&s='.$tmp['state'].'&z='.$tmp['zip'].'&k='.$k;
	$content = file_get_contents($url);
	if($content != '') {
		$array = explode(',',$content);
		return $array;
	} else {
		return false;
	}
}

/**
* checks results
* @param array tmp
* @param int bunk_geo
* @param int bunk_address
* @param mysqli
* @param zip
*/
function geocheck($tmp,$mysqli,$dat,$fh){
	if(trim($tmp['address'])=='' || trim($tmp['city'])=='' || trim($tmp['state'])=='') {
		$dat['bunk_address']++;	// data testing
		echo $dat['bunk_address']." bunk address\n";
	}
	else{
		if($coords = geocode($tmp)) {
			$tmp['latitude'] = $coords[0];
			$tmp['longitude'] = $coords[1];
			echo $tmp['latitude'].",".$tmp['longitude']."\n";
			if($tmp['latitude']==0.0000000||$tmp['longitude']==0.0000000||$tmp['latitude']==''||$tmp['longitude']==''){
				$dat['bunk_geo']++;
				echo $dat['bunk_geo']." bunk geo\n";
			}
			else {
				writeProperty($tmp,$fh); //stays
			}
		}
		else {
			$dat['bunk_geo']++;	// data testing
			echo $dat['bunk_geo']." bunk geo\n";
		}
	}
	return $dat;
}

/**
* checks price
* @param int listprice
* @param int sqft
*/
function pricecheck($listprice,$sqft){
	if($listprice<=100){
		$price=$sqft*$listprice;
	}
	else{
		$price=$listprice;
	}
	return $price;
}
/*******************************************************************



/******************************************************************
Make Thumbnails
*******************************************************************/
/**
* resizes images
* TODO: sizes should probably be moved out of this function and passed as an array
* @param string root_dir
* @param string db
* @param string image source image
* @param string size resize dimensions
* @return void
*/
function makeThumb($root_dir,$db,$image,$size,$extras=false) {
	// This will check to see which to use, main or extras
	if(!$extras){
		$media_dir=$root_dir."media/".$db."/";
	}
	else{
		$media_dir=$root_dir."media/".$db."_extras/";
	}
	$filename = $media_dir.''.$size.'/'.$image;
	
	// Checks if file exists before resizing
	if(file_exists($filename)) {
		//echo $size.'/'.$image.' exists...'."\n";
	} else {
		echo 'Writing data '.$image."\n";
		if(!$extras){
			$media_root = $root_dir.'media/'.$db.'/';
		}
		else{
			$media_root = $root_dir.'media/'.$db.'_extras/';
		}
		$image_path = $media_root.'x/'.$image;

		$image_binary = imageCreateFromJPEG($image_path);

		$width = imagesx($image_binary);
		$height = imagesy($image_binary);

		switch($size) {
			case 'l':
				$thumb_width  = 343;
				$thumb_height = 257;
				break;
			case 'm':
				$thumb_width  = 134;
				$thumb_height = 91;
				break;
			case 's':
				$thumb_width  = 52;
				$thumb_height = 52;
				break;
			case 's2':
				$thumb_width  = 70;
				$thumb_height = 50;
				break;
		}

		$thumb_image = imageCreateTrueColor($thumb_width,$thumb_height);

		imageCopyResampled($thumb_image, $image_binary, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);

		imageJPEG($thumb_image,$media_root.$size.'/'.$image);

		imageDestroy($image_binary);
		imageDestroy($thumb_image);
	}
}
/******************************************************************/



/*******************************************************************
Make Reports
*******************************************************************/
/**
* Creates a report for feed scripts and outputs to the screen
* @param string db
* @param array dat
* @param int entries
* @param int run_time
*/
function runreports($db,$dat,$entries,$run_time) {
	$localhost=trim(shell_exec('hostname'));
	if($localhost=='propertymapsinc02.theplanet.host'){
		$from_name='Data Feed Report';
		$from_email='admin@propertymaps.com';
		$to_email='admin@propertymaps.com,nquam@propertymaps.com';
		$subject='Data Feed '.$db.' report';
	}
	elseif($localhost=='neptune.propertymaps.com'){
		$from_name='NEPTUNE Data Feed Report';
		$from_email='admin@propertymaps.com';
		$to_email='nquam@propertymaps.com';
		$subject=$db.' Data Feed report from NEPTUNE';
	}
	else{
		$from_name='Other Data Feed Report';
		$from_email='admin@propertymaps.com';
		$to_email='nquam@propertymaps.com';
		$subject=$db.' Data Feed report from other';
	}
	/**
	* Creates and sends email report
	* REPORT
	*/

	$valid = ($dat['rows'] - $dat['bunk_data'] - $dat['price'] - $dat['vc'] - $dat['zips']);
	$bad = $dat['bunk_latlon']-$dat['bunk_address'];
	$content = '';
	$content .= 'Data report for '.$db.' data insert'."\n";
	$content .= '-------------------------------------------------------------'."\n";
	$content .= 'Total Rows: '.$dat['rows']."\n";
	$content .= 'Bunk Data: '.$dat['bunk_data']."\n";
	$content .= 'No Price: '.$dat['price']."\n";
	$content .=  'Vacant/Commercial: '.$dat['vc']."\n";
	$content .=  'Empty Zip Codes: '.$dat['zips']."\n";
	$content .=  'TOTAL VALID: '.$valid."\n";
	$content .=  'Bunk lat/lon: '.$dat['bunk_latlon']."\n";
	$content .=  'Bunk address: '.$dat['bunk_address']."\n";
	$content .=  'Bunk geocode: '.$dat['bunk_geo']."\n";
	if($dat['bunk_latlon']-$dat['bunk_address']!=0&&$dat['bunk_geo']!=0){
		$content .=  'GEO RATIO: '.($dat['bunk_geo']/($dat['bunk_latlon']-$dat['bunk_address']))."\n";
	}
	else{
		$content .=  "GEO RATIO: 0\n";
	}
	$content .=  'TOTAL INSERT: '.($valid - $dat['bunk_geo']-$dat['bunk_address'])."\n";
	$content .=  'LAST INSERT: '.$entries."\n";
	$content .=  'Script finished in: '.$run_time." seconds\n";
	$content .=  '-------------------------------------------------------------'."\n";
	/**************************************************************/
	echo '<pre>'.$content.'</pre>';
	sendGeneral($from_name,$from_email,$to_email,$subject,$content);
}

/**
* Creates a report for feed scripts and outputs to the screen
* @param string db
* @param array dat
* @param int entries
* @param int run_time
*/
function flawreports($db,$dat,$entries,$run_time) {
	$localhost=trim(shell_exec('hostname'));
	if($localhost=='mail.propertymaps.com'){
		$from_name='WARNING Data Feed Report';
		$from_email='admin@propertymaps.com';
		$to_email='admin@propertymaps.com,nquam@propertymaps.com';
		$subject='Possible FAIL in Data Feed '.$db;
	}
	elseif($localhost=='neptune.propertymaps.com'){
		$from_name='NEPTUNE WARNING Data Feed Report';
		$from_email='admin@propertymaps.com';
		$to_email='nquam@propertymaps.com';
		$subject=$db.' Possible FAIL Data Feed report from NEPTUNE';
	}
	else{
		$from_name='WARNING Data Feed Report';
		$from_email='admin@propertymaps.com';
		$to_email='nquam@propertymaps.com';
		$subject=$db.' Possible FAIL Data Feed report from other';
	}
	/**
	* Creates and sends email report
	* REPORT
	*/

	$valid = ($dat['rows'] - $dat['bunk_data'] - $dat['price'] - $dat['vc'] - $dat['zips']);
	$bad = $dat['bunk_latlon']-$dat['bunk_address'];
	$content = '';
	$content .= 'Data report for '.$db.' data insert'."\n";
	$content .= "There was a possible failure in this feed. Please check output.\n";
	$content .= '-------------------------------------------------------------'."\n";
	$content .= 'Total Rows: '.$dat['rows']."\n";
	$content .= 'Bunk Data: '.$dat['bunk_data']."\n";
	$content .= 'No Price: '.$dat['price']."\n";
	$content .=  'Vacant/Commercial: '.$dat['vc']."\n";
	$content .=  'Empty Zip Codes: '.$dat['zips']."\n";
	$content .=  'TOTAL VALID: '.$valid."\n";
	$content .=  'Bunk lat/lon: '.$dat['bunk_latlon']."\n";
	$content .=  'Bunk address: '.$dat['bunk_address']."\n";
	$content .=  'Bunk geocode: '.$dat['bunk_geo']."\n";
	if($dat['bunk_latlon']-$dat['bunk_address']!=0&&$dat['bunk_geo']!=0){
		$content .=  'GEO RATIO: '.($dat['bunk_geo']/($dat['bunk_latlon']-$dat['bunk_address']))."\n";
	}
	else{
		$content .=  "GEO RATIO: 0\n";
	}
	$content .=  'TOTAL INSERT: '.($valid - $dat['bunk_geo'])."\n";
	$content .=  'LAST INSERT: '.$entries."\n";
	$content .=  'Script finished in: '.$run_time." seconds\n";
	$content .=  '-------------------------------------------------------------'."\n";
	/**************************************************************/
	echo '<pre>'.$content.'</pre>';
	sendGeneral($from_name,$from_email,$to_email,$subject,$content);
}
/******************************************************************/



/*******************************************************************
MAILER
*******************************************************************/
/**
* Send general email
* @param string from_name
* @param string from_email
* @param string to_email
* @param string subject
* @param string content
* @return void
*/
function sendGeneral($from_name,$from_email,$to_email,$subject,$content) {
	$header = prepHeader($from_name,$from_email,false);
	mail($to_email,$subject,trim($content),$header,'-f '.$from_email);
	echo "\nEmail report has been sent.\n";
}

/**
* Prepares email header
* @param string $from_name sender name
* @param string $from_email sender email address
* @param boolean html if the email is html
* @return string prepared header
*/
function prepHeader($from_name,$from_email,$html) {
	$generic_header='From: "'.$from_name.'" <'.$from_email.'>'."\n";
	$generic_header.='Reply-To: "'.$from_name.'" <'.$from_email.'>'."\n";
	$generic_header.='Return-path: "'.$from_name.'" <'.$from_email.'>'."\n";
	if($html) {
		$header = 'Content-type: text/html; charset=iso-8859-1'."\n".$generic_header;
	} else {
		$header = 'Content-type: text/plain; charset=iso-8859-1'."\n".$generic_header;
	}
	return $header;
}
/******************************************************************/

/*******************************************************************
TOOLS
*******************************************************************/
/**
* Get run time for script
* @return time
*/
function getmicrotime(){
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}
?>
