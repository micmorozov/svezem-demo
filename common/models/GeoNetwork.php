<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 28.11.16
 * Time: 17:40
 */

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

class GeoNetwork extends ActiveRecord{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'geo_network';
    }

    /**
     * @return ActiveQuery
     */
    public function getCountry()
    {
        return $this->hasOne(Country::className(), ['id' => 'countryid']);
    }

    /**
     * @return ActiveQuery
     */
    public function getRegion()
    {
        return $this->hasOne(Region::className(), ['id' => 'regionid']);
    }

    /**
     * @return ActiveQuery
     */
    public function getCity(){
        return $this->hasOne(City::className(), ['id' => 'cityid']);
    }
}