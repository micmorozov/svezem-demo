<?php

namespace frontend\modules\cabinet\models;

use common\models\Notification;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

/**
 * NotificationSearch represents the model behind the search form about `common\models\Response`.
 */
class NotificationSearch extends Notification
{
  public $themes;
  public $pageSize;

  /**
   * @inheritdoc
   */
  public function rules() {
    return [
      ['themes', 'in', 'range' => [
        self::THEME_CARGO_REQUEST,
        self::THEME_PASSENGER_REQUEST,
        self::THEME_COURIER_REQUEST,
        self::THEME_CARGO_TRANSPORTATION,
        self::THEME_RESPONSE_FOR_REQUEST,
        self::THEME_DEAL,
        self::THEME_PAID_SERVICE,
        self::THEME_OTHER,
        self::THEME_NEWS
      ], 'allowArray' => true
      ],
      ['pageSize', 'integer'],
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

    $user_id = (Yii::$app->user->isGuest) ? 0 : Yii::$app->user->identity->id;

    /** @var ActiveQuery $query */
    $query = Notification::find()->where(['user_id' => $user_id])->orderBy(['created_at' => SORT_DESC]);
    if (!empty($this->themes) && count($this->themes)) {
      $query->andWhere(['theme' => $this->themes]);
    }

    $dataProvider = new ActiveDataProvider([
      'query' => $query,
      'pagination' => [
        'pageSize' => !empty($this->pageSize) ? $this->pageSize : 10,
        'route' => '/cabinet/notifications',
      ],
      'sort' => [
        'route' => '/cabinet/notifications',
      ],
    ]);

    if (!$this->validate()) {
      // uncomment the following line if you do not want to return any records when validation fails
      $query->where('0=1');
      return $dataProvider;
    }

    return $dataProvider;
  }

}
