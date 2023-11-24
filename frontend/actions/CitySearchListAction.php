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
use common\models\Region;
use yii\base\Action;

class CitySearchListAction extends Action
{
    public function run($query){
        $query = LangCorrectHelper::toRussian(urldecode($query));

        $items = [];

        $regionModels = Region::findList(urldecode($query));
        foreach ($regionModels as $model) {
            $title_ru = $model['title_ru'];
            if(!empty($model['region_ru'])) {
                $title_ru .= ', ' . $model['region_ru'];
            }
            $title_ru .= ', ' . $model['country']['title_ru'];
            $items[] = [
                'id' => $model['id'],
                'text' => $title_ru,
                'type' => 'region',
                'region' => '1'
            ];
        }

        $cityModels = City::findList($query);
        foreach ($cityModels as $model) {
            $title_ru = $model['title_ru'];
            if(!empty($model['region_ru'])) {
                $title_ru .= ', ' . $model['region_ru'];
            }
            $title_ru .= ', ' . $model['country']['title_ru'];
            $items[] = [
                'id' => $model['id'],
                'text' => $title_ru,
                'type' => 'city'
            ];
        }

        return $items;
    }
}