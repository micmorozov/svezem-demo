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
 * Class SphinxTransportCommon
 * @package common\SphinxModels
 *
 * @property int $id
 * @property int $city_from
 * @property int $city_to
 * @property int $region_from
 * @property int $region_to
 * @property int $created_by
 * @property int $status
 * @property int $top
 * @property int $show_main_page
 * @property int $recommendation
 */
class SphinxTransportCommon extends ActiveRecord
{
    //используется в поиске в сложных запросах
    public $count;

    public static function indexName(){
        return 'transport_common';
    }
}