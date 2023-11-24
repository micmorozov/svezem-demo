<?php

namespace common\helpers;

use common\models\City;
use common\models\FromToLocationInterface;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;

class LocationCacheHelper
{
    /**
     * Сбрасываем кэш
     * @param FromToLocationInterface $model
     * @param CacheInterface $cache
     */
    public static function invalidateTag(FromToLocationInterface $model, CacheInterface $cache)
    {
        /** @var City $cityFrom */
        $cityFrom = $model->cityFrom;
        /** @var City $cityTo */
        $cityTo = $model->cityTo;
        $regionFrom = $cityFrom->region;
        $regionTo = $cityTo->region;
        $countryFrom = $cityFrom->country;
        $countryTo = $cityTo->country;

        TagDependency::invalidate($cache, [
            $model::tableName(),
            $model::tableName() . "-from-".$cityFrom->getCode(),
            $model::tableName() . "-to-".$cityTo->getCode(),
            $model::tableName() . "-from-".$cityFrom->getCode()."-to-".$cityTo->getCode(),

            $model::tableName() . "-from-".$regionFrom->getCode(),
            $model::tableName() . "-to-".$regionTo->getCode(),

            $model::tableName() . "-from-".$countryFrom->getCode(),
            $model::tableName() . "-to-".$countryTo->getCode(),
        ]);
    }
}