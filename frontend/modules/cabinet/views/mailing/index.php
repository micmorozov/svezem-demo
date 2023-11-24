<?php

use common\models\User;
use yii\widgets\ActiveForm;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model User */

$this->title = "Личный кабинет - Управление email подписками";
?>
<main class="content">
    <div class="container">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b>Управление Email подписками</b></h1>
        </div>
        <div class="bl-white profile-settings">
            <div>
                <?php $form = ActiveForm::begin(); ?>

                <?= $form->field($model, 'news')->checkbox() ?>
                <div class="form-group">
                    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
                </div>
                <?php $form = ActiveForm::end(); ?>
            </div>
        </div>
</main>
