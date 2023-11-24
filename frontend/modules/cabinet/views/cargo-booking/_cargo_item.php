<?php

/** @var $model Cargo */
/** @var $this View */

/* @var $pageTpl PageTemplates */

use common\helpers\TemplateHelper;
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

//Если передан шаблон, то заполняем поле груза
$cargo_title = '';
if(isset($pageTpl)){
    $pageTpl = TemplateHelper::fillTemplate($pageTpl, [
        'cargo_name' => $model->name??'груз',
        'cargo_name_rod' => $model->name_rod??'груза'
    ], ['cargo_title']);

    $cargo_title = $pageTpl->cargo_title;
}

if(trim($cargo_title) == ''){
    $cargo_title = $category;
}
?>
<div class="services">
    <div class="row services__item content__block">
        <div title="Закреплено" class="services__item-pin"></div>
        <div class="col-md-12 col-sm-12 services__item-content">
            <div class="services__item-head">
                <div class="services__item-head-logo">
                    <img src="<?= $model->icon ?>" alt="<?= $category ?>" title="<?= $category ?>"/>
                </div>
                <div class="services__item-head-info">
                    <h3 class="services__item-head-title">
                        <small style="font-weight:400" class="pull-right">
                            Размещено:&nbsp;<?= Yii::t("app", '{0, date, dd.MM.yyyy}', $model->created_at) ?>
                            &nbsp;(<?= $model->views_count ?>)
                        </small>
                        <?= $cargo_title ?: '' ?>
                        <br/>
                    </h3>
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
                                    <span class="direction__city">по <?= GeographicalNamesInflection::getCase($model->cityFrom->title_ru,
                                            Cases::DATIVE); ?></span>
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
            <div style="padding-bottom: 10px">
                <hr/>
                <cargo-phone-button
                    :domain="'//<?= Yii::getAlias('@domain') ?>'"
                    :url="'/cargo/default/fetch-phone/'"
                    :obj_id="<?= $model->id ?>">
                </cargo-phone-button>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
</div>