<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 25.04.18
 * Time: 11:20
 */

namespace frontend\modules\cargo\models;

use common\helpers\RouteHelper;
use common\models\Cargo;
use common\models\CargoCategory;
use common\models\CategoryFilter;
use common\models\City;
use common\models\query\CargoQuery;
use Yii;
use yii\base\Model;
use yii\caching\TagDependency;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;

/**
 * Class CargoPassing
 * @package frontend\modules\cargo\models
 *
 * @property City $cityFrom
 * @property City $cityTo
 */
class CargoPassing extends Model
{
    const CACHE_TIME = 3600;

    public $city_from;
    public $city_to;
    public $radius;
    public $cargoCategoryIds;

    public $categoryFilter;

    //ИД грузов, которые не должны попасть в результат выдачи
    public $excludeCargo;

    /** @var TagDependency */
    private $tagDependency;

    public $page;

    public function init()
    {
        parent::init();

        $this->tagDependency = new TagDependency(['tags' => 'cargoSearchCache']);

        $this->page = Yii::$app->request->get('page', 1);
    }

    public function rules()
    {
        return [
            [['city_from', 'city_to', 'radius'], 'required'],
            [['cargoCategoryIds', 'categoryFilter'], 'safe'],
            ['radius', 'in', 'range' => Yii::$app->params['passingCargoRange']]
        ];
    }

    public function attributeLabels()
    {
        return [
            'city_from' => 'Откуда',
            'city_to' => 'Куда',
            'radius' => 'Отклонение от маршрута'
        ];
    }

    /**
     * @param array $params
     * @return bool|ActiveDataProvider
     */
    public function search($params = [])
    {
        $this->load($params);

        if ($this->validate()) {
            $query = $this->passingQuery();
        } else {
            return false;
        }

        $pageSize = Yii::$app->params['itemsPerPage']['defaultPageSize'];

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'defaultPageSize' => $pageSize,
                //'route' => Url::toRoute(['/cargo/default/search']),
                'forcePageParam' => false,
            ]
        ]);

        return $dataProvider;
    }

    /**
     * @return CargoQuery
     */
    public function passingQuery()
    {
        $query = Cargo::find()
            ->joinWith(['cityFrom cf', 'cityTo ct', 'categories'])
            ->with(['cityFrom.country', 'cityTo.country'])
            ->where(['status' => Cargo::STATUS_ACTIVE])
            ->orderBy(['id' => SORT_DESC])
            ->cache(self::CACHE_TIME, $this->tagDependency);

        if (isset($this->excludeCargo)) {
            $query->andWhere(['<>', Cargo::tableName().'.id', $this->excludeCargo]);
        }

        if ($this->city_from != $this->city_to) {
            RouteHelper::buildRoute($this->city_from, $this->city_to);

            $ids = RouteHelper::getPassingCargo($this->city_from, $this->city_to, $this->radius, [
                'build' => true
            ]);

            $query->andWhere([
                Cargo::tableName().'.id' => $ids
            ]);
        } else {
            //по городу
            $query->andWhere([
                'and',
                ['city_from' => $this->city_from],
                ['city_to' => $this->city_from]
            ]);
        }

        if ($this->categoryFilter) {
            $this->cargoCategoryIds = CategoryFilter::getCategoriesByFilterIds($this->categoryFilter);
        }

        //указаны категориии
        if ($this->cargoCategoryIds) {
            $query->joinWith('categories')
                ->andFilterWhere([CargoCategory::tableName().'.id' => $this->cargoCategoryIds]);
        }

        $query->groupBy(Cargo::tableName().'.id');

        return $query;
    }

    /**
     * @return array|null|ActiveRecord
     */
    public function getCityFrom()
    {
        return City::find()
            ->where(['id' => $this->city_from])
            ->with(['country'])
            ->one();
    }

    /**
     * @return array|null|ActiveRecord
     */
    public function getCityTo()
    {
        return City::find()
            ->where(['id' => $this->city_to])
            ->with(['country'])
            ->one();
    }

    /**
     * @param $direction 'From' | 'To'
     * @return array
     */
    public function getCityString($direction)
    {
        $result = [];
        if (null !== $this->{"city$direction"}) {
            /** @var City $city */
            $city = $this->{"city$direction"};
            $text = $city->title_ru;
            if ( !empty($city->region_ru)) {
                $text .= ', '.$city->region_ru;
            }
            $text .= ', '.$city->country->title_ru;
            $result = [$city->id => $text];
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getMap()
    {
        $query = $this->passingQuery();

        $query->isExpired(false);

        /** @var Cargo[] $cargos */
        $cargos = $query->cache(3600)->all();

//        $route = [
//            'from' => $this->cityFrom->latitude.','.$this->cityFrom->longitude,
//            'to' => $this->cityTo->latitude.','.$this->cityTo->longitude
//        ];

        $route = [
            'from' => $this->cityFrom->title_ru.", ".$this->cityFrom->region_ru,
            'to' => $this->cityTo->title_ru.", ".$this->cityTo->region_ru,
        ];

        $markers = [];
        foreach ($cargos as $cargo) {
            $markers[] = [
                'id' => $cargo->id,
                'coord' => [
                    'lat' => $cargo->cityFrom->latitude,
                    'lng' => $cargo->cityFrom->longitude
                ]
            ];
        }

        return [
            'route' => $route,
            'markers' => $markers
        ];
    }
}
