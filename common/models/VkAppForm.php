<?php
namespace common\models;

use common\validators\UserPassValidator;
use ferrumfist\yii2\recaptcha\ReCaptchaValidator;
use Yii;
use yii\base\Model;

/**
 * Форма для получения access_token по ИД приложения
 */
class VkAppForm extends Model
{
    public $app_id;
    public $private_key;

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['app_id', 'private_key'], 'required']
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels() {
        return [
            'app_id' => 'ID приложения',
            'private_key' => 'Защищённый ключ'
        ];
    }
}
