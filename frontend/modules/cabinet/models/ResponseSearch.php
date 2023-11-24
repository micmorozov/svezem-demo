<?php

namespace frontend\modules\cabinet\models;

use common\models\Cargo;
use common\models\Offer;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

/**
 * ResponseSearch represents the model behind the search form about `common\models\Response`.
 */
class ResponseSearch extends Offer
{
  const STATUS_OPEN = 0;
  const STATUS_CHOSEN_ME = 10;
  const STATUS_CHOSEN_NOT_ME = 20;

  public $statuses;
  public $pageSize;

  /**
   * @inheritdoc
   */
  public function rules() {
    return [
      ['statuses', 'in', 'range' => [self::STATUS_OPEN, self::STATUS_CHOSEN_ME, self::STATUS_CHOSEN_NOT_ME], 'allowArray' => true],
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
    $query = Offer::find()
      ->joinWith(['cargo'])
      ->where(['offer.created_by' => $user_id]);
    if (!empty($this->statuses) && count($this->statuses)) {
      $filter[] = 'or';
      foreach ($this->statuses as $status) {
        $filter[] = self::getStatusFilter($status);
      }
      $query->andWhere($filter);
    } else {
      $query->andWhere(['cargo.status' => [Cargo::STATUS_ACTIVE, Cargo::STATUS_TRANSPORTER_CHOSEN]]);
    }
    $query->andWhere(['not', ['offer.status' => Offer::STATUS_CANCELED]]);

    $dataProvider = new ActiveDataProvider([
      'query' => $query,
      'pagination' => [
        'pageSize' => !empty($this->pageSize) ? $this->pageSize : 10,
        'route' => '/cabinet/responses',
      ],
      'sort' => [
        'route' => '/cabinet/responses',
      ],
    ]);

    if (!$this->validate()) {
      // uncomment the following line if you do not want to return any records when validation fails
      $query->where('0=1');
      return $dataProvider;
    }

    return $dataProvider;
  }

  /**
   * @param bool $full
   * @return array
   */
  public static function getStatusLabels($full = false) {
    return [
      self::STATUS_OPEN => ($full ? '<span>(Заявка открыта, идет поиск перевозчика)</span>' : 'Открытые'),
      self::STATUS_CHOSEN_ME => ($full ? '<span class="bl-green"><span>(Заявка закрыта &mdash; вас выбрали исполнителем)</span></span>' : 'Закрытые (я перевозчик)'),
      self::STATUS_CHOSEN_NOT_ME => ($full ? '(Заявка закрыта, перевозчиком выбран другой пользователь)' : 'Закрытые (я участник торгов)'),
    ];
  }

  /**
   * @param bool $full
   * @return array
   */
  public static function getStatusLabel($status, $full = false) {
    $labels = static::getStatusLabels($full);
    return $labels[$status];
  }

  /**
   * @param $status
   * @return mixed
   */
  public static function getStatusFilter($status) {
    $filters = [
      self::STATUS_OPEN => ['cargo.status' => Cargo::STATUS_ACTIVE],
      self::STATUS_CHOSEN_ME => [
        'cargo.status' => Cargo::STATUS_TRANSPORTER_CHOSEN,
        'offer.status' => Offer::STATUS_ACCEPTED
      ],
      self::STATUS_CHOSEN_NOT_ME => ['and',
        ['cargo.status' => Cargo::STATUS_TRANSPORTER_CHOSEN],
        ['not', ['offer.status' => Offer::STATUS_ACCEPTED]]
      ],
    ];
    return $filters[$status];
  }
}
