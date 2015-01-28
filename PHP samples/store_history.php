<?
/******************************************************************
* This file stores a history for each day
******************************************************************/

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
/******************************************************************/

$prop_count=0;
$rent_count=0;
$res_count=0;
$mobile_count=0;
$multi_count=0;
$condo_count=0;
$city_count=0;
$state_count=0;

$curdate = mktime();

// Total number of properties & total_by_state numbers
$sql="SELECT `refID`,`abbr`,`name`,`count`,`count_S`,`count_M`,`count_D`,`count_R`,`count_B` FROM `PM_live_main`.`mls_state` WHERE `count`>0";
$result = query($mysqli,$sql);
$i=0;
while($data = mysqli_fetch_assoc($result)) {
	$store_state['refID'][$i] = $data['refID'];
	$store_state['abbr'][$i] = $data['abbr'];
	$store_state['name'][$i] = $data['name'];
	$store_state['count'][$i] = $data['count'];
	$store_state['count_S'][$i] = $data['count_S'];
	$store_state['count_M'][$i] = $data['count_M'];
	$store_state['count_D'][$i] = $data['count_D'];
	$store_state['count_R'][$i] = $data['count_R'];
	$store_state['count_B'][$i] = $data['count_B'];
	$prop_count+=$data['count'];
	$i++;
}

// Total number of properties viewed
$sql="SELECT COUNT(`counterID`) AS COUNT FROM `PM_live_main`.`lead_counter` WHERE `type` = 'detail'";
$result = mysqli_fetch_assoc(query($mysqli,$sql));
$prop_views=$result['COUNT'];

// Total number of Residential properties
$sql="SELECT `count_S` FROM `PM_live_main`.`mls_state` WHERE `count`>0";
$result = query($mysqli,$sql);
while($data = mysqli_fetch_assoc($result)) {
$res_count+=$data['count_S'];
}

// Total number of Condo properties
$sql="SELECT `count_M` FROM `PM_live_main`.`mls_state` WHERE `count`>0";
$result = query($mysqli,$sql);
while($data = mysqli_fetch_assoc($result)) {
$condo_count+=$data['count_M'];
}

// Total number of Multifamily properties
$sql="SELECT `count_D` FROM `PM_live_main`.`mls_state` WHERE `count`>0";
$result = query($mysqli,$sql);
while($data = mysqli_fetch_assoc($result)) {
$multi_count+=$data['count_D'];
}

// Total number of Rental properties
$sql="SELECT `count_R` FROM `PM_live_main`.`mls_state` WHERE `count`>0";
$result = query($mysqli,$sql);
while($data = mysqli_fetch_assoc($result)) {
$rent_count+=$data['count_R'];
}

// Total number of Mobile Home properties
$sql="SELECT `count_B` FROM `PM_live_main`.`mls_state` WHERE `count`>0";
$result = query($mysqli,$sql);
while($data = mysqli_fetch_assoc($result)) {
$mobile_count+=$data['count_B'];
}

// Total number of cities with properties
$sql="SELECT COUNT('name') AS COUNT FROM `PM_live_main`.`mls_city`";
$result = mysqli_fetch_assoc(query($mysqli,$sql));
$city_count=$result['COUNT'];

// Total number of states with properties
$sql="SELECT COUNT('name') AS COUNT FROM `PM_live_main`.`mls_state`";
$result = mysqli_fetch_assoc(query($mysqli,$sql));
$state_count=$result['COUNT'];

// Total number of affiliate brokers
$sql="SELECT COUNT(`brokerID`) AS COUNT FROM `PM_live_main`.`broker` WHERE `status` = 'Affiliate'";
$result = mysqli_fetch_assoc(query($mysqli,$sql));
$broker_affiliate=$result['COUNT'];

// Total number of brokers agreement signed
$sql="SELECT COUNT(`brokerID`) AS COUNT FROM `PM_live_main`.`broker` WHERE `status` = 'Agreement Signed'";
$result = mysqli_fetch_assoc(query($mysqli,$sql));
$broker_agreement=$result['COUNT'];

// Total number of brokers signed contract
$sql="SELECT COUNT(`brokerID`) AS COUNT FROM `PM_live_main`.`broker` WHERE `status` = 'Contract Signed'";
$result = mysqli_fetch_assoc(query($mysqli,$sql));
$broker_contract=$result['COUNT'];

// Total number of users
$sql="SELECT COUNT('userID') AS COUNT FROM `PM_live_main`.`user` WHERE `role` = 'user'";
$result = mysqli_fetch_assoc(query($mysqli,$sql));
$user_count=$result['COUNT'];

// Total number of leads
$sql="SELECT COUNT('callbackID') AS COUNT FROM `PM_live_main`.`lead_callback`";
$result = mysqli_fetch_assoc(query($mysqli,$sql));
$lead_count=$result['COUNT'];
$sql="SELECT COUNT('emailID') AS COUNT FROM `PM_live_main`.`lead_email`";
$result = mysqli_fetch_assoc(query($mysqli,$sql));
$lead_count+=$result['COUNT'];
$sql="SELECT COUNT('messageID') AS COUNT FROM `PM_live_main`.`lead_message`";
$result = mysqli_fetch_assoc(query($mysqli,$sql));
$lead_count+=$result['COUNT'];

// Total by city
$sql="SELECT `boardID`,`state`,`name`,`count`,`count_S`,`count_M`,`count_D`,`count_R`,`count_B` FROM `PM_live_main`.`mls_city` WHERE `count`>0 ORDER 
BY `state`";
$result = query($mysqli,$sql);
$i=0;
while($data = mysqli_fetch_assoc($result)) {
	$store['boardID'][$i] = $data['boardID'];
	$store['state'][$i] = $data['state'];
	$store['name'][$i] = $data['name'];
	$store['count'][$i] = $data['count'];
	$store['count_S'][$i] = $data['count_S'];
	$store['count_M'][$i] = $data['count_M'];
	$store['count_D'][$i] = $data['count_D'];
	$store['count_R'][$i] = $data['count_R'];
	$store['count_B'][$i] = $data['count_B'];
	$scount[$store['state'][$i]]+=1;
	$i++;
}

// Write to DB //
// Insert total_counts
echo $curdate;
$sql = 'INSERT INTO `PM_history`.`total_counts` (date,properties,prop_views,count_S,count_M,count_D,count_R,count_B,cities,states,broker_affiliate,broker_agreement,broker_contract,users,leads) VALUES('.$curdate.','.$prop_count.','.$prop_views.','.$res_count.','.$condo_count.','.$multi_count.','.$rent_count.','.$mobile_count.','.$city_count.','.$state_count.','.$broker_affiliate.','.$broker_agreement.','.$broker_contract.','.$user_count.','.$lead_count.')';
query($mysqli,$sql);

// Insert total_by_city
for($i=0;$i<count($store['name']);$i++){
	$sql = 'INSERT INTO `PM_history`.`total_by_city` (boardID,date,state,city,count,count_S,count_M,count_R,count_D,count_B) VALUES("'.$store['boardID'][$i].'","'.$curdate.'","'.$store['state'][$i].'","'.$store['name'][$i].'","'.$store['count'][$i].'","'.$store['count_S'][$i].'","'.$store['count_M'][$i].'","'.$store['count_R'][$i].'","'.$store['count_D'][$i].'","'.$store['count_B'][$i].'")';
	query($mysqli,$sql);
}

// Insert total_by_state
for($i=0;$i<count($store_state['name']);$i++){
	$sql = 'INSERT INTO `PM_history`.`total_by_state` (refID,date,state,count,count_S,count_M,count_R,count_D,count_B,total_cities) VALUES("'.$store_state['refID'][$i].'","'.$curdate.'","'.$store_state['abbr'][$i].'","'.$store_state['count'][$i].'","'.$store_state['count_S'][$i].'","'.$store_state['count_M'][$i].'","'.$store_state['count_R'][$i].'","'.$store_state['count_D'][$i].'","'.$store_state['count_B'][$i].'","'.$scount[$store_state['abbr'][$i]].'")';
	query($mysqli,$sql);
}
/************************************************************/
?>
