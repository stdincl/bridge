<?php
namespace stdincl\bridge\exception;

use Exception;

use stdincl\bridge\util\Translator;

/**
 * Excepcion principal de Bridge.
 * Permite asociar datos extra
 * 
 * @author Diego Rodriguez Gomez
 */
class BridgeException extends Exception {

    /**
     * Diccionario de errores
     */
	private $errors = [];

    /**
     * Constructor
     * 
     * @param string    $message  Mensaje de error
     * @param array     $errors   Diccionario de errores
     * @param int       $code     Ver Exception::__construct
     * @param Exception $previous Ver Exception::__construct
     */
	public function __construct(
        $message,
        $errors=[],
        $code=0,
        Exception $previous=null
    ){
        parent::__construct(print_r($message,1),$code,$previous);
        $this->errors = is_array($errors)?$errors:[];
    } 

    /**
     * Genera un array de respuesta para la petición
     * 
     * @return array {
     *      code:    string Mensaje sin traducir
     *      message: string Mensaje con intento de traduccion
     *      errors:  array  Diccionario de informacion extra del error
     * }
     */
    public function response(){
        $code       = $this->getMessage();
        $errors     = $this->errors;
        $message    = Translator::text($code,$errors);
        return [
            'code'    => $code,
            'message' => $message,
            'errors'  => $errors
        ];
    }
    
}
?>