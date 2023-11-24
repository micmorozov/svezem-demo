<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 21.11.17
 * Time: 12:08
 */

namespace common\behaviors;

use common\helpers\SlugHelper;
use yii\behaviors\SluggableBehavior;

class SlugBehavior extends SluggableBehavior
{
    protected function generateSlug($slugParts): string
    {
        return SlugHelper::genSlug($slugParts);
    }
}