<?php

use common\helpers\Convertor;
use common\helpers\TemplateHelper;
use common\models\Cargo;
use common\models\CargoTags;
use frontend\modules\cargo\assets\CargoViewAsset;
use frontend\widgets\Share;
use morphos\Cases;
use morphos\Russian\GeographicalNamesInflection;
use morphos\Russian\RussianLanguage;
use simialbi\yii2\schemaorg\helpers\JsonLDHelper;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use common\models\LocationCategorySearch;
use yii\widgets\ListView;

/** @var $cargo Cargo */
/** @var $this View */
/** @var $tags CargoTags[] */
/** @var $passing ActiveDataProvider[] */

// Определяем текстовку для заголовков и h1
if ($cargo->city_from == $cargo->city_to) {
    $strCityDirection = 'по ' . GeographicalNamesInflection::getCase($cargo->cityFrom->title_ru, Cases::DATIVE);
} else {
    $strCityDirection = 'из ' . GeographicalNamesInflection::getCase($cargo->cityFrom->title_ru,
            Cases::GENITIVE) . ' ' . RussianLanguage::in(GeographicalNamesInflection::getCase($cargo->cityTo->title_ru,
            Cases::ACCUSATIVE));
}

$countryFromTitle = $cargo->cityFrom->country->title_ru;
$countryToTitle = $cargo->cityTo->country->title_ru;

$category = isset($cargo->cargoCategory) ? $cargo->cargoCategory->category : "";

$cargo_name = (trim($cargo->name_vin) != '') ? trim($cargo->name_vin) : 'Груз';
$cargo_name_rod = (trim($cargo->name_rod) != '') ? trim($cargo->name_rod) : 'Груза';
$title = "Дешевая перевозка {$cargo_name_rod} {$strCityDirection} - объявление #{$cargo->id}";
$h1 = "Перевезти {$cargo_name} {$strCityDirection}";
$description = $cargo->description;
$keywords = 'грузоперевозка, доставка, транспортировка, перевозка грузов';

$tpl = TemplateHelper::get(
    'cargo-view',
    $cargo->cityFrom,
    $cargo->cargoCategory,
    [
        'cargo_id' => $cargo->id,
        'cargo_name' => $cargo_name,
        'cargo_name_rod' => $cargo_name_rod,
        'direction' => $strCityDirection,
        'category' => isset($cargo->cargoCategory) ? $cargo->cargoCategory->category : '',
        'description' => $cargo->description
    ]
);
if ($tpl) {
    if($tpl->title) $title = $tpl->title;
    if($tpl->h1) $h1 = $tpl->h1;
    if($tpl->desc) $description = $tpl->desc;
    if($tpl->keywords) $keywords = $tpl->keywords;
}

$this->title = $title;
$this->registerMetaTag([
    'name' => 'description',
    'content' => $description
]);

//Микроразметка
JsonLDHelper::add($cargo->structured);

Yii::$app->opengraph->type = 'website';
Yii::$app->opengraph->title = $this->title;
Yii::$app->opengraph->description = $description;
Yii::$app->opengraph->image = $cargo->getIconPng(true);
Yii::$app->opengraph->url = Yii::$app->request->absoluteUrl;

$successPhoneMsg = "Хотите стать партнером и получать доступ к контактам в числе первых? Подключите услугу <a href='https://" . Yii::getAlias('@domain') . "/cargo/booking/'>бронирование заказов</a>";
$successPhoneMsg = addslashes($successPhoneMsg);
?>
<main class="content">
    <div class="container application">
        <?= $this->render('//common/_breadcrumbs') ?>
        <div class="page-title">
            <h1 class="h3 text-uppercase">
                <b><?= $h1 ?></b>
                <small>(<?= $cargo->getStatusLabel(true) ?>)</small>
            </h1>
        </div>
        <div class="application__block content__block app-block">
            <div class="app-block__info">
                <div class="app-block__img-wrap" title="<?= $category ?>">
                    <img src="<?= $cargo->icon ?>" alt="<?= $category ?>"/>
                </div>
                <div class="app-block__info-details">
                    <div class="app-block__category">Категория : <?= $category ?></div>
                    <div class="app-block__direction-info clear">
                        <?php if ($cargo->city_from != $cargo->city_to):
                            $direction = $cargo->cityFrom->title_ru . ' - ' . $cargo->cityTo->title_ru;
                            ?>
                            <div class="app-block__direction direction">
                                <div class="direction__from direction__item">
                                <span class="direction__flag">
                                    <?= Html::img($cargo->cityFrom->country->flagIcon,
                                        ['alt' => $countryFromTitle, 'title' => $countryFromTitle]) ?>
                                </span>
                                    <span class="direction__city"><?= $cargo->cityFrom->title_ru ?></span>
                                </div>
                                <div class="direction__arrow direction__item">
                                    <i class="fas fa-long-arrow-alt-right"
                                       style="font-size: 25px;color: #3e99dd;"></i>
                                </div>
                                <div class="direction__to direction__item">
                                <span class="direction__flag">
                                    <?= Html::img($cargo->cityTo->country->flagIcon,
                                        ['alt' => $countryToTitle, 'title' => $countryToTitle]) ?>
                                </span>
                                    <span class="direction__city"><?= $cargo->cityTo->title_ru ?></span>
                                </div>
                            </div>
                        <?php else:
                            $direction = 'по ' . GeographicalNamesInflection::getCase($cargo->cityFrom->title_ru,
                                    Cases::DATIVE);
                            ?>
                            <div class="app-block__direction direction">
                                <div class="direction__from direction__item">
                                <span class="direction__flag">
                                    <?= Html::img($cargo->cityFrom->country->flagIcon,
                                        ['alt' => $countryFromTitle, 'title' => $countryFromTitle]) ?>
                                </span>
                                    <span class="direction__city">по <?= GeographicalNamesInflection::getCase($cargo->cityFrom->title_ru,
                                            Cases::DATIVE); ?></span>
                                </div>
                            </div>
                        <?php endif ?>
                        <div class="app-block__km-hour">
                            <?php
                            //если разные города и известно расстояние
                            if ($cargo->city_from != $cargo->city_to && $cargo->distance): ?>
                                <?= Convertor::distance($cargo->distance) ?> / <?= Convertor::time($cargo->duration) ?>
                            <?php endif ?>
                        </div>
                    </div>
                    <div class="app-block__excerpt">
                        <?= $cargo->description ?>
                    </div>
                    <br><br>

                    <?= Html::a("<i class=\"fas fa-bell\"></i>&nbsp;Подписаться на грузы {$direction}",
                        Url::toRoute(['/sub/default/index',
                            'locationFrom' => $cargo->cityFrom,
                            'locationTo' => $cargo->cityTo,
                            'categoriesId' => $cargo->categoriesId
                        ]), ['class' => '', 'rel' => 'nofollow']) ?>
                </div>
            </div>
            <div class="app-block__bottom-bar">
                <div class="app-block__post-det">
                    Размещено: <?= Yii::t("app", '{0, date, dd.MM.yyyy}', $cargo->created_at) ?> <br>Просмотров:
                    <span
                            class="strong"><?= $cargo->views_count ?></span></div>
                <div class="app-block__social">
                    <span class="share-text">Поделиться: </span>
                    <span class="share-links">
                    <?php
                    if ($cargo->city_from == $cargo->city_to) {
                        $twitterCity = $strCityDirection;
                    } else {
                        $twitterCity = $cargo->cityFrom->title_ru . "-" . $cargo->cityTo->title_ru;
                    }

                    $twitterText = $twitterCity . ": " . $cargo->description;
                    ?>
                    <?= Share::widget([
                        'linkParams' => [
                            Share::VK => [
                                'title' => "Требуется доставить " . $strCityDirection . ": " . $cargo->description
                            ]
                        ],
                        'twitterText' => $twitterText
                    ]) ?>
                </span>
                </div>
            </div>
        </div>
        <div class="author__wrap clear">
            <div class="col-md-5 col-md-push-7 col-sm-push-6 col-sm-6">
            </div>
            <div class="col-md-7 col-md-pull-5 col-sm-pull-6 col-sm-6">
                <div class="application__author author">
                    <h2 class="author__title">Информация об отправителе</h2>
                </div>
            </div>
            <div class="col-sm-12 col-md-7">
                <div class="application__author author">
                    <div class="author__block clear">
                        <div class="author__img-wrap">
                            <?= Html::img($cargo->profile->image, ['class' => "author__img"]) ?>
                        </div>
                        <div class="author__details" id="vue-phone">
                            <div class="author__name"><?= $cargo->profile->contact_person ?></div>
                            <div class="author__contact-det">
                                <template v-if="isGuest">
                                    <a href="https://<?= Yii::getAlias('@domain') ?>/account/login/" rel="nofollow">Авторизуйтесь</a> для получения контактов отправителя
                                </template>
                                <template v-else>
                                    <div class="author__contact-det">
                                        <template>
                                            <cargo-phone-button
                                                    :domain="'//<?= Yii::getAlias('@domain') ?>'"
                                                    :url="'/cargo/default/fetch-phone/'"
                                                    :obj_id="<?= $cargo->id ?>">
                                            </cargo-phone-button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?= $this->render('_tags', ['tags' => array_slice($tags, 0, round(count($tags)/2))]) ?>

        <!--div class="application__route-wrap">
            <h2 class="content__title">Направление перевозки</h2>
            <div class="application__route route clear">
                <div class="route__tab-nav clear">
                    <div class="col-md-6 col-sm-6 route__cities-wrap from active">
                        <div class="route__cities">Забрать груз :
                            <div class=""><span class="route__city"><?= $cargo->cityFrom->title_ru ?></span>
                                <span class="flag"><?= Html::img($cargo->cityFrom->country->flagIcon, ['width' => '38px']) ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-6 route__cities-wrap to">
                        <div class="route__cities">Сдать груз :
                            <div class=""><span class="route__city"><?= $cargo->cityTo->title_ru ?></span>
                                <span class="flag"><?= Html::img($cargo->cityTo->country->flagIcon, ['width' => '38px']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 route__map" id="route__from">
                    <div id="route-map" style="height: 400px"></div>
                </div>
                <div class="col-md-12 route__distance">
                    Расстояние: <?= Convertor::distance($cargo->distance) ?> <?= Convertor::time($cargo->duration) ?></div>
            </div>
        </div-->

        <?= $this->render('_tags', ['tags' => array_slice($tags, round(count($tags)/2))]) ?>

        <div class="application__cargo cargo">
            <h2 class="content__title">Похожие грузы</h2>
            <!--div id="passingItems"></div-->

            <?= ListView::widget([
                'id' => 'passing_items',
                'dataProvider' => $passing,
                'itemView' => '@frontend/modules/cargo/views/search/_cargo_item',
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

    </div>
</main>
<?php

CargoViewAsset::register($this);

//подгрузка попутных грузов
/*$this->registerJs("$.ajax({
    type: 'GET',
    url: '/cargo/default/passing-items/',
    data: 'id={$cargo->id}',
    success: function(resp){
        $('#passingItems').html(resp);
    }
});");*/

$this->registerJs("window.cargoViewID={$cargo->id};", View::POS_HEAD);
$this->registerJs("cargo_view_init();", View::POS_END);
