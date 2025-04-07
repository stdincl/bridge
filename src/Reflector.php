<?php
namespace stdincl\bridge;

use Exception;
use ReflectionClass;

use stdincl\bridge\util\Check;
use stdincl\bridge\Environment;
use stdincl\bridge\exception\BridgeException;

/**
 * Extensión de la clase nativa de reflección.
 * Su objetivo principal es usar el request capturado y usarlo como input para la clase.
 * 
 * @author Diego Rodriguez Gomez
 */
class Reflector extends ReflectionClass {
  
	/**
	 * Constructor
	 */
	public function __construct(){
		# $class = getenv('BRIDGEC');
		$class = $_SERVER['REDIRECT_BRIDGEC'];
		if(!Check::onlyChars($class,'abcdefghijklmnopqrstuvwxyz0123456789')){
			throw new BridgeException('class-name-is-invalid',['class'=>$class]);
		}
		try {
			parent::__construct('\\'.Environment::get()->psr4().$class);
		} catch (Exception $e){
			throw new BridgeException('class-method-not-found',['class'=>$class]);
		}
	}

	/**
	 * Retorna un metodo con nombre extraido desde la 
	 * variable REDIRECT_BRIDGEM
	 * 
	 * @return ReflectionMethod
	 */
	public function getMethod(string $name = null){
		# $method = getenv('BRIDGEM');
		$method = is_null($name)?$_SERVER['REDIRECT_BRIDGEM']:$name;
		if(!Check::onlyChars($method,'abcdefghijklmnopqrstuvwxyz0123456789_')){
			throw new BridgeException('class-method-is-invalid',[
				'method'=>$method
			]);
		}
		return parent::getMethod($method);
	}

	/**
	 * Retorna un valor de dato recibido en la peticion
	 * 
	 * @param string $name Nombre del dato
	 * 
	 * @return mixed Valor del dato, null si no existe
	 */
	public function getData($name){
		$this->data = is_null($this->data)?array_merge(
			$_GET,
			$_POST,
			$_FILES
		):$this->data;
		return isset($this->data[$name])?$this->data[$name]:null;
	}

	/**
	 * Ejecuta un método de la clase usando los datos recibidos en el request.
	 * 
	 * @return mixed Resultado de la función
	 */
	public static function execute(){
		$reflector  = new Reflector();
		$reflection = $reflector->newInstanceArgs([]);
		$method     = $reflection->getMethod(); 
		return call_user_func_array(
			[
				$reflection,
				$method->getName()
			],
			array_reduce(
				$method->getParameters(),
				function($collector,$parameter) use ($reflector) {
					$collector[] = $reflector->getData($parameter->name);
					return $collector;
				},
				[]
			)
		);
	}
}
?>