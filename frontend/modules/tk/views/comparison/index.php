<?php

use frontend\helpers\Load;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use frontend\widgets\Select2;
use yii\web\JsExpression;
use frontend\modules\tk\assets\TkCompareAsset;

/* @var $this yii\web\View */
/* @var $model frontend\modules\tk\models\TkCompareSearch */
/* @var $form ActiveForm */

// Устанавливаем значение по умолчанию
$title = '';
$descr = '';
$keywords = '';
$h1 = '';
$text = '';
// Если есть шаблон. устанавливаем его
if ($pageTpl) {
    $title = $pageTpl->title;
    $descr = $pageTpl->desc;
    $keywords = $pageTpl->keywords;
    $h1 = $pageTpl->h1;
    $text = nl2br($pageTpl->text);
}
$this->title = $title;
$this->registerMetaTag([
    'name' => 'description',
    'content' => $descr
]);

TkCompareAsset::register($this);
?>
<main class="content">
    <div class="container price-comparison">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b><?= $h1 ?></b></h1>
            <div class="content__subtitle"><?= $text ?></div>
        </div>
        <?php $form = ActiveForm::begin([
            'id' => 'tk_search_form'
        ]); ?>
        <?= Html::hiddenInput('socket_id', '', ['id' => 'socket_id']) ?>
        <?= Html::hiddenInput('session_timestamp', '', ['id' => 'session_timestamp']) ?>
        <div class="panel">
            <div class="panel-body row">
                <div class="col-sm-6">
                    <?= //Откуда
                    $form->field($model, 'city_from', [
                        //'options' => ['class' => 'search__input-wrap'],
                        //'labelOptions' => ['class' => 'search__label']
                    ])
                        ->widget(Select2::class, [
                            'options' => [
                                'style' => 'width: 100%',
                                'class' => 'ajax-select form-control'
                            ],
                            'data' => [],
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
                </div>
                <div class="col-sm-6">
                    <?= //Куда
                    $form->field($model, 'city_to', [
                    ])
                        ->widget(Select2::class, [
                            'options' => [
                                'style' => 'width: 100%',
                                'class' => 'ajax-select form-control'
                            ],
                            'data' => [],
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
                </div>
                <div class="col-sm-12 col-md-6">
                    <?= $form->field($model, 'weight', [
                        'inputOptions' => [
                            'placeholder' => "Введите массу груза",
                            'class' => "form-control"
                        ]
                    ])->label('Масса <span class="hide-mob">груза (кг)</span>')
                    ?>
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <label for="" class="col-md-12">Габариты груза (м)</label>
                        <?= $form->field($model, 'depth', [
                            'options' => ['class' => 'form-group col-md-4'],
                            'labelOptions' => [
                                'class' => 'hidden-md visible-sm visible-xs'
                            ],
                            'inputOptions' => [
                                'class' => "form-control",
                                'placeholder' => "Длинна"
                            ]
                        ])
                        ?>
                        <?= $form->field($model, 'height', [
                            'options' => ['class' => 'form-group col-md-4'],
                            'labelOptions' => [
                                'class' => 'hidden-md visible-sm visible-xs'
                            ],
                            'inputOptions' => [
                                'class' => "form-control",
                                'placeholder' => "Высота"
                            ]
                        ])
                        ?>
                        <?= $form->field($model, 'width', [
                            'options' => ['class' => 'form-group col-md-4'],
                            'labelOptions' => [
                                'class' => 'hidden-md visible-sm visible-xs'
                            ],
                            'inputOptions' => [
                                'class' => "form-control",
                                'placeholder' => "Ширина"
                            ]
                        ])
                        ?>
                    </div>
                </div>
                <div class="col-sm-12 text-center">
                    <?= Html::submitButton('Поиск', ['class' => "btn btn-primary btn-svezem has-spinner", 'id' => 'search-btn']) ?>
                </div>
            </div>
        </div>
        <?php ActiveForm::end(); ?>
        <div class="price-comparison-prices companies">
            <div class="transportation__head">
                <div class="price-comparison__btn-wrap"></div>
                <div class="price-comparison__title-wrap"><h2 class="content__title">Цены транспортных компаний</h2>
                </div>
            </div>

            <div id="tkCompare" v-cloak>
                <div v-if="distance" class="v-cloak--hidden">
                    Расстояние: {{number_format(distance)}} км. Дней в пути: {{days}}
                    <br>
                    {{detailsCity}}
                    <br>
                    {{detailsParams}}
                    <br><br>
                    <tk-item v-for="tk in filteredList" :id="tk.id" :name="tk.name" :icon="tk.icon"
                             :cost="tk.cost"></tk-item>
                </div>
            </div>

        </div>
    </div>
</main>
<script type="text/x-template" id="tk-search">
    <div class="companies__item item content__block row">
        <div class="item__block">
            <div class="item__info">
                <div class="item__img-block">
                    <div class="item__img-wrap" :style="iconStyle"></div>
                </div>
                <div class="item__details">
                    <div class="item__name">{{name}}</div>
                    <div class="item__sphere">Транспортная компания</div>
                </div>
            </div>
        </div>
        <div class="item__block">
            <div class="item__desc">
                <div class="desc__price">
                    <span class="price">От <span class="green">{{number_format(cost)}} руб.</span></span>
                </div>
                <div class="desc__btn-wrap">
                    <a class="desc__btn content__block-btn" :href="link" target="_blank"> подробное описание
                        <span class=" hide-mob">транспортной компании</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</script>
