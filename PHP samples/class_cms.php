<?
/**
* @package CORE
* @version: class_cms.php,v 0.1 2007/11/20 nquam
*/

class class_cms {
	/*********************************************************
	PROPERTIES
	**********************************************************/
	/**
	* I am the core Data Access Object for generic access
	* @var object
	* @access private
	*/
	private $dbObj; //aggregate
	
	/*********************************************************
	CONSTRUCTOR/DESTRUCTOR
	**********************************************************/
	function __construct(class_db $dbObj) {
		$this->dbObj=$dbObj;
	}
	
	/*********************************************************
	PUBLIC
	**********************************************************/
	
	/**
	* Inserts a new PR entry into the cms table
	* @param string $date pass in unix timestamp
	* @param string $title 
	* @param string $subtitle
	* @param string $body
	* @access public
	*/
	public function insertPR($date,$title,$subtitle,$body,$pdf){
		$query = 'INSERT INTO `PM_dev_main`.`cms` (date,letter,title,subtitle,body,status,pdf_status)VALUES("'.$date.'","'.$this->makeletter($date).'","'.$this->dbObj->escapeData($title).'","'.$this->dbObj->escapeData($subtitle).'","'.$this->dbObj->escapeData($body).'","1","'.$pdf.'")';
		$this->dbObj->query($query);
	}
	
	/**
	* Updates the selected PR entry
	* @param int $prid
	* @param string $date pass in unix timestamp
	* @param string $title
	* @param string $subtitle
	* @param string $body
	* @access public
	*/
	public function updatePR($prid,$date,$title,$subtitle,$body){
//		$query = 'UPDATE `PM_dev_main`.`cms` SET `date` = "'.$date.'",`title` = "'.$this->dbObj->escapeData($title).'",`subtitle` = "'.$this->dbObj->escapeData($subtitle).'",`body` = "'.$this->dbObj->escapeData($body).'" WHERE `cms`.`PRID` = "'.$prid.'"';
		$query = 'UPDATE `PM_dev_main`.`cms` SET `date` = "'.$date.'",`title` = "'.$title.'",`subtitle` = "'.$subtitle.'",`body` = "'.$body.'" WHERE `cms`.`PRID` = "'.$prid.'"';
		$this->dbObj->query($query);
	}
	
	/**
	* Deletes a PR, currently changes status to 0 instead of actual delete
	* @param int $prid
	* @access public
	*/
	public function deletePR($prid){
		$query = 'UPDATE `PM_dev_main`.`cms` SET `status` = "0" WHERE `PRID` = '.$prid.' LIMIT 1';
		$this->dbObj->query($query);
		
		$result = $this->getLetter($prid);
		if($data = mysqli_fetch_assoc($result)) {
			$letter = $data['letter'];
		}
		
		$result = $this->getIDdate($prid);
		if($data = mysqli_fetch_assoc($result)) {
			$date_link = date("Y_m_d",$data['date']);
		}
		
		$file = $date_link.'_'.$letter;
		if(file_exists("/home/propmaps/media/press/".$file.".pdf")){
			unlink("/home/propmaps/media/press/".$file.".pdf");
		}
	}
	
	/**
	* Gets PR info indicated by prid to populate form for editing
	* @param int $prid
	* @return result array
	* @access public
	*/
	public function popForm($prid){
		$query = 'SELECT `PRID`,`date`,`title`,`subtitle`,`body` FROM `PM_dev_main`.`cms` WHERE `PRID` = "'.$prid.'"';
		return $this->dbObj->query($query);
	}
	
	/**
	* Gets all PR from the db for CMS display
	* @return result array
	* @access public
	*/
	public function getallPR(){
		$query = 'SELECT `title`,`PRID` FROM `PM_dev_main`.`cms` WHERE `status` = "1" ORDER BY `title` ASC';
		return $this->dbObj->query($query);
	}

	/**
	* Gets all PR for selected year from DB for posting on corporate.press
	* @param int $year_select year to retrieve
	* @return result array
	* @access public
	*/
	public function getPress($year_select){
		$query = 'SELECT `date`,`letter`,`title`,`subtitle`,`pdf_status` FROM `PM_dev_main`.`cms` WHERE `status` = "1" && `date` > UNIX_TIMESTAMP(DATE(\''.$year_select.'-01-01 00:00:00\')) && `date` < UNIX_TIMESTAMP(DATE(\''.($year_select+1).'-01-01 00:00:00\')) ORDER BY `date` DESC';
		return $this->dbObj->query($query);
	}
	
	/**
	* Gets a specific press release based on data from URL on corporate.press
	* @param string $post_date
	* @param string $letter
	* @return result array
	* @access public
	*/
	public function getRelease($post_date,$letter){
		$query = 'SELECT `date`,`title`,`subtitle`,`body`,`pdf_status` FROM `PM_dev_main`.`cms` WHERE `status` = "1" && `date` = "'.$post_date.'" && `letter` = "'.$letter.'" ORDER BY `date` DESC LIMIT 1';
		return $this->dbObj->query($query);
	}
		
	/**
	* Checks date against database to get letter
	* @param string $string
	* @return result string from array letter extension for url name
	* @access public
	*/
	public function makeletter($string){
		$query = 'SELECT `date` FROM `PM_dev_main`.`cms` WHERE `date` = "'.$string.'"';
		$result = $this->dbObj->query($query);
		
		$letter_array = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
		$count = 0;
		while($data = mysqli_fetch_assoc($result)) {
			$count++;
		}
		return $letter_array[$count];
	}
	
	/**
	* Gets letter for selected PR from the db
	* @param int $prid
	* @return result array
	* @access public
	*/
	public function getLetter($prid){
		$query = 'SELECT `letter` FROM `PM_dev_main`.`cms` WHERE `PRID` = "'.$prid.'"';
		return $this->dbObj->query($query);
	}
	
	/**
	* Gets date for selected PR from the db
	* @param int $prid
	* @return result array
	* @access public
	*/
	public function getIDdate($prid){
		$query = 'SELECT `date` FROM `PM_dev_main`.`cms` WHERE `PRID` = "'.$prid.'"';
		return $this->dbObj->query($query);
	}
	
	/**
	* Gets all years from timestamps in the DB
	* @return result array
	* @access public
	*/
	public function getArchiveYears(){
		$query = 'SELECT YEAR(FROM_UNIXTIME(`date`)) AS \'DATE\' FROM `cms` WHERE `status` = \'1\' GROUP BY YEAR(FROM_UNIXTIME(`date`)) ORDER BY `date` DESC';
		return $this->dbObj->query($query);
	}	
	
	/**
	* for uploading pdf files
	* @param array $FILES
	* @param string $date
	* @return bool result
	* @access public
	*/
	public function fileupload($FILES,$file_rename,$PRID){
	//	$file_rename = date("Y_m_d", $date).'_'.makeletter($this->dbObj,$date).'.pdf';
		if (($FILES["type"] == "application/pdf")){
			if ($FILES["error"] > 0){
				echo "Return Code: " . $FILES["error"] . "<br />";
			}
			else{	
				if (file_exists("/home/propmaps/tmp/upload/" . $FILES["name"])){
					echo $FILES["name"] . " already exists. <br/>";
				}
				else{
					move_uploaded_file($FILES["tmp_name"],
					"/home/propmaps/media/press/" . $file_rename);
					echo "Stored in: " . "upload/" . $file_rename."<br/>"; // renames file by our rules YYYY_MM_DD_letter.pdf
				}
				chmod("/home/propmaps/media/press/".$file_rename, 0644); // files uploaded are auto set to 600, need chmod 644
				if($PRID != 0){
					$query = 'UPDATE `PM_dev_main`.`cms` SET `pdf_status` = "1" WHERE `PRID` = "'.$PRID.'" LIMIT 1'; // must update pdf_status to 1
					$this->dbObj->query($query);
				}
				return true;
			}
		}
		else{
			echo "Invalid file";
			return false;
		}
	}

	/**
	* Creates a file to post the include 'news_array.php' for the front page
	* this includes both `cms` and `wp_posts` from the DB ordered by date
	* @access public
	*******************/
	public function postFront(){
		// select the most recent 8 from both tables then limit to 6 ordered by date most recent first
		$query = '(SELECT `letter`,`title`,`status` AS `post_status`, `PRID` AS `post_name`,`date` FROM `PM_dev_main`.`cms` WHERE `status` = 1 ORDER BY `date` DESC LIMIT 8) UNION (SELECT `post_category` AS `letter`,`post_title` AS `title`,`post_status` AS `status`,`post_name`,UNIX_TIMESTAMP(`post_date`) AS `date` FROM `PM_dev_blog`.`wp_posts` WHERE `post_status` = \'publish\' ORDER BY `post_date` DESC LIMIT 8) ORDER BY `date` DESC LIMIT 6';
		$result = $this->dbObj->query($query);
		
		$i = 0;
		// result == title, status, post_name, date
		while($data = mysqli_fetch_assoc($result)) {
			if($data['post_status']=='publish'){
				/* Blog Posts **************************************************/
				$date = array(); // Date pulled from DB reformatted
				$date['year'] = substr($data['date'], 0, 4);
				$date['month'] = substr($data['date'], 5, 2);
				$date['day'] = substr($data['date'], 8, 2);
			
				$date_print[$i] = date("M d, Y", mktime(0,0,0,$date['month'],$date['day'],$date['year'])); // Makes proper date
				$link[$i] = '/blog/'.$date['year'].'/'.$date['month'].'/'.$date['day'].'/'.$data['post_name'];
				$title[$i] = substr($data['title'], 0, 50);
				$i++;
				/***************************************************************/
			}
			else{
				/* Press Release ***********************************************/
				$date_link[$i] = date("Y_m_d", $data['date']);
				$date_print[$i] = date("M d, Y", $data['date']);
				$title[$i] = substr($data['title'], 0, 100);
				$letter[$i] = $data['letter'];
				
				$link[$i] = "/corporate.press_release?post=".$date_link[$i]."_".$letter[$i]; // link is date followed by letter
				$i++;
				/***************************************************************/
			}
		}
		
		// Open file news_array.php for writing
		
		$file = '/home/propmaps/public_html/includes/news_array.php';
		
		// Adds content to the page
		$stringData = '<script type="text/javascript">'."\n";
		$stringData .= 'var headlinesArray = ['."\n";
		for($i = 0;$i < 6;$i++){
			$stringData .=  '[\''.$link[$i].'\',\''.str_replace("'" , "\'" , $title[$i]).'\',\''.$date_print[$i].'\'],'."\n";
			// ex.	['/corporate.press_2007_09_22_a','PropertyMaps.com Adds Naples, Fla., MLS Listings to Its Free Real Estate Search Site','Sep 22, 2007'],
		}
		$stringData .= '];'."\n";
		$stringData .= '</script>'."\n";
		
		if($this->writefile($stringData,$file)){
			return 'Front page has been updated';
		}
		else{return 'Front page COULD NOT updated!';}
	}

	/**
	* Writes data to file
	* @param string $stringData data to be written
	* @param string $file file name and location
	* @access public
	*******************/
	public function writefile($stringData,$file){
		if(($fh = fopen($file, 'w')) === FALSE){
			return false;
		}
		else{
			fwrite($fh, $stringData);
			fclose($fh);
			chmod($file, 0644);
			return true;
		}
	}
	
	/**
	* makes a unix timestamp
	* @param string $string date passed in
	* @param string $delim delimiter
	* @param string $fmt passed date format
	* @return result string timestamp
	* @access public
	*/
	public function makedate($string,$delim,$fmt) {
		if($string!='') {
			$temp = explode($delim,$string);
			if($fmt == 'MDY'){
				$month = $temp[0];
				$day = $temp[1];
				$year = $temp[2];
			}
			elseif($fmt == 'YMD'){
				$month = $temp[1];
				$day = $temp[2];
				$year = $temp[0];
			}
			elseif($fmt == 'YDM'){
				$month = $temp[2];
				$day = $temp[1];
				$year = $temp[0];
			}
			$string = mktime(0,0,0,$month,$day,$year); // MM,DD,YYYY
		}
		return $string;
	}
	
}
?>