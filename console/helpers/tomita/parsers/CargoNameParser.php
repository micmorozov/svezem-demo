<?php

namespace console\helpers\tomita\parsers;

use phpMorphy;
use Yii;

class CargoNameParser
{
    private $tomitaData = null;
    private $words = [];

    public function __construct($data)
    {
        $this->tomitaData = json_decode($data, 1);
    }

    public function getWords()
    {
        if ( !$this->tomitaData) {
            return null;
        }

        if ( !$this->words) {
            $fact = $this->tomitaData[0]['FactGroup'][0]['Fact'];

            $words = [];

            foreach ($fact as $item) {
                $words[] = [
                    'real' => $item['Field'][0]['Value'],
                    'im' => $this->change($item['Field'][0]['Value']),
                    'rod' => $this->change($item['Field'][0]['Value'], 'РД'),
                    'vin' => $this->change($item['Field'][0]['Value'], 'ВН'),
//                    'im' => $item['Field'][1]['Value'],
//                    'rod' => $item['Field'][2]['Value'],
//                    'vin' => $item['Field'][3]['Value']
                ];
            }

            $this->words = $words;
        }

        return $this->words;
    }

    public function change($text, $case = 'ИМ'){
        /** @var phpMorphy $morphy */
        $morphy = Yii::$app->morphy->getMorphy();

        //разбиваем на слова
        $wordList = preg_split('/\s+/', $text);
        foreach($wordList as $key => $word){
            $word = preg_replace("/[a-zA-Z0-9,]/", "", $word);
            $wordList[$key] = mb_strtoupper($word);
        }

        $resultWordList = [];
        //преобразуем каждое слово в родительный падеж
        foreach($wordList as $word){
            $pos = $morphy->getPartOfSpeech($word);

            //преобразуем только существительные и прилогательные
            if( in_array($pos[0], ['С','П']) ){
                //опции для преобразования слова к нужной форме
                $castFormOpt = [$case];

                //получем информацию для определения числа (единственного, множественного)
                $GramInfo = $morphy->getGramInfo($word);
                if( $GramInfo ){
                    //определяем число
                    if( in_array('ЕД', $GramInfo[0][0]['grammems']) )
                        $castFormOpt[] = 'ЕД';
                    else
                        $castFormOpt[] = 'МН';

                    //определяем род
                    if( in_array('МР', $GramInfo[0][0]['grammems']) )
                        $castFormOpt[] = 'МР';
                    else
                        $castFormOpt[] = 'ЖР';
                }

                $res = $morphy->castFormByGramInfo($word, null, $castFormOpt);

                if( $res ) {
                    $resultWordList[] = $res[0]['form'];
                } else {
                    $resultWordList[] = $word;
                }
            }
            else{
                $resultWordList[] = $word;
            }
        }

        return implode(' ', $resultWordList);
    }
}
