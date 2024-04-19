<?php
namespace stdincl\bridge;

use stdincl\bridge\SQL;
use stdincl\bridge\BridgeException;

class IO {
	static $lang = null;
	static $settings = null;
	static $users = array();
	static $sessionStarted = false;
	static $defaultSetttings = array(
		"debug"=>false,
		"compilation"=>"1000",
		"lang"=>"es",
		"mysql"=>array(
			"hostname"=>"localhost",
			"database"=>"database_name",
			"username"=>"user_name",
			"password"=>"user_pass"
		),
		"socket"=>array(
			"server"=>"",
			"port"=>""
		),
		"driver"=>array(
			"session"=>"select * from io_user where usuario = '<:user:>' and clave=md5('<:pass:>')",
			"user"=>array(
				"name"=>"",
				"picture"=>""
			),
			"pages"=>[]
		),
		"google"=>array(
			"maps"=>array(
				"apikey"=>""
			)
		),
		"flow"=>array(
			"mode"=>"integration",
			"integration"=>array(
				"url"=>"https://sandbox.flow.cl/api",
				"apikey"=>"",
				"secretkey"=>""
			),
			"production"=>array(
				"url"=>"https://www.flow.cl/api",
				"apikey"=>"",
				"secretkey"=>""
			)
		),
		"unsplash"=>array(
			"key"=>"",
			"secret"=>"",
			"auth"=>""
		),
		"transbank"=>array(
			"maxretries"=>3,
			"mode"=>"integration",
			"integration"=>array(
				"url"=>"",
				"apikey"=>"",
				"comercio"=>""
			),
			"production"=>array(
				"url"=>"",
				"apikey"=>"",
				"comercio"=>""
			)
		),
		"facebook"=>array(
			"app"=>array(
				"id"=>"",
				"secret"=>""
			)
		),
		"sessionkey"=>"set-random-key-in-json",
		"session"=>array(
			"USER"=>"query"
		)
	);
	public static function array_merge_recursive_distinct($array1,$array2){
		$level=0;
        $merged = [];
        if(!empty($array2["mergeWithParent"])||$level==0){
            $merged = $array1;
        }
        foreach($array2 as $key=>$value){
            if(is_numeric($key)){
                $merged [] = $value;
            }else{
                $merged[$key] = $value;
            }
            if(is_array($value)&&isset($array1[$key])&&is_array($array1[$key])){
                $level++;
                $merged[$key] = IO::array_merge_recursive_distinct($array1 [$key], $value);
                $level--;
            }
        }
        unset($merged["mergeWithParent"]);
        return $merged;
    }
	public static function root(){
		$reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
		return dirname(dirname(dirname($reflection->getFileName())));
	}
	public static function psr4(){
		$json = json_decode(file_get_contents(IO::root().'/composer.json'),1);
		return array_keys($json['autoload']['psr-4'])[0];
	}
	public static function settingsDirectory(){
		return IO::root().'/bridge';
	}
	public static function settings(){
		if(is_null(IO::$settings)){
			$json = json_decode(file_get_contents(IO::settingsDirectory().'/settings.json'),1);
			IO::$settings = IO::array_merge_recursive_distinct(
				IO::$defaultSetttings,
				is_null($json)?array():$json
			);	
		}
		return IO::$settings;
	}
	public static function translate($l){
		$f = IO::settingsDirectory().'/lang/'.$l.'.json';
		if(file_exists($f)){
			$_SESSION['IO_LANG'] = $l;
			$_SERVER['LANG'] = null;
		}
	}
	public static function _($t){
		if(is_null(IO::$lang)){
			if(!isset($_SESSION['IO_LANG'])){
				$settings = IO::settings();
				IO::translate($settings['lang']);
			}
			$f = IO::settingsDirectory().'/lang/'.$_SESSION['IO_LANG'].'.json';
			if(file_exists($f)){
				IO::$lang = json_decode(file_get_contents($f),true);
			}else{
				return $t;
			}
		}
		return isset(IO::$lang[$t])?IO::$lang[$t]:$t;
	}
	public static function media(){
		return IO::root().'/media';
	}
    public static function exception($error,$customHeaders=array()){
		$customHeaders = is_array($customHeaders)?$customHeaders:array();
		if(isset($customHeaders[0])){
			$error['_error_'] = array();	
			foreach($customHeaders as $key=>$value){
				header('x-bridge-'.$key.': '.$value);
				$error['_bridge_']['x-bridge-'.$key] = $value;
				$e->addParameter($key,$value);
			}
		}
		IO::debug($error);
		$e = new BridgeException($error);
    	throw $e;
	}
    public static function denied(){
		IO::exception('.denied');
	}
	public static function log($text){
		file_put_contents(
			IO::root().'/bridge/tmp/log',
			$text."\n",
			FILE_APPEND
		);
	}
    public static function session($var,$val=null){ 
    	if(!IO::$sessionStarted){
    		IO::$sessionStarted = true;
			session_start();
    	}
		$settings = IO::settings();
		if(!is_null($val)){ 
			$_SESSION[$settings['sessionkey']][$var] = serialize($val); 
		}
		if(isset($_SERVER[$settings['sessionkey']][$var])){
			unset($_SERVER[$settings['sessionkey']][$var]);
		}
		return unserialize($_SESSION[$settings['sessionkey']][$var]);
	}

	public static function user($name){
		$settings = IO::settings();
		if(!isset($settings['session'][$name])){
			IO::exception('.log-out');
		}
		$token = IO::session($name);
		if($token==''){
			IO::exception('.not-found');
		}
		IO::$users[$name] = SQL::queryOne(
			str_replace(
				'<:session:>',
				$token,
				$settings['session'][$name]
			)
		);
		return IO::$users[$name];
	}

	public static function debug($msg){
		$settings = IO::settings();
		if($settings['debug']===true){
			$data  = date('[Y-m-d H:i:s] ').print_r($msg,1)."\n";
			$trace = debug_backtrace();
			foreach ($trace as $i => $t) {
				if($i==0){
					$data .= $t['file'].':'.$t['line']."\n";
				}
			}
			file_put_contents(IO::root().'/bridge/tmp/log/.debug', $data,FILE_APPEND);
		}
	}
	public static function googleMapsScript(){
		$settings = IO::settings();
		if($settings['google']['maps']['apikey']!=''){
			echo '<script src="//maps.googleapis.com/maps/api/js?key='.$settings['google']['maps']['apikey'].'&libraries=visualization,drawing,geometry&language=es"></script>';
		}
	}
	public static function location($path){
		header('location:'.$path);
		die();
	}
    public static function shutdown(){ 
    	if(class_exists('SQL',false)){
    		SQL::off();
    	}
    	array_walk($_SERVER['o'], function(&$e, $k){$e = null;});
    	$_SERVER['o'] = null;
    	unset($_SERVER['o']);
		$_SERVER['LANG'] = null;
		$_SERVER['R'] = null;
    	array_walk($_GET, function(&$e, $k){$e = null;});
    	array_walk($_POST, function(&$e, $k){$e = null;});
    	$_SERVER = null;
    	session_write_close();
    }
}
?>