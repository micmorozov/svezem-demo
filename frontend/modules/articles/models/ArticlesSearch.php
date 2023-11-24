<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 29.11.17
 * Time: 9:35
 */
namespace frontend\modules\articles\models;

use common\models\Articles;
use yii\data\ActiveDataProvider;
use common\models\CargoCategory;
use yii\caching\TagDependency;
use Yii;
use yii\db\ActiveQuery;

class ArticlesSearch extends Articles
{
    public $categoryIds;

    public $pageSize;

    public $order = SORT_DESC;

    public function search($params = []){
        /** @var ActiveQuery $query */
        $this->load($params);

        $query = Articles::find()
            ->active()
            ->where(['cityid' => $this->cityid])
            ->groupBy('id')
            ->orderBy(["id" => $this->order]);

        //указаны категориии
        if( $this->categoryIds ){
            //Поиск категории
            //Если указана дочерняя категория, то ищим по ней и по родительской
            //Если корневая, то по ней и всем её дочерним

            $cats = CargoCategory::findAll($this->categoryIds);
            $catIds = (array)$this->categoryIds;
            foreach($cats as $cat){
                $catIds = array_merge($catIds, $cat->root ? $cat->nodesids : $cat->parentsids);
            }

            $query->joinWith('categories')
                ->andFilterWhere([CargoCategory::tableName().'.id' => $catIds]);
        }

        $pageSize = $this->pageSize ? $this->pageSize
            : Yii::$app->session->get('per-page', Yii::$app->params['itemsPerPage']['defaultPageSize']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'defaultPageSize' => $pageSize,
                'forcePageParam' => false,
            ],
        ]);

        $dependency = new TagDependency(['tags' => 'articleSearchCache']);

        Yii::$app->db->cache(function($db) use ($dataProvider){
            $dataProvider->prepare();
        }, 3600, $dependency);

        return $dataProvider;
    }
}
