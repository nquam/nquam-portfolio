
/***************************************************
* Check if all fields have entries.
***************************************************/

function fieldcheck(){
	var failure=false;

	var boxes = new Array();
	boxes[0] = document.clc.wages_amt.value;
	boxes[1] = document.clc.invest_amt.value;
	boxes[2] = document.clc.alimony_amt.value;
	boxes[3] = document.clc.other_amt.value;
	boxes[4] = document.clc.down_amt.value;
	boxes[5] = document.clc.rate_amt.value;
	boxes[6] = document.clc.term_amt.value;
	boxes[7] = document.clc.insur_amt.value;
	boxes[8] = document.clc.taxes_amt.value;
	boxes[9] = document.clc.carpay_amt.value;
	boxes[10] = document.clc.alimonypd_amt.value;
	boxes[11] = document.clc.ccpay_amt.value;
	boxes[12] = document.clc.debts_amt.value;

	var txts = new Array();
	txts[0] = "wages";
	txts[1] = "invest";
	txts[2] = "alimony";
	txts[3] = "other";
	txts[4] = "down";
	txts[5] = "rate";
	txts[6] = "term";
	txts[7] = "insur";
	txts[8] = "taxes";
	txts[9] = "carpay";
	txts[10] = "alimonypd";
	txts[11] = "ccpay";
	txts[12] = "debts";
	
	var i=0;
	while(i<13){
		if(!isnumeric(boxes[i])){
			document.getElementById(txts[i]).style.color="#f00";
			//document.getElementsByName(amts[i]).style.border="2px solid #f00";
			document.getElementById("afford_mort").style.color="#f00";
			document.getElementById("afford_home").style.color="#f00";
			//	document.clc.loan_amount.style.border="2px solid #f00";
			failure=true;
		}
		else{
			document.getElementById(txts[i]).style.color="#000";
			//	document.temps.loan_amount.style.border="1px solid #c0c0c0";
		}
		i++;
	}
	if(failure==true){
		return false;
	}
	else{return true}
	}

/***************************************************
* Do algorithms to find affordable amounts
*
* Simple explanation of algorithms
* AffordablePayent=TotalIncome*28%
* AffordableDifference=TotalExpenses-AffordablePayent*28.5710%
* AdjustedPayment=(TotalIncome*28%)-(TotalExpense-(Total_income*28%)*28.5710%)
* AffordHome=(AdjustedPayment/((IntRate/100)/12))*(1-math.pow((1+((IntRate/100)/12)),(-1*(Term*12))))+DownPayment
***************************************************/
function dosum(){

	if(fieldcheck()){
		var TotalIncome= Number(document.clc.wages_amt.value)+Number(document.clc.invest_amt.value)+
		Number(document.clc.alimony_amt.value)+Number(document.clc.other_amt.value);
		document.getElementById("total_income").innerHTML=floordata(TotalIncome);

		var TotalExpense= Number(document.clc.insur_amt.value)/12+
		Number(document.clc.taxes_amt.value)/12+Number(document.clc.carpay_amt.value)+
		Number(document.clc.alimonypd_amt.value)+Number(document.clc.ccpay_amt.value)+
		Number(document.clc.debts_amt.value);
		document.getElementById("total_payment").innerHTML=floordata(TotalExpense);

		// Percentage used by banks to find affordable payments
		var OverallPercent=0.28;
		// Adjsted percentage for more exact math
		var AffordPercent=0.285710;
		var AffordPayment=(TotalIncome*OverallPercent)-(TotalExpense-(TotalIncome*OverallPercent)*AffordPercent);

		// Checks to see if you can afford payments
		if(AffordPayment<=0){
			document.getElementById("afford_mort").innerHTML = "NA";
			document.getElementById("afford_home").innerHTML = "NA";
			document.getElementById("afford_mort").style.color="#f00";
			document.getElementById("afford_home").style.color="#f00";
			document.getElementById("info").innerHTML = "<br><div style='font-weight:600;color:#ff0000;padding:5;border:1px solid #9A2B11;width:400px'>According to the data you entered, you might not be able to afford a mortgage.</font>";
		}

		else{
			document.getElementById("afford_mort").style.color="#000";
			document.getElementById("afford_home").style.color="#000";
			document.getElementById("info").innerHTML = "";
			// Checks if difference exists due to expenditures
			if(TotalExpense-(AffordPercent*(OverallPercent*TotalIncome))>0){
				var AffordHome=(AffordPayment/((document.clc.rate_amt.value/100)/12))*(1-Math.pow((1+((document.clc.rate_amt.value/100)/12)),(-1*(document.clc.term_amt.value*12))))+Number(document.clc.down_amt.value);
				document.getElementById("afford_mort").innerHTML = floordata(AffordPayment);
				document.getElementById("afford_home").innerHTML = floordata(AffordHome);
			}
			else{
				AffordPayment=TotalIncome*OverallPercent;
				var AffordHome=(AffordPayment/((document.clc.rate_amt.value/100)/12))*(1-Math.pow((1+((document.clc.rate_amt.value/100)/12)),(-1*(document.clc.term_amt.value*12))))+Number(document.clc.down_amt.value);
				document.getElementById("afford_mort").innerHTML = floordata(AffordPayment);
				document.getElementById("afford_home").innerHTML = floordata(AffordHome);
			}
		}
	}
	else{
		document.getElementById("info").innerHTML = "<br><div style='font-weight:600;color:#ff0000;padding:5;border:1px solid #9A2B11;width:400px'>Sorry, we cannot display any data because there is information missing from the form or you have entered incorrect data.<br>Please make sure you have all boxes filled properly.</font>";
		document.getElementById("afford_mort").innerHTML="NA";
		document.getElementById("afford_home").innerHTML="NA";
	}
}

