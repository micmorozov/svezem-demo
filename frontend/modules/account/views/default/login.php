<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */

/* @var $model LoginForm */

use common\models\LoginForm;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Страница авторизации на svezem.ru';
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
                    <b>Войти в личный кабинет</b>
                </h1>
            </div>
            <?php $form = ActiveForm::begin([
                'options' => ['class' => 'auth__form']
            ]); ?>
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <div class="panel panel-primary">
                        <div class="panel-body">
                            <div class="form-horizontal">
                                <?= $form->field($model, 'email', [
                                    'template' => '{label}<div class="col-sm-9">{input}{error}</div>',
                                    'options' => ['class' => 'form-group'],
                                    'labelOptions' => ['class' => 'col-sm-3 control-label'],
                                    'inputOptions' => [
                                        'class' => 'form-control',
                                        'placeholder' => "Email адрес или телефон"
                                    ]
                                ])
                                ?>
                                <?php
                                $recover = Html::a('Забыли пароль?', ['/account/request-password-reset'], ['class' => "form-forget-pass__link"]);
                                echo $form->field($model, 'password', [
                                    'template' => '{label}<div class="col-sm-9">{input}{error}</div>',
                                    'options' => ['class' => 'form-group'],
                                    'labelOptions' => ['class' => 'col-sm-3 control-label'],
                                    'inputOptions' => [
                                        'class' => 'form-control',
                                        'placeholder' => "Пароль",
                                        'type' => 'password'
                                    ]
                                ]);
                                ?>
                                <?php
                                if ($model->needRecaptcha()) {
                                    echo $form
                                        ->field($model, 'reCaptcha', [
                                            'template' => '{label}<div class="col-sm-9">{input}{error}</div>',
                                            'labelOptions' => ['class' => 'col-sm-3 control-label'],
                                        ])
                                        ->widget(ferrumfist\yii2\recaptcha\ReCaptcha::class);
                                }
                                ?>
                                <div class="form-group">
                                    <div class="col-sm-offset-3 col-sm-9">
                                        <?= Html::a('Забыли пароль?', ['/account/request-password-reset'], ['rel'=>'nofollow']); ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-sm-offset-3 col-sm-9">
                                        <?= Html::submitButton('Войти', ['class' => "btn btn-primary btn-svezem"]) ?>
                                        <?= Html::a('Зарегистрироваться', ["/account/signup"], ['class' => "regi__link", 'style' => 'margin:0 20px', 'rel'=>'nofollow']) ?>
                                    </div>
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <small>
                                    Авторизуясь, Вы соглашаетесь с <?= Html::a('политикой конфиденциальности', '//'.Yii::getAlias('@domain').'/info/legal/privacy-policy/', [
                                        'target' => '_blank',
                                        'rel' => 'nofollow'
                                    ]) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</main>
