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
}
?>