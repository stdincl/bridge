<?php
namespace stdincl\bridge;

use stdincl\bridge\SQL;
use stdincl\bridge\exception\BridgeException;

class Environment {

	private static $environment = null;
	private static $settings = null;
	private $componserConfig = null;

	public function __construct(){}
	
	public function root(){
		$reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
		return dirname(dirname(dirname($reflection->getFileName())));
	}
	public function componserConfig(){
		if(is_null($this->componserConfig)){
			$this->componserConfig = json_decode(file_get_contents(Environment::get()->root().'/composer.json'),1);
		}
		return $this->componserConfig;
	}
	public function psr4(){
		return array_keys($this->componserConfig()['autoload']['psr-4'])[0];
	}

	public function settings($key=null){
		if(is_null(Environment::$settings)){
			Environment::$settings = json_decode(file_get_contents(Environment::get()->root().'/bridge/settings.json'),1);
		}
		return isset(Environment::$settings[$key])?
				Environment::$settings[$key]:
				Environment::$settings;
	}

	public static function get(){
        if(is_null(Environment::$environment)){
        	Environment::$environment = new Environment();
        }
        return Environment::$environment;
	}
}
?>