<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;

/**
 * This is the model class for table "search_page_tags".
 *
 * @property integer $id
 * @property string $url
 * @property string $name
 * @property integer $cityid
 */
class SearchPageTags extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'search_page_tags';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['url', 'name'], 'required'],
            ['cityid', 'integer'],
            [['url'], 'string', 'max' => 256],
            [['name'], 'string', 'max' => 128],
            [['cityid'], 'exist', 'skipOnError' => true, 'targetClass' => City::class, 'targetAttribute' => ['cityid' => 'id']]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'url' => 'Урл',
            'name' => 'Наименование',
            'cityid' => 'Город'
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(City::class, ['id' => 'cityid']);
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        try {
            return parent::save($runValidation, $attributeNames);
        } catch(Exception $e){
            // при одновременных запросах регистрации валидатор уникальности пропускает одинаковые email
            // чтобы выдать пользователю красивый ответ ловим исключение базы данных
            // о дублирующихся значениях
            //Integrity constraint violation: 1062
            if( $e->errorInfo[1] == 1062 )
                $this->addError('url', $e->getMessage());

            return false;
        }
    }
}