<?php
namespace stdincl\bridge\exception;

class BridgeException extends \Exception {

	private $errors = [];

	public function __construct($message,$code=0,\Exception $previous=null,$errors=[]){
        parent::__construct(print_r($message,1),$code,$previous);
        if(is_array($errors)){
            $this->errors = $errors;
        }
    } 
    public function response(){
        $code       = $this->getMessage();
        $errors     = $this->errors;
        $message    = Translator::text($code,$errors);
        return [
            'code'   =>$code,
            'message'=>$message,
            'errors' =>$errors
        ];
    }

    public static function defaultResponse($message){
        $exception = new BridgeException($message);
        return $exception->response();
    }
}
?>