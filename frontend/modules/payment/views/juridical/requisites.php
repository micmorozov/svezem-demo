<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\PaymentRequisites */
/* @var $form ActiveForm */

$this->title = 'Оплата для юридических лиц или ИП';
?>
<main class="content cargo-list__wrap">
    <div class="container">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b><?= $this->title ?></b></h1>
        </div>
        <br>
        <div class="content__subtitle">Пожалуйста, уточните реквизиты организации-плательщика:</div>

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'organization') ?>
        <?= $form->field($model, 'inn') ?>
        <?= $form->field($model, 'kpp') ?>
        <?= $form->field($model, 'bic') ?>
        <?= $form->field($model, 'bank') ?>
        <?= $form->field($model, 'account') ?>
        <?= $form->field($model, 'corr_account') ?>
        <?= $form->field($model, 'jur_address') ?>
        <?= $form->field($model, 'post_address') ?>

        <div class="form-group">
            <?= Html::submitButton('Выписать счет',
                [
                    'class' => 'btn btn-primary btn-svezem',
                    'style' => 'text-transform: none; width: 250px;'
                ]) ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</main>
