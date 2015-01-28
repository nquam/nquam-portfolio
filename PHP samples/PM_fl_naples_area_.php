<?
/* STATICS ********************************************************/
if(isset($_SERVER['argv'])) {
	chdir(dirname($_SERVER['argv'][0]));
	define('ROOT',ereg_replace('_dataset\/.*','',getcwd()));
} else {
	define('ROOT',$_SERVER['DOCUMENT_ROOT']);
}
/******************************************************************/


/* INCLUDES *******************************************************/
require_once(ROOT.'_dataset/scripts/config.php');
require_once(ROOT.'_dataset/scripts/common.php');
require_once(ROOT.'fbx_listfunctions.php');
require_once(ROOT.'includes/states_array.php');
require_once(ROOT.'includes/type_array.php');
/******************************************************************/

$time_start = getmicrotime();
$startserver = getmicrotime(1);

/* VARS ***********************************************************/
$boardID = '280';
$state = 'FL';
$db = 'PM_fl_naples_area_';
$mls_type = 'idx';
$zip_array = array();
populateZips($mysqli,$state,$zip_array);	// create zip array for this state
$dat=array('bunk_latlon'=>0,'bunk_geo'=>0,'bunk_address'=>0,'bunk_data'=>0,'price'=>0,'vc'=>0,'zips'=>0,'rows'=>0);
/******************************************************************/


/* LISTS **********************************************************/
$feature_lists = array(
	'interior_kitchen'			=>	'O01,O02,O03,O04,O27,O28',
	'interior_misc'				=>	'O07,O08,O09,O10,O11,O12,O13,O14,O15,O16,O17,O18,O19,O21,O22,O23,O29,O30,O31,O32,O33',
	'interior_bedbath'			=>	'O05,O06,O20,O24,O25,O26',
	'interior_other'			=>	'',
	'interior_floor'			=>	'',
	'interior_window'			=>	'',

	'exterior_lot'				=>	'',
	'exterior_dwell'			=>	'',
	'exterior_const'			=>	'',
	'exterior_park'				=>	'',
	'exterior_roof'				=>	'',
	'exterior_pool'				=>	'C11,C15,U01,U02,U03,U04,U05,U06,U07,U08,U09,U10,U11,U12,X18',
	'exterior_water'			=>	'',
	'exterior_misc'				=>	'',

	'utility_cool'				=>	'M01,M02,M03,M04,M20,M34,M35',
	'utility_heat'				=>	'M05,M06,M07,M08,M09,M10,M11,M12,M13,M14,M15,M16,M17,M18,M19,M21,M22,M23,M24,M36,M37',
	'utility_energy'			=>	'',
	'utility_sewer'				=>	'Q01,Q02,Q13,Q14,Q15,Q16,Q17,Q31',
	'utility_water'				=>	'Q03,Q04,Q05,Q06,Q10,Q11,Q12,Q28'
);
$amenity_lists = array(
	'Clubhouse'					=>	'C04,C05',
	'Swimming Pool'				=>	'C11,C15,U01,U02,U03,U04,U05,U06,U07,U08,U09',
	'Spa/Hot Tub'				=>	'U13,U14,U15',
	'Fitness Center'			=>	'C20',
	'Gate'						=>	'C21',
	'Gate Guarded'				=>	'C22',
	'Nature Trails'				=>	'C10,C20',
	'Child&rsquo;s Play Area'	=>	'C14,W41',
	'Golf Course'				=>	'C07',
	'Tennis'					=>	'C19,U22',
	'Baseball/Handball'			=>	'C08',
	'Boat/Kayak Ramp'			=>	'C03,U19,U20,U21',
	'BBQ Picnic Area'			=>	'C13'
);
$feature_array = array();											// array for feature descriptions
populateFeatures($mysqli,$db,$feature_lists,$feature_array);		// populate features not explicity defined above
/******************************************************************/

$curdate=date("m-d-Y");

$myFile = ROOT.'_dataset/data/idx/'.$db.'/'.$curdate.'_data.txt';
$fh = fopen($myFile, 'w');
$myFile = ROOT.'_dataset/data/idx/'.$db.'/'.$curdate.'_data.txt';
$fh = fopen($myFile, 'w') or die("can't open file");


$handle = fopen(ROOT.'_dataset/data/idx/'.$db.'/data.txt', 'r');
while(($row = fgetcsv($handle)) !== FALSE) {
	$dat['rows']++;	// data testing
	if(isset($row[84])							// data is not bunk
		&& $row[17]!='' && $row[17]!='0'		// price not equal 0
		&& $row[13]!='V' && $row[13]!='C'		// don't include vacant/commercial
		&& strlen($row[29])>='5'				// don't include empty zips
		) {

		// minor var massaging
		$features = $row[84];
		$pool_description = getFeatures('exterior_pool',$features);
		$pool = ($pool_description=='') ? 'N': 'Y';
		$photo = (trim($row[68])=='X') ? 'Y': 'N';
		$zip = substr($row[29],0,5);	// only get the 5 digit zip
		if($row[41]>=20000) { $sqft=0; } else {$sqft=$row[41];}
		// populate array
		$tmp = array(
			'mlsID'				=>	$row[2],
			'property_type'		=>	$row[13],
			'date'				=>	dateFormat($row[9]),
			'price'				=>	pricecheck($row[17],$sqft),
			'photo'				=>	$photo,
			'bedrooms'			=>	$row[42],
			'baths'				=>	$row[43],
			'sq_ft'				=>	$sqft,
			'lot_size'			=>	escapeData($mysqli,$row[38]),
			'latitude'			=>	$row[26],
			'longitude'			=>	$row[25],
			'pool'				=>	$pool,
			'description'		=>	escapeData($mysqli,$row[15]),
			'address'			=>	$row[20].' '.escapeData($mysqli,$row[21]).' '.$row[22],
			'city'				=>	getLocation('city',$zip,$row[27]),
			'county'			=>	getLocation('county',$zip,$row[32]),
			'state'				=>	$row[1],
			'zip'				=>	$zip,
			'subdivision'		=>	escapeData($mysqli,$row[34]),
			'year_built'		=>	$row[36],
			'HOA_fees'			=>	$row[57],
			'tax'				=>	$row[61],
			'agent_name'		=>	nameFormat(escapeData($mysqli,$row[7])),
			'agent_office'		=>	escapeData($mysqli,$row[4]),
			'agent_phone'		=>	phoneFormat($row[5]),
			'interior_kitchen'	=>	getFeatures('interior_kitchen',$features),
			'interior_misc'		=>	getFeatures('interior_misc',$features),
			'interior_bedbath'	=>	getFeatures('interior_bedbath',$features),
			'interior_other'	=>	getFeatures('interior_other',$features),
			'interior_floor'	=>	getFeatures('interior_floor',$features),
			'interior_window'	=>	getFeatures('interior_window',$features),
			'exterior_lot'		=>	getFeatures('exterior_lot',$features),
			'exterior_dwell'	=>	getFeatures('exterior_dwell',$features),
			'exterior_const'	=>	getFeatures('exterior_const',$features),
			'exterior_park'		=>	getFeatures('exterior_park',$features),
			'exterior_roof'		=>	getFeatures('exterior_roof',$features),
			'exterior_pool'		=>	$pool_description,
			'exterior_water'	=>	getFeatures('exterior_water',$features),
			'exterior_misc'		=>	getFeatures('exterior_misc',$features),
			'utility_cool'		=>	getFeatures('utility_cool',$features),
			'utility_heat'		=>	getFeatures('utility_heat',$features),
			'utility_energy'	=>	getFeatures('utility_energy',$features),
			'utility_sewer'		=>	getFeatures('utility_sewer',$features),
			'utility_water'		=>	getFeatures('utility_water',$features),
			'amenities'			=>	getAmenities($row[84]),
			'photo_count'		=>	$row[88],
			'photo_url'			=>	$row[89]
		);

		// geocode lookup
		if($tmp['latitude']=='' || $tmp['longitude']=='' || $tmp['latitude']=='0' || $tmp['longitude']=='0') {
			$dat['bunk_latlon']++;		// data testing
			$dat=geocheck($tmp,$mysqli,$dat,$fh);
			
		} else {
			writeProperty($tmp,$fh);
		}
	} else {
		// data testing ////////////////////////////////////////////
		if(!isset($row[84])) {
			$dat['bunk_data']++;
			continue;
		}
		if($row[17]=='' || $row[17]=='0') {
			$dat['price']++;
			continue;
		}
		if($row[13]=='V' || $row[13]=='C') {
			$dat['vc']++;
			continue;
		}
		if(strlen($row[29])<'5') {
			$dat['zips']++;
			continue;
		}
		////////////////////////////////////////////////////////////
	}
}
fclose($fh);
fclose($handle);

/* INSERT *********************************************************/
$sql = 'TRUNCATE TABLE `shadow`';
query($mysqli,$sql);
// opens data file and creates sql calls
$load="LOAD DATA LOCAL INFILE '".ROOT."_dataset/data/idx/".$db."/".$curdate."_data.txt' IGNORE INTO TABLE `shadow` FIELDS TERMINATED BY '|' ENCLOSED BY '\"' ESCAPED BY '\\\\' LINES TERMINATED BY '\n'";
query($mysqli,$load);

/* TIMER **********************************************************/
echo "\n";
$stopserver = getmicrotime(1);
$servertime = $stopserver - $startserver;
$run_time=round($servertime, 2);
echo "processed in: ".$run_time." seconds\n";

/* SHADOWING ******************************************************/
$sql = 'SELECT COUNT(mlsID) AS COUNT FROM `main`';
$entries = mysqli_fetch_array(query($mysqli,$sql));
$sql = 'SELECT COUNT(mlsID) AS COUNT FROM `shadow`';
$shadow = mysqli_fetch_array(query($mysqli,$sql));

if($shadow[0]<($entries[0]*0.7)){
	flawreports($db,$dat,$entries[0],$run_time);
}
else{
	$sql = 'TRUNCATE TABLE `'.$db.'`.`main`';
	query($mysqli,$sql);
	$sql = 'INSERT INTO `'.$db.'`.`main` SELECT * FROM `'.$db.'`.`shadow`';
	query($mysqli,$sql);
	runreports($db,$dat,$entries[0],$run_time);
}
/******************************************************************/

/* SUMMARY ********************************************************/
// take care of mls summary listings
require(ROOT.'_dataset/scripts/generic/mls_listing.php');

// add counts to state
require(ROOT.'_dataset/scripts/generic/mls_state.php');

// add counts to city
require(ROOT.'_dataset/scripts/generic/mls_city.php');

// upate last_update
require(ROOT.'_dataset/scripts/generic/board.php');

echo "finished\n";
/******************************************************************************************/

?>
