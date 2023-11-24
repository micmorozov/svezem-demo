<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 18.09.18
 * Time: 13:10
 */

namespace common\modules\NeuralNetwork\ngram;


abstract class Ngramm
{
    /**
     * @param $text
     * @return array
     */
    abstract static public function getNram($text);
}