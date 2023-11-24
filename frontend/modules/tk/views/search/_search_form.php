<?php

use frontend\modules\tk\models\TkSearch;
use frontend\widgets\Select2;
use yii\widgets\ActiveForm;
use frontend\widgets\searchLocation\SearchLocation;
use yii\helpers\Html;
use common\models\CategoryFilter;
use yii\helpers\Url;

/** @var $model TkSearch */
?>

<?php $form = ActiveForm::begin([
    'action' => Url::toRoute(['/tk/search/index']),
    'method' => 'get',
    'options' => [
        'class' => 'sticky-top',
        'data-pjax' => '1'
    ],
    'id' => 'searchForm'
]) ?>
<div class="panel panel-default">
    <div class="panel-heading" ><!--data-toggle="collapse" data-target="#search_filter" style="cursor: pointer"-->
        <i class="fas fa-search"></i> Поиск
    </div>
    <div class="panel-body row">
        <div class="col-md-12">
            <?= $form->field($model, 'locationFrom')
                ->widget(SearchLocation::class, [
                    'data' => $model->getLocationString($model->getLocationFrom())
                ])
                ->label('Город');
            ?>
        </div>
        <div class="col-md-12">
            <?= $form->field($model, 'categoryFilter', [
                    'options' => ['class' => 'form-group'],
                    'labelOptions' => ['class' => "control-label"]
                ])
                ->widget(Select2::class, [
                    'data' => CategoryFilter::tkSearchFilter(),
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
        <div class="col-md-12 text-center">
            <?= Html::submitButton('Поиск', [
                'class' => 'btn btn-primary btn-svezem btn-search',
                'name' => 'searchClick',
                'value' => 1
            ]) ?>
        </div>
    </div>
</div>
<?php ActiveForm::end() ?>
