<?php
namespace stdincl\bridge;

use stdincl\bridge\exception\BridgeException;

class Controller {
	private $request = null;
	public function __construct($request){
		$this->setRequest($request);
	}
	public function setRequest($request){
		$this->request = $request;
	}
	public function getRequest(){
		return $this->request;
	}
	public static function __call($methodName,$arguments){
		$className = get_called_class();
		$methodName = '_'.$methodName;
		if(method_exists($className,$methodName)){
			return call_user_func_array([
				$this,
				$methodName
			],$arguments);
		}else{
			throw new BridgeException(
				'.method-not-found',
				[
					'class-name'=>$className,
					'method-name'=>$methodName
				]
			);
		}
    }
}
?>