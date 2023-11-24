<?php

namespace frontend\components;

use simialbi\yii2\schemaorg\models\GeoShape;
use simialbi\yii2\schemaorg\models\Place;

class Offer extends \simialbi\yii2\schemaorg\models\Offer
{
    /** @var $eligibleRegion GeoShape|Place */
    public $eligibleRegion;
}