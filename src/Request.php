<?php
namespace stdincl\bridge;

use stdincl\bridge\auth\Credentials;
use stdincl\bridge\API;
use stdincl\bridge\util\Check;
use stdincl\bridge\exception\BridgeException;

/**
 * Clase que al ser instanciada captura los datos del request nativo
 * 
 * @author Diego Rodriguez Gomez
 */
class Request {

	private $credentials = null;
	private $parameters = [];

	public $class  = null;
	public $method = null;

	/**
	 * Constructor
	 */
	public function __construct(){
		$this->catchCredentials();
		$this->catchParameters();
		$this->catchAPI();
	}

	
	public function catchCredentials(){
		$this->credentials = new Credentials();
		$this->credentials->processHttpRequest();
	}


	public function catchParameters(){
		$this->parameters = array_merge(
			$_GET,
			$_POST,
			$_FILES
		);
	}

	/**
	 * Captura la clase y methodo solicitados en la request
	 */
	public function catchAPI(){

		$class = $_SERVER['REDIRECT_BRIDGEC'];
		if(!Check::onlyChars($class,'abcdefghijklmnopqrstuvwxyz0123456789')){
			throw new BridgeException('class-name-is-invalid',[
				'class'=>$class
			]);
		}
		$this->class = $class;

		$method = $_SERVER['REDIRECT_BRIDGEM'];
		if(!Check::onlyChars($method,'abcdefghijklmnopqrstuvwxyz0123456789_')){
			throw new BridgeException('class-method-is-invalid',[
				'method'=>$method
			]);
		}
		$this->method = $method;
	}
	
	public function getClass(){
		return $this->class;
	}
	public function getMethod(){
		return $this->method;
	}

	public function getCredentials(){
		return $this->credentials;
	}

	public function getClassNamespace(){
		return '\\'.Environment::get()->psr4().$this->getClass();
	}

	public function getParameters(){
		return $this->parameters;
	}


	public function getParameter($name){
		return isset($this->parameters[$name])?$this->parameters[$name]:'';
	}


	public static function redirect($path){
		header('location:'.$path);
		die();
	}
}
?>