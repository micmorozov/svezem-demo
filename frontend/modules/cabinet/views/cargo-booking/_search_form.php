<?php

use common\models\CargoCategory;
use frontend\modules\cabinet\models\CargoBookingSearch;
use frontend\widgets\searchLocation\SearchLocation;
use frontend\widgets\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;

/*  @var $model CargoBookingSearch */
/* @var $this View */
/* @var bool $openFilter */

$cacheKey = 'CargoBookingCache';

if (!$catrgories = Yii::$app->cache->get($cacheKey)) {
    $catrgories = CargoCategory::find()
        ->root()
        ->showModerCargo()
        ->all();

    foreach ($catrgories as $category) {
        $subCats = array_filter($category->nodes, function ($cat) {
            /** @var $cat CargoCategory */
            return $cat->show_moder_cargo;
        });

        $catrgories = array_merge($catrgories, $subCats);
    }

    Yii::$app->cache->set($cacheKey, $catrgories, 86400);
}
?>
<?php $form = ActiveForm::begin([
    'id' => 'transport-search-form',
    'action' => [Url::toRoute(['/cabinet/cargo-booking/'])],
    'method' => 'get',
    'options' => [
        'data-pjax' => '1',
        //'class' => 'sticky-top'
    ],
]) ?>
<div class="panel panel-default">
    <div class="panel-heading"><!--data-toggle="collapse" data-target="#search_filter" style="cursor: pointer"-->
        <i class="fas fa-search"></i> Поиск
    </div>
    <div class="panel-body row">
        <div class="col-sm-12 col-md-12">
            <?= //Откуда
            $form->field($model, 'locationFrom', [
                'labelOptions' => ['class' => '']
            ])->widget(SearchLocation::class, [
                'data' => $model->getLocationString($model->getLocationFrom())
            ]);
            ?>
        </div>
        <div class="col-sm-12 col-md-12">
            <?= //Куда
            $form->field($model, 'locationTo', [
                'labelOptions' => ['class' => '']
            ])->widget(SearchLocation::class, [
                'data' => $model->getLocationString($model->getLocationTo())
            ]);
            ?>
        </div>
        <div class="col-sm-12">
            <?= $form->field($model, 'cargoCategoryIds', [
                    'options' => ['class' => 'form-group'],
                    'labelOptions' => ['class' => "control-label"]
                ])
                ->label('Вид транспорта')
                ->widget(Select2::class, [
                    'data' => ArrayHelper::map(CargoCategory::filterList(), 'id', 'category'),
                    'options' => [
                        'class' => 'form-control',
                        'style' => 'width:100%',
                        'multiple' => 'multiple',
                        'placeholder' => 'Все виды перевозок',
                        'data-minimum-results-for-search' => "Infinity",
                    ],
                    'pluginOptions' => [
                        'theme' => 'bootstrap',
                        'placeholder' => "Выберите вид автотранспорта",
                    ]
                ])
            ?>
        </div>
        <?= $form->field($model, 'status')->hiddenInput()->label(false) ?>
        <div class="col-sm-12 text-center" style="padding-top: 15px;">
            <div class="col-md-12 col-sm-12 search__item">
                <?= Html::submitButton('Поиск', [
                    'class' => 'btn btn-primary btn-svezem btn-search',
                    'name' => 'searchClick',
                    'value' => 1
                ]) ?>
            </div>
        </div>
    </div>
</div>
<?php $form = ActiveForm::end() ?>
