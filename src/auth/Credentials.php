<?php
namespace stdincl\bridge\auth;

class Credentials { 
	private $type = '';
	private $token = '';
	public function __construct($type='',$token=''){
		$this->setType($type);
		$this->setToken($token);
	}
	public function setType($type){
		$this->type = $type;
	}
	public function getType(){
		return $this->type;
	}
	public function setToken($token){
		$this->token = $token;
	}
	public function getToken(){
		return $this->token;
	}
	public function processHttpRequest(){
		if(isset($_SERVER['HTTP_AUTH'])){
			$auth = explode(' ', $_SERVER['HTTP_AUTH']);
			$this->setType($auth[0]);
			$this->setToken($auth[1]);
		}
	}
}
?>