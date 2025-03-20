<?php
namespace stdincl\bridge;

use stdincl\bridge\Request;
use stdincl\bridge\Reflector;
use stdincl\bridge\Response;
use stdincl\bridge\exception\BridgeException;

class Router { 
	public function __construct(){
		session_start();
		header('Content-Type:application/json');
		if($_SERVER['REQUEST_METHOD']=='OPTIONS'){
			http_response_code(200);
			return;
		}
		try{
			$request 	= new Request();
			$reflector 	= new Reflector($request);
			$response 	= new Response($reflector);
			$result 	= $response->execute();
			http_response_code(200);
		}catch(BridgeException $error){
			$result 	= $error->buildResponse();
			http_response_code(400);
		}catch(\Exception $error) {
			$result 	= BridgeException::defaultMessage();
			http_response_code(400);
		}
		$json = json_encode(
			$result,
			JSON_NUMERIC_CHECK
		);
		header('Content-Length: '.strlen($json));
		echo $json;
	}
}
?>