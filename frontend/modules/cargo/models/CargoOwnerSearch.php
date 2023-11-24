<?php

namespace frontend\modules\cargo\models;

use common\models\Cargo;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

/**
 * CargoOwnerSearch represents the model behind the search form about `common\models\Cargo`.
 */
class CargoOwnerSearch extends Cargo
{
  public $statuses;
  public $pageSize;

  /**
   * @inheritdoc
   */
  public function rules() {
    return [
      ['statuses', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_ARCHIVE], 'allowArray' => true],
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

    // Запускаем валидацию что бы отработали правила
    $valid = $this->validate();

    /** @var ActiveQuery $query */
    $query = Cargo::find()
        ->select('cargo.*')
        ->joinWith(['cargoCategory'])
        ->joinWith(['profile'])
        ->andWhere(['cargo.created_by' => Yii::$app->user->identity->id])
        ->andFilterWhere(['in', 'cargo.status', $this->statuses])
        ->groupBy('cargo.id');

    $dataProvider = new ActiveDataProvider([
        'query' => $query,
        'pagination' => [
            'pageSize' => $this->pageSize,
            'route' => '/cargo',
        ],
        'sort' => [
            'route' => '/cargo',
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
      self::STATUS_ACTIVE => ('Открытые'),
      //self::STATUS_TRANSPORTER_CHOSEN => ('Закрытые (перевозчик выбран)'),
      self::STATUS_ARCHIVE => ('В архиве')
    ];
  }
}
