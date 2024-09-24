<?php
namespace stdincl\bridge;

use stdincl\bridge\auth\Credentials;
use stdincl\bridge\API;

class Request { 
	private $credentials = null;
	private $parameters = [];
	private $API = null;
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
	public function catchAPI(){
		$this->API = new API(
			$_SERVER['REDIRECT_BRIDGEC'],
			$_SERVER['REDIRECT_BRIDGEM']
		);
	}
	public function getAPI(){
		return $this->API;
	}
	public function getCredentials(){
		return $this->credentials;
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