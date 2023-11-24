<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 14.11.18
 * Time: 17:43
 */

namespace common\SphinxModels;

use yii\sphinx\ActiveRecord;

class SphinxTransportRealTime extends ActiveRecord
{
    public static function indexName(){
        return 'transport_realtime';
    }
}