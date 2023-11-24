<?php

use common\helpers\Convertor;
use common\models\Cargo;
use yii\helpers\Html;

/** @var Cargo $cargo */
/** @var bool $relativeTime */
/** @var string $contactUrl */

$relativeTime = $relativeTime ?? false;

///// Эмодзи /////////
$emCalendar = json_decode('"'."\xF0\x9F\x93\x85".'"');
$emDirection = json_decode('"'."\xE2\x86\x94".'"');
$emDescription = json_decode('"'."\xE2\x84\xB9".'"');
$emPhone = json_decode('"'."\xE2\x98\x8E".'"');
//////////////////////

//Текст заказа
$order = /*'Заказ от ' . */Yii::$app->formatter->asDate($cargo->created_at, 'dd.MM.y');
if( $relativeTime ){
    $order .= ' (' .Yii::$app->formatter->asRelativeTime($cargo->created_at).')';
}

//Текст направления
if ($cargo->city_from == $cargo->city_to) {
    $direction = /*'По городу: '.*/$cargo->cityFrom->title_ru . ', ' . $cargo->regionFrom->title_ru;
} else {
    $direction = /*'Направление: '.*/$cargo->cityFrom->title_ru.' - '.$cargo->cityTo->title_ru;
    if ($cargo->distance) {
        $direction .= ', '.Convertor::distance($cargo->distance).', '.Convertor::time($cargo->duration);
    }
}

$text = $emCalendar. ' ' . $order . "\n\n";
$text .= $emDirection . ' ' . $direction . "\n\n";
$text .= $emDescription . ' ' . $cargo->description . "\n\n";
$text .= Html::a($emPhone . " Посмотреть контакты заказчика", $contactUrl);

echo json_encode([
    'text' => $text
]);
?>