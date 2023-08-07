<?php
namespace stdincl\bridge;


class Check {
	public static function isInt($dato){
		return intval($dato)==$dato;
	}
	public static function isFloat($dato){
		return floatval($dato)==$dato;
	}
	public static function isEmail($dato){
		return filter_var($dato, FILTER_VALIDATE_EMAIL);
	}
	public static function isRut($rut){
		$rut = str_replace('.','',$rut);
		if(strlen($rut) > 10){
			return false;
		}
		if(strstr($rut, '-') == false){
			return false;
		}
		$array_rut_sin_guion = explode('-',$rut);
		$rut_sin_guion = $array_rut_sin_guion[0];
		$digito_verificador = $array_rut_sin_guion[1];

		if(is_numeric($rut_sin_guion)== false){
			return false;
		}
		if ($digito_verificador != 'k' and $digito_verificador != 'K'){
			if(is_numeric($digito_verificador)== false){
				return false;
			}
		}
		$cantidad = strlen($rut_sin_guion);
		for ( $i = 0; $i < $cantidad; $i++){
			$rut_array[$i] = $rut_sin_guion[$i];
		}
		$i = ($cantidad-1);
		$x=$i;
		for ($ib = 0; $ib < $cantidad; $ib++){
			$rut_reverse[$ib]= $rut_array[$i];
			$rut_reverse[$ib];
			$i=$i-1;
		}
		$i=2;
		$ib=0;
		$acum=0;
		do{
		    if( $i > 7 ){
			    $i=2;
		    }
			$acum = $acum + ($rut_reverse[$ib]*$i);
			$i++;
			$ib++;
		}while( $ib <= $x);

		$resto = $acum%11;
		$resultado = 11-$resto;
		if ($resultado == 11) { $resultado=0; }
		if ($resultado == 10) { $resultado='k'; }
		if ($digito_verificador == 'k' or $digito_verificador =='K') { $digito_verificador='k';}

		if ($resultado == $digito_verificador){
			return true;
		}else{
			return false;
		}
    }
	public static function isDate($dato){

		if ( preg_match( "([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})", $dato, $r ) ) {
			return true;
		} else if ( preg_match( "([0-9]{1,4})-([0-9]{1,2})-([0-9]{1,2})", $dato, $r ) ) {
			return true;
		} else if (preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $dato, $r)) { 
			if (checkdate($r[2], $r[3], $r[1])) { 
				return true; 
			} 
    	}  else {
			return false;
		}
	}
	public static function getExtension($dato){
		$res = explode('.', $dato); 
		if(isset($res[count($res)-1])){
			return strtolower($res[count($res)-1]);
		}else{
			return '';
		}
	}
	public static function isExtension($dato, $parametros){
		$parametros = is_array($parametros)?$parametros:array($parametros);
		$res = explode('.', $dato);
		return (
			in_array(
				(isset($res[count($res)-1]))?strtolower($res[count($res)-1]):'', 
				$parametros
			)
			||
			in_array(
				(isset($res[count($res)-1]))?strtoupper($res[count($res)-1]):'', 
				$parametros
			)
		);
	}
    public static function onlyChars($String, $PermitidosList='abcdefghijklmnopqrstuvwxyz0123456789-_'){
		for($i=0 ; $i < strlen($String) ; $i++){
			if(strpos($PermitidosList, strtolower($String[$i])) === false){
				return false;
			}
		}
        return true;
	}
}
?>