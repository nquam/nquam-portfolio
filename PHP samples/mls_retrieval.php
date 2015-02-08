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
$boardID = '5759';
$state = 'MA';
$db = 'PM_ma_mlspin';
$mls_type = 'rets';
$zip_array = array();
populateZips($mysqli,$state,$zip_array);    // create zip array for this state
$dat=array('bunk_latlon'=>0,'bunk_geo'=>0,'bunk_address'=>0,'bunk_data'=>0,'price'=>0,'vc'=>0,'zips'=>0,'rows'=>0);

/* SCRIPT *********************************************************/
echo "Starting...\n\n";
$mysqli->select_db($db) or die($mysqli->error); // change to MLS feed DB
$q_class=array('SF','MF','CC','RN','MH');    // Type classification. SF=Single Family
$row[0]='';

$curdate=date("m-d-Y");

$myFile = ROOT.'_dataset/data/rets/'.$db.'/'.$curdate.'_data.txt';
$fh = fopen($myFile, 'w');
$myFile = ROOT.'_dataset/data/rets/'.$db.'/'.$curdate.'_data.txt';
$fh = fopen($myFile, 'w') or die("can't open file");

for($p=0;$p<=4;$p++){
    echo "connect mlspin ".$q_class[$p]."\n";
    $dat=rconnect($q_class[$p],$mysqli,$fh,$dat);
}
fclose($fh);

echo "retrieval finished.\n";

/* INSERT *********************************************************/
$sql = 'TRUNCATE TABLE `shadow`';
query($mysqli,$sql);
// opens data file and creates sql calls
$load="LOAD DATA LOCAL INFILE '".ROOT."_dataset/data/rets/".$db."/".$curdate."_data.txt' IGNORE INTO TABLE `shadow` FIELDS TERMINATED BY '|' ENCLOSED BY '\"' ESCAPED BY '\\\\' LINES TERMINATED BY '\n'";
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

if($shadow[0]<($entries[0]*0.75)){
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

/* FUNCTIONS *****************************************************/
//Connect to RETS DB
function rconnect($q_class,$mysqli,$fh,$dat){
    /* CONFIG ************************/
    $server = 'rets.mlspin.com';
    $port = 80;
    
    $username = '*******';
    $password = '*******';
    $mlsid = '*****';
    
    $user_agent = 'CEL/1.8';
    
    $login_area = 'GET /login/index.asp HTTP/1.1';
    $login_uri = '/login/index.asp';
    $search_uri = '/search/index.asp';
    
    $rets_version = 'RETS/1.0';
    
    $qop = 'auth';
    $nc = '00000001';
    
    $nonce = '*******';
    $opaque = '*******';
    
    // this is the select of what we actually use
    $q_select['SF'] = 'LIST_NO,ACRE,LIST_AGENT,LIST_DATE,LIST_OFFICE,LIST_PRICE,NO_BEDROOMS,NO_FULL_BATHS,REMARKS,SQUARE_FEET,STREET_NO,STREET_NAME,SUB_AREA,TAXES,TOWN_NUM,YEAR_BUILT,ZIP_CODE,LAT,LONG,PHOTO_COUNT,NO_HALF_BATHS,AMENITIES,HOA_FEE,APPLIANCES,BEACH_DESCRIPTION,BED2_DSCRP,BED3_DSCRP,BED4_DSCRP,BED5_DSCRP,BTH1_DSCRP,BTH2_DSCRP,BTH3_DSCRP,CONSTRUCTION,COOLING,DIN_DSCRP,ENERGY_FEATURES,EXTERIOR,EXTERIOR_FEATURES,FAM_DSCRP,FLOORING,FOUNDATION,GARAGE_PARKING,GARAGE_SPACES,HEATING,INTERIOR_FEATURES,KIT_DSCRP,LAUNDRY_DSCRP,LIV_DSCRP,LOT_DESCRIPTION,MBR_DSCRP,OTH1_DSCRP,OTH2_DSCRP,OTH3_DSCRP,OTH4_DSCRP,OTH5_DSCRP,OTH6_DSCRP,PARKING_FEATURE,PARKING_SPACES,SEWER_AND_WATER,SF_TYPE,STYLE,WATERFRONT';
    $q_select['MF'] = 'LIST_NO,ACRE,LIST_AGENT,LIST_DATE,LIST_OFFICE,LIST_PRICE,NO_BEDROOMS,NO_FULL_BATHS,REMARKS,SQUARE_FEET,STREET_NO,STREET_NAME,SUB_AREA,TAXES,TOWN_NUM,YEAR_BUILT,ZIP_CODE,LAT,LONG,PHOTO_COUNT,NO_HALF_BATHS,AMENITIES,BEACH_DESCRIPTION,EXTERIOR,EXTERIOR_FEATURES,LOT_DESCRIPTION,PARKING_FEATURE,WATERFRONT,CONSTRUCTION,ENERGY_FEATURES,FLOORING,FOUNDATION,MF_TYPE,ROOF_MATERIAL,SEWER_AND_WATER';
    $q_select['CC'] = 'LIST_NO,ACRE,LIST_AGENT,LIST_DATE,LIST_OFFICE,LIST_PRICE,NO_BEDROOMS,NO_FULL_BATHS,REMARKS,SQUARE_FEET,STREET_NO,STREET_NAME,SUB_AREA,TAXES,TOWN_NUM,YEAR_BUILT,ZIP_CODE,LAT,LONG,PHOTO_COUNT,NO_HALF_BATHS,AMENITIES,HOA_FEE,APPLIANCES,ASSC_FEE_INCLUDES,BEACH_DESCRIPTION,BED2_DSCRP,BED3_DSCRP,BED4_DSCRP,BTH1_DSCRP,BTH2_DSCRP,COOLING,CONSTRUCTION,DIN_DSCRP,ENERGY_FEATURES,EXTERIOR,EXTERIOR_UNIT_FEATURES,FAM_DSCRP,FLOORING,HEATING,INTERIOR_FEATURES,KIT_DSCRP,LAUNDRY_DSCRP,LIV_DSCRP,MBR_DSCRP,OTH1_DSCRP,OTH2_DSCRP,PARKING_FEATURE,POOL_DESCRIPTION,ROOF_MATERIAL,SEWER_AND_WATER,WATERFRONT';
    $q_select['RN'] = 'LIST_NO,ACRE,LIST_AGENT,LIST_DATE,LIST_OFFICE,LIST_PRICE,NO_BEDROOMS,NO_FULL_BATHS,REMARKS,SQUARE_FEET,STREET_NO,STREET_NAME,SUB_AREA,TAXES,TOWN_NUM,YEAR_BUILT,ZIP_CODE,LAT,LONG,PHOTO_COUNT,NO_HALF_BATHS,AMENITIES,APPLIANCES,EXTERIOR_FEATURES,INTERIOR_BLDG_FEAT,RENT_FEE_INCLUDES';
    $q_select['MH'] = 'LIST_NO,ACRE,LIST_AGENT,LIST_DATE,LIST_OFFICE,LIST_PRICE,NO_BEDROOMS,NO_FULL_BATHS,REMARKS,SQUARE_FEET,STREET_NO,STREET_NAME,SUB_AREA,TAXES,TOWN_NUM,YEAR_BUILT,ZIP_CODE,LAT,LONG,PHOTO_COUNT,NO_HALF_BATHS,AMENITIES,HOA_FEE,AMENITIES,APPLIANCES,BASEMENT_FEATURE,BEACH_DESCRIPTION,BED2_DSCRP,BED3_DSCRP,BED4_DSCRP,BED5_DSCRP,BTH1_DSCRP,BTH2_DSCRP,BTH3_DSCRP,CONSTRUCTION,COOLING';
    
    $q_query = '(PROP_TYPE=\''.$q_class.'\'),(STATUS=ACT)';
    $q_search_type = 'Property';
    $q_format = 'COMPACT-DECODED'; // What format will be requested
    $q_type = 'DMQL2';
    $q_limit = ''; // limits how many records will be grabbed, use for debug otherwise leave empty
    $q_count = '0';
    /******************************************************************/
    
    // A1/A2/resp is used in the RETS Authorization
    $A1 = md5($username.':'.$mlsid.':'.$password);
    $A2 = md5('GET:'.$login_uri);
    $resp = md5($A1.':'.$nonce.':'.$A2);
    
    // Request connection
    $out = 'GET '.$login_uri.' HTTP/1.1'."\r\n";
    $out .= 'RETS-Version: '.$rets_version."\r\n";
    $out .= 'Authorization: Digest username="'.$username.'", realm="'.$mlsid.'", nonce="'.$nonce.'", opaque="'.$opaque.'", uri="'.$login_uri.'", response="'.$resp.'"'." \r\n";
    $out .= 'Host: '.$server.':'.$port."\r\n";
    $out .= "Accept: */*\r\n";
    $out .= 'User-Agent: '.$user_agent."\r\n";
    $response = RETS_CONNECT_LOGIN($server, $port, $out);
    $cookie = extractText($response,'Set-Cookie: ',';');
    
    
    $query = 'Class='.$q_class.'&Select='.$q_select[$q_class].'&Query='.$q_query.'&SearchType='.$q_search_type.'&Format='.$q_format.'&QueryType='.$q_type.'&Limit='.$q_limit.'&Count='.$q_count;
    $uri = $search_uri.'?'.$query;
    $out = 'GET '.$uri." HTTP/1.1\r\n";
    $out .= 'RETS-Version: '.$rets_version."\r\n";
    $out .= 'Host: '.$server.':'.$port."\r\n";
    $out .= "Accept: */*\r\n";
    $out .= 'User-Agent: '.$user_agent."\r\n";
    $out .= 'Cookie: '.$cookie."\r\n";
    $dat = RETS_CONNECT($server, $port, $out, $q_class,$mysqli,$fh,$dat);
    
    return $dat;
}

// RETS login scripts
function RETS_CONNECT_LOGIN($server, $port, $out) {
    // This ensures that all connections are closed.
    $out .= "Connection: Close\r\n\r\n";

    // Creates socket connection to the RETS server
    $fp = fsockopen($server,$port,$errno,$errstr,30);

    // IMPORTANT - This checks to make sure the socket connection is a valid socket connection
    // If this wasn't done, and the connection wasn't a valid resource it can send the script into
    // an endless loop and bomb the server
    if(!is_resource($fp)){
        die();
    }
    fputs($fp, $out);
    
    $response = '';
    while (!feof($fp)) {
        $response .= fgets($fp, 1000000);
        $reponse;
    }
    fclose($fp);
    return $response;
} 

// RETS pulldown script
function RETS_CONNECT($server, $port, $out, $q_class,$mysqli,$fh,$dat) {
    // This ensures that all connections are closed.
    $out .= "Connection: Close\r\n\r\n";

    // Creates socket connection to the RETS server
    $fp = fsockopen($server,$port,$errno,$errstr,30);

    // IMPORTANT - This checks to make sure the socket connection is a valid socket connection
    // If this wasn't done, and the connection wasn't a valid resource it can send the script into
    // an endless loop and bomb the server
    if(!is_resource($fp)){
        die();
    }
    fputs($fp, $out);
    $rootarea=ROOT;
    $response = '';
    while (!feof($fp)) {
        $response = fgets($fp, 1000000);
        $dat=writedata($response,$q_class,$mysqli,$fh,$dat);
    }

    return $dat;
} 

// Check & write data to storage file
function writedata($resp,$q_class,$mysqli,$fh,$dat){
    $prop_type=array('SF','MF','CC','RN','MH');     // Property types available
    $st=array('SF'=>21,'MF'=>21,'CC'=>21,'RN'=>21,'MH'=>21);        // This is the row where the features start
    $end=array('SF'=>61,'MF'=>34,'CC'=>51,'RN'=>25,'MH'=>35);       // This is where the features end
    $prop_conv=array('SF'=>'S','MF'=>'D','CC'=>'M','RN'=>'R','MH'=>'B');    // Converts the rets types to our DB types

    $row = (explode("\t",$resp));
    
    if($row[0]=='<DATA>'){
        $dat['rows']++;    // data testing
        if(isset($row[1]) && $row[1]!=''        // data is not bunk for mlsid
        && $row[6]!='' && $row[6]!='0'        // price not equal 0
        && strlen($row[17])>='5'                // don't include empty zips
        ) {
            // grabs real city name from town database
            $sql = 'SELECT city,county,state FROM `towns` WHERE `town_num` = '.$row[15].'';
            $cityfind = mysqli_fetch_array(query($mysqli,$sql));
            // grabs real office name from offices database
            $sql = 'SELECT name FROM `offices` WHERE `ID` = "'.$row[5].'"';
            $officefind = mysqli_fetch_array(query($mysqli,$sql));
            // grabs real agent name from agents database
            $sql = 'SELECT first_name,last_name FROM `agents` WHERE `ID` = "'.$row[3].'"';
            $agentfind = mysqli_fetch_array(query($mysqli,$sql));

            // Check half baths
            if($row[21] >= 6){
                $half_bath=0;
            }
            else{$half_bath = $row[21];}
            
            // get property features
            $totalfeature = retsfeatures($row,$st[$q_class],$end[$q_class]);    // find in common
            if($totalfeature[12]!=''){
                $pool='Y';
            } else {$pool='N';}
            
            // minor var massaging
            $zip = substr($row[17],0,5);    // only get the 5 digit zip
            if($row[10]>=20000) { $sqft=0; } else {$sqft=$row[10];}
            // populate array
            // ORDER MUST BE EXACT SAME STRUCTURE AS DB
            $tmp = array(
            'mlsID'                =>    $row[1],
            'property_type'    =>    $prop_conv[$q_class],
            'date'                =>    retsdateFormat($row[4]), // YYYY-MM-DD
            'price'                =>    pricecheck($row[6],$sqft),
            'photo'                =>    'Y',
            'bedrooms'            =>    $row[7],
            'baths'                =>    $row[8],
            'half_baths'        => $half_bath,
            'sq_ft'                =>    $sqft,
            'lot_size'            =>    escapeData($mysqli,$row[2]),
            'latitude'            =>    $row[18],
            'longitude'            =>    $row[19],
            'pool'                =>    $pool,
            'description'        =>    escapeData($mysqli,$row[9]),
            'address'            =>    $row[11].' '.escapeData($mysqli,$row[12]).' '.$row[13],
            'city'                =>    getLocation('city',$zip,$cityfind['city']),
            'county'                =>    getLocation('county',$zip,$cityfind['county']),
            'state'                =>    $cityfind['state'],
            'zip'                    =>    $zip,
            'photo_count'        => $row[20],
            'subdivision'        =>    '',
            'year_built'        =>    $row[16],
            'HOA_fees'            =>    $row[23],
            'tax'                    =>    $row[14],
            'agent_name'        =>    nameFormat(escapeData($mysqli,$agentfind['first_name']." ".$agentfind['last_name'])),
            'agent_office'        =>    escapeData($mysqli,$officefind['name']),
            'agent_phone'        => '',
            'interior_kitchen'=> $totalfeature[0],
            'interior_misc'    =>    $totalfeature[1],
            'interior_other'    =>    $totalfeature[2],
            'interior_bedbath'=>    $totalfeature[3],
            'interior_floor'    =>    $totalfeature[4],
            'interior_window'    =>    $totalfeature[5],
            'exterior_lot'        =>    $totalfeature[6],
            'exterior_dwell'    =>    $totalfeature[7],
            'exterior_const'    =>    $totalfeature[8],
            'exterior_park'    =>    $totalfeature[9],
            'exterior_roof'    =>    $totalfeature[10],
            'exterior_pool'    =>    $totalfeature[11],
            'exterior_water'    =>    $totalfeature[12],
            'exterior_misc'    =>    $totalfeature[13],
            'utility_cool'        =>    $totalfeature[14],
            'utility_heat'        =>    $totalfeature[15],
            'utility_energy'    =>    $totalfeature[16],
            'utility_sewer'    =>    $totalfeature[17],
            'utility_water'    =>    $totalfeature[18],
            'amenities'            =>    $totalfeature[19],
            );
            if($tmp['price']<100){
                $dat['price']++;
            }
            if($tmp['HOA_fees'] >= 9000){
                $tmp['HOA_fees'] = 0;
            }
            if($tmp['tax'] >= 900000){
                $tmp['tax'] = 0;
            }
            if($row[18]==0||$row[19]==0||$row[18]==''||$row[19]=='' || $tmp['longitude']<=-125.244 || $tmp['longitude']>=-66.835 || $tmp['latitude']<=24.512 || $tmp['latitude']>=49.418){    // lat&lon not empty
                $dat['bunk_latlon']++;
                $dat=geocheck($tmp,$mysqli,$dat,$fh);
            
        } else {
            writeProperty($tmp,$fh);
            }
                } else {
                    // data testing ////////////////////////////////////////////
                    if(!isset($row[1])) {    //mlsid
                        $dat['bunk_data']++;
                    }
                    if($row[6]=='' || $row[6]=='0') {    //price
                        $dat['price']++;
                    }
                    if(strlen($row[17])<'5') {    //zip
                        $dat['zips']++;
                    }
                    ////////////////////////////////////////////////////////////
                }
        }
            return $dat;
}

function extractText($content,$start,$end,$reverse=false) {
    if(strrpos($content,$start)===false) {
        return false;
    }
    $startpoint = ($reverse) ? strrpos($content,$start)+strlen($start) : strpos($content,$start)+strlen($start);
    $endpoint = strpos($content,$end,$startpoint);
    $length = $endpoint - $startpoint;
    
    return trim(substr($content,$startpoint,$length));
}

// Changes date to UNIX timestamp
function retsdateFormat($string) {
    if($string!='') {
        $temp = explode('-',$string);
        $string = mktime(0,0,0,$temp[1],$temp[2],$temp[0]); // MM,DD,YYYY
    }
    return $string;
}
/**************************************************************/
?>