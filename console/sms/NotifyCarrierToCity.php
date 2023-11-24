<?php
/** @var City $city */
/** @var Cargo $cargo */

use common\models\Cargo;
use common\models\City;
use morphos\Russian\GeographicalNamesInflection;
use morphos\Cases;
use morphos\Russian\RussianLanguage;

$cityName = GeographicalNamesInflection::getCase($city->title_ru, Cases::ACCUSATIVE);
?>
Новый груз <?= RussianLanguage::in($cityName) ?>: <?= Yii::getAlias('@domain')."/g{$cargo->id}" ?>
