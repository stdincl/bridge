<?php
namespace stdincl\bridge\database;

use stdincl\bridge\Environment;

class SQL {
	public static $count=0;
	public static $conexion;
	# Override mysql configuration using bridge/settings.json@databases.{$select} if exists
	public static function use($select){
		$settings = Environment::get()->settings();
		if(
			isset($settings['databases']) 
			&& 
			isset($settings['databases'][$select])
		){
			$settings['mysql'] = $settings['databases'][$select];
			SQL::$conexion = null;
		}
	}
	public static function on(){
		$settings = Environment::get()->settings();
		if(SQL::$conexion){}else{
			if($settings['mysql']['database']==''){
				throw new BridgeException('.no-database');
			}
			SQL::$conexion = mysqli_connect(
				$settings['mysql']['hostname'], 
				$settings['mysql']['username'], 
				$settings['mysql']['password'],
				$settings['mysql']['database']
			);
			if(mysqli_connect_error()){
				throw new BridgeException('.connection-error');
			}
			if($settings['mysql']['encoding']!=''){
				mysqli_set_charset(SQL::$conexion,$settings['mysql']['encoding']);
				mysqli_query(SQL::$conexion,"SET NAMES '".$settings['mysql']['encoding']."'");
			}
		}
		return SQL::$conexion;
	}	
	public static function off(){
		if(SQL::$conexion){
			mysqli_close(SQL::$conexion);
		}
	}
	public static function begin(){
		return mysqli_query(SQL::on(),'BEGIN');
	}		
	public static function end($saving){
		return mysqli_query(SQL::on(),$saving?'COMMIT':'ROLLBACK');
	}
	public static function exe($Q){
		SQL::$count++;
		try {
        	$res = mysqli_query(SQL::on(),$Q);
		} catch (\Exception $e) {
            throw new BridgeException($e->getMessage());
		}
        if($res){
            return $res;
        }else{
            throw new BridgeException(mysqli_error(SQL::on()));
        }
	}
	public static function standardObjectResult($result){
		if(!isset(array_keys($result)[0])){
			return new \stdClass();
		}
		return $result;
	}
	public static function query($q,$i=''){
		$r = SQL::resultToArray(SQL::exe($q));
		return ($i!='')?SQL::standardObjectResult(array_column($r,null,$i)):$r;
	}
	public static function queryOne($Q,$paran=null){
		$r = SQL::resultToArray(SQL::exe($Q));
		if(!is_null($r[0])){ 
			return isset($paran)?$r[0][$paran]:$r[0]; 
		}
		throw new BridgeException('.not-found');
	}
	
	public static function fn($Q){
		$r = SQL::queryOne('select '.$Q.' as r');
		return $r['r'];
	}
	public static function resultToArray($q){
		$o = array();
		if(gettype($q)=='object'){
			while( $r = mysqli_fetch_assoc($q) ) {
				$o[] = $r;
			}
		}
		return $o;
	}
	public static function lastID(){
		return mysqli_insert_id(SQL::on());
	}

	public static function __callStatic($methodName,$arguments){
		if(method_exists(get_called_class(),$methodName)){
			throw new BridgeException('.sql-function-not-found',[
				'function'=>$methodName
			]);
		}
		return SQL::fn($methodName.'('.implode(',',array_map(function($argument){ return '\''.$argument.'\''; },$arguments)).')');
    }
}
?>