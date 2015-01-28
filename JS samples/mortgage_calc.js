
/***************************************************
* Make select buttons for day and year
***************************************************/
function makeselect(){
	var cont_sday = '<select name=selday>';
	for(i=1;i<=31;i++){
		cont_sday += '<option value='+i+'>'+i+'</option>';
	}
	cont_sday += '</select>';
	document.getElementById("sday").innerHTML = cont_sday;
	
	var cont_syear = '<select name=selyear>';
	for(i=1985;i<=2020;i++){
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
	if(!isnumeric(document.clc.loan_amount.value)){
		document.getElementById("loan_txt").style.color="red";
		document.clc.loan_amount.style.border="2px solid #ff0000";
		failure=true;
	}
		else{
			document.getElementById("loan_txt").style.color="black";
			document.clc.loan_amount.style.border="1px solid #c0c0c0";
		}
	if(!isnumeric(document.clc.term.value)){
		document.getElementById("term_txt").style.color="red";
		document.clc.term.style.border="2px solid #ff0000";
		failure=true;
	}
		else{
			document.getElementById("term_txt").style.color="black";
			document.clc.term.style.border="1px solid #c0c0c0";
		}
	if(!isnumeric(document.clc.rate.value)){
		document.getElementById("rate_txt").style.color="red";
		document.clc.rate.style.border="2px solid #ff0000";
		failure=true;
	}
		else{
			document.getElementById("rate_txt").style.color="black";
			document.clc.rate.style.border="1px solid #c0c0c0";
		}
	if(failure==true){
		return false;
	}
	else{return true}
}

/***************************************************
* Create sums based on entered values.
* Figures values for monthly payments and 
* paid off date. 
***************************************************/
function dosum(){
	if(fieldcheck()){
		var LoanAmount= document.clc.loan_amount.value;
		var DownPayment= "0";
		var AnnualInterestRate = document.clc.rate.value/100;
		var Years= document.clc.term.value;
		var MonthRate=AnnualInterestRate/12;
		var NumPayments=Years*12;
		Prin=LoanAmount-DownPayment;

		var MonthPayment=Math.floor((Prin*MonthRate)/(1-Math.pow((1+MonthRate),(-1*NumPayments)))*100)/100
		var endyear = Number(document.clc.term.value) + Number(document.clc.selyear.value);

		var paidoffdate = "" + document.clc.selmonth.value + "/" + document.clc.selday.value + "/" + endyear;
		document.getElementById("paidoff").innerHTML = paidoffdate;
		document.getElementById("payments").innerHTML = floordata(MonthPayment);
		amortization(NumPayments,MonthPayment);
	}
	else{
		document.getElementById("paidoff").innerHTML = "null";
		document.getElementById("payments").innerHTML = "null";
		document.getElementById("am_table").innerHTML = "<br><div style='font-weight:600;color:#ff0000;padding:5;border:1px solid #9A2B11;width:400px'>Sorry, we cannot display any data because there is information missing from the form or you have entered incorrect data.<br>Please make sure you have all boxes filled properly.</font>";
	}
}

/***************************************************
* Amortization Table.
* Creates dynamic table based on the input values. 
***************************************************/
function amortization(NumPayments,MonthPayment){
	var months_left = Number(NumPayments);
	var countmonth = Number(document.clc.selmonth.value);
	var column_count = 1;
	var cur_year=document.clc.selyear.value;
	var balance=Number(document.clc.loan_amount.value);
	var int_bal=document.clc.loan_amount.value;
	var interest=0;
	var output_everything="";
	var total_interest=0;
	
	while(months_left>0){
		// open table rows
		var month_output="<tr><td class='am_table_f_head'>Month<br>Year</td>";
		var payment_output="<tr><td class='am_table_f_std'>Payment</td>";
		var principal_output="<tr><td class='am_table_f_std'>Principal Paid</td>";
		var interest_output="<tr><td class='am_table_f_std'>Interest Paid</td>";
		var balance_output="<tr><td class='am_table_f_std'>Balance</td>";
		var total_interest_output="<tr><td class='am_table_f_std'>Total Interest</td>";
		
		// loop through table creation until table reaches 12 columns
		while(column_count<=12&&months_left>0){
			if(countmonth<=12){
				// Month/Year
				month_output+="<td class='am_table_head'>"+countmonth+"<br>"+cur_year+"</td>";
				countmonth++;
				months_left--;
			}
			else{
				// Can not change order here
				cur_year++;
				countmonth=1;
				month_output+="<td class='am_table_head'>"+countmonth+"<br>"+cur_year+"</td>";
				countmonth++;
				months_left--;
			}
			
			// Payment
			payment_output+="<td class='am_table_std'>$"+floordata(MonthPayment)+"</td>";
			// Interest Paid (Balance * interest rate / 12)
			interest=((int_bal*(Number(document.clc.rate.value)/100))/12);
			interest_output+="<td class='am_table_std'>$"+floordata(interest)+"</td>";
			// Total Interest (Balance + previous interest)
			total_interest=total_interest+interest;
			total_interest_output+="<td class='am_table_std'>$"+floordata(total_interest)+"</td>";
			// Principal paid (payment-interest)
			int_prin=MonthPayment-interest;
			principal_output+="<td class='am_table_std'>$"+floordata(int_prin)+"</td>";
			// Balance (UnroundedPayment – principal paid)
			int_bal=int_bal-int_prin;
			if(months_left>0){
				balance_output+="<td class='am_table_std'>$"+floordata(int_bal)+"</td>";
			}
			else{balance_output+="<td class='am_table_std'>$"+0+"</td>"}
				
			column_count++;
		}
		
		// close table rows
		month_output+="</tr>";
		payment_output+="</tr>";
		principal_output+="</tr>";
		interest_output+="</tr>";
		balance_output+="</tr>";
		total_interest_output+="</tr>";

			
		// Output order
		// Payment, Interest paid, Total Interest, Principal paid, Balance
		output_everything+=month_output+payment_output+interest_output+total_interest_output+principal_output+balance_output;
		
		// clear tags
		month_output="";
		payment_output="";
		principal_output="";
		interest_output="";
		balance_output="";
		total_interest_output="";
		
		// restart column_count
		column_count=1;
	}	
	document.getElementById("am_table").innerHTML="<table class='am_table' border='1' cellpadding='3' cellspacing='0' width='500'>"+output_everything+"</table>";
}
