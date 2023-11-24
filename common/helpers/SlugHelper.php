<?php
/**
 * Created by PhpStorm.
 * User: Морозов Михаил
 * Date: 21.02.2017
 * Time: 15:32
 */

namespace common\helpers;


class SlugHelper
{

	/**
	 * Транслитирация кирилической строки
	 *
	 * @param $string
	 * @return string
	 */
	public static function rus2translit($string){
		// Нужно, что бы транслитеры правильно отображались в поддомене, потому замена мягкого и твердого знаков на апостроф недопустима
		$converter = array(
			'а' => 'a',   'б' => 'b',   'в' => 'v',
			'г' => 'g',   'д' => 'd',   'е' => 'e',
			'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
			'и' => 'i',   'й' => 'y',   'к' => 'k',
			'л' => 'l',   'м' => 'm',   'н' => 'n',
			'о' => 'o',   'п' => 'p',   'р' => 'r',
			'с' => 's',   'т' => 't',   'у' => 'u',
			'ф' => 'f',   'х' => 'h',   'ц' => 'c',
			'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
			'ь' => '',  'ы' => 'y',   'ъ' => '',
			'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

			'А' => 'A',   'Б' => 'B',   'В' => 'V',
			'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
			'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
			'И' => 'I',   'Й' => 'Y',   'К' => 'K',
			'Л' => 'L',   'М' => 'M',   'Н' => 'N',
			'О' => 'O',   'П' => 'P',   'Р' => 'R',
			'С' => 'S',   'Т' => 'T',   'У' => 'U',
			'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
			'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
			'Ь' => '',  'Ы' => 'Y',   'Ъ' => '',
			'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya'
		);

        $slug = strtr($string, $converter);
        //Любые символы НЕ БУКВЫ и НЕ ЦИФРЫ заменяем '-'
        $slug = preg_replace('/[^\w\d]/', '-', $slug);
        //повторяющиеся тире заменяем одним
        $slug = preg_replace('/-{2,}/', '-', $slug);
        //если тире вначале или конце, то удаляем
        $slug = preg_replace('/(^-|-$)/', '', $slug);

        return $slug;
	}

	public static function genSlug($slugParts): string
    {
        if(!is_array($slugParts)) $slugParts = [$slugParts];

        $slugParts = implode('-', $slugParts);

        $replacement = '-';

        $string = SlugHelper::rus2translit($slugParts);
        $string = preg_replace('/[^a-zA-Z0-9=\s—–-]+/u', '', $string);
        $string = preg_replace('/[=\s—–-]+/u', $replacement, $string);
        $string = trim($string, $replacement);

        return strtolower($string);
    }
}