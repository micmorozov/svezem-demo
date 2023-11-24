<?php

use common\models\Cargo;
use common\models\Setting;
use yii\helpers\Html;

/** @var $model Cargo */
/*
$blockMinutes = Setting::getValueByCode(Setting::CARGO_BOOKING_BLOCK, 30);
$ttl = strtotime("+$blockMinutes min", $model->created_at) - time();

if( $ttl > 0 ) {
?>
    <div style="color: red">До публикации контактов на сайте осталось: <span class="timer" id="timer_<?= $model->id ?>" data-time="<?= $ttl ?>"></span>
    </div>
    <br>
<?php
}
*/
echo Html::button('Забронировать', [
    'class' => 'search__btn content__btn booking',
    'data-cargo_id' => $model->id
]);
