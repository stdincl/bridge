<?php
namespace stdincl\bridge;

use stdincl\bridge\IO;

class Image {


	## Metodo para crear imagen de ANCHO o ALTO MAXIMO
	public static function Cuadro($lados,$imagen,$retornar=false){
		$i = new Image();
		return $i->_Cuadro($lados,$imagen,$retornar);
	}	
	## Metodo para crear imagen de ANCHO maximo (sin importar alto)
	public static function AnchoMax($ancho,$imagen,$retornar=false){
		$i = new Image();
		return $i->_AnchoMax($ancho,$imagen,$retornar);
	}
	## Metodo para crear imagen de ALTO maximo (sin importar ancho)
	public static function AltoMax($alto,$imagen,$retornar=false){
		$i = new Image();
		return $i->_AltoMax($alto,$imagen,$retornar); 
	}
	## Metodo para crear imagen Cuadrada
	# nota: Alineacion = Tope+Izquierda=0; Fondo+Derecha=1; Centrada+Centrada=2;
	public static function Encuadrar($medida,$imagen,$alineacion=2,$retornar=false){
		$i = new Image();
		return $i->_Encuadrar($medida,$imagen,$alineacion,$retornar);
	}



	var $imagen_recurso;					var $imagen_salida;	
	var $imagen_recurso_ancho;				var $imagen_salida_ancho;
	var $imagen_recurso_alto;				var $imagen_salida_alto;
	var $imagen_recurso_desde_x = 0;		var $imagen_salida_desde_x = 0;
	var $imagen_recurso_desde_y = 0;		var $imagen_salida_desde_y = 0;
	var $escala;						    var $imagen_imprimible;	
	var $rango_menor = 1;
	var $rango_mayor = 1000;
	function ValidarMedida($valor){
		if ($valor>=($this->rango_menor) and $valor<$this->rango_mayor){
			return $valor;			
		}else{
			if($valor	<=	($this->rango_menor-1)	){
				return  $this->rango_menor; 
			}else if($valor	>=	$this->rango_mayor){
				return 	$this->rango_mayor; 
			}else{
				return valor;
			}
		}
	}
	function CrearImagen($imagen){
		$this->imagen_recurso		=	$imagen;
		$this->imagen_salida 		= 	imagecreatefromstring($this->imagen_recurso);
		$this->imagen_recurso_ancho	= 	imagesx($this->imagen_salida);
		$this->imagen_recurso_alto	= 	imagesy($this->imagen_salida);
		if($this->imagen_recurso_ancho<=0){
			IO::exception('invalid-image');
		}
	}
	function CrearSalida($ancho,$alto,$retornar=false){
		$this->imagen_salida_ancho = $ancho;
		$this->imagen_salida_alto = $alto;				
		$this->imagen_imprimible	= 	imagecreatetruecolor($this->imagen_salida_ancho,$this->imagen_salida_alto);		
		imagecopyresampled(
			$this->imagen_imprimible,
			$this->imagen_salida,
			$this->imagen_recurso_desde_x,
			$this->imagen_recurso_desde_y,
			$this->imagen_salida_desde_x,
			$this->imagen_salida_desde_y,
			$this->imagen_salida_ancho,
			$this->imagen_salida_alto,
			$this->imagen_recurso_ancho,
			$this->imagen_recurso_alto
		);
		if($retornar){
			ob_start();
			imagejpeg($this->imagen_imprimible, NULL,100); 
			return ob_get_clean();
		}else{
			imagejpeg($this->imagen_imprimible, NULL,100);
		}
		imagedestroy($this->imagen_imprimible);
	}	
	function _Cuadro($lados,$imagen,$retornar=false){
		$lados=$this->ValidarMedida($lados);
		$this->CrearImagen($imagen);
		if ( $this->imagen_recurso_ancho >= $this->imagen_recurso_alto ){
			$this->escala=$lados/$this->imagen_recurso_ancho;
		}else{
			$this->escala=$lados/$this->imagen_recurso_alto;
		}
		return $this->CrearSalida($this->imagen_recurso_ancho * $this->escala , $this->imagen_recurso_alto * $this->escala,$retornar);
	}
	function _AnchoMax($ancho,$imagen,$retornar=false){
		$ancho=$this->ValidarMedida($ancho);
		$this->CrearImagen($imagen);
		$this->escala=$ancho/$this->imagen_recurso_ancho;
		return $this->CrearSalida($this->imagen_recurso_ancho * $this->escala , $this->imagen_recurso_alto * $this->escala,$retornar);
	}	
	function _AltoMax($alto,$imagen,$retornar=false){
		$alto=$this->ValidarMedida($alto);
		$this->CrearImagen($imagen);
		$this->escala=$alto/$this->imagen_recurso_alto;			
		return $this->CrearSalida($this->imagen_recurso_ancho * $this->escala , $this->imagen_recurso_alto * $this->escala,$retornar);
	}	
	function _Encuadrar($medida,$imagen,$alineacion=2,$retornar=false){
		$medida=$this->ValidarMedida($medida);
		$this->CrearImagen($imagen);
		if ( $this->imagen_recurso_ancho <= $this->imagen_recurso_alto ){
			$this->escala = $this->imagen_salida_ancho/$this->imagen_recurso_ancho;
			if($alineacion!=0){	
				if($alineacion==1){	
					$this->imagen_salida_desde_y=($this->imagen_recurso_alto-$this->imagen_recurso_ancho);
				}
				if($alineacion==2){	
					$this->imagen_salida_desde_y=($this->imagen_recurso_alto-$this->imagen_recurso_ancho)/2;
				}						
			}
			$this->imagen_recurso_alto = $this->imagen_recurso_ancho;				
		}else {
			$this->escala = $this->imagen_salida_alto/$this->imagen_recurso_alto;
			if($alineacion!=0){	
				if($alineacion==1){	
					$this->imagen_salida_desde_x=($this->imagen_recurso_ancho-$this->imagen_recurso_alto);
				}else if($alineacion==2){	
					$this->imagen_salida_desde_x=($this->imagen_recurso_ancho-$this->imagen_recurso_alto)/2;
				}						
			}
			$this->imagen_recurso_ancho = $this->imagen_recurso_alto;				
		}			
		return $this->CrearSalida($medida,$medida,$retornar);
	}
}
?>