<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\modules\Notify\models\NotifyRule;

/* @var $this yii\web\View */
/* @var $model common\modules\Notify\models\NotifyRule */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="notify-rule-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'page')->dropDownList(NotifyRule::pageLabels()) ?>

    <?= $form->field($model, 'message')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'type')->dropDownList(NotifyRule::typeLabels()) ?>

    <?= $form->field($model, 'url')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'delay')->textInput()->label($model->getAttributeLabel('delay')." 0-бесконечно") ?>

    <?= $form->field($model, 'rule')->textarea(['rows' => 6]) ?>

    <ul>
        Допустимые переменные и объекты:
        <ol><b>$user</b> - Объект пользователь. Если пользователь не авторизован, то $user = false</ol>
        <ol><b>$user->senderProfile</b> - Объект провиля отправителя, если есть</ol>
        <ol><b>$user->transporterProfile</b> - Объект провиля перевозчика, если есть</ol>
        <ol><b>$subscribe</b> - Подписка пользователя</ol>
        <ol><b>$subscribeRulesCount</b> - Количество правил у подписки</ol>
        <ol><b>$transportCount</b> - Количество транспорта у пользователя</ol>
    </ul>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
