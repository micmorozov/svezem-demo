<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */

/* @var $model PasswordResetRequestForm */

use frontend\models\PasswordResetRequestForm;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Страница напоминания пароля';
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
                    <b>Восстановить пароль</b>
                </h1>
            </div>
            <div class="col-sm-8 col-sm-offset-2">
                <div class="panel panel-primary">
                    <div class="panel-body">
                        <?php $form = ActiveForm::begin(['options' => ['class' => 'auth__form']]) ?>
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
                        <div class="form-group">
                            <div class="col-sm-offset-3 col-sm-9">
                                <?= Html::submitButton('Восстановить пароль', ['class' => "btn btn-primary btn-svezem"]) ?>
                                <?= Html::a('Авторизация', ['/account/login'], ["class" => "regi__link", 'style' => 'margin:0 20px', 'rel'=>'nofollow']) ?>
                            </div>
                        </div>
                        <?php ActiveForm::end() ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
