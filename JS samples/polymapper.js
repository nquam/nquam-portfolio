//<![CDATA[

/* GLOBALS *************************************************/
//var IE = document.all ? true : false;

var startMarker = null;
var endMarker = null;
var routePoints = new Array();
var routeOverlays = new Array();
var polyClose = null;
var map;
var ovmap;
var totalDistance = 0.0;
var opacity = 0.4;
var a_pts = new Array();
var myJSON = eval({"latitude":[],"longitude":[]});
var poly_closed = false;
var i = 0;


var customMaps = [G_NORMAL_MAP,G_SATELLITE_MAP,G_HYBRID_MAP];

/**********************************************************/




/* MAP LOADER ****************************************************/

function load() {
  if (GBrowserIsCompatible()) {
	var centerPoint = new GLatLng(27.921620,-82.471619);

	map = new GMap2(document.getElementById("map"), {draggableCursor:"crosshair"});
	map.setCenter(centerPoint, 8);
	getMapcenter();

	map.addControl(new GScaleControl());
	map.addControl(new GLargeMapControl());


	map.enableContinuousZoom();
	GEvent.addListener(map, "moveend", getMapcenter);
	GEvent.addListener(map, "click", mapClick);

  
	selectButton('btn_0');

	map.addControl(new GOverviewMapControl(new GSize(165,165)));

 }
}



function mapClick(marker, point) {
	
	if(!poly_closed){ // checking if polygon is created, don't allow more than one
		if (!marker) {
			addRoutePoint(point);
		}
		else { 
				closeRoute(a_pts);
		}
	}
	else{
		//alert("is closed, no more");
	}
}

function getMapcenter() {
	var center = map.getCenter();
	var z = map.getZoom();
	document.getElementById("coords").innerHTML = 'Map center:<br>' + center.y.toFixed(6) + ' ' + center.x.toFixed(6) + '<br>Zoom: ' + z;
}

/********************************************************************/

/* POLYTOOL *********************************************************/
function getDistance(lat1, lon1, lat2, lon2, unit) {
	var radlat1 = Math.PI * lat1/180
	var radlat2 = Math.PI * lat2/180
	var radlon1 = Math.PI * lon1/180
	var radlon2 = Math.PI * lon2/180
	var theta = lon1-lon2
	var radtheta = Math.PI * theta/180
	var dist = Math.sin(radlat1) * Math.sin(radlat2) + Math.cos(radlat1) * Math.cos(radlat2) * Math.cos(radtheta);
	dist = Math.acos(dist)
	dist = dist * 180/Math.PI
	dist = dist * 60 * 1.1515
	if (unit=="K") { dist = dist * 1.609344 }
	if (unit=="N") { dist = dist * 0.8684 }
	return dist
}    


function addRoutePoint(point) {
		var dist = 0;
	
		routePoints.push(point);
	
		if (routePoints.length > 1)	{
			plotRoute();
			dist = getDistance(routePoints[routePoints.length-2].y, routePoints[routePoints.length-2].x, point.y, point.x, 'K');
			totalDistance += dist;
			document.getElementById("dist").innerHTML = 'Total Distance: '+ totalDistance.toFixed(3) + ' km' + '<br>Total Points:' + (i+1);
		}
		else {
			startMarker = new GMarker(point);
			map.addOverlay(startMarker);
			routeOverlays.push( new GPolyline(routePoints,'#FF9601',3,1));
		}
		document.getElementById("route").innerHTML += point.y.toFixed(6) + ' ' + point.x.toFixed(6) + ' : ' + dist.toFixed(3) +"<br>";
	
		a_pts[i] = new GLatLng(point.y,point.x);
		myJSON.latitude[i] = point.y;
		myJSON.longitude[i] = point.x;
		debug("MYJSON"+i+"="+myJSON.latitude[i]);
		debug("MYJSON"+i+"="+myJSON.longitude[i]);
		i++;

	

}

function resetRoute() {
	debug('');
	
	routePoints = new Array();
	map.clearOverlays();
	resetptsArray();
	totalDistance = 0;
	document.getElementById("dist").innerHTML = '';
	document.getElementById("route").innerHTML = 'Route points:<br>';
}

function plotRoute() {
	map.removeOverlay(routeOverlays[routeOverlays.length-1]);
	map.removeOverlay(polyClose);

	routeOverlays[routeOverlays.length-1] = new GPolyline(routePoints,'#FF9601',3,1);
	map.addOverlay(routeOverlays[routeOverlays.length-1]);

	
	if (routePoints.length > 2) {
		var pClose = Array();
		pClose.push(routePoints[0]);
		pClose.push(routePoints[routePoints.length-1]);
		polyClose = new GPolyline(pClose,'#9601FF',1,0.5);
		map.addOverlay(polyClose);
	}

}


function resetptsArray(){ // clears values and overlays to start new polygon
	a_pts = new Array();
	i = 0;
	myJSON = eval({"latitude":[],"longitude":[]});
	poly_closed = false;
}


function closeRoute(a_pts) {
	map.removeOverlay(routeOverlays[routeOverlays.length-1]);
	map.removeOverlay(polyClose);
	map.removeOverlay(startMarker);

	routePoints.push(routePoints[0]);

	var poly = new GPolygon(a_pts,"#000000",0.5,0.2,"#47C831",0.5,{clickable:false});
	     			
	map.addOverlay(poly);
	routeOverlays[routeOverlays.length-1] = new GPolyline(routePoints,'#FF9601',3,1);
	map.addOverlay(routeOverlays[routeOverlays.length-1]);
	routePoints = [];
	

	// when route is closed it will ajax call for points
	//	getURL('GET','http://10.1.50.65/_other/polyapi/json_test.php',false,'runresult','xml');
	var req = new XMLHttpRequest();
	var q = construct_q();

	req.open("GET","/_other/polyapi/json_test.php?q="+q, false);
	//passing ie. {"latitude":["28.018651","27.892494","27.732161","27.717573","27.741885","27.868217","28.091366"],"longitude":["-82.809448","-82.875366","-82.776489","-82.705078","-82.595215","-82.595215","-82.781982"]}
	req.send(/*no params*/null);
	req.onreadyStatechange = runresult(req);	
	
	resetptsArray();
	poly_closed = true;
	
}
/********************************************************************/

/* CONTROLS *********************************************************/
function refreshMap() {
	debug('Refresh map');
	var cType = map.getCurrentMapType();
	map.setMapType(G_NORMAL_MAP);
	map.setMapType(cType);
}


function btnClick(btn,value) {
	selectButton(btn.id);
	map.setMapType(customMaps[value]);

	var sDiv = document.getElementById("scale");
	sDiv.innerHTML = '';
	var point = new GPoint(1,1);
	var z = map.getZoom();

	var layers = map.getCurrentMapType().getTileLayers();
	
	for (var n = 0 ; n < layers.length ; n++ ) {
		var url = layers[n].getTileUrl(point,z);
		if (url.match("getTiles")) {
			url += '&sc=1';
			sDiv.innerHTML += '<img src="' + url + '">';
		}
	}
}

function selectButton(btnID) {
	var bDiv = document.getElementById("mapButtons");
	for (var n = 0; n < bDiv.childNodes.length ; n++ ) {
		bDiv.childNodes[n].className = 'button';
	}

	if (btnID) {
		var bDiv = document.getElementById(btnID);
		bDiv.className = 'selectedButton';
	}
}
/********************************************************************/

/* AJAX *************************************************************/
function receiveResponse(req) { // grab response
	if (req.readyState == 4) {
		if (req.status == 200) {
			var response = req.responseText;
			debug('Response: ' + response);
			if (response.match(/1/) ) {
				refreshMap();
			}

		}
		else {
//			alert('status: ' + req.status);
		}
	}
}

function construct_q(){ // construct our JSON query to pass ajax
	var output = '{"latitude":[';
	for(i = 0;i < myJSON.latitude.length;i++){
		output += '"'+myJSON.latitude[i]+'"';
		if(i < myJSON.latitude.length-1){
			output += ',';
		}
	}
	output += '],"longitude":[';
	for(i = 0;i < myJSON.longitude.length;i++){
		output += '"'+myJSON.longitude[i]+'"';
		if(i < myJSON.longitude.length-1){
			output += ',';
		}
	}
	output += ']}';
	return output;
}

function runresult(req){
	if(req.readyState == 4 /*complete*/) {
		if(req.status == 200) {
			var arr = eval('(' + req.responseText + ')');
			debug(arr.latitude[0]+','+arr.longitude[0]);
			addmlspoints(arr);
		}
	}
}

function addmlspoints(arr){ // add points from ajax call

	debug("points returned: "+ arr.latitude.length);
	for (i=0;i<arr.latitude.length;i++){
		var point = new GLatLng(arr.latitude[i],arr.longitude[i]);
	  	var marker = createMarker(point,'<div style="width:240px">'+arr.listingID[i]+'val='+arr.statuss[i]+'</div>', arr.statuss[i])
	  	map.addOverlay(marker);
  }

}

function createMarker(point,html,test) { // create regular marker point
	
	var aIcon = new GIcon(G_DEFAULT_ICON);
	if ( test == 'true') aIcon.image = "http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png";
	else aIcon.image = "http://www.google.com/intl/en_us/mapfiles/ms/micons/red-dot.png";
	markerOptions = { icon:aIcon };
	
	var marker = new GMarker(point, markerOptions);
	GEvent.addListener(marker, "click", function() {
		marker.openInfoWindowHtml(html);
	});
	return marker;
}
/********************************************************************/

/* DEBUG ************************************************************/

function debug(str) {
	dbg = document.getElementById("debug");

	if (str == '') {
		dbg.innerHTML = '';
	}
	else {
		dbg.innerHTML += str + '<br>\n';
	}

	dbg.scrollTop = dbg.scrollHeight;
}
/********************************************************************/

//]]>
