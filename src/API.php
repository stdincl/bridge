<?php
namespace stdincl\bridge;

use stdincl\bridge\Environment;
use stdincl\bridge\util\Check;
use stdincl\bridge\exception\BridgeException;

class API {
	private $class = '';
	private $method = '';
	public function __construct($class,$method){
		$this->setClass($class);
		$this->setMethod('_'.$method);
	}
	public function setClass($class){
		if(!Check::onlyChars($class,'abcdefghijklmnopqrstuvwxyz0123456789')){
			throw new BridgeException('class-name-is-invalid',[
				'class'=>$class
			]);
		}
		$this->class = $class;
	}
	public function getClass(){
		return $this->class;
	}
	public function setMethod($method){
		if(!Check::onlyChars($method,'abcdefghijklmnopqrstuvwxyz0123456789_')){
			throw new BridgeException('class-method-is-invalid',[
				'method'=>$method
			]);
		}
		$this->method = $method;
	}
	public function getMethod(){
		return $this->method;
	}
	private function getClassNamespace($request){
		return '\\'.Environment::get()->psr4().$this->getClass();
	}
}
?>