<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

/* Requirements

-List all cities in a state
GET /v1/states/<STATE>/cities.json
-List cities within a 100 mile radius of a city
GET /v1/states/<STATE>/cities/<CITY>.json?radius=100
- Allow a user to update a row of data to indicate they have visited a particular city.
POST /v1/users/<USER_ID>/visits
 
{
‘city’ : <CITY>,
‘state’ : <STATE>
}
- Return a list of cities the user has visited
GET /v1/users/<USER_ID>/visits

*/

Route::get('/', function()
{
	return View::make('hello');
});

// Route::controller('api', 'ApiController');

Route::get('api/v1', 'BaseController@showUsage');

Route::group(array('prefix' => 'api/v1/states'), function()
{
	Route::get('', 'StatesController@showInfo');
    Route::get('{state}/cities.{format}', 'StatesController@showCity');
    Route::get('{state}/cities/{city}.{format}', 'StatesController@showCityRadius');
});

// Route::resource('api/v1/users', 'UsersController');

Route::group(array('prefix' => 'api/v1/users'), function()
{
    Route::get('{user_id}/visits', array('before'=>'check_user','uses'=>'UsersController@showVisits'));
    Route::post('{user_id}/visits', array('before'=>'check_user','uses'=>'UsersController@postVisit'));
    // Route::get('{user_id}/pvisits', array('before'=>'check_user','uses'=>'UsersController@postVisit'));
});
