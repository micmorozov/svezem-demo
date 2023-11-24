<?php

use common\models\CargoSearchTags;
use common\models\TkSearchTags;
use yii\helpers\Html;
use Svezem\Services\MatrixContentService\MatrixContentService;

/** @var $tags TkSearchTags[] */
/** @var $matrixContentService MatrixContentService */

$tagCount = 25;
?>
<div class="container list-page">
    <div class="list-page__title__wrap">
        <h1 class="list-page__title content__title">Междугородние перевозки</h1>
        <div class="line"></div>
    </div>
    <div class="content__subtitle">
        На нашем сайте можно бесплатно найти груз по России без посредников и диспетчера для перевозки автотранспортом, авиа или жд транспортом
    </div>
    <div class="list-page__block">
        <ul>
            <?php
            foreach($tags as $tag){
                if(!$matrixContentService->isEnoughContent('tk-search-view', $tag->cityFrom, $tag->cityTo, $tag->category))
                    continue;

                /* @var $carg CargoSearchTags*/
                echo Html::beginTag('li');
                echo Html::a($tag->name, $tag->url);
                echo Html::endTag('li');
            }
            ?>
        </ul>
    </div>
    <?php IF(count($tags) > $tagCount): ?>
    <div class="list-page__more-btn">
        <button class="form-custom-button">Показать больше</button>
    </div>
    <?php ENDIF ?>
</div>
<?php
$this->registerJs("
var tagCount = {$tagCount}; 

	hidenews = 'Скрыть';
	shownews = 'Показать больше';

	$(\".list-page__block + .list-page__more-btn button\").html( shownews );
	$(\".list-page__block ul li\").show();
	$(\".list-page__block ul li:not(:lt(\"+tagCount+\"))\").hide();

	$(\".list-page__block + .list-page__more-btn button\").click(function (e){
	  e.preventDefault();
	  if( $(\".list-page__block ul li:eq(\"+tagCount+\")\").is(\":hidden\") )
	  {
	    $(\".list-page__block ul li:hidden\").fadeIn('slow');
	    $(\".list-page__block + .list-page__more-btn button\").html( hidenews );
	  }
	  else
	  {
	    $(\".list-page__block ul li:not(:lt(\"+tagCount+\"))\").fadeOut('slow');
	    $(\".list-page__block + .list-page__more-btn button\").html( shownews );
	  }
	});
");