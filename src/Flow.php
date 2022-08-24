<?php
namespace stdincl\bridge;

use stdincl\bridge\IO;

class Flow {
	public function __construct(){}
	public static function createPayent($data){
		/*
			$data = array(
						"commerceOrder" => rand(1100,2000),
						"subject" => "Pago de prueba",
						"currency" => "CLP",
						"amount" => 5000,
						"email" => "cliente@gmail.com",
						"paymentMethod" => 9,
						"urlConfirmation" => Config::get("BASEURL") . "/examples/payments/confirm.php",
						"urlReturn" => Config::get("BASEURL") ."/examples/payments/result.php",
						"optional" => $optional
					);
		*/
		$flowApi = new Flow();
		return $flowApi->send(
			"payment/create", 
			$data,
			"POST"
		);
	}
	public static function getPayent($token){
		$flowApi = new Flow(); 
		return $flowApi->send(
			'payment/getStatus', 
			array(
				"token" => $token
			), 
			"GET"
		);
	}
	public static function apiURL(){
		$settings = IO::settings();
		return $settings['flow'][$settings['flow']['mode']]['url'];
	}
	public static function apiKey(){
		return $settings['flow'][$settings['flow']['mode']]['apikey'];
	}
	public static function apiSecret(){
		return $settings['flow'][$settings['flow']['mode']]['secretkey'];
	}
	
	/**
	 * Funcion que invoca un servicio del Api de Flow
	 * @param string $service Nombre del servicio a ser invocado
	 * @param array $params datos a ser enviados
	 * @param string $method metodo http a utilizar
	 * @return string en formato JSON
	 */
	public function send( $service, $params, $method = "GET") {
		$method = strtoupper($method);
		$url = Flow::apiURL().'/'.$service;
		$params = array("apiKey" => Flow::apiKey()) + $params;
		$data = $this->getPack($params, $method);
		$sign = $this->sign($params);
		if($method == "GET") {
			$response = $this->httpGet($url, $data, $sign);
		} else {
			$response = $this->httpPost($url, $data, $sign);
		}
		# IO::exception(print_r($response,1));
		if(isset($response["info"])) {
			$code = $response["info"]["http_code"];
			$body = json_decode($response["output"], true);
			if($code == "200") {
				return $body;
			} elseif(in_array($code, array("400", "401"))) {
				IO::exception($body["message"], $body["code"]);
			} else {
				IO::exception("Unexpected error occurred. HTTP_CODE: " .$code , $code);
			}
		} else {
			IO::exception("Unexpected error occurred.");
		}
	}
	
	/**
	 * Funcion que empaqueta los datos de parametros para ser enviados
	 * @param array $params datos a ser empaquetados
	 * @param string $method metodo http a utilizar
	 */
	private function getPack($params, $method) {
		$keys = array_keys($params);
		sort($keys);
		$data = "";
		foreach ($keys as $key) {
			if($method == "GET") {
				$data .= "&" . rawurlencode($key).'='.rawurlencode($params[$key]);
			} else {
				$data .= "&" . $key.'='.$params[$key];
			}
		}
		return substr($data, 1);
	}
	
	/**
	 * Funcion que firma los parametros
	 * @param string $params Parametros a firmar
	 * @return string de firma
	 */
	private function sign($params) {
		$keys = array_keys($params);
		sort($keys);
		$toSign = "";
		foreach ($keys as $key) {
			$toSign .= "&" . $key . "=" . $params[$key];
		}
		$toSign = substr($toSign, 1);
		if(!function_exists("hash_hmac")) {
			IO::exception("function hash_hmac not exist", 1);
		}
		return hash_hmac('sha256', $toSign , Flow::apiSecret());
	}
	
	
	/**
	 * Funcion que hace el llamado via http GET
	 * @param string $url url a invocar
	 * @param array $data datos a enviar
	 * @param string $sign firma de los datos
	 * @return string en formato JSON 
	 */
	private function httpGet($url, $data, $sign) {
		$url = $url . "?" . $data . "&s=" . $sign;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$output = curl_exec($ch);
		if($output === false) {
			$error = curl_error($ch);
			IO::exception($error, 1);
		}
		$info = curl_getinfo($ch);
		curl_close($ch);
		return array("output" =>$output, "info" => $info);
	}
	
	/**
	 * Funcion que hace el llamado via http POST
	 * @param string $url url a invocar
	 * @param array $data datos a enviar
	 * @param string $sign firma de los datos
	 * @return string en formato JSON 
	 */
	private function httpPost($url, $data, $sign ) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data . "&s=" . $sign);
		$output = curl_exec($ch);
		if($output === false) {
			$error = curl_error($ch);
			IO::exception($error, 1);
		}
		$info = curl_getinfo($ch);
		curl_close($ch);
		return array("output" =>$output, "info" => $info);
	}
	
	
}
?>