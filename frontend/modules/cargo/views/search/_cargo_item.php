<?php

/** @var $model Cargo */
/** @var $this View */
/* @var $pageTpl PageTemplates */

use common\helpers\Convertor;
use common\models\Cargo;
use common\models\PageTemplates;
use frontend\modules\cargo\assets\CargoItemAsset;
use morphos\Cases;
use morphos\Russian\GeographicalNamesInflection;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\web\View;

$countryFromTitle = $model->cityFrom->country->title_ru;
$countryToTitle = $model->cityTo->country->title_ru;

//Категория
$category = isset($model->cargoCategory) ? $model->cargoCategory->category : '';

CargoItemAsset::register($this);
?>
<div class="services" <?= ($model->isExpired) ? 'style="opacity:0.4"' : ''; ?>>
    <div class="row services__item content__block">
        <div title="Закреплено" class="services__item-pin"></div>
        <div class="col-md-8 col-sm-8 services__item-content">
            <div class="services__item-head">
                <div class="services__item-head-logo">
                    <img src="<?= $model->icon ?>" alt="<?= $category ?>" title="<?= $category ?>"/>
                </div>
                <div class="services__item-head-info">
                    <div class="h3 services__item-head-title">
                        <?= $model->title($pageTpl??null)?>
                    </div>
                    <div class="services__item-head-direction">
                        <?php IF($model->city_from != $model->city_to): ?>
                            <div class="trans__direction direction">
                                <div class="direction__from direction__item">
                            <span class="direction__flag">
                                <?= Html::img($model->cityFrom->country->flagIcon,
                                    ['alt' => $countryFromTitle, 'title' => $countryFromTitle]) ?>
                            </span>
                                    <span class="direction__city"><?= $model->cityFrom->title_ru ?></span>
                                </div>
                                <div class="direction__arrow direction__item">
                                    <i class="fas fa-long-arrow-alt-right" style="font-size: 25px;color: #3e99dd;"></i>
                                </div>
                                <div class="direction__to direction__item">
                            <span class="direction__flag">
                                <?= Html::img($model->cityTo->country->flagIcon,
                                    ['alt' => $countryToTitle, 'title' => $countryToTitle]) ?>
                            </span>
                                    <span class="direction__city"><?= $model->cityTo->title_ru ?></span>
                                </div>
                            </div>
                        <?php ELSE: ?>
                            <div class="trans__direction direction">
                                <div class="direction__from direction__item">
                            <span class="direction__flag">
                                <?= Html::img($model->cityFrom->country->flagIcon,
                                    ['alt' => $countryFromTitle, 'title' => $countryFromTitle]) ?>
                            </span>
                                    <span class="direction__city"><?= $model->cityFrom->title_ru?><?= $model->region_from ? (', ' . $model->regionFrom->title_ru) : '' ?></span>
                                </div>
                            </div>
                        <?php ENDIF ?>
                    </div>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="services__item-info">
                <?php
                $limit = 100;

                //обрезанная строка для мобильной версии
                echo StringHelper::truncate($model->description, $limit, '');

                //троеточие для модильной версии
                if(mb_strlen($model->description) > $limit){
                    echo Html::tag('span', '...', ['class' => 'only-mobile-comment']);
                }

                //оставшейся кусок для ПК версии
                echo Html::tag('span', mb_substr($model->description, $limit), ['class' => "hide-mob-comment"]);
                ?>
            </div>
            <div>
                <small style="font-weight:400">
                    Размещено:&nbsp;<?= Yii::t("app", '{0, date, dd.MM.yyyy}', $model->created_at) ?>
                </small>
            </div>
        </div>
        <div class="col-md-4 col-sm-4" style="padding: 15px 15px;">
            <div class="services__item-price text-center">
                <?php if($model->city_from != $model->city_to): ?>
                    <?php if($model->distance) : ?>
                        <?= Convertor::distance($model->distance) ?> / <?= Convertor::time($model->duration) ?>
                    <?php endif; ?>
                <?php else : ?>
                    Доставка по городу
                <?php endif ?>
            </div>

            <?php if($model->isExpired) : ?>
                <div class="text-center" style="color: #ac4137">
                    <i style="font-size: 36px;" class="fa fa-ban"></i>
                    <span style="display: inline-block;padding: 9px;vertical-align: top;font-weight: bold;">Объявление не актуально</span>
                </div>
            <?php endif; ?>
            <div class="services__item-contact text-center" style="margin-bottom: 16px;">
                <?php if($model->isExpired) : ?>
                    <?= Html::a('ПОСМОТРЕТЬ В АРХИВЕ', $model->url) ?>
                <?php else : ?>
                    <?= Html::a('ПРЕДЛОЖИТЬ УСЛУГИ ПЕРЕВОЗКИ', $model->url) ?>
                <?php endif; ?>
            </div>

            <?php if($model->created_by == Yii::$app->user->id) : ?>
                <div class="services__item-actions row">
                    <div class="col-xs-6 text-center">
                        <?= Html::a('<i class="fas fa-pencil-alt"></i>&nbsp;Редактировать',
                            ['/cargo/default/update', 'id' => $model->id], [
                                'class' => "services__item-action",
                                'data-pjax' => '0'
                            ]) ?>

                    </div>
                    <div class="col-xs-6 text-center" style="border-left: 1px dashed #dbecf9;">
                        <?= Html::a('<i class="fas fa-times"></i>&nbsp;Удалить', '#', [
                            'class' => "services__item-action delete_cargo_item",
                            'style' => "color:red;",
                            'data-id' => $model->id
                        ]) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="clearfix"></div>
    </div>
</div>
