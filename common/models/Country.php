<?php

namespace common\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "countries".
 *
 * @property integer $id
 * @property string $title_ru
 * @property string $code
 *
 * @property City[] $cities
 * @property Region[] $regions
 *
 * @property string $flagIcon
 */
class Country extends ActiveRecord implements LocationInterface
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'countries';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['title_ru'], 'string', 'max' => 60]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title_ru' => 'Title Ru',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getCities()
    {
        return $this->hasMany(City::class, ['country_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRegions()
    {
        return $this->hasMany(Region::class, ['country_id' => 'id']);
    }

    /**
     * @param $title_ru
     * @return static[]
     */
    public static function findList($title_ru)
    {
        return static::find()
            ->where(['like', 'title_ru', $title_ru])
            ->limit(10)
            ->asArray()
            ->all();
    }

    public function getFlagIcon(){
        return 'https://'.Yii::getAlias('@assetsDomain')."/img/flags/{$this->code}.svg";
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        // TODO Пока нет разделения на страны, код страны не возвращаем
        return '';//$this->code;
    }

    public function getFullTitle(): string
    {
        return $this->getTitle();
    }

    public function getTitle(): string
    {
        return $this->title_ru;
    }
    public function getParentLocation(): array
    {
        $result = [];

        //array_push($result, $this);

        return $result;
    }

    public function equal(LocationInterface $eq = null): bool
    {
        if(is_null($eq)) return false;

        return $this->getId() === $eq->getId();
    }
}
