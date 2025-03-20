<?php
namespace stdincl\bridge;

use Exception;
use ReflectionClass;

use stdincl\bridge\exception\BridgeException;

/**
 * Estensión de la clase nativa de reflección.
 * Su objetivo principal es usar el request capturado y usarlo como input para la clase.
 * 
 * @author Diego Rodriguez Gomez
 */
class Reflector extends ReflectionClass { 

	private $request = null;
	
	private $reflection = null;
	
	/**
	 * Constructor
	 */
	public function __construct($request){
		parent::__construct($request->getClassNamespace());
		$this->setRequest($request);
		$this->buildReflection();
	}
	
	/**
	 * Genera la refleccion usando el request capturado
	 */
	private function buildReflection(){
		try{
			$this->getMethod($this->getRequest()->getMethod());
			$this->reflection = $this->newInstanceArgs([$this->getRequest()]);
		}catch(Exception $e){
			throw new BridgeException('class-method-not-found',[
				'class'=>$this->getRequest()->getClass(),
				'method'=>$this->getRequest()->getMethod()
			]);
		}
	}
	
	/**
	 * Setter request
	 */
	public function setRequest($request){
		$this->request = $request;
	}
	
	/**
	 * Getter Request
	 */
	public function getRequest(){
		return $this->request;
	}

	/**
	 * Getter reflection
	 */
	public function getReflection(){
		return $this->reflection;
	}
}
?>