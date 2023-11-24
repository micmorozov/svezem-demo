<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */

/* @var $model SignupForm */

use frontend\modules\account\models\SignupForm;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use common\models\Profile;
use yii\helpers\Html;
use yii\web\JsExpression;
use frontend\widgets\Select2;
use common\models\City;

$this->title = 'Страница регистрации на svezem.ru';
$this->registerMetaTag([
    'name' => 'description',
    'content' => 'Пройдите процедуру регистрации в сервисе, чтобы получать предложения перевозчиков и уведомления о новых грузах'
]);
?>
<main class="content auth">
    <div class="container">
        <div class="auth__block">
            <div class="page-title">
                <h1 class="h3 text-uppercase text-center">
                    <b>Регистрация</b>
                </h1>
            </div>
            <?php $form = ActiveForm::begin(['options' => ['class' => 'auth__form']]); ?>
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
                                        'class' => "form-control",
                                        'placeholder' => "Email адрес или телефон"
                                    ]
                                ]) ?>
                                <?= $form->field($model, 'types', [
                                    'template' => '<div class="checkbox-list">{label}<div class="col-sm-9">{input}{error}</div></div>',
                                    'options' => ['class' => 'form-group'],
                                    'labelOptions' => ['class' => 'col-sm-3 control-label'],
                                ])->checkboxList([
                                    Profile::TYPE_SENDER => "Я буду размещать заявки на перевозку",
                                    Profile::TYPE_TRANSPORTER_NOT_SPECIFIED => "Я буду выполнять заявки на перевозку"
                                ], [
                                    'item' => function ($index, $label, $name, $checked, $value) {
                                        $id = 'jt-' . $index;
                                        return '<div>' .
                                            Html::input('checkbox', $name, $value, ['id' => $id]) .
                                            Html::label("<span></span>$label", $id) .
                                            '</div>';
                                    },
                                    //чтобы не было рамки
                                    'data-noerror' => '1'
                                ]) ?>
                                <?= $form
                                    ->field($model, 'contact_person', [
                                        'template' => '{label}<div class="col-sm-9">{input}{error}</div>',
                                        'options' => ['class' => 'form-group'],
                                        'labelOptions' => ['class' => 'col-sm-3 control-label'],
                                        'inputOptions' => [
                                            'class' => 'form-control',
                                            'placeholder' => "ФИО"
                                        ]
                                    ])
                                ?>
                                <?= $form
                                    ->field($model, 'city_id', [
                                        'template' => '{label}<div class="col-sm-9">{input}{error}</div>',
                                        'labelOptions' => ['class' => 'col-sm-3 control-label'],
                                    ])
                                    ->widget(Select2::class, [
                                        'options' => [
                                            'style' => 'width: 100%;',
                                            'class' => 'form-control ajax-select'
                                        ],
                                        'data' => !empty($model->city_id) ? [$model['city_id'] => City::findOne(['id' => $model['city_id']])->getFQTitle()] : [],
                                        'pluginOptions' => [
                                            'allowClear' => true,
                                            'theme' => 'bootstrap',
                                            'minimumInputLength' => 3,
                                            'ajax' => [
                                                'url' => Url::to(['/city/list']),
                                                'dataType' => 'json',
                                                'data' => new JsExpression('function(params) { return {query:params.term}; }'),
                                                'processResults' => new JsExpression('function(data) { return {results:data};}'),
                                                'delay' => 250,
                                                'cache' => true
                                            ],
                                            'placeholder' => 'Выберите город'
                                        ]
                                    ]);
                                ?>
                                <div class="form-group">
                                    <div class="col-sm-offset-3 col-sm-9">
                                        <?= Html::submitButton('Регистрация', ["class" => "btn btn-primary btn-svezem"]) ?>
                                        <?= Html::a('Авторизация', ['/account/login'], ["class" => "regi__link", 'style' => 'margin:0 20px', 'rel'=>'nofollow']) ?>
                                    </div>
                                </div>
                                <div style="text-align: center;">
                                    <small>
                                        Регистрируясь, Вы соглашаетесь с <?= Html::a('политикой конфиденциальности', '//'.Yii::getAlias('@domain').'/info/legal/privacy-policy/', [
                                            'target' => '_blank',
                                            'rel' => 'nofollow'
                                        ]) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
    </div>
</main>
