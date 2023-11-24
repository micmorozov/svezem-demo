<?php

use common\helpers\PhoneHelpers;
use frontend\modules\tk\assets\TkViewAsset;
use frontend\modules\tk\models\Tk;
use frontend\modules\tk\models\TkDetails;
use frontend\widgets\Share;
use simialbi\yii2\schemaorg\helpers\JsonLDHelper;
use yii\caching\TagDependency;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\web\View;
use yii\helpers\Url;
use Svezem\Services\MatrixContentService\MatrixContentService;

/** @var $model Tk */
/** @var $this View */
/** @var $trDataProvider ActiveDataProvider */
/** @var $matrixContentService MatrixContentService */

$this->title = "Отзывы о грузоперевозке - {$model->name} -  #{$model->id}";
$description = "Отзывы о грузоперевозке, контакты, объявления {$model->name} - #{$model->id}";
$this->registerMetaTag([
    'name' => 'description',
    'content' => $description
]);
// Микроразметка
JsonLDHelper::add($model->structured);

$tagCount = 15;

Yii::$app->opengraph->type = 'website';
Yii::$app->opengraph->title = $this->title;
Yii::$app->opengraph->description = $description;
Yii::$app->opengraph->image = $model->iconPath('preview_198', true, true);
Yii::$app->opengraph->url = Yii::$app->request->absoluteUrl;

TkViewAsset::register($this);
?>
<main class="content">
    <div class="container transport">
        <?= $this->render('//common/_breadcrumbs') ?>
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b>Страница "<?= $model->name ?>"</b></h1>
        </div>
        <div class="transport__block content__block main-block">
            <div class="main-block__info">
                <div class="main-block__img-wrap" title="<?= $model->name ?>">
                    <img src="<?=$model->iconPath('preview_198', false, true)?>" alt="Транспортная компания <?=$model->name?>"/>
                </div>
                <div class="main-block__info-details">
                    <div class="main-block__info-details__head">
                        <div class="main-block__name"><?= $model->name ?></div>
                    </div>
                    <div class="main-block-details">
                        <table>
                            <tr>
                                <td class="strong">Адрес:</td>
                                <td><?= $model->getCityAddress(); ?></td>
                            </tr>
                            <?php if($model->email): ?>
                                <tr>
                                    <td class="strong">E-mail:</td>
                                    <td><?= $model->email ?></td>
                                </tr>
                            <?php endif ?>
                            <?php if($model->getPhones()): ?>
                                <tr>
                                    <td class="strong">Телефон:</td>
                                    <td class="phone">
                                    <span class="mob-green" id="tk_phone">
                                        <phone-button
                                            :url="'/tk/default/fetch-phone/'"
                                            :obj_id="<?= $model->id ?>"
                                        ></phone-button>
                                    </span>
                                    </td>
                                </tr>
                            <?php endif ?>
                        </table>
                        <?php if($model->url): ?>
                            <div class="main-block-details__website">
                                <?= Html::a('Перейти на сайт компании', $model->url, ['target' => '_blank', 'rel' => 'nofollow noopener noreferrer']) ?>
                            </div>
                        <?php endif ?>
                        <?php if($model->getDetails()->count()): ?>
                            <div class="main-block-details__contacts">
                                <?= Html::a('Посмотреть контакты в других городах', '#') ?>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
            <div class="main-block-contacts" style="display: none">
                <?php
                /** @var TkDetails[] $details */
                $details = $model->getDetails()
                    ->joinWith('city')
                    ->all();

                FOREACH($details as $detail): ?>
                    <div class="main-block-contacts__item">
                        <div class="main-block-contacts__address"><?= $detail->city->title_ru.", ".$detail->city->region_ru ?> <?= $detail->address ?></div>
                        <div class="main-block-contacts__email">
                            <strong>E-mail: </strong><?= implode(',', $detail->email) ?></div>
                        <?php
                        foreach($detail->phone as $phone){ ?>
                            <div class="main-block-contacts__phone">
                                <strong>Телефон:</strong>
                                <span class="mob-green">
                                    <?= Html::a(PhoneHelpers::formatter($phone), 'tel:'.PhoneHelpers::formatter($phone, '', true)) ?>
                                </span>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                <?php ENDFOREACH ?>
            </div>

            <div class="main-block-excerpt">
                <?= $model->describe ?>
            </div>

            <div class="main-block__bottom-bar">
                <span class="transport-regdate"></span>
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

        <?php
        if($this->beginCache(Yii::$app->request->absoluteUrl, [
            'duration' => 3600,
            'dependency' => new TagDependency(['tags' => 'matrixContent'])
        ])){
            ?>
            <?php if($model->categories): ?>
                <div class="tags">
                    <h2 class="content__title">Виды перевозок с которыми работает компания</h2>
                    <div class="tags__container">
                        <?php
                        foreach($model->categories as $category){
                            $isEnough = $matrixContentService->isEnoughContent('cargo-transportation-view', null, null, $category);
                            if( !$isEnough)
                                continue;

                            echo Html::a($category->category, Url::toRoute(['/cargo/transportation/search2', 'slug' => $category]), ['class' => "tags__item"]);
                            echo "\n";
                        }
                        ?>
                        <?php IF(count($model->categories) > $tagCount): ?>
                        <span class="tags__nav">
                    <span class="tags__hide">
                        <i class="fa fa-chevron-down" aria-hidden="true"></i>
                        <span class="text">Еще</span>
                    </span>
                            <?php ENDIF ?>
                </span>
                    </div>
                </div>
            <?php endif ?>
            <?php
            $this->endCache();
        }
        ?>
        <?php
        /*Reviews::widget([
            'model' => $model,
            'dataProviderConfig' => [
                'pagination' => [
                    'pageSize' => 5,
                    'pageParam' => 'reviewPage'
                ]
            ]
        ]);*/ ?>
        <?php /*?>
        <div class="reviews">
                <div class="reviews__title-wrap">
                    <h2 class="content__title">Отзывы</h2>
                </div>
                <?php IF( $model->reviews ) : ?>
                <div class="reviews-slider">
                    <?php FOREACH($model->reviews as $review): ?>
                        <div class="reviews-slider-item-wrap">
                            <div class="reviews-slider-item">
                                <div class="reviews-slider-item__header">
                                    <div class="reviews-slider-item__img" style="background-image: url('img/ava.jpg')"></div>
                                    <div class="reviews-slider-item__det">
                                        <div class="reviews-slider-item__name"><?= $review->sender->username ?></div>
                                        <div class="reviews-slider-item__date"><?= Yii::$app->formatter->asDate($review->created_at, 'dd.MM.yyy') ?></div>
                                    </div>
                                </div>
                                <div class="reviews-slider-item__body">
                                    <?= $review->message ?>
                                </div>
                            </div>
                        </div>
                    <?php ENDFOREACH ?>
                </div>
            <?php ENDIF ?>
            <?php IF( !Yii::$app->user->isGuest ): ?>
                <div class="reviews-add">
                    <div class="reviews-add__author"><?= Yii::$app->user->identity->username ?></div>
                    <?php
                    $reviewModel = new \common\models\TkReviews();
                    $reviewModel->tk_id = $model->id;

                    $form = ActiveForm::begin([
                        'action' => '/tk/review/create/',
                        'fieldConfig' => ['class'=>'frontend\components\field\ExtendField']
                    ]) ?>
                    <?= $form->field($reviewModel, 'tk_id')->hiddenInput()->label(false) ?>
                    <div class="reviews-add-body">
                        <div class="reviews-add__photo" style="background-image: url(img/ava.jpg)"></div>
                        <?= $form->field($reviewModel, 'message', [
                            'options' => ['class'=>"reviews-add__field"]
                        ])->textarea(['class'=>"fullwidth form-comment-textarea reviews-add__textarea"])->label(false) ?>
                    </div>
                    <div class="reviews-add__btn">
                        <span class="rating rating-nro"></span>
                        <?= Html::submitButton('Оставить отзыв', ['class'=>"form-custom-button"]) ?>
                    </div>
                    <?php ActiveForm::end() ?>
                </div>
            <?php ENDIF ?>
        </div>
        <?php */ ?>

        <!--div class="content__line"></div-->
        <?php /*Pjax::begin() ?>
        <div class="content__services services content__item">
            <h2 class="content__title">Объявления перевозчика</h2>
            <?= ListView::widget([
                'dataProvider' => $trDataProvider,
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
        <div class="content__pagination carrier-search__pagination row">
            <?= \frontend\widgets\PaginationWidget::widget([
                'pagination' => $trDataProvider->getPagination(),
                'registerLinkTags' => true,
                'registerRobotsTags' => true
            ]) ?>
        </div>
        <?php Pjax::end() */ ?>
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
