<?
// Simple GeSHi demo

// Include the GeSHi library
include('geshi/geshi.php');

// Choose file
$myFile = "scripts/rent_vs_buy_calc.js";
$fh = fopen($myFile, 'r');
$contents = fread($fh, 12000);
fclose($fh);

// Make a new GeSHi object, with the source, language and path set
$source = $contents;

$language = 'javascript';
$path = 'geshi/';

$geshi = new GeSHi($source, $language, $path);
?>
<html>

<head>
<title>PropertyMaps- Tools- Rent vs. Buy Calculator</title>

<script src="scripts/rent_vs_buy_calc.js">
</script>
<script src="scripts/common.js">
</script>
<style>
	.main{font-family:Verdana;font-size:11px}
	.main_table{font-family:Verdana;font-size:12px}
	.header{font-size:18px;font-weight:700}
	.input_wide{width:100px;border:1px solid #c0c0c0;padding:2px}
	.input_thin{width:60px;border:1px solid #c0c0c0;padding:2px}
	.result{border:1px solid #c0c0c0;width:100px;padding-left:3px;font-family:arial}
	.result_txt{font-weight:600;}
	.am_table{font-size:9px;text-decoration:underline;font-family:Arial}
	.am_table_f_head{text-align:right;font-weight:500;padding:3px;background:#ccc}
	.am_table_f_std{text-align:right;padding:3px;}
	.am_table_head{text-align:center;padding:3px;background:#ccc}
	.am_table_std{text-align:center;padding:3px;}
	.calc{font-weight:700;color:#297c08}
	.tdleft{height:30px;width:200px}
	.tdmiddle{text-align:right;width:15px}
	.tdright{width:230px}
</style>
	
</head>

<body>
	<form name="clc">
		<div class="main" align="left">
			<div class="header">Rent vs Buy calculator</div><br /><br />
			<div class="calc">Is it better for you to rent or buy?</div><br /><br />
			<div>These values are only estimates.</div>
			<br />
			<table class="main_table" width="450px" border="0" cellspacing="0" cellpadding="3">
				<tr>
					<td class="tdleft">
						<div id="rent_txt">Monthly Rent:</div>
					</td>
					<td class="tdmiddle">
						$
					</td>
					<td class="tdright">
						<input name="rent" class="input_wide" value="1000.00">
					</td>
				</tr>
				<tr>
					<td class="tdleft">
						<div id="rentinc_txt">Average Rent Increase :</div>
					</td>
					<td class="tdmiddle">
					</td>
					<td>
						<input name="rentinc" class="input_thin" value="1.5"> % per Year
					</td>
				</tr>
				<tr>
					<td class="tdleft">
						<div id="loan_txt">Loan Amount:</div>
					</td>
					<td class="tdmiddle">
						$
					</td>
					<td class="tdright">
						<input name="loan" class="input_wide" value="200000.00">
					</td>
				</tr>
				<tr>
					<td class="tdleft">
						<div id="down_txt">Down Payment:</div>
					</td>
					<td class="tdmiddle">
						$
					</td>
					<td class="tdright">
						<input name="down" class="input_wide" value="2000.00">
					</td>
				</tr>
				<tr>
					<td class="tdleft">
						<div id="rate_txt">Mortgage Rate:</div>
					</td>
					<td class="tdmiddle">
					</td>
					<td class="tdright">
						<input name="rate" class="input_thin" value="7.00"> %
					</td>
				</tr>
				<tr>
					<td class="tdleft">
						<div id="term_txt">Mortgage Term:</div>
					</td>
					<td class="tdmiddle">
					</td>
					<td class="tdright">
						<input name="term" class="input_thin" value="30"> Years
					</td>
				</tr>
				<tr>
					<td>
					</td>
					<td class="tdmiddle">
					</td>
					<td>
						<input type="BUTTON" value="Calculate" onClick="dosum()" name="BUTTON">
					</td>
				</tr>
				<tr>
					<td>
						<br />
					</td>
				</tr>
				<tr>
					<td class="result_txt">
						Total Mortgage Cost:
					</td>
					<td class="tdmiddle">$
					</td>
					<td>
						<div id="total_mort" class="result">0</div>
					</td>
				</tr>
				<tr>
					<td class="result_txt">
						Total Rent Cost:
					</td>
					<td class="tdmiddle">
						$
					</td>
					<td>
						<div id="total_rent" class="result">0</div>
					</td>
				</tr>
				<tr>
					<td id="diff_txt" class="result_txt">
						Total From Purchasing:
					</td>
					<td class="tdmiddle">$
					</td>
					<td>
						<div id="diff" class="result">0</div>
					</td>
				</tr>
			</table>
		</div><br />
		<div id="info"></div>
	</form>
<?php echo $geshi->parse_code(); ?>
	</body>
</html>
