<?php
namespace stdincl\bridge\exception;

class BridgeException extends \Exception {
	private $params = [];
	public function __construct($message,$code=0,\Exception $previous=null,$params=[]){
        parent::__construct(print_r($message,1),$code,$previous);
        if(is_array($params)){
            foreach($params as $paramName=>$paramValue){
                $this->addParameter($paramName,$paramValue);
            }
        }
    }
    public function addParameter($param,$value){
    	$this->params[$param] = $value;
    }
    public function removeParameter($param){
    	unset($this->params[$param]);
    }
    public function getParameters(){
    	return $this->params;
    }
    public function buildResponse(){
        $code       = $this->getMessage();
        $params     = $this->getParameters();
        $message    = Translator::text($code,$params);
        return [
            'code'=>$code,
            'message'=>$message,
            'info'=>$params
        ];
    }

    public static function defaultMessage(){
         return [
            'code'=>'0',
            'message'=>'',
            'info'=>[]
        ];
    }
}
?>