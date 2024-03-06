<?php
namespace stdincl\bridge;

use stdincl\bridge\IO;
use stdincl\bridge\SQL;
use stdincl\bridge\Parse;
use stdincl\bridge\Controller;

class Getnet extends Controller {
	public static $CORRECT_REQUEST_STATES = array(
		'OK',
		'APPROVED'
	);
	public static function log(
		$method,
		$action,
		$data,
		$result
	){
		$logPath = IO::media().'/getnet/';
		$logfile = $logPath.date('Y-m-d-H:m:s').'.'.random_int(0, 10000000000).'.log';
		while (file_exists($logfile)){
			$logfile = $logPath.date('Y-m-d-H:m:s').'.'.random_int(0, 10000000000).'.log';
		}
		@file_put_contents(
			$logfile, 
			'action : '.$action.' ['.$method.']'."\n".
			'request: '.print_r($data,1)."\n".
			'result : '.print_r($result,1)
		);
		return $logfile;
	}
	public static function auth($settings){
		$nonce = time().'-'.$_SERVER['REMOTE_ADDR'];
		$seed  = date('c');
		return array(
			'login' 	=> $settings['login'],
			'tranKey' 	=> base64_encode(hash('sha256',$nonce.$seed.$settings['trankey'],true)),
			'nonce' 	=> base64_encode($nonce),
			'seed' 		=> $seed
		);
	}

	public static function execute(
		$method,
		$action,
		$data=array(),
		$intent=1
	){	
		$bridgeSettings = IO::settings();
		$settings = $bridgeSettings['getnet'][$bridgeSettings['getnet']['mode']];
		$data = is_array($data)?$data:array();
		$data['auth'] = Getnet::auth($settings);
		$data['locale'] = 'es_CL';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $settings['url'].$action);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	    $resultRaw = curl_exec($ch);
		$logfile = Getnet::log($method,$action,$data,$resultRaw);
	    if($resultRaw===false){
	    	# Fue error de comunicacion - reintentar conexion
	    	if($intent<$bridgeSettings['maxretries']){
	    		file_put_contents($logfile,"\n".'retry: '.($intent+1),FILE_APPEND);
	    		return Getnet::execute(
					$method,
					$action,
					$data,
					$intent+1
	    		);
	    	};
	    	file_put_contents($logfile,"\n".'error: getnet-connection-error',FILE_APPEND);
		    IO::exception('getnet-connection-error');
		}
		$result = json_decode($resultRaw,true);
		if(json_last_error()==JSON_ERROR_NONE){
			if(in_array($result['status']['status'],Getnet::$CORRECT_REQUEST_STATES)){
				return $result;	
			}else{
				file_put_contents($logfile,"\n".'error: '.print_r($result,1),FILE_APPEND);
				IO::exception('GetNet Error:'.$resultRaw);
			}
		}else{
	    	file_put_contents($logfile,"\n".'error: getnet-response-error',FILE_APPEND);
			IO::exception('getnet-response-error');
		}
	}
	/*
		Crea un pago en Getnet
		retorna
			{
				requestId:[0-9]+,
				processUrl:[URL]
			}
		requestId es el ID del pago, se puede usar con Getnet::get(requestId) para obtener el estado del pago
		Se debe ir a la direccion processUrl por GET
	*/
	public static function create(
		$amount,
		$orderID,
		$returnURL,
		$currency='CLP'
	){	
		$expiration = date('c',strtotime(date('Y-m-d H:i:s').' +10 minutes'));
		return Getnet::execute(
			'POST',
			'/api/session/',
			array(
				'payment'=>array(
					'reference'=>$orderID,
					'amount'=> array(
						 'currency'=>$currency,
						 'total'=>$amount
					 ),
					'allowPartial'=>false
				),
				'expiration'=>$expiration,
				'returnUrl'=>$returnURL,
				'cancelUrl'=>$returnURL,
				'ipAddress'=>$_SERVER['REMOTE_ADDR'],
				'userAgent'=>$_SERVER['HTTP_USER_AGENT']
			)
		);
	}
	public static function get($requestId){
		Parse::int($requestId);
		return Getnet::execute(
			'POST',
			'/api/session/'.$requestId
		);
	}


}
?>