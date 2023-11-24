<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 22.06.17
 * Time: 14:41
 */

namespace frontend\components;

use common\models\Cargo;
use common\models\FastCity;
use common\models\Region;
use Yii;
use yii\base\Component;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Expression;
use yii\db\Query;

class CityComponent extends Component
{
    /**
     * Получение регионов и городов, доступных для выбора
     * @param string $char
     * @return ArrayDataProvider
     */
    static public function getRegionCity($char = null)
    {
        // Приходится использовать внешнее кэширование так как кэширование запроса не работает с joinWith
        $cacheKey = self::class . ":$char";
        //if (!$query = Yii::$app->cache->get($cacheKey)) {
            $query = Region::find()
                ->joinWith('fastcity', true, 'INNER JOIN')
                ->joinWith([
                    'fastcity' => function ($q) {
                        $q->orderBy(['title' => SORT_ASC]);
                    }
                ], true, 'INNER JOIN')
                ->where(["visible" => 1])
                ->andWhere(new Expression('LEFT(title_ru, 1)=:char', [':char' => $char]))
                ->orderBy(['regions.title_ru' => SORT_ASC])
                ->all();

        //    Yii::$app->cache->set($cacheKey, $query, 86400);
        //}

        return $query;
        /*return new ArrayDataProvider([
            'allModels' => $query,
            'pagination' => [
                'pageSize' => false
            ]
        ]);*/
    }

    /**
     * @return array|mixed
     */
    public static function getAlphabet()
    {
        $cacheKey = 'Alphabet';
        //if (!$alphabet = Yii::$app->cache->get($cacheKey)) {
            $alphabet = (new Query())
                ->from(Region::tableName())
                ->select(new Expression('LEFT(title_ru, 1) chare'))
                ->innerJoin(FastCity::tableName(), Region::tableName() . '.id = ' . FastCity::tableName() . '.regionid')
                ->where(['visible' => 1])
                ->orderBy('chare')
                ->groupBy('chare')
                ->all();

            $alphabet = array_map(function ($item) {
                return $item['chare'];
            }, $alphabet);

            //Yii::$app->cache->set($cacheKey, $alphabet, 86400);
        //}

        return $alphabet;
    }

    /**
     * @return array
     */
    public static function greatRequestCity()
    {
        return (new Query())
            ->select('title, code, count(city_from) count')
            ->from(FastCity::tableName())
            ->innerJoin(Cargo::tableName(), FastCity::tableName() . '.cityid = ' . Cargo::tableName() . '.city_from')
            ->groupBy('city_from')
            ->orderBy(['count' => SORT_DESC])
            ->limit(10)
            ->cache()
            ->all();
    }
}