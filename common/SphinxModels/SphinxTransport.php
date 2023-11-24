<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 08.11.18
 * Time: 15:55
 */

namespace common\SphinxModels;

use yii\sphinx\ActiveRecord;

/**
 * Class SphinxTransport
 * @package common\SphinxModels
 *
 * @property int $city_from
 * @property int $city_to
 */
class SphinxTransport extends ActiveRecord
{
    //используется в поиске в сложных запросах
    public $count;

    // минимальная цена в наборе
    public $min_price;

    public static function indexName(){
        return 'svezem_transport';
    }
}