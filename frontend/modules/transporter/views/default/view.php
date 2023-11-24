<?php

use common\models\Profile;
use common\models\TransporterTags;
use frontend\modules\transporter\assets\TransporterViewAsset;
use frontend\widgets\PaginationWidget;
use frontend\widgets\Share;
use simialbi\yii2\schemaorg\helpers\JsonLDHelper;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\web\View;
use yii\widgets\ListView;
use yii\widgets\Pjax;
use yii\helpers\Url;
use common\helpers\TemplateHelper;

/** @var $profile Profile */
/** @var $this View */
/** @var $tags TransporterTags[] */
/** @var ActiveDataProvider $trProvider */
/** @var ActiveDataProvider $similarProvider */

$title = "Отзывы о грузоперевозке перевозчика {$profile->contact_person} (#{$profile->id})";
$description = "Отзывы о грузоперевозке, контакты, объявления перевозчика {$profile->contact_person} - #{$profile->id}";
$h1 = "Страница перевозчика \"{$profile->contact_person}\"";

$tpl = TemplateHelper::get(
    'transporter-view',
    $profile->city,
    null,
    [
        'id' => $profile->id,
        'transporter_name' => $profile->contact_person,
        'min_price' => 0
    ]
);
if ($tpl) {
    if($tpl->title) $title = $tpl->title;
    if($tpl->h1) $h1 = $tpl->h1;
    if($tpl->desc) $description = $tpl->desc;
}

$this->title = $title;
$this->registerMetaTag([
    'name' => 'description',
    'content' => $description
]);

// Микроразметка
JsonLDHelper::add($profile->structured);

$tagCount = 15;

Yii::$app->opengraph->type = 'website';
Yii::$app->opengraph->title = $title;
Yii::$app->opengraph->description = $description;
Yii::$app->opengraph->image = $profile->imagePng;
Yii::$app->opengraph->url = Yii::$app->request->absoluteUrl;

TransporterViewAsset::register($this);
?>
<main class="content">
    <div class="container carrier">
        <?= $this->render('//common/_breadcrumbs') ?>
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b><?= $h1?></b></h1>
        </div>
        <div class="carrier__block content__block main-block">
            <div class="main-block__info">
                <div class="main-block__img-wrap">
                    <img src="<?= $profile->image ?>" alt="Грузоперевозчик <?= $profile->contact_person ?>"
                         title="<?= $profile->contact_person ?>"/>
                </div>
                <div class="main-block__info-details">
                    <div class="main-block__info-details__head">
                        <div class="main-block__name"><?= $profile->contact_person ?></div>
                    </div>
                    <div class="main-block-details">
                        <table>
                            <tr>
                                <td class="strong">Статус:</td>
                                <td><?= $profile->getPersonLabel(false, false) ?></td>
                            </tr>
                            <tr>
                                <td class="strong">Город:</td>
                                <td><?= $profile->city->title_ru ?></td>
                            </tr>
                            <?php if ($profile->contact_email): ?>
                                <tr>
                                    <td class="strong">E-mail:</td>
                                    <td><?= Html::a($profile->contact_email, 'mailto:' . $profile->contact_email) ?></td>
                                </tr>
                            <?php endif ?>
                            <?php if ($profile->contact_phone): ?>
                                <tr>
                                    <td class="strong">Телефон:</td>
                                    <td class="phone">
                                    <span class="mob-green" id="transporter_profile">
                                        <phone-button
                                                :url="'/transporter/default/fetch-phone/'"
                                                :obj_id="<?= $profile->id ?>"
                                                :complaint="true"
                                        ></phone-button>
                                    </span>
                                    </td>
                                </tr>
                            <?php endif ?>
                        </table>
                    </div>
                </div>
            </div>
            <div class="main-block__bottom-bar">
                <div class="carrier-regdate">На сайте
                    с <?= Yii::$app->formatter->asDate($profile->created_at, 'dd. MM. yyyy') ?></div>
                <div class="main-block__social">
                    <span class="share-text">Поделиться: </span>
                    <span class="share-links">
                    <?= Share::widget([
                        'linkParams' => [
                            Share::VK => [
                                'title' => $description
                            ]
                        ],
                        'twitterText' => $description
                    ]) ?>
                </span>
                </div>
            </div>
        </div>

        <?= $this->render('_tags', ['tags' => array_slice($tags,0, round(count($tags)/2))]) ?>

        <div class="content__line"></div>

        <?php Pjax::begin() ?>
        <div class="content__services services content__item comment_show" id="scrollTo">
            <h2 class="content__title">Объявления перевозчика</h2>
            <?= ListView::widget([
                'id' => 'search_items',
                'dataProvider' => $trProvider,
                'itemView' => '@frontend/modules/transport/views/search/_transport_item',
                'itemOptions' => [
                    'tag' => false
                ],
                'options' => [
                    'tag' => 'div'
                ],
                'layout' => "{items}",
                'viewParams' => [
                    'showContactButton' => false, // Показывать ли кнопку "ПОСМОТРЕТЬ КОНТАКТЫ"
                    'fullDescription' => true
                ],
            ]);
            ?>
        </div>
        <div class="content__pagination carrier-search__pagination">
            <?= PaginationWidget::widget([
                'pagination' => $trProvider->getPagination(),
                'registerLinkTags' => true,
                'registerRobotsTags' => true,
                'scrollTo' => 'scrollTo',
                'searchFade' => 'search_items'
            ]) ?>
        </div>
        <?php Pjax::end() ?>

        <?= $this->render('_tags', ['tags' => array_slice($tags, round(count($tags)/2))]) ?>

        <div class="content__services services content__item comment_show" id="scrollTo">
            <h2 class="content__title">Предложения других перевозчиков</h2>
            <?= ListView::widget([
                'id' => 'search_items',
                'dataProvider' => $similarProvider,
                'itemView' => '@frontend/modules/transport/views/search/_transport_item',
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

$this->registerJs("
var tagCount = $tagCount; 

hidenews = '<i class=\"fa fa-chevron-up\" aria-hidden=\"true\"></i><span class=\"text\">Скрыть</span>';
shownews = '<i class=\"fa fa-chevron-down\" aria-hidden=\"true\"></i><span class=\"text\">Еще</span>';

$(\".tags__container .tags__nav\").html( shownews );
$(\".tags__container .tags__item\").show();
$(\".tags__container .tags__item:not(:lt(\"+tagCount+\"))\").hide();

$(\".tags__container .tags__nav\").click(function (e){
  e.preventDefault();
  if( $(\".tags__container .tags__item:eq(\"+tagCount+\")\").is(\":hidden\") )
  {
    $(\".tags__container .tags__item:hidden\").fadeIn('slow');
    $(\".tags__container .tags__nav\").html( hidenews );
  }
  else
  {
    $(\".tags__container .tags__item:not(:lt(\"+tagCount+\"))\").fadeOut('slow');
    $(\".tags__container .tags__nav\").html( shownews );
  }
});
");
