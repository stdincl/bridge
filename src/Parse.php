<?php
namespace stdincl\bridge;

use stdincl\bridge\IO;
use stdincl\bridge\Check;
use stdincl\bridge\Image;

class Parse {
	public static function string(&...$li){
		foreach ($li as $i => &$s) {
			$s = htmlentities($s,ENT_COMPAT); 
			try { 
				$s = mysqli_real_escape_string(SQL::on(),$s);
			} catch (Exception $e) { 
				$s = mysql_real_escape_string($s);
			}
		}
		
	}
	public static function decode(&...$li){
		foreach ($li as $i => &$s) {
			$s = html_entity_decode($s);
		}
	}
	public static function email(&...$li){
		foreach ($li as $i => &$e) {
			if(!preg_match("/^[a-z]+([\.]?[a-z0-9_-]+)*@[a-z0-9]+([\.-]+[a-z0-9]+)*\.[a-z]{2,3}$/", strtolower($e) )){
				IO::exception('.email-format-invalid');
			}
		}
	}
	public static function time(&...$li){
		foreach ($li as $i => &$e) {
			if(!preg_match("/^([0-9]{1,2}):([0-9]{1,2})$/",$e,$r)){
				IO::exception('.time-format-invalid');
			}
		}
	}
	public static function date(&...$li){
		foreach ($li as $i => &$e) {
			$isValid = false;
			if(preg_match("/^([0-9]{1,2}):([0-9]{1,2})$/",$e,$r)){
				$isValid = true;
			}else if(preg_match("/^([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})$/",$e,$r)){
				$isValid = true;
			}else if(preg_match("/^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/",$e,$r)){
				$isValid = true;
			}else if(preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/",$e,$r)){ 
				if(checkdate($r[2],$r[3],$r[1])){
					$isValid = true;
				}
	    	}
	    	if(!$isValid){
	    		IO::exception('.date-format-invalid');
	    	}
		}
	}
	public static function id(&...$li){
		foreach ($li as $i=>&$n) {
			$n = preg_replace('/[^0-9-]/','',$n);
			$n = $n==''?0:$n;
			if($n<=0){
				IO::exception('incorrect-id');
			}
	    }
	}
	public static function int(&...$li){
		foreach ($li as $i=>&$n) {
			$n = preg_replace('/[^0-9-]/','',$n);
			$n = $n==''?0:$n;
	    }
	}
	public static function bool(&...$li){
		foreach ($li as $i=>&$b) {
			$b = ($b>0||$b==='on')?1:0;
	    }
	}
	public static function float(&...$li){
		foreach ($li as $i => &$f) {
			$f = str_replace(',','.',preg_replace('/[^0-9.,-]/','',$f));
			$f = $f==''?0:$f;
		}
	}
	public static function fromBase64(&...$li){
		foreach ($li as $i => &$s) {
			$s = addslashes(base64_decode(str_replace(' ','+', $s)));
		}
	}
	public static function blob(&$f,$formatosPermitidos=array(),$autoResizeTo=0){ 
		Parse::int($autoResizeTo);
		if($f['error']==1){
			IO::exception(str_replace('<:size:>', ini_get('upload_max_filesize'), IO::_('.upload-max-size-exceeded')));
		}
		if(is_array($f) and isset($f['tmp_name']) and $f['tmp_name']!='' and $f['size']!=''){
			$file = file_get_contents($f['tmp_name']);
			if(isset($formatosPermitidos[0])){
				if(!Check::isExtension($f['name'],$formatosPermitidos)){
					$tipos = '';
					$ctipos = count($formatosPermitidos);
					foreach($formatosPermitidos as $i=>$a){
						$tipos .= ($i==0?'':(($i==$ctipos-1)?' o ':', ')).$a;
					}
					IO::exception(
						str_replace(
							'<:file:>', 
							$f['name'], 
							str_replace(
								'<:types:>', 
								$tipos, 
								IO::_('.invalid-format')
							)
						)
					);
				}
			}
			if($autoResizeTo>0){
				if(Check::isExtension($f['name'],array('jpeg','png'))){
					$file = Image::Cuadro($autoResizeTo,$file,true);  
				}
			}
			$f = $file;
		}else{
			IO::exception('.file-required');
		}
	}

	public static function file(&$f,$formatosPermitidos=array()){ 
		if($f['error']==1){
			IO::exception(str_replace('<:size:>', ini_get('upload_max_filesize'), IO::_('.upload-max-size-exceeded')));
		}
		if(is_array($f) and isset($f['tmp_name']) and $f['tmp_name']!='' and $f['size']!=''){
			if(isset($formatosPermitidos[0])){
				if(!Check::isExtension($f['name'],$formatosPermitidos)){
					$tipos = '';
					$ctipos = count($formatosPermitidos);
					foreach($formatosPermitidos as $i=>$a){
						$tipos .= ($i==0?'':(($i==$ctipos-1)?' o ':', ')).$a;
					}
					IO::exception(
						str_replace(
							'<:file:>', 
							$f['name'], 
							str_replace(
								'<:types:>', 
								$tipos, 
								IO::_('.invalid-format')
							)
						)
					);
				}
			}
			$tempPath = IO::root().'/bridge/tmp/'.rand(10000000,10000000000).time().'.tmp';
			while (file_exists($tempPath)) {
				$tempPath = IO::root().'/bridge/tmp/'.rand(10000000,10000000000).time().'.tmp';
			}
			move_uploaded_file($f['tmp_name'], $tempPath);
			$f = $tempPath;
		}else{
			IO::exception('.file-required');
		}
	}
}
?>