<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 21.11.17
 * Time: 11:43
 */

namespace frontend\actions;

use common\helpers\langCorrect\LangCorrectHelper;
use common\models\City;
use yii\base\Action;

class CityListAction extends Action
{
    public function run($query){
        $query = LangCorrectHelper::toRussian(urldecode($query));

        $models = City::findList($query);
        $items = [];

        foreach ($models as $model) {
            $title_ru = $model['title_ru'];
            if(!empty($model['region_ru'])) {
                $title_ru .= ', ' . $model['region_ru'];
            }
            $title_ru .= ', ' . $model['country']['title_ru'];
            $items[] = [
                'id' => $model['id'],
                'text' => $title_ru,
            ];
        }
        return $items;
    }
}