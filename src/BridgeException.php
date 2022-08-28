<?php
namespace stdincl\bridge;

use stdincl\bridge\IO;

class BridgeException extends \Exception {
	public $params = array();
	public function __construct($message,$code=0,\Exception $previous=null){
        parent::__construct($message,$code,$previous);
    }
    public function addParameter($param,$value){
    	$this->params[$param] = $value;
    }
    public function removeParameter($param,$value){
    	unset($this->params[$param]);
    }
    public function getParameters($param,$value){
    	return $this->params;
    }
}
?>