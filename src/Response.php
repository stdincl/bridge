<?php
namespace stdincl\bridge;

use stdincl\bridge\Reflector;
use stdincl\bridge\Request;

class Response { 
	private $reflector;
	public function __construct($reflector){
		$this->setReflector($reflector);
	}
	public function setReflector($reflector){
		$this->reflector = $reflector;
	}
	public function getReflector(){
		return $this->reflector;
	}

	public function execute(){
		$request = $this->getReflector()->getRequest();
		return call_user_func_array(
			[
				$this->getReflector()->getReflection(),
				$this->getReflector()->getRequest()->getAPI()->getMethod()
			],
			array_reduce(
				$this->getReflector()->getParameters(),
				function($collector,$parameter) use ($request) {
					$collector[] = $request->getParameter($parameter->name);
					return $collector;
				},
				[]
			)
		);
	}
}
?>