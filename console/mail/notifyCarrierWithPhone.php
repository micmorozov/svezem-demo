<?php

use common\models\Cargo;
use yii\helpers\Html;
use common\helpers\Convertor;

/** @var Cargo $cargo */
/** @var $subscribeEditUrl string */
/** @var string $contactsUrl */
/** @var string $cargoBookingPay */
/** @var bool $showPhone */

//Текст заказа
$order = 'Заказ от ' . Yii::$app->formatter->asDate($cargo->created_at, 'dd.MM.y');

//Текст направления
if ($cargo->city_from == $cargo->city_to) {
    $direction = 'По городу: '.$cargo->cityFrom->getFQTitle();
} else {
    $direction = 'Направление: '.$cargo->cityFrom->getFQTitle().' - '.$cargo->cityTo->getFQTitle();
    if ($cargo->distance) {
        $direction .= ', '.Convertor::distance($cargo->distance).', '.Convertor::time($cargo->duration);
    }
}
?>
Здравствуйте!<br>
<br>
Новый заказ по вашей подписке:<br>
------------------------------------------------------<br>
<?= $order ?>
<br>
<?= $direction ?>
<br><br>
<?= $cargo->description ?>
<br><br>
<span style="font-weight: bold; font-size: large">Телефон заказчика:  <a href="tel:+<?= $cargo->createdBy->phone ?>">+<?= $cargo->createdBy->phone ?></a></span><br><br>

------------------------------------------------------
<br>
<br>
С уважением,<br>
Команда сервиса Svezem.ru<br>
https://svezem.ru<br>
admin@svezem.ru<br>
8(800)201-23-56
