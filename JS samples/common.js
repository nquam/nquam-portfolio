/***************************************************
* Math.floor the data
* Rounds and cuts off past two decimal places
* Also keeps cents like .40 formatted properly
***************************************************/
function floordata(amount){
	amount=(Math.round(amount*100))/100;
	amount -= 0;
	return (amount == Math.round(amount)) ? amount + '.00' : (  (amount*10 == Math.round(amount*10)) ? amount + '0' : amount);
}

/***************************************************
* Check for all numbers
***************************************************/
function isnumeric(sText){
	var ValidChars = "0123456789.";
	var IsNumber=true;
	var Char;
	for (i = 0; i < sText.length && IsNumber == true; i++){
		Char = sText.charAt(i);
		if (ValidChars.indexOf(Char) == -1){
			IsNumber = false;
		}
	}
	return IsNumber;
}