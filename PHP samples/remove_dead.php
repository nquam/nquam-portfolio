<?php
// Must include db, limit, and offset in your arg for this to work

/* STATICS ********************************************************/
chdir(dirname($_SERVER['argv'][0]));
define('ROOT',ereg_replace('_dataset\/.*','',getcwd()));

/******************************************************************/


/* INCLUDES *******************************************************/
require_once(ROOT.'_dataset/scripts/config.php');
require_once(ROOT.'_dataset/scripts/common.php');
require_once(ROOT.'fbx_listfunctions.php');
/******************************************************************/




$arg = $_SERVER['argv'][1];
$loc = array('x','s','s2','m','l');
$locex = array('x','s','l');
$totalmls = 0;
$totalcount = 0;
$removecount = 0;
$deadmls = 0;

if($arg=='all'){
	$command = "find /home/propmaps/media/PM_*/x/. -type f | wc -l";
	$output = shell_exec($command." 2>&1");  //system call
	$query = "SELECT * FROM `PM_live_main`.`live_feeds`";
	$result = mysqli_query($mysqli, $query);
	while($row = mysqli_fetch_array($result)){
		$db = $row['db_name'];
		$media_dir = "/home/propmaps/media/".$db."/x";
		$media_dir_ex = "/home/propmaps/media/".$db."_extras/x";
		$mysqli->select_db($db) or die($mysqli->error); // change to MLS feed DB
		$dirmn = "/home/propmaps/media/".$db;
		$direx = "/home/propmaps/media/".$db."_extras";
		if ($handle = opendir($media_dir)) {
			echo "look for dead in ".$db.".\n";
			$query = "SELECT `mlsID` FROM `".$db."`.`main` ORDER BY `mlsID`";
			$mls_result = mysqli_query($mysqli, $query);
			$count = 0;
			$store_mlsid = array();
			while($row = mysqli_fetch_array($mls_result)){
				$store_mlsid[$count]=$row['mlsID'];
				$count++;
			}
			
			while(false !== ($file = readdir($handle))){
				$length = strlen($file)-4;
				$mlsnum = substr($file, 0, $length);
				if($mlsnum != ''){
					$totalmls++;
					$mlsok = false;
					for($i = 0;count($store_mlsid) > $i;$i++){
						if($store_mlsid[$i] == $mlsnum){
							//echo "ml OK = ".$mlsnum."\n";
							$totalcount++;
							$i = 1000000;
							$mlsok = true;
						}
					}
					if(!$mlsok){
						echo $mlsnum." dead\n";
						$deadmls++;
						for($i = 0;$i < count($loc);$i++){
							$myFile = $dirmn.'/'.$loc[$i].'/'.$mlsnum.".jpg";
							if(file_exists($myFile)){
								$fh = fopen($myFile, 'w') or die("can't open file");
								fclose($fh);
								unlink($myFile);
								$removecount++;
							}
							$totalcount++;
						}
						for($i = 0;$i < count($locex);$i++){
							$myFile = $direx.'/'.$locex[$i].'/'.$mlsnum.".jpg";
							if(file_exists($myFile)){
								$fh = fopen($myFile, 'w') or die("can't open file");
								fclose($fh);
								unlink($myFile);
								echo $myFile."\n";
								$removecount++;
							}
							$totalcount++;
						}
					}
				}
			}
			closedir($handle);
		}
	}
	echo "Removed ".$removecount." images out of ".$output."\n";
}
elseif($arg != ''){
	$db = $arg;
	$command = "find /home/propmaps/media/".$db."*/x/. -type f | wc -l";
	$output = shell_exec($command." 2>&1");  //system call
	$media_dir = "/home/propmaps/media/".$db."/x";
	$media_dir_ex = "/home/propmaps/media/".$db."_extras/x";
	$mysqli->select_db($db) or die($mysqli->error); // change to MLS feed DB
	$dirmn = "/home/propmaps/media/".$db;
	$direx = "/home/propmaps/media/".$db."_extras";
	if ($handle = opendir($media_dir)) {
		echo "look for dead in ".$db.".\n";
		
		$query = "SELECT `mlsID` FROM `".$db."`.`main` ORDER BY `mlsID`";
		$result = mysqli_query($mysqli, $query);
		$count = 0;
		$store_mlsid = array();
		while($row = mysqli_fetch_array($result)){
			$store_mlsid[$count] = $row['mlsID'];
			$count++;
		}
		
		while(false !== ($file = readdir($handle))){
			$length = strlen($file)-4;
			$mlsnum = substr($file, 0, $length);
			if($mlsnum != ''){
				$totalmls++;
				$mlsok = false;
				for($i = 0;count($store_mlsid) > $i;$i++){
					if($store_mlsid[$i] == $mlsnum){
						//echo "ml OK=".$mlsnum."\n";
						$totalcount++;
						$i = 1000000;
						$mlsok = true;
					}
				}
				if(!$mlsok){
					echo $mlsnum." dead\n";
					$deadmls++;
					for($i = 0;$i < count($loc);$i++){
						$myFile = $dirmn.'/'.$loc[$i].'/'.$mlsnum.".jpg";
						if(file_exists($myFile)){
							$fh = fopen($myFile, 'w') or die("can't open file");
							fclose($fh);
							unlink($myFile);
							$removecount++;
						}
						$totalcount++;
					}
					for($i = 0;$i < count($locex);$i++){
						$myFile = $direx.'/'.$locex[$i].'/'.$mlsnum.".jpg";
						if(file_exists($myFile)){
							$fh = fopen($myFile, 'w') or die("can't open file");
							fclose($fh);
							unlink($myFile);
							echo $myFile."\n";
							$removecount++;
						}
						$totalcount++;
					}
				}
			}
		}
		closedir($handle);
	}
	echo "Removed ".$removecount." images out of ".$output."\n";
}
else{echo "You may have an incorrect arg\n";}
echo "Total MLS #s = ".$totalmls."\n";
echo "Dead MLS #s = ".$deadmls."\n";

?>