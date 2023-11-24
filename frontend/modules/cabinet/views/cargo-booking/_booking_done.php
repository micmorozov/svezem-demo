<?php

use common\helpers\PhoneHelpers;
use yii\helpers\Html;
use common\models\Cargo;

/** @var $model Cargo */
?>
Телефон заказчика: <a href="tel:<?=PhoneHelpers::formatter($model->profile->contact_phone, '', true) ?>" style="white-space: nowrap;"><b><?= PhoneHelpers::formatter($model->profile->contact_phone) ?></b></a><br><br><br>
<div id="booking_info_<?= $model->id ?>">
    <span style="color: green">Сделка закрыта: <?= number_format($model->booking_price, 0, '.', ' ') ?> руб.</span>
    <?= Html::a('изменить', '#', [
        'data-cargo_id' => $model->id,
        'class' => 'changePrice'
    ]) ?>
</div>
<div id="booking_info_edit_<?= $model->id ?>" style="display: none">
    <?= Html::input('text', 'price', $model->booking_price, [
        'class' => 'form-control',
        'id' => 'price_'.$model->id,
        'autocomplete' => 'off',
        'placeholder' => 'Укажите сумму сделки в рублях'
    ]);
    ?>
    <br>
    <?= Html::button('Сохранить', [
        'class' => 'search__btn content__btn booking_edit',
        'data-cargo_id' => $model->id
    ]) ?>
    &nbsp;&nbsp;&nbsp;
    <?= Html::a('отменить', '#', [
        'class' => 'cancel_edit',
        'data-cargo_id' => $model->id
    ]) ?>
</div>
