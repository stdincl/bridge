<?php
namespace stdincl\bridge;

use ReflectionClass;

use Composer\Autoload\ClassLoader;

use stdincl\bridge\SQL;
use stdincl\bridge\exception\BridgeException;

/**
 * Acceso a variables de entorno y configuraciones de Bridge
 * 
 * @author Diego Rodriguez Gomez
 */
class Environment {

	/**
	 * Documento json de configuracion cargado
	 */
	private static $settings = null;

	/**
	 * Documento json de configuracion de composer cargado
	 */
	private $componserConfig = null;

	/**
	 * Constructor
	 */
	public function __construct(){}
	
	/**
	 * Retorna la ruta de acceso al root del sitio
	 * 
	 * @return string Ruta de acceso al root
	 */
	public function root(){
		$reflection = new ReflectionClass(ClassLoader::class);
		return dirname(dirname(dirname($reflection->getFileName())));
	}

	/**
	 * Retorna el array desde el json composer.json
	 * 
	 * @return array Documento
	 */
	public function componserConfig(){
		if(is_null($this->componserConfig)){
			$this->componserConfig = json_decode(file_get_contents(Environment::get()->root().'/composer.json'),1);
		}
		return $this->componserConfig;
	}

	/**
	 * Retorna el primer key psr-4 del autoload del archivo composer
	 * 
	 * @return string Key psr-4 para acceder a los controladores
	 */
	public function psr4(){
		return array_keys($this->componserConfig()['autoload']['psr-4'])[0];
	}

	/**
	 * Retorna el array json del archivo de configuracion de config.json
	 * 
	 * @param string $key Key a buscar en config.json
	 * 
	 * @return mixed Array de configuración config.json o valor de la key opcional
	 */
	public function settings($key=null){
		if(is_null(Environment::$settings)){
			Environment::$settings = json_decode(file_get_contents(Environment::get()->root().'/api/settings.json'),1);
		}
		return isset(Environment::$settings[$key])?
				Environment::$settings[$key]:
				Environment::$settings;
	}

	/**
	 * Instancia Singleton
	 */
	private static $environment = null;

	/**
	 * Acceso Singleton 
	 * 
	 * @return Environment Instancia singleton
	 */
	public static function get(){
        if(is_null(Environment::$environment)){
        	Environment::$environment = new Environment();
        }
        return Environment::$environment;
	}
}
?>