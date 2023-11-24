<?php

/* @var $this yii\web\View */
/* @var $user common\models\User */

$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['account/reset-password', 'token' => $user->password_reset_token]);
?>
Привет, <?= $user->username ?>,

Пожалуйста, следуйте по ссылке ниже, чтобы восстановить ваш пароль:

<?= $resetLink ?>

С уважением, команда <?= Yii::$app->name ?>.