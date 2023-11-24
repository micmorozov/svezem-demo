<?php
namespace frontend\modules\cabinet\models;

use common\models\City;
use common\models\DisposableEmailDomain;
use common\models\Payment;
use common\models\PaymentSystem;
use common\models\Profile;
use common\models\User;
use yii\base\Model;
use Yii;
use yii\db\ActiveQuery;

/**
 * Juridicalform
 */
class JuridicalForm extends Model
{
  public $payment_id;
  public $name;
  public $address;

  /**
   * @inheritdoc
   */
  public function rules() {
    return [
      [
        'payment_id',
        'exist',
        'targetClass' => Payment::className(),
        'targetAttribute' => 'id',
        'filter' => function($q) {
          /** @var $q ActiveQuery */
          $q->andWhere(['created_by' => userId()]);
          $q->andWhere(['status' => Payment::STATUS_PENDING]);
        }
      ],

      ['name', 'trim'],
      ['name', 'required'],
      ['name', 'string', 'max' => 255],

      ['address', 'trim'],
      ['address', 'required'],
      ['address', 'string', 'max' => 255],
    ];
  }

  /**
   * @return array
   */
  public function attributeLabels() {
    return [
      'sum' => 'Сумма',
      'name' => 'Наименование юр. лица',
      'address' => 'Почтовый адрес юр. лица',
    ];
  }

  /**
   * @return bool
   */
  public function process() {
    if ($this->validate()) {
      $payment_system = PaymentSystem::findOne(['code' => 'juridical']);

      /** @var Payment $payment */
      $payment = Payment::findOne($this->payment_id);
      $payment->payment_system_id = $payment_system->id;
      $payment->status = Payment::STATUS_JURIDICAL_PROCESS;
      $payment->juridical_name = $this->name;
      $payment->juridical_address = $this->address;

      return $payment->save();
    }

    return false;
  }
}
