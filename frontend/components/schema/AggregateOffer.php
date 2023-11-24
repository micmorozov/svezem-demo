<?php

namespace frontend\components\schema;

use simialbi\yii2\schemaorg\models\AggregateRating;

class AggregateOffer extends \simialbi\yii2\schemaorg\models\AggregateOffer
{
    /** @var AggregateRating $aggregateRating */
    public $aggregateRating;

    public $priceCurrency;

    public $availability;
}