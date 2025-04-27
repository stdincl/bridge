<?php
namespace stdincl\bridge;

use stdincl\bridge\database\SQL;

use stdincl\bridge\exception\BridgeException;

/**
 * Controlador principal.
 * Permite acceso a usuario local y llamada a metodos publicos sin "_"
 * 
 * @author Diego Rodriguez Gomez
 */
class Controller {

	/**
	 * Constructor
	 */
	public function __construct(){}

	/**
	 * Retorna un usuario identificado via header.
	 * Si se recibe la cabecera:
	 * 		Authorization USER_TYPE TOKEN
	 * 
	 * @param string $type Tipo de usuario debe calzar con USER_TYPE
	 * 
	 * @return array Datos del usuario
	 */
	public function auth($type){
		if(!isset($_SERVER['HTTP_AUTHORIZATION'])){
			throw new BridgeException('user-credentials-not-receibed');
		}
		# Extrae key de las cabeceras
		$auth  = explode(' ', $_SERVER['HTTP_AUTHORIZATION']);
		$this->type  = isset($auth[0])?$auth[0]:'';
		$this->token = isset($auth[1])?$auth[1]:'';
		# Usuarios disponibles para idenficacion
		$users = Environment::get()->settings('users');
		# Check si no esta definido el acceso
		if(!isset($users[$this->type])){
			throw new BridgeException('user-access-not-exists');
		}
		# Check si no hay token definido
		if($this->token==''){
			throw new BridgeException('user-access-token-not-exists');
		}
		# Check tipo
		if($this->type!==$type){
			throw new BridgeException('user-access-not-found');
		}
		# Retorna el usuario desde la base de datos
		return SQL::queryOne(
			str_replace(
				'<:session:>',
				$this->token,
				$users[$type]
			)
		);
	}

	/**
	 * Sobrecarga de método
	 * Util para llamar a funciones publicas (con prefijo "_") sin el "_".
	 * Si se llama a la funcion $class->demo() y no existe, pero si existe $class->_demo()
	 * esta función de sobrecarga buscará $class->"_".demo() antes de fallar.
	 * Busca una funcion con prefijo "_" y si existe la ejecuta
	 * 
	 * [Importante: Esta función se llama automaticamente si no se encuentra una funcion]
	 * 
	 * @param string $methodName Nombre de la clase que falló
	 * @param array  $arguments  Argumentos de la funcion que falló
	 * 
	 * @return mixed Resultado de la funcion
	 */
	public function __call(
		$methodName,
		$arguments
	){
		$class = get_called_class();
		$method = '_'.$method;
		if(method_exists($class,$method)){
			return call_user_func_array([
				$this,
				$method
			],$arguments);
		}else{
			throw new BridgeException(
				'class-method-not-found',
				[
					'class'  => $class,
					'method' => $method
				]
			);
		}
    }

}
?>