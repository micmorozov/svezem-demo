<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 22.11.17
 * Time: 11:31
 */

namespace common\helpers\twig_extensions;

use morphos\Russian\GeographicalNamesInflection;
use morphos\Russian\RussianLanguage;
use Twig\TwigFilter;

/**
 * Class GeoRussianDecline
 * @package common\helpers\twig_extensions
 */
class GeoRussianDecline extends RussianDeclineBase
{
    /**
     * GeoRussianDecline constructor.
     * @param array $filters
     */
    public function __construct($filters = []){
        $filters = [
            new TwigFilter('in', [$this, 'inFilter'])
        ];

        parent::__construct($filters);
    }

    /**
     * @param $text
     * @param $case
     * @return mixed
     */
    protected function padezhMethod($text, $case){
        // Если text представлен в виде город_регион, вырезаем город, обрабатываем его и потом добавляем регион
        // TODO Это лайфхак для городов с одинаковыми названиями
        $parts = explode('_', $text);
        $cityName = GeographicalNamesInflection::getCase($parts[0], $case);
        if(isset($parts[1])) {
            $cityName .= ' (' . $parts[1] . ')';
        }

        return $cityName;
    }

    /**
     * @param $word
     * @return string
     */
    public function inFilter($word){
        return RussianLanguage::in($word);
    }
}