<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */

/* @var $model ResetPasswordForm */

use frontend\modules\account\models\ResetPasswordForm;
use yii\captcha\Captcha;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Страница авторизации в сервисе';
$this->registerMetaTag([
    'name' => 'description',
    'content' => 'Авторизуйтесь в сервисе, чтобы получить доступ к личному кабинету перевозчика и отправителя'
]);
?>
<main class="content auth">
    <div class="container">
        <div class="auth__block">
            <div class="page-title">
                <h1 class="h3 text-uppercase text-center">
                    <b>Сменить пароль</b>
                </h1>
            </div>
            <div class="col-sm-8 col-sm-offset-2">
                <div class="panel panel-primary">
                    <div class="panel-body">
                        <?php $form = ActiveForm::begin(['options' => ['class' => 'auth__form']]); ?>
                        <div class="form-horizontal">
                            <?= $form->field($model, 'password', [
                                'template' => '{label}<div class="col-sm-9">{input}{error}</div>',
                                'options' => ['class' => 'form-group'],
                                'labelOptions' => ['class' => 'col-sm-3 control-label'],
                                'inputOptions' => [
                                    'class' => 'form-control',
                                    'tabindex' => 1,
                                    'placeholder' => 'Новый пароль'
                                ]
                            ])->passwordInput() ?>
                            <?= $form->field($model, 'password_repeat', [
                                'template' => '{label}<div class="col-sm-9">{input}{error}</div>',
                                'options' => ['class' => 'form-group'],
                                'labelOptions' => ['class' => 'col-sm-3 control-label'],
                                'inputOptions' => [
                                    'class' => 'form-control',
                                    'tabindex' => 2,
                                    'placeholder' => 'Пароль повторно'
                                ]
                            ])->passwordInput() ?>

                            <div class="form-group">
                                <div class="col-sm-offset-3 col-sm-9">
                                <?= Html::submitButton('Сохранить', ['class' => "btn btn-primary btn-svezem"]) ?>
                                </div>
                            </div>
                        </div>
                        <?php ActiveForm::end() ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
