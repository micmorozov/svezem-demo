<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 18.09.18
 * Time: 13:10
 */

namespace common\modules\NeuralNetwork\ngram;

use Wamania\Snowball\Russian;

class Unigram extends Ngramm
{
    static public function getNram($text){
        //удаляем цифры
        $text = preg_replace('/\d/', '',$text);
        //удаляем email
        $text = preg_replace('/\w+@\w+\.\w+/u', '', $text);
        //удаляем все кроме символов и пробелов
        $text = preg_replace('/[^ \w]+/u', '', $text);
        //удаляем слова короче 3 символов
        $text = preg_replace('/\b\w{1,2}\b/u', '', $text);
        //удаляем пробелы идущие подряд
        $text = preg_replace('/\s+/', ' ', $text);

        $words = preg_split('/\s/', $text);

        $stemmer = new Russian();
        $stemWords = [];
        foreach($words as $word){
            $stemWord = $stemmer->stem($word);

            if( mb_strlen($stemWord) < 3 )
                continue;

            $stemWords[] = $stemWord;
        }

        return $stemWords;
    }
}