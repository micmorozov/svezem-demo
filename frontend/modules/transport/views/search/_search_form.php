<?php
/*  @var $model TransportSearch */
/* @var $this View */
/* @var TransportSearchTags[] $tags */

use common\models\TransportSearchTags;
use frontend\modules\transport\models\TransportSearch;
use frontend\widgets\Select2;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;
use frontend\widgets\searchLocation\SearchLocation;
use common\models\CategoryFilter;
use yii\helpers\Html;

?>
<?php $form = ActiveForm::begin([
    'action' => Url::toRoute(['/transport/search/index']),
    'method' => 'get',
    'options' => ['data-pjax' => '1', 'class' => 'sticky-top'],
    'id' => 'searchForm'
]) ?>
<div class="panel panel-default">
    <div class="panel-heading" ><!--data-toggle="collapse" data-target="#search_filter" style="cursor: pointer"-->
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
            <?= $form
                ->field($model, 'categoryFilter', [
                    'options' => ['class' => 'form-group'],
                    'labelOptions' => ['class' => "control-label"]
                ])->widget(Select2::class, [
                    'data' => CategoryFilter::transportSearchFilter(),
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
        <?php if(!empty($tags)): ?>
            <div class="col-sm-12">
                <a href="<?= Url::toRoute('/transport/search/all') ?>" class="pull-right" data-pjax="0">
                    <span class="tags__hide"><i class="fas fa-external-link-alt"></i><span class="text">Все </span></span>
                </a>
                <a href="javascript:" data-toggle="collapse" data-target="#fastFilter">Быстрые фильтры <span class="caret"></span></a>
                <div id="fastFilter" class="collapse">
                    <div style="padding-top: 15px">
                        <?php
                        foreach($tags as $tag ){
                            echo Html::a($tag->name, $tag->url, ['class'=>'badge']);
                        }
                        ?>

                    </div>
                </div>
            </div>
        <?php endif; ?>
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
