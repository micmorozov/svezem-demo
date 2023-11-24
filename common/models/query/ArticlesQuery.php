<?php

namespace common\models\query;

use common\models\Articles;
use common\models\CargoCategory;
use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[Articles]].
 *
 * @see Articles
 */
class ArticlesQuery extends ActiveQuery
{
    /**
     * @inheritdoc
     * @return Articles[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Articles|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    public function active($status = Articles::STATUS_ACTIVE){
        return $this->andWhere(['status' => $status]);
    }

    public function categorySlug($slug){
        return $this->joinWith('categories')
            ->andWhere([CargoCategory::tableName().".slug"=>$slug]);
    }
}