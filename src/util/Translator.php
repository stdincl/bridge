<?php 
namespace stdincl\bridge\util;

use stdincl\bridge\IO;
use stdincl\bridge\util\Check;
use stdincl\bridge\exception\BridgeException;

/**
 * Clase para acceder a archivos de lenguaje de servidor
 * 
 * @author Diego Rodriguez Gomez
 */
class Translator {

	/**
	 * Indica el nombre del archivo de lenguaje seleccionado
	 */
	public $selectedLang = '';

	/**
	 * Lista de archivos de lenguaje instanciados
	 */
	public $langs = [];

	/**
	 * Traduce un texto usando el archivo de lenguaje seleccionado y reemplaza 
	 * los labels que contenga.
	 * 
	 * @example 
	 * Para un archivo de lenguaje:
	 * { "demo": "Usuario <:user:> no existe" }
	 * Ejecutar:
	 * Translator::text('demo',['administrador'])
	 * Retornará:
	 * "Usuario administrador no existe"
	 * 
	 * 
	 * @param string $text   Texto a traducir
	 * @param array  $labels Diccionario de labels para traducir en el texto. 
	 * 
	 * @return string Texto traducido o mismo texto si no se encuentra
	 */
	public function text($text,$labels=[]){
		$langDictionary = $this->getSelectedLangDictionary();
		$text = isset($langDictionary[$text])?$langDictionary[$text]:$text;
		$labels = is_array($labels)?$labels:[];
		foreach($labels as $label=>$value){
			$text = str_replace('<:'.$label.':>', str_replace("'",'',$value), $text);
		}
		return $text;
	}

	/**
	 * Retorna el archivo de lenguaje seleccionado.
	 * Si no hay uno definido usa el que esté definido por defecto en /api/settings/options.json
	 * 
	 * @return array Archivo de lenduaje seleccionado
	 */
	public function getSelectedLangDictionary(){
		if(!isset($this->langs[$this->selectedLang])){
			$this->setSelectedLang(Environment::get()->settings('lang'));
			$this->langs[$this->selectedLang] = json_decode(file_get_contents($this->langPath($this->selectedLang)),1);	
		}
		return $this->langs[$this->selectedLang];
	}

	/**
	 * Selecciona un archivo de lenguaje.
	 * Debe ser uno del directorio /api/settings/languages/{$lang}.json
	 * Si el archivo no existe selecciona uno por defecto
	 * 
	 * @return void
	 */
	public function setSelectedLang($lang){
		if(!Check::onlyChars($lang,'abcdefghijklmnopqrstuvwxyz0123456789_')){
			throw new BridgeException("unsupported-lang");
		}
		if(!file_exists($this->langPath($lang))){
			throw new BridgeException("unsupported-lang");
		}
		$this->selectedLang = $lang;
	}

	/**
	 * Retorna la ruta de acceso al archivo de lenguaje
	 * 
	 * @return string Ruta a lang.json desde el root del sitio
	 */
	private function langPath($lang){
		return Environment::get()->root().'/api/languages/'.$lang.'.json';
	}

	/**
	 * Acceso Singleton
	 * 
	 * @return Translator Instancia
	 */
	public static $translator = null;
	public static function get(){
        if(is_null(Translator::$translator)){
        	Translator::$translator = new Translator();
        }
        return Translator::$translator;
	}
	
	/**
	 * Sobrecarga para acceder a archivo de lenguaje usando funciones
	 * estáticas.
	 * 
	 * Translator::example();  retorna {lang.json}.example
	 * Translator::demoText(); retorna {lang.json}.demo-text
	 * 
	 * @return string Contenido traducido
	 */
	public static function __callStatic($m,$a){
		return Translator::get()->text(strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $m)));
    }
}
?>