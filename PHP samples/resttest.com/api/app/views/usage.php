<html>
<body>
	<h3>List all cities in a state</h3>
	<fieldset>
		<legend>Usage</legend>
		GET api/v1/states/&lt;STATE&gt;/cities.json
	</fieldset>

	<h3>List cities within a given radius</h3>
	<fieldset>
		<legend>Usage</legend>
		GET api/v1/states/&lt;STATE&gt;/cities/&lt;CITY&gt;.json?radius=&lt;RADIUS(INT)&gt;
	</fieldset>
	<h3>Add a city a user has visited</h3>
	<i>- Allow a user to update a row of data to indicate they have visited a particular city.</i>
	<br/>
	<fieldset>
		<legend>Usage</legend>
		POST api/v1/users/&lt;USER_ID&gt;/visits
		<br/><br/>
		JSON POST DATA FORMAT<br/>
		{
		‘city’ : &lt;CITY&gt;,
		‘state’ : &lt;STATE&gt;
		}
	</fieldset>
	<h3>List the cities a user has visited</h3>
		<i>- Return a list of cities the user has visited</i>
	<fieldset>
		<legend>Usage</legend>
		GET api/v1/users/&lt;USER_ID&gt;/visits
	</fieldset>
</body>
</html>