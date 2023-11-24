<?php

namespace common\models\query;

use common\models\FastCity;
use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[FastCity]].
 *
 * @see FastCity
 */
class FastCityQuery extends ActiveQuery
{
    /**
     * @inheritdoc
     * @return FastCity[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return FastCity|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @param bool $state
     * @return $this
     */
    public function visible($state=1){
        return $this->andWhere(['visible' => $state]);
    }
}
