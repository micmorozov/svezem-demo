<?php

namespace common\models\query;
use common\models\CargoCategory;
use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[CargoCategory]].
 *
 * @see CargoCategory
 */
class CargoCategoryQuery extends ActiveQuery
{
    /**
     * @inheritdoc
     * @return CargoCategory[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return CargoCategory|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    public function root($default = 1)
    {
        return $this->andWhere("[[root]]=$default");
    }

    public function transportType($default = 1)
    {
        return $this->andWhere("[[transport_type]]=$default");
    }

    public function loadType($default = 1)
    {
        return $this->andWhere("[[load_type]]=$default");
    }

    public function showFilter($default = 1)
    {
        return $this->andWhere("[[show_filter]]=$default");
    }

    public function showAddTransport($default = 1)
    {
        return $this->andWhere("[[show_add_transport]]=$default");
    }

    public function showModerTrTK($default = 1)
    {
        return $this->andWhere("[[show_moder_tr_tk]]=$default");
    }

    public function showModerCargo($default = 1)
    {
        return $this->andWhere("[[show_moder_cargo]]=$default");
    }
}