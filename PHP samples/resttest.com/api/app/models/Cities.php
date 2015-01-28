<?php

class Cities extends Eloquent {

    /**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'cities';

	public static function getAllCities($state)
	{
		self::checkParams(array("state"=>$state));
		$cities = self::where('state','=',$state)->take(0)->get();
		if(count($cities) <= 0)
		{
			throw new Exception("$state does not exist", 200);
		}
		return $cities;
	}

	public static function getCity($state, $city)
	{
		self::checkParams(array("state"=>$state,"city"=>$city));

		try
		{
			return self::where('state','=',$state)->where('name','=',$city)->firstorfail();
		}
		catch(Exception $e)
		{
			throw new Exception("$city, $state does not exist", 200);
		}
	}

	public static function getNearCities($state,$city,$distance)
	{
		self::checkParams(array("state"=>$state,"city"=>$city,"radius"=>$distance));

		$city_info = self::getCity($state,$city);
		
		//km =6371
		//mi = 3959
		return Cities::select('id','name',DB::raw("( 3959 * acos( cos( radians(".$city_info->latitude.") ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(".$city_info->longitude.") ) + sin( radians(".$city_info->latitude.") ) * sin(radians(latitude)) ) ) AS distance "))
        ->having('distance','<=',$distance)
        ->take(0)
        ->get();
	}

	private static function checkParams($params)
	{
		$exception = NULL;
		foreach($params as $key=>$param)
		{
			if(!isset($param))
			{
				$exception .= " $key is not set. ";
			}
		}
		
		if($exception)
		{
			throw new Exception(trim($exception, " \t"), 400);
		}
	}
}