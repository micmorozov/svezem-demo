<?php

use common\models\CargoSearchTags;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $tags CargoSearchTags[] */
/* @var $this View */
/* @var bool $openFilter */

$tagCount = 1;
?>
<div class="posts__tags tags" <?= $openFilter?'style="display:block"':''?>>
    <div class="tags__container">
<?php
foreach($tags as $tag){
    echo Html::a($tag->name, $tag->url, ['class'=>'tags__item']);
}
?>
    <a href="<?= Url::toRoute('/cargo/search/all') ?>" class="tags__link"  data-pjax="0">
        <span class="tags__hide"><span class="text">Еще </span><i class="fas fa-external-link-alt" aria-hidden="true"></i> </span>
    </a>
    </div>
</div>
