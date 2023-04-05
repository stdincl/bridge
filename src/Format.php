<?php
namespace stdincl\bridge;

use stdincl\bridge\Parse;

class Format {
	public static function money($v,$currentSymbol=''){
		$v += 0;
		$number = explode('.', $v);
		$base = $number[0];
		$decimals = strlen(isset($number[1])?$number[1]:'');
		return $currentSymbol.number_format($v,$decimals,',','.');
	}
	public static function normalize($name){
		Parse::decode($name); 
		$name = strtolower($name);
		$name = str_replace('á', 'a', $name);
		$name = str_replace('é', 'e', $name);
		$name = str_replace('í', 'i', $name);
		$name = str_replace('ó', 'o', $name);
		$name = str_replace('ú', 'u', $name);
		$name = str_replace('ñ', 'n', $name);
		$name = str_replace(' ', '-', $name);
		$name = preg_replace("/[^a-z0-9-.]+/i", '', $name);
		return $name;
	}
}
?>