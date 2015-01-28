
/***************************************************
* Check if all fields have entries.
***************************************************/

function fieldcheck(){
	var failure=false;

	var boxes = new Array();
	boxes[0] = document.clc.rent.value;
	boxes[1] = document.clc.rentinc.value;
	boxes[2] = document.clc.loan.value;
	boxes[3] = document.clc.down.value;
	boxes[4] = document.clc.rate.value;
	boxes[5] = document.clc.term.value;

	var txts = new Array();
	txts[0] = "rent_txt";
	txts[1] = "rentinc_txt";
	txts[2] = "loan_txt";
	txts[3] = "down_txt";
	txts[4] = "rate_txt";
	txts[5] = "term_txt";
	var i=0;
	while(i<=5){
		if(!isnumeric(boxes[i])||boxes[i]==""){
			document.getElementById(txts[i]).style.color="#f00";
			failure=true;
		}
		else{
			document.getElementById(txts[i]).style.color="#000";
			//	document.temps.loan_amount.style.border="1px solid #c0c0c0";
		}
		i++;
	}
	if(failure==true){
		document.getElementById("info").innerHTML = "<br><div style='font-weight:600;color:#ff0000;padding:5;border:1px solid #9A2B11;width:400px'>Sorry, we cannot display any data because there is either information missing or you have entered incorrect data.<br>Please make sure you have all boxes filled properly.</font>";
		document.getElementById("total_mort").innerHTML="NA";
		document.getElementById("total_rent").innerHTML="NA";
		document.getElementById("diff").innerHTML="NA";
		document.getElementById("diff").style.color="#000";
		document.getElementById("diff_txt").style.color = "#000";
		return false;
	}
	else{
		document.getElementById("info").innerHTML = "";
		return true;
	}
}

/***************************************************
* Create sums based on entered values.
* Figures values for monthly payments and 
* paid off date. 
***************************************************/
function dosum(){
	if(fieldcheck()){
		var MonthRate=(Number(document.clc.rate.value)/100)/12;
		var NumberPayments=Number(document.clc.term.value)*12;
		var Principal=Number(document.clc.loan.value)-Number(document.clc.down.value);
		
		var MonthlyPayment=Math.floor((Principal*MonthRate)/(1-Math.pow((1+MonthRate),(-1*NumberPayments)))*100)/100;
		var TotalPayments=MonthlyPayment*NumberPayments;
		var TotalRent=0;
		var YearRent=12*Number(document.clc.rent.value);
		var appreciation=(Number(document.clc.rentinc.value)/100)*YearRent;
		
		for(i=Number(document.clc.term.value);i>0;i--){
			TotalRent+=YearRent;
			YearRent=appreciation+YearRent;
			appreciation=(Number(document.clc.rentinc.value)/100)*YearRent;
		
		}
		// output
			document.getElementById("total_mort").innerHTML = floordata(TotalPayments);
			document.getElementById("total_rent").innerHTML = floordata(TotalRent);
			if(floordata(TotalRent-TotalPayments)<=0){
				document.getElementById("diff_txt").innerHTML = "Total Loss From Purchasing:";
				document.getElementById("diff_txt").style.color = "#f00";
				document.getElementById("diff").style.color="#f00";
			}
			else{
				document.getElementById("diff_txt").innerHTML = "Total Saved From Purchasing:";
				document.getElementById("diff_txt").style.color = "#080";
				document.getElementById("diff").style.color="#080";
			}
			document.getElementById("diff").innerHTML = floordata(TotalRent-TotalPayments);
	}
}

