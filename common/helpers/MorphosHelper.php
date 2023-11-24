<?php
/**
 * Хелпер по работе с библиотекй морфологии Morphos
 * https://github.com/wapmorgan/Morphos/
 */

namespace common\helpers;

use morphos\Russian\NounDeclension;
use morphos\Cases;

class MorphosHelper {
    /**
    * Склоняем каждое слово в предложении
    */
    static public function getCaseSentence($sentence, $case){
        $result = [];
        $parts = \yii\helpers\StringHelper::explode($sentence, ' ');
        foreach($parts as $word){
            array_push($result, NounDeclension::getCase($word, $case));
        }

        return implode(' ', $result);
    }
}