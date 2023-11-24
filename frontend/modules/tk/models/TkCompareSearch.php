<?php

namespace frontend\modules\tk\models;

use common\models\CargoCategory;
use Yii;
use common\models\City;
use common\models\TkSearchStat;
use yii\base\Model;
use yii\caching\TagDependency;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

class TkCompareSearch extends Model
{
  // models
  public $cityFrom;
  public $cityTo;
  public $categoryIds;

  // Города
  public $city_from;
  public $city_to;
  // Вес
  public $weight;
  
  // Габариты
  public $width;
  public $height;
  public $depth;

  public $pageSize;

  public $order = SORT_DESC;

    //искать по указанному региону
    public $selectRegion;

    /**
    * @inheritdoc
    */
    public function rules() {
        return [
            [['city_from', 'city_to', 'weight', 'width', 'height', 'depth'], 'required', 'on'=>'PriceCompare'],
            ['city_from', 'exist', 'targetClass' => City::class, 'targetAttribute' => 'id'],
            ['city_to', 'exist', 'targetClass' => City::class, 'targetAttribute' => 'id'],
            ['categoryIds', 'exist', 'targetClass' => CargoCategory::class, 'targetAttribute' => 'id'],

            [['weight', 'width', 'height', 'depth'], 'number', 'numberPattern' => '/^\s*[-+]?[0-9]*[.,]?[0-9]+([eE][-+]?[0-9]+)?\s*$/'],

            ['pageSize', 'integer']
        ];
    }

  public function attributeLabels() {
    return [
      'city_from' => 'Откуда',
      'city_to' => 'Куда',
      'weight' => 'Вес (кг)',

      'width' => 'Ширина',
      'height' => 'Высота',
      'depth' => 'Длина'
    ];
  }

    /**
    * Creates data provider instance with search query applied
    *
    * @param array $params
    * @return ActiveDataProvider
    */
    public function search($params = null){
        /** @var ActiveQuery $query */
        $this->load($params);

        //требуется для отображения выбранного города
        //в выпадающем списке формы поиска
        if ($this->city_from) {
            $this->cityFrom = City::find()
              ->where(['id' => $this->city_from])
              ->one();
        }

        $query = Tk::find()
            ->joinWith('details')
            ->where([
                'status' => Tk::STATUS_ACTIVE
            ])
            ->groupBy('id')
            ->orderBy(["id" => $this->order]);

        //Эти два условия определяют что ТК имеет филиалы в обоих городах
        if( $this->city_to ){
            $subQuery = TkDetails::find()
                ->where(['cityid'=>$this->city_to])
                ->andWhere('tk_id='.Tk::tableName().'.[[id]]');

            $query->andFilterWhere(['exists', $subQuery]);
        }

        if( $this->city_from ){
            $subQuery = TkDetails::find()
                ->where(['cityid'=>$this->city_from])
                ->andWhere('tk_id='.Tk::tableName().'.[[id]]');

            $query->andFilterWhere(['exists', $subQuery]);
        }

        //в вьюхе при обращении к detail мы должны быть уверены что первая будет
        //тот город, что указан в качестве отправителя
        if( $this->city_from ){
            $query->joinWith(['details'=>function($q){
                /** @var $q ActiveQuery  */
                $q->andWhere(['cityid'=>$this->city_from]);
            }]);
        }

        //указаны категориии
        if( $this->categoryIds ){
            //Поиск категории
            //Если указана дочерняя категория, то ищим по ней и по родительской
            //Если корневая, то по ней и всем её дочерним

            $cats = CargoCategory::findAll($this->categoryIds);
            $catIds = (array)$this->categoryIds;
            foreach($cats as $cat){
                $catIds = array_merge($catIds, $cat->root ? $cat->nodesids : $cat->parentsids);
            }

            $query->joinWith('categories')
                ->andFilterWhere([CargoCategory::tableName().'.id' => $catIds]);
        }

        //=== Поиск по Региону
        //указан регион отправки
        if(null !== $this->selectRegion){
            $query->joinWith(['details'=>function($q){
                /** @var $q ActiveQuery  */
                $q->andWhere(['region_id'=>$this->selectRegion]);
            }]);
        }

        $pageSize = $this->pageSize ? $this->pageSize
          : Yii::$app->session->get('per-page', Yii::$app->params['itemsPerPage']['defaultPageSize']);

        $dataProvider = new ActiveDataProvider([
          'query' => $query,
          'pagination' => [
              'defaultPageSize' => $pageSize,
              'forcePageParam' => false,
          ],
        ]);

        $dependency = new TagDependency(['tags' => 'tkSearchCache']);

        Yii::$app->db->cache(function($db) use ($dataProvider){
            $dataProvider->prepare();
        }, 3600, $dependency);
        return $dataProvider;
    }

  /**
   * @param $direction 'From' | 'To'
   * @return array
   */
  public function getCityString($direction) {
    $result = [];
    if (null !== $this->{"city$direction"}) {
      /** @var City $city */
      $city = $this->{"city$direction"};
      $title_ru = $city->title_ru;
      if (!empty($city->region_ru)) {
        $title_ru .= ', ' . $city->region_ru;
      }
      $title_ru .= ', ' . $city->country->title_ru;
      $result = [$city->id => $title_ru];
    }
    return $result;
  }

	/**
	 * Получаем несколько последних запросов ТК
	 * @param int $count - Количество последних запросов
	 */
  public function getLastSearch($count=5){
  	$query = TkSearchStat::find()
		->orderBy('`moment` DESC')
		->limit($count);

	   $dataProvider = new ActiveDataProvider([
		   'query' => $query
	   ]);

	  return $dataProvider;
  }
}
