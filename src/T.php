<?php 
namespace stdincl\bridge;
use stdincl\bridge\IO;
class T {
	public static function _($t){
		return IO::_($t);
	}
	public static function __callStatic($m,$a){
		return T::_(strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $m)));
    }
}
?>