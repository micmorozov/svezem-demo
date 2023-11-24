<?php

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $profile common\models\Profile */

$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['cabinet/profile/confirm', 'token' => $user->auth_key, 'id' => $profile->id]);
?>
Привет, <?= $user->username ?>!

Пожалуйста, следуйте по ссылке ниже, чтобы подтвердить Ваш почтовый ящик:

<?= $resetLink ?>

С уважением, команда <?= Yii::$app->name ?>.
