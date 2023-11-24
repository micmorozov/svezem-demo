<?php

use common\models\CargoTags;
use yii\helpers\Html;
use yii\web\View;

/** @var $tags CargoTags[] */
/** @var $this View */

if(!$tags) return;
?>
<div class="application__tags tags">
<?php
foreach($tags as $tag ){
    echo Html::a($tag->name, $tag->url, ['class'=>'tags__item']);
}
?>
</div>