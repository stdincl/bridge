<?php
namespace stdincl\bridge;

use stdincl\bridge\T;
use stdincl\bridge\IO;
use stdincl\bridge\Check;
use stdincl\bridge\BridgeException;

class Bridge { 
	public static $classNameKey = '_bridge_framework_class_name_key';
	public static $methodNameKey = '_bridge_framework_method_class_name_key';
	public static function connect(){
		header('Content-Type:application/json');
		if($_SERVER['REQUEST_METHOD']=='OPTIONS'){
			http_response_code(200);
			return;
		}
		if(isset($_SERVER['HTTP_AUTH'])){
			$_SERVER['HTTP_AUTH'] = explode(' ', $_SERVER['HTTP_AUTH']);
			IO::session($_SERVER['HTTP_AUTH'][0],$_SERVER['HTTP_AUTH'][1]);
		}
		$className = '\\'.IO::psr4().$_GET[Bridge::$classNameKey];unset($_GET[Bridge::$classNameKey]);
		$methodName = $_GET[Bridge::$methodNameKey];unset($_GET[Bridge::$methodNameKey]);
		$requestParameters = array_merge(
			$_GET,
			$_POST,
			$_FILES
		);
		try{
			if(!Bridge::reflectionRequestIsValid($className,$methodName)){
				IO::exception('.denied');
			}
			try{
				$reflector = new \ReflectionClass($className);
				$reflectorMethod = $reflector->getMethod('_'.$methodName);
			}catch(\Exception $e){
				IO::exception(
					'.method-not-found',
					array(
						'method-name'=>$className.'::'.$methodName
					)
				);
			}
			$reflectorMethodParameters = $reflectorMethod->getParameters();
			$executionParameters = array();
			foreach($reflectorMethodParameters as $i=>$reflectorMethodParameter){
				$executionParameters[] = $requestParameters[$reflectorMethodParameter->name];
			}
			$result = call_user_func_array(
				$className.'::_'.$methodName,
				$executionParameters
			);
			http_response_code(200);
		}catch(BridgeException $e){
			$error_code = str_replace("'",'',$e->getMessage());
			$error = T::_($error_code,$e->getParameters());
			$result = array(
				'error_code'=>$error_code,
				'error'=>$error,
				'error_info'=>array()
			);
			$result['error_info'] = array();
			foreach ($e->getParameters() as $name=>$value){
				$result['error_info'][$name] = $value;
			}
			http_response_code(400);
		}
		$jsonResult = json_encode(
			$result,
			JSON_NUMERIC_CHECK
		);
		header('Content-Length: '.strlen($jsonResult));
		echo $jsonResult;
	}
	public static function reflectionRequestIsValid($className,$classMethodName){
		return (
			Bridge::isValidClassName($className)
			&&
			Bridge::isValidClassMethodName($classMethodName)
		);
	}
	public static function isValidClassName($className){
		return Check::onlyChars($className,'\abcdefghijklmnopqrstuvwxyz0123456789');
	}
	public static function isValidClassMethodName($classMethodName){
		return Check::onlyChars($classMethodName,'\abcdefghijklmnopqrstuvwxyz0123456789_');
	}
}
?>