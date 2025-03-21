<?php
namespace stdincl\bridge;

use Exception;

use stdincl\bridge\Request;
use stdincl\bridge\Reflector;
use stdincl\bridge\Response;
use stdincl\bridge\exception\BridgeException;

/**
 * Router es el componente inicial en la ejecución de cualquier request.
 * Se encarga de capturar los detalles de la peticion usando Request, instanciar 
 * el controlador apropiado y retornar una respuesta json vàlida.
 * 
 * @author Diego Rodriguez Gomez
 */
class Router { 

	/**
	 * Constructor
	 */
	public function __construct(){
		if($_SERVER['REQUEST_METHOD']=='OPTIONS'){
			http_response_code(200);
			return;
		}
		session_start();
		header('Content-Type:application/json');
		try{
			$result = Reflector::execute(); 
			http_response_code(200);
		}catch(BridgeException $e){
			$result = $e->response();
			http_response_code(400);
		}catch(Exception $e) {
			$result = BridgeException::defaultResponse($e->getMessage());
			http_response_code(400);
		}
		$json = json_encode($result,JSON_NUMERIC_CHECK);
		header('Content-Length: '.strlen($json));
		echo $json;
	}

}
?>