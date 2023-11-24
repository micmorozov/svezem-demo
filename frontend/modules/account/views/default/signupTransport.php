<?php

/* @var $this yii\web\View */
/* @var $form ActiveForm */
/* @var $model Transport */

/* @var $loginSignup LoginSignup */

use common\models\CargoCategory;
use common\models\LoginSignup;
use common\models\Transport;
use frontend\assets\Select2Asset;
use frontend\modules\account\assets\TransportValidateAsset;
use frontend\modules\account\widgets\FormLogin;
use frontend\widgets\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\ActiveForm;

Select2Asset::register($this);

$this->title = 'Добавление предложения по грузоперевозке';
$this->registerMetaTag([
    'name' => 'description',
    'content' => 'Добавление предложения по грузоперевозке'
]);

TransportValidateAsset::register($this);
?>
<main class="container content cargo-list__wrap add-offer">
    <div class="page-title">
        <h1 class="h3 text-uppercase"><b>Добавление предложения о перевозке</b></h1>
    </div>
    <?php $form = ActiveForm::begin([
        'id' => 'addTransport',
        'options' => [
            'enctype' => 'multipart/form-data',
            'class' => 'form2'
        ],
        'scrollToError' => false,
        'validateOnType' => true
    ]); ?>
    <div class="panel panel-primary">
        <div class="panel-heading">
            <b>Описание транспорта</b>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-4 col-md-push-8">
                    <?= $this->render('add_image', ['form' => $form, 'model' => $model]) ?>
                </div>
                <div class="col-md-8 col-md-pull-4">
                    <div class="row">
                        <div class="col-md-12">
                            <?= $form
                                ->field($model, 'transportTypeId', [
                                    'options' => ['class' => 'form-group'],
                                    'labelOptions' => ['class' => "control-label"]
                                ])
                                ->widget(Select2::class, [
                                    'data' => ArrayHelper::merge(['' => ''], ArrayHelper::map(CargoCategory::transportTypeList(), 'id', 'category')),
                                    'options' => [
                                        'class' => 'form-control',
                                        'style' => 'width:100%',
                                        'data-minimum-results-for-search' => "Infinity",
                                    ],
                                    'pluginOptions' => [
                                        'theme' => 'bootstrap',
                                        'placeholder' => "Выберите вид автотранспорта",
                                    ]
                                ])
                            ?>
                        </div>
                    </div>
                    <div class="row add-offer-block">
                        <div class="col-md-12">
                            <?= $form
                                ->field($model, 'loadMethodIds', [
                                    'template' => '<div class="row checkbox-list"><div class="col-md-12">{label}</div>{input}<div class="col-md-12">{error}</div></div>',
                                ])
                                ->label('Cпособ погрузки')
                                ->checkboxList(ArrayHelper::map(CargoCategory::loadTypeList(), 'id', 'category'), [
                                    'item' => function ($index, $label, $name, $checked, $value) {
                                        $id = 'lt-' . $index;
                                        return '<div class="col-md-6">' .
                                            Html::input('checkbox', $name, $value, ['id' => $id, 'checked' => $checked]) .
                                            Html::label("<span></span>$label", $id) .
                                            '</div>';
                                    },
                                    //чтобы не было рамки
                                    'data-noerror' => '1'
                                ]);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="panel panel-primary">
        <div class="panel-heading">
            <b>Категория перевозимых грузов</b>
        </div>
        <div class="panel-body add-offer-block">
            <?= $form->field($model, 'cargoCategoryIds', [
                'template' => '<div class="checkbox-list">{input}<div class="col-md-12">{error}</div></div>',
            ])
                ->checkboxList(ArrayHelper::map(CargoCategory::showAddTransportList(), 'id', 'category'), [
                    'class' => 'row',
                    'item' => function ($index, $label, $name, $checked, $value) {
                        $id = 'cc-' . $index;
                        return '<div class="col-sm-6 col-md-4 col-lg-3">' .
                            Html::input('checkbox', $name, $value, ['id' => $id, 'checked' => $checked]) .
                            Html::label("<span></span>$label", $id) .
                            '</div>';
                    },
                    //чтобы не было рамки
                    'data-noerror' => '1'
                ]);

            ?>
        </div>
    </div>
    <div class="panel panel-primary">
        <div class="panel-heading">
            <b>Описание рейса</b>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form
                        ->field($model, 'city_from', [
                            // 'divWrapperStyle' => 'width:100%',
                            'options' => ['class' => 'form-group'],
//                'labelOptions' => [
//                    // 'class' => 'form-label',
//                    // 'style' => 'min-width: 170px;'
//                ]
                        ])
                        ->widget(Select2::class, [
                            'options' => [
                                'style' => 'width: 100%;',
                                'class' => 'ajax-select'
                            ],
                            'data' => $model->city_from ? [$model->city_from => $model->cityFrom->getFQTitle()] : [],
                            'pluginOptions' => [
                                'theme' => 'bootstrap',
                                'allowClear' => true,
                                'minimumInputLength' => 3,
                                'ajax' => [
                                    'url' => Url::to(['/city/list/']),
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
                </div>
                <div class="col-md-6">
                    <?= $form
                        ->field($model, 'city_to', [
                            'options' => ['class' => 'form-group'],
                            // 'divWrapperStyle' => 'width:100%',
                            // 'options' => ['style' => 'width:100%'],
                            // 'labelOptions' => [
                            //    'class' => 'form-label',
                            //  'style' => 'min-width: 170px;'
                            //]
                        ])
                        ->widget(Select2::class, [
                            'options' => [
                                'style' => 'width: 100%;',
                                'class' => 'ajax-select'
                            ],
                            'data' => $model->city_to ? [$model->city_to => $model->cityTo->getFQTitle()] : [],
                            'pluginOptions' => [
                                'theme' => 'bootstrap',
                                'allowClear' => true,
                                'minimumInputLength' => 3,
                                'ajax' => [
                                    'url' => Url::to(['/city/list/']),
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
                </div>
            </div>
        </div>
    </div>
    <div class="panel panel-primary">
        <div class="panel-heading">
            <b>Условия оплаты</b>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'price_from')->begin() ?>
                    <?= $form->field($model, 'price_from', [
                        'template' => '{label}',
                        'options' => [
                            'tag' => false
                        ]
                    ]) ?>
                    <div class="input-group">
                        <?= $form->field($model, 'price_from', [
                            'template' => '{input}',
                            'options' => ['tag' => false],
                            'labelOptions' => ['class' => 'control-label'],
                            'inputOptions' => [
                                'class' => "form-control",
                                'placeholder' => "Стоимость",
                                'autocomplete' => 'off'
                            ]
                        ]) ?>
                        <span class="input-group-addon">руб. за</span>
                        <?= $form->field($model, 'payment_estimate', [
                            'template' => '{input}',
                            'options' => ['tag' => false,],
                            'labelOptions' => ['class' => 'control-label'],
                            'inputOptions' => [
                                'class' => "form-control",
                            ]
                        ])
                            ->label(false)
                            ->dropDownList(Transport::getEstimateLabels(), ['placeholder' => 'Выберете', 'class' => 'form-control input-group-text'])
                        ?>
                    </div>
                    <?= $form->field($model, 'price_from', [
                        'template' => '{error}',
                    ]) ?>
                    <?= $form->field($model, 'price_from')->end() ?>
                </div>
            </div>
        </div>
    </div>

    <div class="panel panel-primary">
        <div class="panel-heading">
            <b>Комментарий</b>
        </div>
        <div class="panel-body">
            <div class="row">
                <div class="col-md-12">
                    <?= $form
                        ->field($model, 'description', [
                            'options' => ['class' => 'form-group']
                        ])
                        ->label(false)
                        ->textarea([
                            'class' => "form-control",
                            'rows' => 6,
                            'placeholder' => 'Пример: 5 т. 34 куба, ТЕРМОС размеры кузова: длина 6,45м; высота 2,5м.; ширина 2,1м. -5-ти тонник, в будке установлено ТЕПЛО (термос-бабочка) 28 кубов. Удобная погрузка-разгрузка, на три стороны'
                        ])
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php if (Yii::$app->user->isGuest) echo FormLogin::widget(['model' => $loginSignup, 'form' => $form]) ?>
    <?= Html::submitButton($model->isNewRecord ? 'Добавить  предложение' : 'Сохранить изменения', [
        'id' => 'submitBtn',
        'class' => 'btn btn-primary btn-svezem',
        'style' => 'outline:0;'
    ]) ?>
    <br><br>
    <small>
        Нажимая "Добавить предложение", Вы соглашаетесь с <?= Html::a('политикой конфиденциальности', '//'.Yii::getAlias('@domain').'/info/legal/privacy-policy/', [
            'target' => '_blank',
            'rel' => 'nofollow'
        ]) ?>
    </small>
    <?php ActiveForm::end(); ?>
</main>
