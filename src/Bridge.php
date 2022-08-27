<?php
namespace stdincl\bridge;

use stdincl\bridge\IO;
use stdincl\bridge\Check; 

class Bridge { 
	public static $classNameKey = '_bridge_framework_class_name_key';
	public static $methodNameKey = '_bridge_framework_method_class_name_key';
	public static function connect(){
		header('Content-Type:application/json');
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
			$reflector = new \ReflectionClass($className);
			try{
				$reflectorMethod = $reflector->getMethod('_'.$methodName);
			}catch(\Exception $e){
				IO::exception('.method-not-found');
			}
			$reflectorMethodParameters = $reflectorMethod->getParameters();
			$executionParameters = array();
			foreach($reflectorMethodParameters as $i=>$reflectorMethodParameter){
				$executionParameters[] = $requestParameters[$reflectorMethodParameter->name];
			}
			$result = array(
				's'=>'1',
				'c'=>200,
				'm'=>call_user_func_array(
					$className.'::_'.$methodName,
					$executionParameters
				)
			);
		}catch(\Exception $e){
			$result = (array('s'=>'0','c'=>$e->getMessage(),'m'=>str_replace("'",'',IO::_($e->getMessage()))));
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
		return Check::onlyChars($s,'abcdefghijklmnopqrstuvwxyz0123456789');
	}
	public static function isValidClassMethodName($classMethodName){
		return Check::onlyChars($s,'abcdefghijklmnopqrstuvwxyz0123456789_');
	}
}
?>