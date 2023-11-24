<?php

namespace frontend\actions;

use common\helpers\langCorrect\LangCorrectHelper;
use common\models\City;
use common\models\FastCity;
use common\models\Region;
use yii\base\Action;

class LocationDropDownListAction extends Action
{
    public function run($query){
        $query = LangCorrectHelper::toRussian(urldecode($query));

        $items = [];

        $regionModels = Region::find()
            ->where(['like', 'title_ru', $query])
            ->limit(10)
            ->all();
        foreach ($regionModels as $model) {
            $items[] = [
                'code' => $model->getCode(),
                'value' => $model->getFullTitle(),
                'type' => 'region'
            ];
        }

        $cityModels = FastCity::find()
            ->where(['like', 'title', $query])
            ->limit(10)
            ->all();
        foreach ($cityModels as $model) {
            $city = $model->city;
            $items[] = [
                'code' => $city->getCode(),
                'value' => $city->getFullTitle(),
                'type' => 'city'
            ];
        }

        return $items;
    }
}