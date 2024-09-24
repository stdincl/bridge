<?php 
namespace stdincl\bridge\util;
use stdincl\bridge\IO;
use stdincl\bridge\util\Check;
use stdincl\bridge\exception\BridgeException;
class Translator {
	public static $translator = null;
	public $selectedLang = '';
	public $langs = [];
	public function text($text,$labels=[]){
		$langDictionary = $this->getSelectedLangDictionary();
		$text = isset($langDictionary[$text])?$langDictionary[$text]:$text;
		$labels = is_array($labels)?$labels:[];
		foreach($labels as $label=>$value){
			$text = str_replace('<:'.$label.':>', str_replace("'",'',$value), $text);
		}
		return $text;
	}
	public function getSelectedLangDictionary(){
		if(isset($this->langs[$this->selectedLang])){
			return $this->langs[$this->selectedLang];
		}
		$this->setSelectedLang(Environment::get()->settings('lang'));
		if(!isset($this->langs[$this->selectedLang])){
			$this->langs[$this->selectedLang] = json_decode(file_get_contents($this->langPath($this->selectedLang)),1);	
		}
		return $this->langs[$this->selectedLang];
	}
	public function setSelectedLang($lang){
		if(!Check::onlyChars($lang,'abcdefghijklmnopqrstuvwxyz0123456789_')){
			throw new BridgeException("unsupported-lang");
		}
		if(!file_exists($this->langPath($lang))){
			throw new BridgeException("unsupported-lang");
		}
		$this->selectedLang = $lang;
	}

	private function langPath($lang){
		return Environment::get()->root().'/bridge/lang/'.$lang.'.json';
	}

	public static function get(){
        if(is_null(Translator::$translator)){
        	Translator::$translator = new Translator();
        }
        return Translator::$translator;
	}
	public static function __callStatic($m,$a){
		return Translator::get()->text(strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $m)));
    }
}
?>