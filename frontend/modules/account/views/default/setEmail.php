<?php

use frontend\modules\account\assets\SetMailAsset;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use common\helpers\TelegramHelper;

/* @var $this yii\web\View */
/* @var $model frontend\modules\account\models\EmailForm */
/* @var $form ActiveForm */

$this->title = 'Получение дополнительных данных';

SetMailAsset::register($this);
?>
<main class="content auth">
    <div class="container">
        <div class="auth__block">
            <div class="auth__title__wrap">
                <h1 class="auth__title content__title">Бесплатная подписка на грузы</h1>
                <div class="line"></div>
            </div>
            <div class="content__subtitle">
                Укажите свой email и мы будем отправлять вам уведомления о новых грузах совершенно бесплатно. Так же вы можете подписаться на наш <a href="<?= TelegramHelper::getLinkToCommonChannel() ?>" target="_blank" class="widget__link" rel="nofollow">Телеграм канал</a> или <a href="https://vk.com/svezem_allcargo" target="_blank" class="widget__link" rel="nofollow">группу ВК</a> и следить за грузами online
            </div>

            <?php $form = ActiveForm::begin(); ?>

            <?= $form->field($model, 'email') ?>

            <div class="form-group">
                <?= Html::submitButton('Сохранить', ['class' => 'form-custom-button submitBtn btn btn-primary btn-svezem', 'style'=>'max-width: 150px;']) ?>
                <?= Html::button('Отказаться от уведомлений', ['class' => 'btn btn-link', 'id'=>'skipBtn']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</main>
