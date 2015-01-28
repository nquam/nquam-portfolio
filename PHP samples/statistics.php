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
/******************************************************************/

$db_list=array('PM_ca_bareis','PM_ca_merced_count','PM_ca_san_francisc','PM_fl_manatee',
	'PM_fl_melbourne_ar','PM_fl_mid_regional','PM_fl_punta_gorda_','PM_fl_sarasota',
	'PM_fl_venice_area','PM_il_mlsni','PM_ma_mlspin','PM_tx_austin');
$prop_count=0;
$rent_count=0;
$res_count=0;
$mobile_count=0;
$multi_count=0;
$condo_count=0;
$city_count=0;
$state_count=0;

$curtime = mktime();

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

// Total number of brokers
$sql="SELECT COUNT(`boardID`) AS COUNT FROM `board` WHERE `status` = '1'";
$result = mysqli_fetch_assoc(query($mysqli,$sql));
$broker_count=$result['COUNT'];

// Total number of users
$sql="SELECT COUNT('userID') AS COUNT FROM `PM_live_main`.`user`";
$result = mysqli_fetch_assoc(query($mysqli,$sql));
$user_count=$result['COUNT'];

// Total number of leads

// Total by city
$sql="SELECT `boardID`,`state`,`name`,`count`,`count_S`,`count_M`,`count_D`,`count_R`,`count_B` FROM `PM_live_main`.`mls_city` WHERE `count`>0 ORDER BY `state`";
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
	$i++;
}
// Insert total_counts
/************************************************************/


?>








<html>
	<style>
		.td1{border:1px solid #c0c0c0;background:#cccccc;padding-left:3px;font-family:arial;font-size:11px}
		.td2{border:1px solid #c0c0c0;width:200px;padding-left:3px;font-family:arial;font-size:11px;padding-bottom:25px}
		.td3{border:1px solid #c0c0c0;width:100px;padding-left:3px;font-family:arial;font-size:11px;padding-bottom:25px}
		.td31{border:1px solid #c0c0c0;background:#eeeeee;width:200px;padding-left:3px;font-family:arial;font-size:11px;padding-bottom:25px}
	</style>
	<body>

Current Data Statistics<br>
		<div style="border:solid 1px black;width:300px;align:left;position:relative">
			<table cellspacing='0' cellpadding=2 width='300px'>
				<tr>
					<td class="td1">Total property listings</td>
				</tr>
				<tr>
					<td class="td2"><? echo number_format($prop_count) ?></td>
				</tr>
				<tr>
					<td class="td1">Total Residential property listings</td>
				</tr>
				<tr>
					<td class="td2"><? echo number_format($res_count) ?></td>
				</tr>
				<tr>
					<td class="td1">Total Condo/Townhome property listings</td>
				</tr>
				<tr>
					<td class="td2"><? echo number_format($condo_count) ?></td>
				</tr>
				<tr>
					<td class="td1">Total Multi Family property listings</td>
				</tr>
				<tr>
					<td class="td2"><? echo number_format($multi_count) ?></td>
				</tr>
				<tr>
					<td class="td1">Total Rental property listings</td>
				</tr>
				<tr>
					<td class="td2"><? echo number_format($rent_count) ?></td>
				</tr>
				<tr>
					<td class="td1">Total Mobile Home property listings</td>
				</tr>
				<tr>
					<td class="td2"><? echo number_format($mobile_count) ?></td>
				</tr>
				<tr>
					<td class="td1">Cities with listings</td>
				</tr>
				<tr>
					<td class="td2"><? echo number_format($city_count) ?></td>
				</tr>
				<tr>
					<td class="td1">States with listings</td>
				</tr>
				<tr>
					<td class="td2"><? echo $state_count ?></td>
				</tr>
				<tr>
					<td class="td1">Number of Brokers</td>
				</tr>
				<tr>
					<td class="td2"><? echo $broker_count ?></td>
				</tr>
				<tr>
					<td class="td1">Number of Users</td>
				</tr>
				<tr>
					<td class="td2"><? echo $user_count ?></td>
				</tr>
			</table>
		</div>
		<hr>
		<div style="align:right;position:relative">
			<table cellpadding=0 cellspacing=0>
				<tr>
					<td class="td1">State</td>
					<td class="td1">Total Count</td>
					<td class="td1">Single Family</td>
					<td class="td1">Condo/Town Home</td>
					<td class="td1">Multi-Family</td>
					<td class="td1">Rental</td>
					<td class="td1">Mobile Home</td>
				</tr>
				<?
				for($i=0;$i<count($state_store['state']);$i++){
					echo '<tr>';
					echo '<td class="td3">'.$state_store['name'][$i].'</td>';
					echo '<td class="td3">'.$state_store['count'][$i].'</td>';
					echo '<td class="td3">'.$state_store['count_S'][$i].'</td>';
					echo '<td class="td3">'.$state_store['count_M'][$i].'</td>';
					echo '<td class="td3">'.$state_store['count_D'][$i].'</td>';
					echo '<td class="td3">'.$state_store['count_R'][$i].'</td>';
					echo '<td class="td3">'.$state_store['count_B'][$i].'</td>';
					echo '</tr>';
				}
				?>
			</table>
		</div>
		<hr>
		<div style="align:right;position:relative">
			<table cellpadding=0 cellspacing=0>
				<tr>
					<td class="td1">State</td>
					<td class="td1">City</td>
					<td class="td1">Count</td>
				</tr>
				<?
				for($i=0;$i<count($store['name']);$i++){
					echo '<tr>';
					echo '<td class="td3">'.$store['state'][$i].'</td>';
					echo '<td class="td31">'.$store['name'][$i].'</td>';
					echo '<td class="td3">'.$store['count'][$i].'</td>';
					echo '</tr>';
				}
				?>
			</table>
		</div>
	</body>
</html>