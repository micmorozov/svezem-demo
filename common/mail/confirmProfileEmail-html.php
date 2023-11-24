<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */
/* @var $profile common\models\Profile */

$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['cabinet/profile/confirm', 'token' => $user->auth_key, 'id' => $profile->id]);
?>
<div class="password-reset">
    <p>Привет, <?= Html::encode($user->username) ?>!</p>

    <p>Пожалуйста, следуйте по ссылке ниже, чтобы подтвердить Вашу электронную почту:</p>

    <p><?= Html::a(Html::encode($resetLink), $resetLink) ?></p>

    <small>С уважением, команда <?= Yii::$app->name ?>.</small>
</div>



