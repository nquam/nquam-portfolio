/***************************************************
* Make select buttons for year
***************************************************/
function makeselect(){
	var cont_syear = '<select name=selyear>';
	for(i=2008;i<=2040;i++){
		cont_syear += '<option value='+i+'>'+i+'</option>';
	}
	cont_syear += '</select>';
	document.getElementById("syear").innerHTML = cont_syear;
}

/***************************************************
* Check if all fields have entries.
***************************************************/

function fieldcheck(){
	var failure=false;

	var boxes = new Array();
	boxes[0] = document.clc.balance.value;
	boxes[1] = document.clc.rate.value;

	var txts = new Array();
	txts[0] = "balance_txt";
	txts[1] = "rate_txt";
	
	var i=0;
	while(i<=1){
		if(!isnumeric(boxes[i])||boxes[i]==""){
			document.getElementById(txts[i]).style.color="#f00";
			failure=true;
		}
		else{
			document.getElementById(txts[i]).style.color="#000";
		}
		i++;
	}
	if(failure==true){
		document.getElementById("info").innerHTML = "<br><div style='font-weight:600;color:#ff0000;padding:5;border:1px solid #9A2B11;width:400px'>Sorry, we cannot display any data because there is either information missing or you have entered incorrect data.<br>Please make sure you have all boxes filled properly.</font>";
		document.getElementById("monthly_goal").innerHTML="NA";
		document.getElementById("payments").innerHTML="NA";
		document.getElementById("interest").innerHTML="NA";
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
		var currentTime = new Date()
		var mnth= currentTime.getMonth()+1;
		var yr=currentTime.getFullYear();
		var endmonth=Number(document.clc.selmonth.value);
		var endyear=Number(document.clc.selyear.value);
		var fullmonth=12-endmonth;
		var fullyear=(endyear-yr)*12;
		var totalmonths=fullyear+fullmonth;
		
		var MonthRate=(Number(document.clc.rate.value)/100)/12;
		var NumberPayments=totalmonths;
		var Principal=Number(document.clc.balance.value);
		
		var MonthlyPayment=Math.floor((Principal*MonthRate)/(1-Math.pow((1+MonthRate),(-1*NumberPayments)))*100)/100;


		var months_left = Number(totalmonths);
		var int_bal=document.clc.balance.value;
		var interest=0;
		var output_everything="";
		var total_interest=0;
		
		while(months_left>0){
			// loop through table creation until table reaches 12 columns
			// Interest Paid (Balance * interest rate / 12)
			interest=((int_bal*(Number(document.clc.rate.value)/100))/12);
			// Total Interest (Balance + previous interest)
			total_interest=total_interest+interest;
			// Principal paid (payment-interest)
			int_prin=MonthlyPayment-interest;
			// Balance (UnroundedPayment – principal paid)
			int_bal=int_bal-int_prin;
			months_left--;
		}
		
		// output
			document.getElementById("monthly_goal").innerHTML = floordata(MonthlyPayment);
			document.getElementById("payments").innerHTML = totalmonths;
			document.getElementById("interest").innerHTML = floordata(total_interest);
}
}
