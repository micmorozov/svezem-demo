<?php
/**
 * Created by PhpStorm.
 * User: ferrum
 * Date: 29.11.17
 * Time: 12:56
 */

namespace console\controllers;

use common\models\ArticleTags;
use Yii;
use yii\console\Controller;

class ArticleTagsController extends Controller
{
    /**
     * Генерация тегов для статей
     */
    public function actionGenerate(){
        //очищаем таблицу
        Yii::$app->db->createCommand()->truncateTable(ArticleTags::tableName())->execute();

        $query =<<<QUERY
SELECT DISTINCT category_id, category, slug 
FROM `articles_category_assn` acs 
LEFT JOIN cargo_category cc 
ON cc.id = acs.category_id
QUERY;

        $result = Yii::$app->db->createCommand($query)->queryAll();

        foreach($result as $item){
            $tag = new ArticleTags();
            $tag->url = '/articles/'.$item['slug'].'/';
            $tag->name = $item['category'];
            if( !$tag->save() ){
                Yii::error("Не удалось создать тег ".print_r($tag->attributes,1)."\n".print_r($tag->getErrors(),1), 'ArticleTagsController.Generate');
            }
        }
    }
}