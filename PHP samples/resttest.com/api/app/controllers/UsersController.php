<?php 
/**
* 
*/
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UsersController extends BaseController {

  public function showVisits($user_id)
  {
      $data = new stdClass();
      
      $results = Visits::findVisits($user_id);
      $data->results = $results;
      $data->status = "200 OK";
      $data->count = count($results);
      return json_encode($data);
  }
 
  /**
   * Store a newly created resource in storage.
   *
   * @return Response
   */
  public function postVisit($user_id)
  {

    // $params = json_decode("{'city' : 'Ashville','state' : 'AL'}");
    // $city = Cities::getCity($params['state'],$params['city']);
    $params = Input::json();
    
    try
    {
      $city = Cities::getCity($params->get('state'),$params->get('city'));
    }
    catch(Exception $e)
    {
      $data = array(
        "error" => array(
          "status" => "200 OK",
          "message" => $e->getMessage(),
          "error_code" => $e->getCode()
        )
      );
      return json_encode($data);
    }
    

    $visit = new Visits;
    $visit->user_id = $user_id;
    $visit->city_id = $city->id;
    
    try
    {
      $visit->save();
      $data = array("status"=>"200 OK","message"=>"Data inserted successfully.");
    }
    catch(Exception $e)
    {
      $data = array(
        "error" => array(
          "status" => "200 OK",
          "message" => $e->getMessage(),
          "error_code" => $e->getCode()
        )
      );
    }

    return json_encode($data);
    
  }
 
}