<?php
use common\helpers\StringHelper;
use common\models\PageTemplates;
use common\models\Service;
use common\models\Transport;
use frontend\modules\transport\assets\TransportItemAsset;
use morphos\Cases;
use morphos\Russian\GeographicalNamesInflection;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use common\models\CargoCategory;

/* @var $model Transport */
/* @var $this View */
/* @var $pageTpl PageTemplates */
/* @var bool $isChita */
/* @var int $cutText */
/* @var book $fullDescription  */
/** @var CargoCategory $category  */

$category = $category ?? null;

$price = (isset($model->price_from) && $model->price_from > 0) ? number_format($model->price_from, 0, '.',
        ' ')." руб." : "Договорная";

$cutText = isset($cutText) ? $cutText : false;

$countryFromTitle = $model->cityFrom->country->title_ru;
$countryToTitle = $model->cityTo->country->title_ru;

// Отображать ли кнопку "ПОСМОТРЕТЬ КОНТАКТЫ"
$showContactButton = $showContactButton ?? true;

//Показывать полный текст описания
$fullDescription = $fullDescription ?? false;

// Отображать ли описание к объявлению
$pageTpl = $pageTpl ?? null;
$showDescription = !($pageTpl && $pageTpl->isServicePageTpl());

TransportItemAsset::register($this);
?>
<div class="content__block row services__item <?= $model->colored > time() ? 'colored' : '' ?>">
    <!--    <div title="Закреплено" class="services__item-pin"></div>-->
    <div class="col-md-8 col-sm-12 services__item-content">
        <div class="services__item-head">
            <div class="services__item-head-logo">
                <img style="max-width:86px;max-height:86px" src="<?= $model->getImagePath('preview_86', true) ?>"
                     alt="Грузоперевозчик <?= $model->profile->contact_person; ?>"/>
            </div>
            <div class="services__item-head-info">
                <div class="h3 services__item-head-title"><?= Transport::titleItemByTemplate($model, $pageTpl, $category)?></div>
                <div class="services__item-head-direction">
                    <?php if($model->city_from != $model->city_to): ?>
                        <div class="trans__direction direction">
                            <div class="direction__from direction__item">
                                <span class="direction__flag">
                                    <?= Html::img($model->cityFrom->country->flagIcon, [
                                        'alt' => $countryFromTitle,
                                        'title' => $countryFromTitle
                                    ]) ?>
                                </span>
                                <span class="direction__city"><?= $model->cityFrom->title_ru ?></span>
                            </div>
                            <div class="direction__arrow direction__item">
                                <i class="fas fa-long-arrow-alt-right" style="font-size: 25px;color: #3e99dd;"></i>
                            </div>
                            <div class="direction__to direction__item">
                                        <span class="direction__flag">
                                            <?= Html::img($model->cityTo->country->flagIcon, [
                                                'alt' => $countryToTitle,
                                                'title' => $countryToTitle
                                            ]) ?>
                                        </span>
                                <span class="direction__city"><?= $model->cityTo->title_ru ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="trans__direction direction">
                            <div class="direction__from direction__item">
                                        <span class="direction__flag">
                                            <?= Html::img($model->cityFrom->country->flagIcon, [
                                                'alt' => $countryFromTitle,
                                                'title' => $countryFromTitle
                                            ]) ?>
                                        </span>
                                <span class="direction__city">
                                    по <?= GeographicalNamesInflection::getCase($model->cityFrom->title_ru, Cases::DATIVE); ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <?php
            if($showDescription):
        ?>
            <div class="services__item-info">
                <?php
                if(mb_strlen($model->description_short) == mb_strlen($model->description) || $fullDescription){
                    echo $model->description;
                } else{
                    $controls = "collapse_transport_{$model->id}";

                    echo Html::tag('span', $model->description_short, ['id' => $controls.'_short']);

                    echo Html::tag('span', $model->description, [
                        'id' => $controls,
                        'style' => 'display: none;'
                    ]);
                    echo " ";
                    echo Html::beginTag('span', [
                        'class' => 'tr_full_text',
                        'style' => 'color: #2a82e6; cursor: pointer;',
                        'data-target' => $controls,
                    ]);
                    echo '<span class="showBtn">Подробнее <i class="fa fa-chevron-down"></i></span>';
                    echo '<span class="hideBtn" style="display: none">Скрыть <i class="fa fa-chevron-up"></i></span>';
                    echo Html::endTag('span');
                }
                ?>
            </div>
        <?php
            endif;
        ?>
    </div>
    <div class="col-md-4 col-sm-12" style="padding-bottom: 16px;">
        <div class="services__item-price text-center">
            Цена от&nbsp;<span class="services__item-price-value"><?= $price ?></span>&nbsp;за <?= $model->estimateLabel ?>
        </div>
        <div class="services__item-contact text-center text-uppercase">
            <?php if($showContactButton): ?>
                <?= Html::a('Посмотреть контакты', $model->url, ['data-pjax' => 0])
                ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="clearfix"></div>
    <?php if(Yii::$app->user->id == $model->created_by) : ?>
        <div class="col-md-12 services__item-promo">
            <div class="row">
                <div class="col-md-8 col-sm-7">
                    <div class="row">
                        <?php
                        /** @var Service[] $services */
                        $services = Service::find()
                            ->where(['id' => Yii::$app->params['transportServices']])
                            ->cache(3600)
                            ->all();

                        $time = time();

                        foreach($services as $service):
                            switch($service->id){
                                case Service::SEARCH:
                                    $progress = $model->topProgress;
                                    $serviceColumn = 'top';
                                    break;
                                case Service::MAIN_PAGE:
                                    $progress = $model->mainPageProgress;
                                    $serviceColumn = 'show_main_page';
                                    break;
                                case Service::COLORED:
                                    $progress = $model->coloredProgress;
                                    $serviceColumn = 'colored';
                                    break;
                                case Service::RECOMMENDATIONS:
                                    $progress = $model->recommendationProgress;
                                    $serviceColumn = 'recommendation';
                                    break;
                                default :
                                    $progress = 0;
                            }

                            if( $model->{$serviceColumn} > $time ) {
                                $title = 'Услуга "'.$service->name.'" истекает '.Yii::$app->formatter->asRelativeTime($model->top);
                            } else {
                                $title = $service->name;
                            }
                            ?>
                            <div class="col-12 col-sm-3" style="padding: 0 5px;">
                                <a href="<?= Url::to([
                                    '/payment/transport',
                                    'service_id' => $service->id,
                                    'item_id' => $model->id
                                ]) ?>"
                                   class="btn btn-sm btn-default services__item-promo-btn" data-pjax="0"
                                    title="<?= htmlspecialchars($title) ?>">
                                    <span class="services__item-promo-btn-progress <?= $progress < 30 ? 'warning' : '' ?>"
                                          style="width: <?= $progress ?>%;"></span>
                                    <span class="services__item-promo-btn-title"><?= $service->name ?></span>
                                </a>

                                <?php if($progress > 0 && $progress < 30): ?>
                                    <a href="<?= Url::to([
                                        '/payment/transport',
                                        'service_id' => $service->id,
                                        'item_id' => $model->id
                                    ]) ?>" class="btn btn-xs btn-default services__item-promo-btn--extend"
                                       data-pjax="0">
                                        <span class="services__item-promo-btn-title">Продлить</span>
                                    </a>
                                <?php endif ?>
                            </div>
                        <?php
                        endforeach;
                        ?>
                    </div>
                </div>
                <div class="col-md-4 col-sm-5">
                    <div class="services__item-actions row">
                        <div class="col-xs-6 text-center">
                            <?= Html::a('<i class="fas fa-pencil-alt"></i>&nbsp;Редактировать',
                                ['/transport/update', 'id' => $model->id], [
                                    'class' => "services__item-action",
                                    'data-pjax' => '0'
                                ]) ?>
                        </div>
                        <div class="col-xs-6 text-center" style="border-left: 1px dashed #dbecf9;">
                            <?= Html::a('<i class="fas fa-times"></i>&nbsp;Удалить', '#', [
                                "class" => "services__item-action delete_transport_item",
                                "style" => "color:red;",
                                'data-id' => $model->id
                            ]) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
            $blockId = "item_".StringHelper::str_rand(6);

            if( !$model->existPostion() )
                $this->registerJs("reloadItem('{$blockId}', {$model->id})");
        ?>
        <div class="col-md-12 services__item-promo" id="<?= $blockId ?>">
        <?= $this->render('_position', ['model' => $model]) ?>
        </div>
        <div class="clearfix"></div>
    <?php endif; ?>
</div>
