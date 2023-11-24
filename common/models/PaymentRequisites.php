<?php

namespace common\models;

use common\validators\INNValidator;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\validators\CompareValidator;

/**
 * This is the model class for table "payment_requisites".
 *
 * @property int $userid Пользователь
 * @property string $organization Наименование организации
 * @property string $inn ИНН
 * @property string $kpp КПП
 * @property string $bic БИК
 * @property string $bank Банк
 * @property string $account Расчетный счет
 * @property string $corr_account Корреспондентский счет
 * @property string $jur_address Юридический адрес
 * @property string $post_address Почтовый адрес
 *
 * @property User $user
 */
class PaymentRequisites extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'payment_requisites';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['organization', 'inn', 'bic', 'bank', 'account', 'corr_account', 'jur_address', 'post_address'], 'required'],
            //КПП требуется только если ИНН не 12 цифр
            [['kpp'], 'required', 'when' => function($model){
                /** @var PaymentRequisites $model */
                return strlen($model->inn) != 12;
            }, 'whenClient' => 'function (attribute, value) {
                var innSel = "#'.Html::getInputId($this, 'inn').'";
                var inn = attribute.$form.find(innSel).val();
                return inn.length != 12;
            }'],
            [['inn', 'kpp', 'bic', 'account', 'corr_account'], 'integer'],
            [['organization', 'jur_address', 'post_address'], 'string', 'max' => 128],
            [['inn'], INNValidator::class],
            [['kpp', 'bic'], 'string', 'min' => 9, 'max' => 9],
            [['bank'], 'string', 'max' => 64],
            [['account', 'corr_account'], 'string', 'min' => 20, 'max' => 20],
            [['userid'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['userid' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'userid' => 'Пользователь',
            'organization' => 'Наименование организации',
            'inn' => 'ИНН',
            'kpp' => 'КПП',
            'bic' => 'БИК',
            'bank' => 'Банк',
            'account' => 'Расчетный счет',
            'corr_account' => 'Корреспондентский счет',
            'jur_address' => 'Юридический адрес',
            'post_address' => 'Почтовый адрес',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userid']);
    }
}
