<?php

use common\helpers\Convertor;
use common\models\Cargo;
use yii\helpers\Html;

/** @var Cargo $cargo */
/** @var bool $relativeTime */
/** @var string $contactUrl */
/** @var string $subscribeUrl */

$relativeTime = $relativeTime ?? false;
$subscribeUrl = $subscribeUrl ?? false; // Если устанолвено, то показываем ссылку на настройку

///// Эмодзи /////////
$emCalendar = json_decode('"'."\xF0\x9F\x93\x85".'"');
$emDirection = json_decode('"'."\xE2\x86\x94".'"');
$emDescription = json_decode('"'."\xE2\x84\xB9".'"');
$emPhone = json_decode('"'."\xE2\x98\x8E".'"');
$emOption = json_decode('"' . "\xF0\x9F\x94\xA7" . '"');
//////////////////////

//Текст заказа
$order = 'Заказ от ' . Yii::$app->formatter->asDate($cargo->created_at, 'dd.MM.y');
if( $relativeTime ){
    $order .= ' (' .Yii::$app->formatter->asRelativeTime($cargo->created_at).')';
}

//Текст направления
if ($cargo->city_from == $cargo->city_to) {
    $direction = 'По городу: '.$cargo->cityFrom->title_ru . ', ' . $cargo->regionFrom->title_ru;
} else {
    $direction = 'Направление: '.$cargo->cityFrom->title_ru.' - '.$cargo->cityTo->title_ru;
    if ($cargo->distance) {
        $direction .= ', '.Convertor::distance($cargo->distance).', '.Convertor::time($cargo->duration);
    }
}

$text = $emCalendar. ' ' . $order . "\n\n";
$text .= $emDirection . ' ' . $direction . "\n\n";
$text .= $emDescription . ' ' . $cargo->description . "\n\n";

// отображаем телефон заказчика
$text .= $emPhone . ' +' . $cargo->createdBy->phone;

echo json_encode([
    'text' => $text
]);
?>



