<?php 
namespace stdincl\bridge;
use stdincl\bridge\IO;
class T {
	public static function _($t,$parameters=array()){
		$text = IO::_($t);
		$parameters = is_array($parameters)?$parameters:array();
		foreach($parameters as $param=>$value){
			$text = str_replace('<:'.$param.':>', str_replace("'",'',$value), $text);
		}
		return $text;
	}
	public static function __callStatic($m,$a){
		return T::_(
			strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $m)),
			$a[0]
		);
    }
}
?>