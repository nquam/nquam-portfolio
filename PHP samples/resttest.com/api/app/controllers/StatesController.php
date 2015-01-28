<?php 
/**
* 
*/

class StatesController extends BaseController {

/**
   * Display a listing of the resource.
   *
   * @return Response
   */
  public function showCity($state,$format)
  {
    $data = new stdClass();
    try
    {
      $results = Cities::getAllCities($state);
      $data->results = $results;
      $data->status = "200 OK";
      $data->count = count($results);
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

    switch ($format)
    {
        case 'xml'  : return Formatter::make($data, 'json')->to_xml();
        case 'json': return json_encode($data);
    }
    
  }
  
  public function showCityRadius($state,$city,$format)
  {
    $data = new stdClass();

    $distance = Input::get('radius');

    try
    {
      $results = Cities::getNearCities($state,$city,$distance);
      $data->results = $results;
      $data->status = "200 OK";
      $data->count = count($results);
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
    
    switch ($format)
    {
        case 'xml'  : return Formatter::make($data, 'json')->to_xml();
        case 'json': return json_encode($data);
    }
    
  }

  public function showInfo()
  {
    return 'todo:api info';
  }

}