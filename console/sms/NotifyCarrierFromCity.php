<?php
/** @var City $city */
/** @var Cargo $cargo */

use common\models\Cargo;
use common\models\City;
use morphos\Russian\GeographicalNamesInflection;
use morphos\Cases;

$cityName = GeographicalNamesInflection::getCase($city->title_ru, Cases::GENITIVE);
?>
Новый груз из <?= $cityName ?>: <?= Yii::getAlias('@domain')."/g{$cargo->id}" ?>
