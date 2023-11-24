<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 25.04.18
 * Time: 11:13
 */

use common\models\PageTemplates;
use frontend\modules\cargo\assets\PassingViewAsset;
use frontend\modules\cargo\models\CargoPassing;
use frontend\widgets\PaginationWidget;
use yii\data\ActiveDataProvider;
use yii\web\View;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use frontend\widgets\Select2;
use yii\web\JsExpression;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use yii\helpers\Html;
use common\models\CategoryFilter;
use common\models\LocationCategorySearch;

/** @var View $this */
/** @var CargoPassing $model */
/** @var ActiveDataProvider $dataProvider */
/* @var $pageTpl PageTemplates */

// Устанавливаем значение по умолчанию
$title = 'Поиск попутных грузов';
$descr = 'Поиск попутных грузов';
$keywords = 'Поиск попутных грузов';
$h1 = 'Поиск попутных грузов';
$text = 'Поиск попутных грузов';

// Если есть шаблон. устанавливаем его
if ($pageTpl) {
    $title = $pageTpl->title;
    $descr = $pageTpl->desc;
    $keywords = $pageTpl->keywords;
    $h1 = $pageTpl->h1;
    $text = nl2br($pageTpl->text);
}

$this->registerMetaTag([
    'name' => 'description',
    'content' => $descr
]);

$this->title = $title;
PassingViewAsset::register($this);
$this->registerJs("cargo_passing_init();", View::POS_END);
?>
<main class="content">
    <div class="container passing-loads">
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b><?= $h1 ?></b></h1>
            <div class="content__subtitle"><?= $text ?></div>
        </div>
        <?php Pjax::begin() ?>
        <?php $form = ActiveForm::begin([
            'action' => Url::toRoute('/cargo/default/passing'),
            'method' => 'get',
            'options' => [
                'data-pjax' => '1',
                'class' => 'form1'
            ],
            'id' => 'transport-search-form'
        ]) ?>
        <div class="panel">
            <div class="panel-body row">
                <div class="col-sm-6">
                    <?= //Откуда
                    $form->field($model, 'city_from', [
                        'labelOptions' => ['class' => '']
                    ])->widget(Select2::class, [
                        'options' => [
                            'style' => 'width: 100%',
                            'class' => 'ajax-select form-control'
                        ],
                        'data' => $model->getCityString('From'),
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
                        'labelOptions' => ['class' => '']
                    ])->widget(Select2::class, [
                        'options' => [
                            'style' => 'width: 100%',
                            'class' => 'ajax-select form-control'
                        ],
                        'data' => $model->getCityString('To'),
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
                <div class="col-md-6 col-sm-12">
                    <?= $form->field($model, 'radius', [
                        'options' => ['class' => 'search__input-wrap'],
                        'labelOptions' => ['class' => ""]
                    ])->widget(Select2::class, [
                        'data' => array_combine(Yii::$app->params['passingCargoRange'], Yii::$app->params['passingCargoRange']),
                        'options' => [
                            'data-minimum-results-for-search' => "Infinity",
                            'class' => 'simple-select1 form-control',
                            'style' => "width: 100%"
                        ],
                        'pluginOptions' => [
                            'theme' => 'bootstrap',
                            'placeholder' => "Выберите вид автотранспорта",
                        ]
                    ])->label('<span class="hide-mob">Максимальное</span> отклонение <span class="hide-mob">от маршрута (км)</span>')
                    ?>
                </div>
                <div class="col-md-6 col-sm-12">
                    <?= $form
                        ->field($model, 'categoryFilter', [
                            'options' => ['class' => 'form-group'],
                            'labelOptions' => ['class' => "control-label"]
                        ])
                        ->label('Что')
                        ->widget(Select2::class, [
                            'data' => CategoryFilter::cargoSearchFilter(),
                            'options' => [
                                'class' => 'form-control',
                                'style' => 'width:100%',
                                'multiple' => 'multiple',
                                'data-minimum-results-for-search' => "Infinity",
                            ],
                            'pluginOptions' => [
                                'theme' => 'bootstrap',
                                'placeholder' => "Выберите вид автотранспорта",
                            ]
                        ])
                    ?>
                </div>
                <div class="col-md-12 col-sm-12 text-center">
                    <button class="search__btn content__btn has-spinner">Поиск</button>
                </div>
            </div>
        </div>
        <?php ActiveForm::end() ?>
        <div class="content__line hide-mob"></div>
        <?php if ($dataProvider): ?>
            <div class="transportation">
                <div class="transportation__head">
                    <div class="passing-loads__btn-wrap"></div>
                    <?= Html::a('Подписаться на новые грузы', [
                        '/sub/',
                        'locationFrom' => $model->city_from,
                        'locationTo' => $model->city_to,
                        'categoriesId' => $model->cargoCategoryIds
                    ], [
                        'class' => 'content__btn cargo-search__btn',
                        'data-pjax' => '0'
                    ]) ?>
                    <div class="passing-loads__title-wrap" id="scrollTo">
                        <h2 class="content__title">Найденные грузы</h2>
                    </div>
                </div>
                <?= ListView::widget([
                    'id' => 'search_items',
                    'dataProvider' => $dataProvider,
                    'itemView' => '/search/_cargo_item',
                    'itemOptions' => [
                        'tag' => false
                    ],
                    'options' => [
                        'tag' => 'div'
                    ],
                    'layout' => "{items}"
                ]);
                ?>
            </div>
            <div class="content__pagination passing-loads__pagination">
                <?= PaginationWidget::widget([
                    'pagination' => $dataProvider->getPagination(),
                    'registerLinkTags' => true,
                    'registerRobotsTags' => true,
                    'scrollTo' => '#scrollTo',
                    'searchFade' => 'search_items'
                ]) ?>
            </div>
        <?php else: ?>
            <div class="transportation">
                <div class="transportation__head">
                    <div class="passing-loads__btn-wrap"></div>
                    <div class="passing-loads__title-wrap" id="scrollTo">
                        <h2 class="content__title">Заполните параметры фильтра</h2>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php
        //скролл до результатов поиска
        $this->registerJs("
$('.search__btn').click(function(){
    $('html, body').animate({
        scrollTop: $('#scrollTo').offset().top
    }, 500);
    $('#search_items').css('opacity', 0.3);
});
"
        );
        ?>
        <?php Pjax::end() ?>
        <div id="cargo-search__map" class="cargo-search__map"
             style="display: <?= Yii::$app->request->queryParams ? 'block' : 'none' ?>"></div>
    </div>
</main>
