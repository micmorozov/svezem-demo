<?php

use common\helpers\TemplateHelper;
use common\helpers\Utils;
use common\models\Service;
use common\models\Transport;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var $model Transport */

/** @var Redis $redis */
$redis = Yii::$app->redisTemp;

if( !$model->existPostion()){
    echo 'Ваше объявление размещено на следующих страницах: Идет расчет...';
    Transport::calculatePosition($model->id);
    return false;
} else{
    $page = $redis->get($model->positionKeyFrom());

    $tpl = TemplateHelper::get('transport-search-from-city-view', $model->cityFrom);
    $text = $tpl->tag_name.' '.Utils::pagePositionTextStyle($page);
    $src = Url::to([
        '/transport/search',
        'TransportSearch[locationFrom]' => $model->city_from
    ]);
    $links[] = Html::a($text, $src, ['data-pjax' => 0]);

    //=============================
    $page = $redis->get($model->positionKeyFromTo());

    $tpl = TemplateHelper::get('transport-search-from-to-city-view', $model->cityFrom, null, [
        'city_to' => $model->cityTo->title_ru
    ]);
    $text = $tpl->tag_name.' '.Utils::pagePositionTextStyle($page);
    $src = Url::to([
        '/transport/search',
        'TransportSearch[locationFrom]' => $model->city_from,
        'TransportSearch[locationTo]' => $model->city_to
    ]);
    $links[] = Html::a($text, $src, ['data-pjax' => 0]);

    //=============================
//    if($list = $redis->zRange($model->positionKeyCat(), 0, -1, true)){
//        $catId = array_rand($list);
//        $page = $list[$catId];
//        $category = CargoCategory::findOne($catId);
//
//        $result[] = [
//            'text' => '"'.$category->category.'" '.Utils::pagePositionTextStyle($page),
//            'src' => Url::to([
//                '/transport/search',
//                'TransportSearch[locationFrom]' => $model->city_from,
//                'TransportSearch[cargoCategoryIds]' => $category->id
//            ])
//        ];
//
//        $text = '"'.$category->category.'" '.Utils::pagePositionTextStyle($page);
//        $src = Url::to([
//            '/transport/search',
//            'TransportSearch[locationFrom]' => $model->city_from,
//            'TransportSearch[cargoCategoryIds]' => $category->id
//        ]);
//        $links[] = Html::a($text, $src, ['data-pjax' => 0]);
//    }

    echo "Ваше объявление размещено на следующих страницах: ".implode(', ', $links)."<br>";

    //Нет на Главной и не оплачен
    $page = $redis->get($model->positionKeyMainCity());
    if($page != 1 && !$model->mainPageProgress){
        echo "<span style='color: red;'>Вашего объявления нет на главной странице!</span> ".Html::a('Разместить', [
                '/payment/transport',
                'service_id' => Service::MAIN_PAGE,
                'item_id' => $model->id
            ], ['data-pjax' => 0]);
        echo "<br>";
    }

    //Нет на Рекомендованной и не оплачен
    $page = $redis->get($model->positionKeyRecommend());
    if($page != 1 && !$model->recommendationProgress){
        //Вашего объявления нет на главной странице! Закрепить. Закрепить - ссылка на оплату
        echo "<span style='color: red;'>Вашего объявления нет на странице рекомендованных!</span> ".Html::a('Разместить', [
                '/payment/transport',
                'service_id' => Service::RECOMMENDATIONS,
                'item_id' => $model->id
            ], ['data-pjax' => 0]);
    }
}
