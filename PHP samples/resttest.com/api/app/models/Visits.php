<?php

class Visits extends Eloquent {

    /**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'visit_list';

	public static function findVisits($user_id)
	{
		if(!isset($user_id))
		{
			throw new Exception("User ID is not set", 400);
		}

		try
		{
			return self::select()
				->where('user_id','=',$user_id)
				->join('cities', 'visit_list.city_id', '=', 'cities.id')
				->take(0)
				->get();
		}
		catch(Exception $e)
	    {
	      $catch = array(
	        "error" => array(
	          "status" => "200 OK",
	          "message" => $e->getMessage(),
	          "error_code" => $e->getCode()
	        )
	      );
	      return $catch;
	    }
	}
}