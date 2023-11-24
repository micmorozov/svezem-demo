<?php

use yii\helpers\Url;
use yii\widgets\ActiveForm;
use frontend\widgets\Select2;
use yii\web\JsExpression;
use common\models\City;
use common\models\Cargo;
use yii\helpers\Html;

/** @var $model Cargo */

?>
<main class="content cargo-list__wrap">
    <div class="container add-offer">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b>Редактирование груза</b></h1>

        </div>

        <?php $form = ActiveForm::begin([
            'fieldConfig' => ['class' => 'frontend\components\field\ExtendField'],
            'options' => [
                'enctype' => 'multipart/form-data',
                'class' => 'form2'
            ],
            'scrollToError' => false,
            'validateOnType' => true
        ]); ?>

        <div class="add-offer-block">
            <div class="add-offer-block-header">Направление перевозки</div>
                <?= $form->field($model, 'city_from', [
                        'options' => ['class' => 'form-field'],
                        'labelOptions' => [
                            'class' => 'form-label'
                        ]
                    ])
                    ->widget(Select2::class, [
                        'options' => [
                            'class' => 'ajax-select'
                        ],
                        'data' => [$model->city_from => City::findOne($model->city_from)->getFQTitle()],
                        'pluginOptions' => [
                            'allowClear' => true,
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
                    ])->label('Откуда');
                ?>

                <?= $form->field($model, 'city_to', [
                    'options' => ['class' => 'form-field'],
                    'labelOptions' => [
                        'class' => 'form-label'
                    ]
                ])
                    ->widget(Select2::class, [
                        'options' => [
                            'class' => 'ajax-select'
                        ],
                        'data' => [$model->city_to => City::findOne($model->city_to)->getFQTitle()],
                        'pluginOptions' => [
                            'allowClear' => true,
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
                    ])->label('Куда');
                ?>
        </div>

        <div class="add-offer-block">
            <div class="add-offer-block-header">Описание заявки</div>
                <?= $form->field($model, 'description', [
                    'template' => "<span class='form__field-wrapper'>
                        <div class='error-wrapper error-wrapper__long' style='position: relative; max-width:100%;'>
                            {error}
                            {input}
                        </div>
                    </span>"
                ])
                    ->label(false)
                    ->textarea([
                        'id' => 'desc',
                        'class' => 'form-custom-textarea'
                    ]);
                ?>
        </div>

        <?= Html::submitButton('Сохранить', [
            'class' => 'form-custom-button',
            'style' => "text-transform: none;padding-left: 50px;padding-right: 50px;"
        ]) ?>

        <?php ActiveForm::end() ?>
    </div>
</main>
