<?php
namespace stdincl\bridge;

use stdincl\bridge\IO;
use stdincl\bridge\SQL;
use stdincl\bridge\Parse;
use stdincl\bridge\Controller;

class Transbank extends Controller {
	public static function log(
		$method,
		$action,
		$data,
		$result
	){
		$logfile = IO::root().'/io/tmp/tbklog/'.date('Y-m-d-H:m:s').'.'.random_int(0, 10000000000).'.log';
		while (file_exists($logfile)){
			$logfile = IO::root().'/io/tmp/tbklog/'.date('Y-m-d-H:m:s').'.'.random_int(0, 10000000000).'.log';
		}
		@file_put_contents(
			$logfile, 
			'action : '.$action.' ['.$method.']'."\n".
			'request: '.print_r($data,1)."\n".
			'result : '.print_r($result,1)
		);
		return $logfile;
	}
	/*
		Transbank::execute 
			Este procedimiento asegura que el resultado será un JSON, de lo contrario lanza una exception transbank-response-error
			Si no se puede establecer la conexion por medio de curl el proceimiento reintenta $settings[$settingsKey]['maxretries'] veces antes de lanzar una exception transbank-connection-error
			Si transbank responde con un mensaje de error en [JSONResponse.error_message] se lanza una exception con el contenido del mensaje
	*/
	public static function execute(
		$method,
		$action,
		$settingsKey='transbank',
		$data=array(),
		$intent=1
	){
		$settings = IO::settings();
		$ch = curl_init();
		curl_setopt(
			$ch, 
			CURLOPT_URL, 
			$settings[$settingsKey][$settings[$settingsKey]['mode']]['url'].$action
		);
		// curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Tbk-Api-Key-Id: '.$settings[$settingsKey][$settings[$settingsKey]['mode']]['comercio'],
			'Tbk-Api-Key-Secret: '.$settings[$settingsKey][$settings[$settingsKey]['mode']]['apikey'],
			'Content-Type: application/json'
	    ));
	    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	    $result = curl_exec($ch);
	    # log persistente
		$logfile = Transbank::log($method,$action,$data,$result);
	    if($result===false){
	    	# Fue error de comunicacion - reintentar conexion
	    	if($intent<$settings[$settingsKey]['maxretries']){
	    		file_put_contents($logfile,"\n".'retry: '.($intent+1),FILE_APPEND);
	    		return Transbank::execute(
					$method,
					$action,
					$settingsKey,
					$data,
					$intent+1
	    		);
	    	};
	    	file_put_contents($logfile,"\n".'error: transbank-connection-error',FILE_APPEND);
		    IO::exception('transbank-connection-error');
		}
		$result = json_decode($result,true);

		if(json_last_error()=='JSON_ERROR_NONE'){
			if($result['error_message']){
	    		file_put_contents($logfile,"\n".'error: '.print_r($result,1),FILE_APPEND);
				IO::exception($result['error_message']);
			}
	    	return $result;
		}else{
	    	file_put_contents($logfile,"\n".'error: transbank-response-error',FILE_APPEND);
			IO::exception('transbank-response-error');
		}

	}

	/*
		Transbank::execute
			Crea una transaccion en transbank y retorna un array: {url:'url_a_transbank',token:'token_para_enviar_a_transbank'} 

	*/
	public static function create(
		$amount,
		$orderID,
		$sessionID,
		$returnURL,
		$settingsKey='transbank'
	){	
		return Transbank::execute(
			'POST',
			'/rswebpaytransaction/api/webpay/v1.0/transactions',
			$settingsKey,
			array(
				'buy_order'=>$orderID,
				'session_id'=>$sessionID,
				'amount'=>$amount,
				'return_url'=>$returnURL
			)
		);
	}


	/*
		Transbank::commit
			Indica a transbank que se recibió el reporte de pago de la transaccion con $tbkToken
			Retorna los detalles de la transaccion
			Solo se debe considerar como validador el campo [response_code]
				response_code : [
					 0 = Transacción aprobada
					-1 = Rechazo de transacción - Reintente (Posible error en el ingreso de datos de la transacción)
					-2 = Rechazo de transacción (Se produjo fallo al procesar la transacción. Este mensaje de rechazo está relacionado a parámetros de la tarjeta y/o su cuenta asociada)
					-3 = Error en transacción (Interno Transbank)
					-4 = Rechazo emisor (Rechazada por parte del emisor)
					-5 = Rechazo - Posible Fraude (Transacción con riesgo de posible fraude)
				]
	*/
	public static function commit(
		$tbkToken,
		$settingsKey='transbank'
	){	
		Parse::string($tbkToken);
		if($tbkToken==''){
			IO::exception('transbank-token-required');
		}
		/*
			{
				"vci": "TSY",
				"amount": 10000,
				"status": "AUTHORIZED",
				"buy_order": "ordenCompra12345678",
				"session_id": "sesion1234557545",
				"card_detail": {
					"card_number": "6623"
				},
				"accounting_date": "0522",
				"transaction_date": "2019-05-22T16:41:21.063Z",
				"authorization_code": "1213",
				"payment_type_code": "VN",
				"response_code": 0,
				"installments_number": 0
			}
		*/
		$result = Transbank::execute(
			'PUT',
			'/rswebpaytransaction/api/webpay/v1.0/transactions/'.$tbkToken,
			$settingsKey
		);
		$result['token'] = $tbkToken;
		return $result;
	}


	/*
		Transbank::get
			Obtiene el reporte de pago de la transaccion con $tbkToken
			Retorna los detalles de la transaccion
			Solo se debe considerar como validador el campo [response_code]
				vci:String	
					Resultado de la autenticación del tarjetahabiente. 
					Puede tomar el valor 
						TSY (Autenticación exitosa)
						TSN (Autenticación fallida)
						TO (Tiempo máximo excedido para autenticación)
						ABO (Autenticación abortada por tarjetahabiente)
						U3 (Error interno en la autenticación)
						NP (No Participa, probablemente por ser una tarjeta extranjera que no participa en el programa 3DSecure)
						ACS2 (Autenticación fallida extranjera)
						-VACIO- si la transacción no se autenticó. 
				amount:Decimal	
					Formato número entero para transacciones en peso y decimal para transacciones en dólares. 
				status:String	
					Estado de la transacción 
						INITIALIZED
						AUTHORIZED
						REVERSED
						FAILED
						NULLIFIED
						PARTIALLY_NULLIFIED
						CAPTURED
				buy_order:String	
					Orden de compra de la tienda indicado en Transaction.create()
				session_id:String
					Identificador de sesión, el mismo enviado originalmente por el comercio en Transaction.create()
				card_detail:Object
					Objeto que representa los datos de la tarjeta de crédito del tarjeta habiente.
				card_detail.card_number:String	
					4 últimos números de la tarjeta de crédito del tarjetahabiente.
					 Solo para comercios autorizados por Transbank se envía el número completo.
				accounting_date:String	
					Fecha de la autorización. formato MMDD	
				transaction_date:String	
					Fecha y hora de la autorización. formato: MMDDHHmm
				authorization_code:String	
					Código de autorización de la transacción 
				payment_type_code:String
					Tipo de pago de la transacción.
						VD = Venta Débito.
						VN = Venta Normal.
						VC = Venta en cuotas.
						SI = 3 cuotas sin interés.
						S2 = 2 cuotas sin interés.
						NC = N Cuotas sin interés
						VP = Venta Prepago.	
				response_code:String	
					Código de respuesta de la autorización. Valores posibles:
						 0 = Transacción aprobada
						-1 = Rechazo de transacción - Reintente (Posible error en el ingreso de datos de la transacción)
						-2 = Rechazo de transacción (Se produjo fallo al procesar la transacción. Este mensaje de rechazo está relacionado a parámetros de la tarjeta y/o su cuenta asociada)
						-3 = Error en transacción (Interno Transbank)
						-4 = Rechazo emisor (Rechazada por parte del emisor)
						-5 = Rechazo - Posible Fraude (Transacción con riesgo de posible fraude)
				installments_amount:Number	
					Monto de las cuotas.
				installments_number:Number	
					Cantidad de cuotas.
				balance:Number	
					Monto restante para un detalle anulado.
	*/
	public static function get(
		$tbkToken,
		$settingsKey='transbank'
	){	
		Parse::string($tbkToken);
		if($tbkToken==''){
			IO::exception('transbank-token-required');
		}
		/*

			{
				"vci": "TSY",
				"amount": 10000,
				"status": "AUTHORIZED",
				"buy_order": "ordenCompra12345678",
				"session_id": "sesion1234557545",
				"card_detail": {
					"card_number": "6623"
				},
				"accounting_date": "0522",
				"transaction_date": "2019-05-22T16:41:21.063Z",
				"authorization_code": "1213",
				"payment_type_code": "VN",
				"response_code": 0,
				"installments_number": 0
			}
		*/
		return Transbank::execute(
			'GET',
			'/rswebpaytransaction/api/webpay/v1.0/transactions/'.$tbkToken,
			$settingsKey
		);
	}

}
?>