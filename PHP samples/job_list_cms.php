<?php
/****************************************************
* Beta, this does not include defunct
* This file does NOT include last year's data. It is non-comparitive. 
* It displays current figures based on the most recent data stored to the summary_data table.
/****************************************************/
include '/var/www/html/intranet/core.php';
$ua = new user_auth(17);
$level = $ua->check($user_user_id);

/* Not required *************************************/
//error_reporting(E_ALL); //  turning on error reporting
//ini_set("display_errors", 1);
/****************************************************/
$debug_content = ''; // this invokes the debug var to store any data. to store in it you need to declare a global in functions. This should be the ONLY true global.
/* INCLUDES ****************************************/
include 'db_class.php';
include 'db_functions.php';
include 'common.php';
$mtime = do_microtime(0); // makes a microtime to get the length of script runtime
/****************************************************/
$db = new db_mysql("localhost", "******", "******"); // creates db class
$db->show_errors(); // hide_errors() will turn this off
/****************************************************/

make_header(); // Places in css data

if(isset($func)){
	if($func=='list'){
		all_jobs($db);
		echo "<div style='font-size:8pt;position:absolute;top:70px;left:500px;background:#dddddd;padding:5px;border:1px solid #888888'>You can close this window when finished</div>";
	}
	elseif($func=='view'){
		job_info($db,$title);
	}
	elseif($func=='edit'){
		edit_job($db,$title);
	}
	elseif($func=='new'){
		new_job($db);
	}
	elseif($func=='delete'){
		delete_job($db,$title);
		echo $title." Deleated!";
		all_jobs($db);
	}
	elseif($func=='Post Job'){
		post_job($db,$title,$qual,$ben,$desc,$loc);
		all_jobs($db);
	}
	else{
		all_jobs($db);
		echo "<div style='font-size:8pt;position:absolute;top:70px;left:500px;background:#dddddd;padding:5px;border:1px solid #888888'>You can close this window when finished</div>";
	}
}
else{
	all_jobs($db);
	echo "<div style='font-size:8pt;position:absolute;top:70px;left:500px;background:#dddddd;padding:5px;border:1px solid #888888'>You can close this window when finished</div>";
}
echo "</body>";

/*
* Prints out a list of all jobs
* @param class $db
*/
function all_jobs($db){
	$items = "date,title,location";
	$where = '';
	$job_data = get_jobs($db,$items,$where);
	echo '<br /><br />';
	echo '<a href="job_list.php?func=new"><img src="newposition.gif" border=0 onMouseOver="this.src=\'newposition_over.gif\'" onMouseOut="this.src=\'newposition.gif\'"></a><br />';
	echo "<h3>Jobs Currently Listed</h3>";
	echo '
	<table border=0 width=800px cellpadding=0px cellspacing=0px>
		<tr>
		<td class="top" width="5%" align="center">Edit</td>
		<td class="top roomy" width="40%">
		Job Title - View
		</td>
		<td class="top roomy" width="20%">
		Location
		</td>
		<td class="top roomy" width="10%">
		Date Updated
		</td>
		<td class="top" width="5%" align=center>Delete</td>
		</tr>
	';
		$even = false;
		$background = '';
	foreach($job_data as $title => $data){
		echo '<tr>';
		$backgroundo = 'evh';
		if($even){
			$background = 'ev0';
			$even = false;
		}
		else{
			$background = 'ev1';
			$even = true;
		}
		echo '
			<script>
				function confirm_click(d_title){
					if(confirm("Are you sure you want to DELETE "+d_title+"?")){
						location.href="job_list.php?func=delete&title="+d_title;
					}
				}
			</script>
			<td align="center" class="'.$background.'" onMouseOver="this.className=\''.$backgroundo.'\'" onMouseOut="this.className=\''.$background.'\'">
				<a href="job_list.php?func=edit&title='.$title.'"><img src="b_edit.png" alt="Edit" border="0" /></a>
			</td>
			<td class="'.$background.' roomy" onMouseOver="this.className=\''.$backgroundo.' roomy\'" onMouseOut="this.className=\''.$background.' roomy\'">
			<a href="job_list.php?func=view&title='.$title.'">'.$title.'</a></td>
			<td class="'.$background.' roomy">'.$data['location'].'</td>
			<td class="'.$background.' roomy">'.make_date($data['date']).'</td>
			<td class="'.$background.'" align="center" onMouseOver="this.className=\''.$backgroundo.'\'" onMouseOut="this.className=\''.$background.'\'"><img src="b_drop.png" alt="Delete" style="cursor:pointer" onClick="confirm_click(\''.$title.'\')" /></td>
			</tr>
		';
	}
	echo "</table>";
}

/*
* Prints out a single job posting data as seen on the website
* @param class $db
* @param var $title
*/
function job_info($db,$title){
	$items = "date,title,description,qualifications,benefits";
	$where = "WHERE `title`='$title'";
	
	$job_data = get_jobs($db,$items,$where);
	echo "<a href='job_list.php' style='color:blue'><-Back</a><br /><br />";
	foreach($job_data as $title => $data){
			echo "<div style='padding-left:10px'>";
			echo "<div style='font-size:12pt;font-weight:bold;background:#cbcbcb;width:100%'>".$title."</div><br />";
			echo "<div style='font-size:8pt'>Posted: ".make_date($data['date'])."</div><br />";
			echo "<div style='font-size:11pt;font-weight:bold;line-height:20px;'>Description</div>";
			echo reformat($data['description'])."<br /><br />";
			echo "<div style='font-size:11pt;font-weight:bold;line-height:20px;'>Qualifications</div>";
			echo reformat($data['qualifications'])."<br /><br />";
			echo "<div style='font-size:11pt;font-weight:bold;line-height:20px;'>Benefits</div>";
			echo reformat($data['benefits'])."<br /><br />";
			echo "</div>";
	}
	echo "<a href='job_list.php' style='color:blue'><-Back</a>";
}

/*
* Does an update of a single record, or insert if the title has been modified
* @param class $db
* @param var $title
*/
function edit_job($db,$title){
	$items = "date,title,location,description,qualifications,benefits";
	$where = "WHERE `title`='$title'";
	
	$job_data = get_jobs($db,$items,$where);
	foreach($job_data as $title => $data){
		new_job($db,$title,$data['location'],$data['description'],$data['qualifications'],$data['benefits'],'Edit');
	}
	
}

/*
* Posts a new job to the database
* @param class $db
* @param var $title
* @param var $qual
* @param var $ben
* @param var $desc
* @param var $loc
*/
function post_job($db,$title,$qual,$ben,$desc,$loc){
	echo "Job posted: <b>".$title."</b><br>";
	$today = make_unicode(date("Y-m-d"));
	
	
	$job_write = array(
	'title' => $title,
	'date' => $today,
	'location' => $loc,
	'description' => $desc,
	'qualifications' => $qual,
	'benefits' => $ben
	);
	
	insert_data($db,$job_write);
}

/*
* Creates a form entry for a new job posting to be entered
* @param class $db
* @param var $title
* @param var $loc
* @param var $desc
* @param var $qual
* @param var $ben
* @param var $type
*/
function new_job($db,$title='',$loc='',$desc='',$qual='',$ben='',$type='New'){
	echo "
	<script>

	function validation(){
		if(!checkvalid()){
			return false;
		}
		else{
			return true;
		}
	}
	
	function checkvalid(){
		var isbad = false;
		for(i=0;i<=1;i++){
			if(document.newjob.elements[i].value=='' || document.newjob.elements[i].value==null){
				isbad = true;
				document.getElementById(document.newjob.elements[i].name).style.color = '#ff0000';
				document.getElementById(document.newjob.elements[i].name).style.fontWeight = 'bold';
			}
		}
		if(isbad){
			return false;
		}
		else{
			return true;
		}
	}
	</script>
	<body>
	<h3>$type Job Position</h3>
	<div id='remarks'>Form items marked with * are required</div>
	<div id='infobox' style='color:#ff0000'></div>
	<form name='newjob' action='job_list.php' method='post' onSubmit='return validation()'>
		<table cellpadding=10>
			<tr>
				<td>
					<div id='title'>Title *</div>
				</td>
				<td>
					<input type='textbox' name='title' value='$title' size='50'/>
				</td>
			</tr>
			<tr>
				<td>
					<div id='loc'>Location *</div>
				</td>
				<td>
					<input type='textbox' name='loc' value='$loc' size='50'/>
				</td>
			</tr>
			<tr>
				<td>
					Description
				</td>
				<td>
					<textarea name='desc' value='' rows='5' cols='50'>$desc</textarea>
				</td>
			</tr>
			<tr>
				<td>
					Qualifications
				</td>
				<td>
					<textarea name='qual' value='' rows='5' cols='50'>$qual</textarea>
				</td>
			</tr>
			<tr>
				<td>
					Benefits
				</td>
				<td>
					<textarea name='ben' value='' rows='5' cols='50'>$ben</textarea>
				</td>
			</tr>
			<tr>
				<td colspan='2'>
					<input type='submit' name='func' value='Post Job'/><input type='button' name='func2' value='Cancel' onClick=\"javascript:location.href='job_list.php'\"/>
				</td>
			</tr>
		</table>
	</form>
	";
}

/*
* Makes the header information for the page and css data for the page
*/
function make_header(){
	echo "
	<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">
	<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>
	<html xmlns=\"http://www.w3.org/1999/xhtml\">
	<head>
	<meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">

	<style>
	.top{background:#94c1ff;border:1px solid #888888;height:24px}
	.ev0{background-image:url(bkgn1.gif);background-repeat:repeat-x;border:1px solid #888888;height:24px}
	.ev1{background-image:url(bkgn2.gif);background-repeat:repeat-x;border:1px solid #888888;height:24px}
	.evh{background-image:url(bkgh.gif);background-repeat:repeat-x;border:1px solid #888888;height:24px}
	.roomy{padding-left:5px;}
	.body{background:#dfe8ef;font-family:Tahoma;}
	a {color:#000000;text-decoration:none}
	a:hover {color:#e8faff;text-decoration:underline}
	</style>
	</head>
	<body class='body'>
	<div style='border-bottom:1px solid black;font-size:14pt;background:#94c1ff;padding-left:5px'>Flagstaff Job Editor</div>
	";
}
?>