<?php

namespace common\models\query;

use common\models\Cargo;
use common\models\Setting;
use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[Cargo]].
 *
 * @see Cargo
 */
class CargoQuery extends ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return Cargo[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return Cargo|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    public function isExpired($expired = true)
    {
        $expiredDays = Setting::getValueByCode(Setting::CARGO_EXPIRE_DAYS, 30);

        if ($expired) {
            return $this->andWhere(['<', 'created_at', strtotime("-$expiredDays days")]);
        } else {
            return $this->andWhere(['>', 'created_at', strtotime("-$expiredDays days")]);
        }
    }
}
