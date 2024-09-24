<?php
namespace stdincl\bridge;

use stdincl\bridge\Environment;
use stdincl\bridge\util\Check;
use stdincl\bridge\exception\BridgeException;

class Reflector extends \ReflectionClass { 
	private $request = null;
	private $reflection = null;
	public function __construct($request){
		parent::__construct($request->getAPI()->getClassNamespace());
		$this->setRequest($request);
		$this->buildReflection();
	}
	public function setRequest($request){
		$this->request = $request;
	}
	public function getRequest(){
		return $this->request;
	}
	private function buildReflection(){
		try{
			$this->getMethod($this->getRequest()->getAPI()->getMethod());
			$this->reflection = $this->newInstanceArgs([$this->getRequest()]);
		}catch(\Exception $e){
			throw new BridgeException('class-method-not-found',[
				'class'=>$this->getRequest()->getAPI()->getClass(),
				'method'=>$this->getRequest()->getAPI()->getMethod()
			]);
		}
	}
	public function getReflection(){
		return $this->reflection;
	}
}
?>