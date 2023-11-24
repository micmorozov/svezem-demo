<?php

use common\models\CargoCategory;
use common\models\CargoCategoryTags;
use common\models\PageTemplates;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use common\helpers\CategoryHelper;
use common\helpers\Utils;
use common\models\FastCity;
use Svezem\Services\MatrixContentService\MatrixContentService;

/** @var $this View */
/** @var $tpl PageTemplates */
/** @var $rootCategories CargoCategory[] */
/** @var int $city_size_mask */
/** @var City|Region|null $location */
/** @var $matrixContentService MatrixContentService */

// Устанавливаем значение по умолчанию
$title = 'Дешевые междугородние грузоперевозки';
$descr = 'Экономьте до 70%. Недорогая грузовая перевозка между городами. Объявления частных перевозчиков и услуги транспортных компаний';
$keywords = 'междугородные, перевозки, межгород, груз, грузовые, грузоперевозки, между городами, недорого';
$h1 = 'Перевозка грузов межгород';
$text = '';
// Если есть шаблон. устанавливаем его
if($tpl) {
    $title = $tpl->title;
    $descr = $tpl->desc;
    $keywords = $tpl->keywords;
    $h1 = $tpl->h1;
    $text = nl2br($tpl->text);

    //переда шаблона в layout
    $this->params['pageTpl'] = $tpl;
}
$this->title = $title;
$this->registerMetaTag([
    'name' => 'description',
    'content' => $descr
]);

?>
<main class="content list-page-wrap">
    <div class="container list-page">
        <?= $this->render('//common/_breadcrumbs') ?>
        <div class="page-title">
            <h1 class="h3 text-uppercase"><b><?= $h1 ?></b></h1>
            <?php if($text): ?>
                <div class="content__subtitle"><?= $text ?></div>
            <?php endif ?>
        </div>
        <?php if(isset($rootCategories) && $rootCategories):?>
        <?= $this->render('//common/_categories_list_by_root', [
            'rootCategories' => $rootCategories,
            'location' => $location,
                'matrixContentService' => $matrixContentService
        ]); ?>
        <?php endif ?>
    </div>
</main>

