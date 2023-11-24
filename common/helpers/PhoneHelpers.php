<?php
namespace common\helpers;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;

class PhoneHelpers{

    /**
     * @param $phone - номер
     * @param string $coutry_code - код страны
     * @param $together - слитно
     * @return string
     * @throws NumberParseException
     */
	static public function formatter($phone, $coutry_code = '', $together = false){
		$coutry_code = empty($coutry_code) ? 'RU' : $coutry_code;

		$phoneUtil = PhoneNumberUtil::getInstance();
		$phone = $phoneUtil->parse($phone, $coutry_code);

		$result = $phoneUtil->format($phone, PhoneNumberFormat::INTERNATIONAL);

		//если слитно, то убираем пробелы и тире
		if( $together ){
            $result = preg_replace('/[\s-]/', '', $result);
        }

		return $result;
	}
}