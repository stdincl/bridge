<?php
namespace stdincl\bridge;

use stdincl\bridge\Parse;

class Date {
	public static function period($from,$to){
		try {
			Parse::date($from);
		}catch(\Exception $e){
			$from = date('Y-m-01');
		}
		try {
			Parse::date($to);
		}catch(\Exception $e){
			$to = date('Y-m-d');
		}
		if(Date::diffDays($from,$to)>0){
			$delta = $to;
			$to = $from;
			$from = $delta;
		}
		return array(
			'from'=>$from,
			'to'=>$to
		);
	}
	public static function diffDays($dateA,$dateB){
		return floor((strtotime($dateA)-strtotime($dateB))/3600/24);
	}
}
?>