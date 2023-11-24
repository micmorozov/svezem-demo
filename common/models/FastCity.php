<?php

namespace common\models;

use common\models\query\FastCityQuery;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\Inflector;

/**
 * This is the model class for table "fast_city".
 *
 * @property integer $id
 * @property string $title
 * @property string $code
 * @property integer $cityid
 * @property integer $regionid
 * @property integer $visible
 * @property int $highlight Выделить
 * @property string $url
 * @property string $name
 * @property int $size;
 * @property bool $main_city
 *
 * @property City $city
 * @property Region $region
 */
class FastCity extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'fast_city';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'code'], 'required'],
            [['cityid', 'visible', 'highlight'], 'integer'],
            [['title', 'code'], 'string', 'max' => 32],
            [['code'], 'unique'],
            [['cityid'], 'exist', 'skipOnError' => true, 'targetClass' => City::class, 'targetAttribute' => ['cityid' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ИД города',
            'title' => 'Наименование города',
            'code' => 'Наименование города латиницей',
            'cityid' => 'ИД города из базы городов',
            'visible' => 'отображать в списке',
            'highlight' => 'Выделить',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getCity()
    {
        return $this->hasOne(City::class, ['id' => 'cityid']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRegion()
    {
        return $this->hasOne(Region::class, ['id' => 'regionid']);
    }

    /**
     * @param $city City
     * @return string
     */
    static function getSubdomain($city){
        $fc = self::findOne(['cityid'=>$city->id]);

        if( $fc )
            return $fc->code;
        else
            return Inflector::slug($city->title_ru);
    }

    /**
     * @inheritdoc
     * @return FastCityQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new FastCityQuery(get_called_class());
    }

    /**
     * @param $title_ru
     * @return static[]
     */
    public static function findList($title)
    {
        return static::find()
            ->where(['like', 'title', $title])
            ->with(['city','region'])
            ->limit(10)
            ->all();
    }

    public function getTitle():string
    {
        return $this->title;
    }
}
