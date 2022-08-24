<?php
namespace stdincl\bridge;

use stdincl\bridge\IO;

session_start();
class Controller {
	public static function __callStatic($m,$a){
		$searchM = '_'.$m;
		$c = get_called_class();
		if(method_exists($c,$searchM)){
			return call_user_func_array($c.'::'.$searchM,$a);
		}else{
			header('IONotFound: '.$c.'::'.$m);
			IO::exception('.method-not-found');
		}
    }
}
?>