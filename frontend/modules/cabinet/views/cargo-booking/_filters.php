<?php

use yii\helpers\Html;
use yii\web\View;

/** @var View $this */
/** @var $filters array */
$this->registerJs("
$('.bookingFilter').click(function(){
$('#search_items').css('opacity', 0.3);
});");
?>
<ul class="nav nav-tabs">
    <?php foreach ($filters['main'] as $filter) : ?>
        <li role="presentation" class="<?= $filter['select'] ? 'active' : '' ?>">
            <?= Html::a($filter['icon'] .' <span class="hidden-xs hidden-sm">'. $filter['name'] . "</span>(" . $filter['count'] . ")", $filter['url'], [
                'class' => 'bookingFilter'
            ]); ?>
        </li>
    <?php endforeach; ?>
</ul>
