<?php
/** 
 * Класс по работе с курсами валют ЦБ
 *
 */
namespace console\models;

use DOMDocument;

class CBRAgent{
	
	private $listCurrency = [];
	
	/**
	 * Загружаем валюты с ЦБ в переменную
	 */
	public function load(){
		$xml = new DOMDocument();
		$url = 'http://www.cbr.ru/scripts/XML_daily.asp';
	
		if (@$xml->load($url)){
			$root = $xml->documentElement;
			$items = $root->getElementsByTagName('Valute');
			foreach ($items as $item){
				$code = $item->getElementsByTagName('CharCode')->item(0)->nodeValue;
				$curs = $item->getElementsByTagName('Value')->item(0)->nodeValue;
				$nominal = $item->getElementsByTagName('Nominal')->item(0)->nodeValue;
	
				$this->listCurrency[$code] = floatval(bcdiv(str_replace(',', '.', $curs), $nominal, 4));
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Получаем курс по коду валюты
	 * @param unknown $code
	 */
	public function get($code){
		return isset($this->listCurrency[$code]) ? $this->listCurrency[$code] : 0;
	}
}