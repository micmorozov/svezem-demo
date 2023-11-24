<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "disposable_email_domain".
 *
 * @property integer $id
 * @property string $domain
 */
class DisposableEmailDomain extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'disposable_email_domain';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['domain', 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'domain' => 'Домен',
        ];
    }
}
