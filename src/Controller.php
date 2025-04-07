<?php
namespace stdincl\bridge;

use stdincl\bridge\exception\BridgeException;

class Controller {

	public function __construct(){}

	public function auth($type){
		if(!isset($_SERVER['HTTP_AUTH'])){
			throw new BridgeException('user-credentials-not-receibed');
		}
		$auth  = explode(' ', $_SERVER['HTTP_AUTH']);
		$this->type  = isset($auth[0])?$auth[0]:'';
		$this->token = isset($auth[1])?$auth[1]:'';
		$credentials = Environment::get()->settings('users');
		if(!isset($credentials[$this->type])||$this->token==''){
			throw new BridgeException('user-credentials-not-exists');
		}
		if($this->type!==$type){
			throw new Exception('user-credentials-not-found');
		}
		return SQL::queryOne(
			str_replace(
				'<:session:>',
				$this->token,
				$credentials[$this->type]
			)
		);
	}

	public function __call($methodName,$arguments){
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