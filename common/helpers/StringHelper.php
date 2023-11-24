<?php
/**
 * Created by PhpStorm.
 * User: Морозов Михаил
 * Date: 01.07.2016
 * Time: 14:11
 */

namespace common\helpers;

class StringHelper {
	/**
	 *
	 * Генерация случайной строки символов
	 * @param integer $length Длина генерируемой строки
	 * @param string $seeds Набор символов из которых происходит генерация
	 * @return string Случайная строка символов длинной $length из набора $seeds
	 */
	public static function str_rand($length = 8, $seeds = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
		$str = '';
		$seeds_count = strlen ( $seeds );

		for($i = 0; $length > $i; $i ++) {
			$str .= $seeds {mt_rand ( 0, $seeds_count - 1 )};
		}

		return $str;
	}
}
