<?php
use common\models\LocationInterface;
use backend\models\CargoSearch;
use yii\web\View;
use common\models\CargoSearchTags;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use frontend\widgets\Select2;
use frontend\widgets\searchLocation\SearchLocation;
use common\models\CategoryFilter;
use yii\helpers\Html;

/**
 * @var LocationInterface $location
 * @var CargoSearch $model
 * @var View $this
 * @var bool $openFilter
 * @var CargoSearchTags[] $tags
 */

?>
<?php $form = ActiveForm::begin([
    'id' => 'searchForm',
    'action' => Url::toRoute(['/cargo/search/index']),
    'method' => 'get',
    'options' => [
        'class' => 'sticky-top',
        'data-pjax' => '1'
    ]
]) ?>
<div class="panel panel-default" style="overflow: hidden">
    <div class="panel-heading">
        <i class="fas fa-search"></i> Поиск
    </div>
    <div class="panel-body ">
        <div class="row">
            <div class="col-md-12">
                <?= //Откуда
                $form->field($model, 'locationFrom', [
                    'labelOptions' => ['class' => 'search__label']
                ])->widget(SearchLocation::class, [
                    'data' => $model->getLocationString($model->locationFrom)
                ]); ?>
            </div>
            <div class="col-md-12">
                <?= //Куда
                $form->field($model, 'locationTo', [
                    'labelOptions' => ['class' => 'search__label']
                ])->widget(SearchLocation::class, [
                    'data' => $model->getLocationString($model->locationTo)
                ]);
                ?>
            </div>
            <div class="col-md-12">
                <?= $form->field($model, 'categoryFilter', [
                        'options' => ['class' => 'form-group'],
                        'labelOptions' => ['class' => "control-label"]
                    ])->widget(Select2::class, [
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
            <?php if(!empty($tags)): ?>
                <div class="col-sm-12">
                    <div class="form-group">
                        <a href="<?= Url::toRoute('/cargo/search/all') ?>" class="pull-right" data-pjax="0">
                            <span class="tags__hide"><i class="fas fa-external-link-alt"></i><span class="text">Все </span></span>
                        </a>
                        <a href="javascript:" data-toggle="collapse" data-target="#fastFilter">Быстрые фильтры <span
                                    class="caret"></span></a>
                        <div id="fastFilter" class="collapse">
                            <div style="padding-top: 15px">
                                <?php
                                foreach ($tags as $tag) {
                                    echo Html::a($tag->name, $tag->url, ['class' => 'badge']);
                                }
                                ?>

                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="col-sm-12 text-center">
                <?= Html::submitButton('Поиск', [
                    'class' => 'btn btn-primary btn-svezem btn-search',
                    'name' => 'searchClick',
                    'value' => 1
                ]) ?>
            </div>
        </div>
    </div>
</div>
<div style="padding-bottom: 20px">
    <?= Html::a('<i class="fas fa-bullhorn"></i> Подписаться на новые грузы',
        Url::toRoute(['/sub/default/index',
            'locationFrom' => $model->getLocationFrom(),
            'locationTo' => $model->getLocationTo(),
            'categoriesId' => $model->getCargoCategoryIds()
        ]),
        [
            'class' => 'btn btn-primary btn-lg btn-block',
            'data-pjax' => '0',
            'rel' => 'nofollow'
        ]) ?>
</div>
<?php $form = ActiveForm::end() ?>
