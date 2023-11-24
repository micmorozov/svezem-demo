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
Посмотреть <?= Html::a('контакты заказчика', $contactsUrl) ?><br><br>
<br><br>
Настроить подписку: <?= Html::a("https://svezem.ru/sub/", $subscribeEditUrl) ?><br>

------------------------------------------------------
<br>
<br>
С уважением,<br>
Команда сервиса Svezem.ru<br>
https://svezem.ru<br>
admin@svezem.ru<br>
8(800)201-23-56
