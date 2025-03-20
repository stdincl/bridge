<?php
namespace stdincl\bridge\auth;

use stdincl\bridge\IO;
use stdincl\bridge\database\SQL;
use stdincl\bridge\exception\BridgeException;
use stdincl\bridge\auth\Credentials;
use stdincl\bridge\Environment;

class User {
	private $credentials = '';
	public function __construct($credentials=null,$automaticAuthorization=false){
		if(is_null($credentials)){
			$credentials = new Credentials();
			$credentials->processHttpRequest();
		}
		$this->setCredentials($credentials);
		if($automaticAuthorization===true){
			$this->auth($credentials);
		}
	}
	public function setCredentials($credentials){
		$this->credentials = $credentials;
	}
	public function getCredentials(){
		return $this->credentials;
	}
	public function auth(){
		$users = Environment::get()->settings('users');
		if(
			!isset($users[$this->credentials->getType()])
			||
			$this->credentials->getToken()==''
		){
			throw new BridgeException('invalid-user-credentials');
		}
		return SQL::queryOne(
			str_replace(
				'<:session:>',
				$this->credentials->getToken(),
				$users[$this->credentials->getType()]
			)
		);
		return $this;
	}
}
?>