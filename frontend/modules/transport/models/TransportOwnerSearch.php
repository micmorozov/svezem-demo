<?php

namespace frontend\modules\transport\models;

use common\models\Transport;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

/**
 * TransportOwnerSearch represents the model behind the search form about `common\models\Transport`.
 */
class TransportOwnerSearch extends Transport
{
  public $statuses;
  public $pageSize;

  /**
   * @inheritdoc
   */
  public function rules() {
    return [
      ['statuses', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED], 'allowArray' => true],
      ['statuses', 'default', 'value' => [self::STATUS_ACTIVE]],
      ['pageSize', 'integer'],
      ['pageSize', 'default', 'value' => Yii::$app->params['itemsPerPage']['defaultPageSize']],
    ];
  }

  /**
   * @inheritdoc
   */
  public function scenarios() {
    // bypass scenarios() implementation in the parent class
    return Model::scenarios();
  }

  /**
   * Creates data provider instance with search query applied
   *
   * @param array $params
   * @return ActiveDataProvider
   */
  public function search($params) {
    $this->load($params);
    $valid = $this->validate();

    /** @var ActiveQuery $query */
    $query = Transport::find()
      ->joinWith(['tripType'])
      //->joinWith(['bodyType'])
      ->joinWith(['profile'])
      ->joinWith(['transportLocations'])
      ->with(['countriesFrom'])
      ->with(['countriesTo'])
      ->with(['regionsFrom'])
      ->with(['regionsTo'])
      ->with(['citiesFrom'])
      ->with(['citiesTo'])
      ->with(['createdBy'])
      ->with(['transportImages'])
      ->andWhere(['transport.status' => $this->statuses])
      ->andWhere(['transport.created_by' => Yii::$app->user->identity->id])
      ->select('transport.*')
      ->groupBy('transport.id');

    $dataProvider = new ActiveDataProvider([
      'query' => $query,
      'pagination' => [
        'pageSize' => $this->pageSize,
        'route' => '/transport',
      ],
      'sort' => [
        'route' => '/transport',
      ],
    ]);

    if (!$valid) {
      // uncomment the following line if you do not want to return any records when validation fails
      $query->where('0=1');
      return $dataProvider;
    }

    return $dataProvider;
  }
  
  /**
   * @return array
   */
  public static function getStatusLabels($colored = false) {
  	return [
  		self::STATUS_ACTIVE => ('Активные'),
        self::STATUS_DELETED => ('Удаленные')
    ];
  }
}
