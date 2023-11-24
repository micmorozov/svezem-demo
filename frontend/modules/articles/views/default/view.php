<?php

use common\models\Articles;
use yii\helpers\Html;
use yii\helpers\Url;
use frontend\widgets\Share;
use yii\web\View;
use yii\widgets\ListView;

/** @var $article Articles */
/** @var $this View */
/** @var array $seeAlso */

$this->title = $article->name;
$this->registerMetaTag([
    'name' => 'description',
    'content' => $article->description
]);

$tagCount = 15;

Yii::$app->opengraph->type = 'article';
Yii::$app->opengraph->title = $this->title;
Yii::$app->opengraph->description = $article->description;
Yii::$app->opengraph->image = $article->imagePath('article');

$articleCategories = $article->categories;
?>
<main class="content">
    <div class="container post">
        <?= $this->render('//common/_breadcrumbs') ?>
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b><?= $article->name ?></b></h1>
        </div>
        <div class="post__tags tags">
            <div class="tags__container">
                Рубрики:
                <?php
                    foreach($articleCategories as $category){
                        echo Html::a($category->category, Url::toRoute('/articles/'.$category->slug), ['class'=>"tags__item"]);
                        echo "\n";
                    }
                ?>
                <?php IF( count($articleCategories) > $tagCount ): ?>
                <span class="tags__nav">
                    <span class="tags__hide"><i class="fa fa-chevron-down" aria-hidden="true"></i> <span class="text">Еще</span></span>
                </span>
                <?php ENDIF ?>
            </div>
        </div>
        <div class="post__item item clear">
            <div class="item__img" title="<?= $article->name ?>"
                 style="background-image: url('<?= $article->imagePath('article') ?>');"></div>
            <div class="item__title-wrap"></div>
            <div class="item__text">
                <?= $article->viewBody ?>
            </div>
            <div class="return">
                <?= Html::a('Вернутся к списку статей', '/articles/') ?>
            </div>
        </div>

        <div class="post__social social">
            <div class="share-text">Поделиться: </div>
            <span class="share-links">
                <?= Share::widget() ?>
            </span>
        </div>

        <div class="posts-type">
            <div class="h2 content__title">Вам также может быть интересно</div>
            <div class="content__line hide-mob"></div>
            <div class="post-type__news">
                <div class="news clear">
                    <?php
                    /** @var Articles $article */
                    foreach($seeAlso as $article){
                        echo $this->render('_article_item_small', [
                                'model' => $article
                        ]);
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="post__tags tags">
            <div>
                <h2 class="h4 text-uppercase"><b>Найти груз или заказать перевозку в категории:</b></h2>
            </div>
            <div class="tags__container">
                <?php
                foreach($articleCategories as $category){
                    echo Html::a($category->category, Url::toRoute(['/cargo/transportation/search2', 'slug'=>$category]), ['class'=>"tags__item"]);
                    echo "\n";
                }
                ?>
            </div>
        </div>

    </div>
</main>
<?= $this->render('//common/_feedot-loader') ?>

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
