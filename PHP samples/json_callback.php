<?

/* VARS ***********************************************************/
$db_main = 'PM_dev_main';
/******************************************************************/

/* DB *************************************************************/
$mysqli = new mysqli("localhost", "******", "********", $db_main);
if (mysqli_connect_errno()) {
   printf("Connect failed: %s\n", mysqli_connect_error());
   exit();
}
/******************************************************************/

$resp = (isset($_REQUEST['json'])) ? $_REQUEST['json']: ''; // gets JSON code from url
$resp = json_decode($resp, true);


/* QUERY SETUP ***************************************************/
$db = 'PM_dev_main';
$where = 'WHERE `longitude` >= "'.$resp['xMin'].'" && `longitude` <= "'.$resp['xMax'].'" && `latitude` >= "'.$resp['yMin'].'" && `latitude` <= "'.$resp['yMax'].'"';
$limit = 'LIMIT 50'; // 2 seconds or less
$offset = 'OFFSET 0';
$sql='SELECT `listingID`,`boardID`,`mlsID`,`city`,`county`,`state`,`zip`,`property_type`,`price`,`bedrooms`,`baths`,`sq_ft`,`latitude`,`longitude`,`pool` FROM `'.$db.'`.`mls_listing` '.$where.' ORDER BY `mlsID` '.$limit.' '.$offset;

/*****************************************************************/

$store = doquery($sql,$mysqli,$resp); // grabs from DB and checks if in polygon
echo json_encode($store); // returns results via AJAX in JSON encoding


/* FUNCTIONS ***************************************************/
function doquery($sql,$mysqli,$resp){
	$result = query($mysqli,$sql);
//	echo $result->num_rows;	
	$store = array( 
		"keys" => array("listingID","mlsID","latitude","longitude"), 
		"data" => array());
		
	$i = 0;
	
	while($data = mysqli_fetch_assoc($result)) {
		
		foreach($store["keys"] as $key){
			$store["data"][] = $data[$key];
		}
		
		if(isPointInPolygon($resp['lng'],$resp['lat'],$data['longitude'],$data['latitude'])){
			$store["data"][] = 'true';
		} else {
			$store["data"][] = 'false';
		}			
		$i++;		
		
	}
	$store["keys"][] = "statuss";
	$store["matches"] = $result->num_rows;
	return $store;
}


function query(mysqli $mysqli, $sql) { // this should be replaced with dbclass
	if(!$result = $mysqli->query($sql)) {
		echo $mysqli->error.'<br>';
		echo $sql.'<br><br>';
	}
	return $result;
}

/**
 * Function to check if a point is contained inside a polygon
 *
 * @var		array	x coords of polygon
 * @var		array	y coords of polygon
 * @var		float	x coord of point
 * @var		float	y coord of point
 * @return	bool
 */

function isPointInPolygon($xp,$yp,$x,$y){
        $num = count($xp);
        $isInside = false;
        for ( $i = 0, $j = $num-1; $i < $num; $j = $i++){
                if (((($yp[$i] <= $y) && ($y<$yp[$j])) ||
                        (($yp[$j]<=$y) && ($y<$yp[$i]))) &&
                        ($x < ($xp[$j] - $xp[$i]) * ($y - $yp[$i]) / ($yp[$j] - $yp[$i]) + $xp[$i]))
                { 
                	$isInside = !($isInside);
				} 

        }
        return $isInside;
}
/******************************************************************/
?>
