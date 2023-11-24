<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 11.10.17
 * Time: 15:57
 */

namespace common\helpers;

use DateTime;
use Yii;
class Convertor{

    static public function time($seconds){
        $seconds = $seconds == '' ? 0 : $seconds;

        $dtF = new DateTime('@0');
        $dtT = new DateTime("@$seconds");
        $diff = $dtF->diff($dtT);

        $d = $diff->d;
        $h = $diff->h;
        $m = $diff->i;

        if( $m >= 30 ){
            $m = 0;
            $h++;

            if( $h == 24 ){
                $h = 0;
                $d++;
            }
        }

        $str = '';
        if( $d ){
            $str .= "$d дн ";
            if( $h )
                $str .= "$h ч";
        }
        else{
            if( $h )
                $str .= "$h ч ";
            else
                $str .= "$m мин";
        }

        return $str;
    }

    static public function distance($m){
        $m = $m == '' ? 0 : $m;

        $km = 0;
        if( $m > 1000 ){
            $km = round($m/1000);
        }

        if( $km ) {
            $value = Yii::$app->formatter->asInteger($km);
            $str = Yii::t('app', "{n} км", ['n' => $value]);
        }
        else
            $str = Yii::t('app', "{n} м", ['n'=>$m]);

        return $str;
    }
}
