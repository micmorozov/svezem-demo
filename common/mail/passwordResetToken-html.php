<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */

$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['account/reset-password', 'token' => $user->password_reset_token]);
?>
<div class="password-reset">
    <p>Привет, <?= Html::encode($user->username) ?>,</p>

    <p>Пожалуйста, следуйте по ссылке ниже, чтобы восстановить ваш пароль:</p>

    <p><?= Html::a(Html::encode($resetLink), $resetLink) ?></p>

    <small>С уважением, команда <?= Yii::$app->name ?>.</small>
</div>
