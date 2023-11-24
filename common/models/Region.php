<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "regions".
 *
 * @property integer $id
 * @property integer $country_id
 * @property string $title_ru
 * @property int $center Административный центр
 * @property string $slug
 *
 * @property City[] $cities
 * @property Country $country
 * @property City $centerCity
 */
class Region extends ActiveRecord implements LocationInterface
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'regions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['country_id'], 'required'],
            [['id', 'country_id'], 'integer'],
            [['title_ru'], 'string', 'max' => 150],
            [['center'], 'exist', 'skipOnError' => true, 'targetClass' => City::class, 'targetAttribute' => ['center' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'country_id' => 'Страна',
            'title_ru' => 'Наименование',
            'center' => 'Административный центр',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getCities()
    {
        return $this->hasMany(City::class, ['region_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(Country::class, ['id' => 'country_id']);
    }

    /**
     * @param $title_ru
     * @return static[]
     */
    public static function findList($title_ru)
    {
        return static::find()
            ->where(['like', 'title_ru', $title_ru])
            ->with(['country'])
            ->limit(10)
            ->all();
    }

    /**
     * @return ActiveQuery
     */
    public function getCenterCity()
    {
        return $this->hasOne(City::class, ['id' => 'center']);
    }

    /**
     * @return ActiveQuery
     */
    public function getFastcity()
    {
        return $this->hasMany(FastCity::class, ['regionid' => 'id']);
    }

    /**
     * @return string
     */
    public function getFQTitle()
    {
        $title_ru = $this->title_ru;
        $title_ru .= ', '.$this->country->title_ru;
        return $title_ru;
    }

    public function getTitle():string
    {
        return $this->title_ru;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->slug;
    }

    public function getFullTitle(): string
    {
        return $this->getTitle() . ', ' . $this->country->getTitle();
    }

    public function getParentLocation(): array
    {
        $result = [];

        array_push($result, /*$this->country, */$this);

        return $result;
    }

    public function equal(LocationInterface $eq = null): bool
    {
        if(is_null($eq)) return false;

        return $this->getId() === $eq->getId();
    }
}
