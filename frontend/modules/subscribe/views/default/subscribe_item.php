<?php

use frontend\modules\subscribe\models\SubscribeRules;
use frontend\widgets\Select2;
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use frontend\widgets\MultupleSelect;
use yii\helpers\ArrayHelper;
use common\models\CargoCategory;

/** @var $model SubscribeRules */
?>
<div class="subscribe_wrap" data-ruleid="<?= $model->id ?>">
    <div class="app-block__direction-info clear">
        <div class="app-block__direction direction">
            <div class="direction__from direction__item">
                <span class="direction__flag">
                    <?php if($model->cityFrom):
                        $countryFrom = $model->cityFrom->country;
                    ?>
                        <?= Html::img("/img/flags/{$countryFrom->code}.svg", ['alt'=>$countryFrom->title_ru, 'title'=>$countryFrom->title_ru]) ?>
                    <?php endif ?>
                </span>
                <span class="direction__city">
                    <?= $model->selectedCity('From') ?>
                </span>
            </div>
            <div class="direction__arrow direction__item">
                <span class="desktop-v">
                    <?= Html::img("/img/icons/direction-arrow-1.svg", ['alt'=>"arrow"]) ?>
                </span>
                <span class="mobile-v">
                    <?= Html::img("/img/icons/direction-arrow-2.svg", ['alt'=>"arrow"]) ?>
                </span>
            </div>
            <div class="direction__to direction__item">
                <span class="direction__flag">
                    <?php if($model->cityTo):
                        $countryTo = $model->cityTo->country;
                        ?>
                        <?= Html::img("/img/flags/{$countryTo->code}.svg", ['alt'=>$countryTo->title_ru, 'title'=>$countryTo->title_ru]) ?>
                    <?php endif ?>
                </span>
                <span class="direction__city">
                    <?= $model->selectedCity('To') ?>
                </span>
            </div>
        </div>
        <div class="app-block__coment"><span class="dscktop_cont">(отходы, загрузка манипулятором)</span><span class="mobile_cont">(1, 9)</span></div>
        <div class="app-block__class"><?= Yii::t('app', '{n, number} {n, plural, =1{сообщение} few{сообщения} other{сообщений}}', ['n' => $model->msgCount]) ?></div>
        <div class="tools_block">
            <span class="edit"></span>
            <span class="delete"></span>
        </div>
    </div>
    <div class="subscribe_form">
        <?php $form = ActiveForm::begin([
            'fieldConfig' => ['class'=>'frontend\components\field\ExtendField'],
            'options' => [
                'id' => 'sub_item_'.$model->id,
                'class' => "form1"
            ]
        ]) ?>
        <div class="add-offer-block">
            <div class="col-md-6 field_wrap">
                <?= //Откуда
                $form->field($model, 'city_from', [
                        'options' => [
                            'class' => 'form-field'
                        ]
                    ])
                    ->label('Откуда:', [
                        'class'=>'form-label'
                    ])
                    ->widget(Select2::class, [
                        'options' => [
                            'style' => 'width: 100%',
                            'class' => 'city_select'
                        ],
                        'data' => $model->getCityString('From'),
                        'allCity' => true
                    ]);
                ?>
                <?= //Откуда
                $form->field($model, 'city_to', [
                    'options' => [
                        'class' => 'form-field'
                    ]
                ])
                    ->label('Куда:', [
                        'class'=>'form-label'
                    ])
                    ->widget(Select2::class, [
                        'options' => [
                            'style' => 'width: 100%',
                            'class' => 'city_select'
                        ],
                        'data' => $model->getCityString('To'),
                        'allCity' => true
                    ]);
                ?>
            </div>
            <div class="col md-6 field_wrap">
                <?=
                $form->field($model, 'categoriesId', [
                    'options' => [
                        'class' => 'form-field'
                    ],
                    'labelOptions' =>[
                        'class' => 'form-label'
                    ],
                ])
                    //->label('Способ')
                    ->widget(MultupleSelect::class, [
                        'options' => [
                            'class' => 'simple-select',
                            'multiple' => 'multiple',
                            'style' => 'width: 100%'
                        ],
                        //'items' => ArrayHelper::map(CargoCategory::filterList(), 'id', 'category'),
                        'items' => ArrayHelper::map(CargoCategory::find()
                            ->root(0)
                            ->showModerCargo()
                            ->all(), 'id', 'category'),
                        'pluginOptions' => [
                            'width' => '100%'
                        ]
                    ])
                ?>
            </div>
            <div class="total_wrap">
                <span class="cost">Кол-во:</span>
                <span><span id="day_price"></span> сообщ. в сутки</span>
            </div>
            <div class="btn_wrap">
                <button class="form-custom-button save_rule">Сохранить правило</button>
                <button class="cancel">ОТМЕНИТЬ</button>
            </div>
        </div>
        <?php ActiveForm::end() ?>
    </div>
</div>
<script>
    jQuery('form#sub_item_<?= $model->id ?> .city_select').select2({
        allowClear: true,
        minimumInputLength: 3,
        'ajax': {
            url: '/city/list/',
            dataType: 'json',
            data: function(params) {
                return {query:params.term};
            },
            processResults: function(data){
                return {results:data};
            },
            delay: 250,
            cache: true
        },
        placeholder: 'Выберите город'
    });

    MultipleSel.init($('form#sub_item_<?= $model->id ?> .simple-select'), {selectAll: false});
</script>
